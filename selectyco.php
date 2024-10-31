<?php

  /*
  Plugin Name: selectyco
  Plugin URI:  https://plugins.svn.wordpress.org/selectyco/
  Description: Single digital content sales via selectyco. Users register once and can purchase single content across multiple platforms. 
  Version:     2.1.5
  Author:      selectyco Media Solutions GmbH
  Author URI:  https://www.selectyco.com
  Text Domain: selectyco
  Domain Path: /languages/
  */
  
  define("SELECTYCO_DIR", plugin_dir_path( __FILE__ ));
  define("SELECTYCO_HOST", "https://api.selectyco.com");
  
  if(!class_exists('syc_class')) {
    class syc_class {
      
      private static $instance;
      
      public static function get_instance() {
        if(!isset(self::$instance))
          self::$instance = new syc_class();
        return self::$instance;
      }
      
      public function __construct() {
        if(is_admin()) {
          require_once SELECTYCO_DIR . 'selectyco-admin.php';
          add_action( 'add_meta_boxes', array(&$this, 'add_syc_metabox') );
          add_action( 'admin_enqueue_scripts', array(&$this, 'add_syc_script_styles') );
          
          add_action( 'wp_ajax_sycApiInsertItem', array(&$this, 'sycApiInsertItem') );
          add_action( 'wp_ajax_nopriv_sycApiInsertItem', array(&$this, 'sycApiInsertItem') );
          
          add_action( 'wp_ajax_sycApiDeactivateItem', array(&$this, 'sycApiDeactivateItem') );
          add_action( 'wp_ajax_nopriv_sycApiDeactivateItem', array(&$this, 'sycApiDeactivateItem') );
          
          add_action( 'wp_ajax_sycApiUpdateItem', array(&$this, 'sycApiUpdateItem') );
          add_action( 'wp_ajax_nopriv_sycApiUpdateItem', array(&$this, 'sycApiUpdateItem') );
          
          add_action( 'wp_ajax_send_licenceRequest', array(&$this, 'send_licenceRequest') );
          add_action( 'wp_ajax_nopriv_send_licenceRequest', array(&$this, 'send_licenceRequest') );
          
          add_action( 'wp_ajax_sycApiGetItems', array(&$this, 'sycApiGetItems') );
          add_action( 'wp_ajax_nopriv_sycApiGetItems', array(&$this, 'sycApiGetItems') );
        }
        else {
          add_filter( 'the_content', array($this, 'add_button_to_post') );
        }
      }
      
      public static function syc_activate() {
        if ( is_admin() ) {
          global $wpdb;
          $table_name = $wpdb->prefix . 'selectyco';
          $charset_collate = $wpdb->get_charset_collate();

          $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wpPostId mediumint(9) NOT NULL,
            sycItemId varchar(36) NOT NULL,
            sycTeaserLen mediumint (4) NULL,
            ts datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            UNIQUE KEY id (id)
          ) $charset_collate;";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          dbDelta( $sql );

          //WP Options
          update_option("selectyco_options", array(
                  "sycVersion" => "2.1.1",
                  "authKey" => "insert valid authentication key here",
                  "buttonWidth" => "250",
                  "displayProperty" => "relative"
                ));
                
          $to = "gg@selectyco.com";
          $subject = "Wordpress-Plugin Activation";
          $msg = get_site_url();
          $headers = "From:selectyco Wordpress Plugin <office@selectyco.com>\r\n";
          wp_mail( $to, $subject, $msg, $headers);
        }
      }
      
      public static function syc_uninstall() {
        // Remove info from DB on deactivate?
        delete_option( 'selectyco_options' );
        
        // drop custom db table
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}selectyco" );
      }
      
      public static function syc_deactivate() {
        // Don't do anything, keep information
      }
    
    
    
      public function add_syc_metabox()
      {
          add_meta_box("syc-meta-box", "selectyco: " . __('Item data','selectyco'), array($this, "syc_meta_box_markup"), "post", "side", "high", null);
      }
      
      public function syc_meta_box_markup($object)
      {
        global $post;
        global $current_screen;
        $wpPostId = $post->ID;
        $sycApiItemExists = false;
        $priceClasses = array('A','B','C','D','E','F','G','H','I','J');

        // existing post and post-edit view
        if( NULL != $wpPostId && 'post' == $current_screen->base) {
          $sycWpItem = $this->getSycWpItem($wpPostId);
          if($sycWpItem != null && $wpPostId == $sycWpItem->wpPostId) {
            $sycApiItem = $this->sycApiGetItem($sycWpItem->sycItemId, "merchant");
            $sycApiItemExists = $sycApiItem != null ? true : false;
            $sycApiItemActive = $sycApiItem->active;
          }
        } 
        ?>
        <form id="sycDataForm">
				<?php 
				if($sycApiItemExists){
          if($sycApiItemActive) {
            $currentDateValidTo = date("Y-m-d H:i:s", strtotime($sycApiItem->dateValidTo.'+6 hours'));
				?>
            <input type="hidden" id="wpPostId" value="<?php echo $wpPostId; ?>">
            <input type="hidden" id="wpSycItemId" value="<?php echo $sycWpItem->sycItemId; ?>">
						<table width="250" border="0" id="selectycoTblAE" cellspacing="0" cellpadding="0">
							<tr id="TrA">
                <td style="width:130px"><?php _e('Teaser length','selectyco')?></td>
								<td>
                  <input style="width:88px" id="newSycTeaserLen" maxlength="4" name="meta-box-teaserLen" type="text" value="<?php echo $sycWpItem->sycTeaserLen ?>">
                </td>
							</tr>
              <tr id="TrB">
                <td><?php _e('Price category','selectyco')?></td>
								<td>
                  <select style="width:88px" id="newSycPriceClass" name="meta-box-priceClass">
											<?php 
													foreach($priceClasses as $priceClass) {
                            if($sycApiItem->priceClass === $priceClass) {
                              echo "<option value=".$priceClass." selected>".$priceClass."</option>";  
                            }
                            else {
                              echo "<option value=".$priceClass.">".$priceClass."</option>";  
                            }
													}
											?>
									</select>
                </td>
							</tr>
              <tr id="TrC">
                <td width="80"><?php _e('free of charge from','selectyco')?></td>
								<td>
                  <input style="width:88px" id="newSycValidTo" name="meta-box-runTime" readonly class="sycDatePicker" type="text" value="<?php echo $currentDateValidTo ?>" >
                </td>
							</tr>
              <tr id="TrD">
								<td colspan="2" align="center">
                  <input type='button' id='sycUpdateItem' class='button button-primary' style="margin-top:10px; width:200px" value="<?php _e('update now','selectyco')?>" />
                </td>
							</tr>
              <tr>
								<td colspan="2" align="center">
                  <div id="sycItemStatus2" style="margin:10px 0; height:18px"></div>
                    <div id="TrE">
                      <hr id="sycHr">
                      <input id='sycDeactivateConfirm' name="permanentlyDelete" type='checkbox' style='margin-top: 17px' value="permanentlyDeleteTrue">
                      <input type='button' id='sycDeactivateItem' class='button button-primary' style="margin-top:10px; width:200px" value="<?php _e('delete selectyco button','selectyco')?>" />
                    </div>
                </td>
							</tr>
						</table>
				<?php
          }
          else {
            echo "<div style='margin-top:10px; text-align:center' class='sycSuccess'>" . __('selectyco button deleted','selectyco') . "</div>";
            echo "<div style='font-size: 9px; text-align:center'># ".$sycWpItem->sycItemId." #</div>";
          }
        }
				else {
          $futureDate = date_create(date('Y-m-d'));
          date_add($futureDate,date_interval_create_from_date_string("45 days"));
          $futureDate = date_format($futureDate,'Y-m-d');
				?>
					<table width="250" border="0" id="selectycoTbl" cellspacing="0" cellpadding="0">
						<tr>
							<td style="width:110px"><?php _e('Itemname','selectyco')?></td>
							<td colspan="2"><input style="width:130px" id="sycItemName" readonly name="meta-box-itemName" type="text" value=""></td>
						</tr>
						<tr>
							<td><?php _e('Type of Item','selectyco')?></td>
							<td colspan="2">
									<select style="width:88px" name="meta-box-laufZeit" id="sycItemType">
											<?php
													$itemTypes = array(0=>'Article',1=>'Video',2=>'Contentplacement',3=>'ePaper');
													foreach($itemTypes as $itKey => $itVal ) {
														 echo "<option value=".$itKey.">".__($itVal,'selectyco')."</option>";
													}
											?>
									</select><br>
							</td>
						</tr>
						<tr>
							<td><?php _e('Teaser length','selectyco')?></td>
							<td colspan="2"><input style="width:88px" id="sycTeaserLen" maxlength="4" name="meta-box-teaserLen" type="text" value="250"></td>
						</tr>
						<tr>
							<td><?php _e('Price category','selectyco')?></td>
							<td colspan="2">
								<select style="width:88px" id="sycPriceClass" name="meta-box-priceClass">
											<?php 
													foreach($priceClasses as $priceClass) {
														echo "<option value=".$priceClass.">".$priceClass."</option>";
													}
											?>
									</select>
							</td>
						</tr>
            <tr>
              <td><?php _e('free of charge from','selectyco')?></td>
							<td colspan="2">
                <input style="width:88px" id="sycValidTo" name="meta-box-runTime" readonly class="sycDatePicker" type="text" value="<?php echo $futureDate; ?>">
							</td>
						</tr>
						<tr>
							<td colspan="2" align="center">
								<input style="width:150px" name="meta-box-buyUrl" id="sycBuyUrl" type="hidden" value="<?php echo get_permalink( $wpPostId ); ?>">
								<input type="hidden" id="wpPostId" value="<?php echo $wpPostId; ?>">
                <input type='button' id='sycInsertItem' class='button button-primary' style="margin-top:10px; width:200px" value="<?php _e('create selectyco item','selectyco')?>" />
							</td>
						</tr>
						<tr>
							<td colspan="2" style="padding-top:10px; text-align:center">
								<span id="sycItemStatus"></span>
							</td>
						</tr>
					</table>
				<?php } ?>
        </form>
        <?php  
      }
      
      public function add_syc_script_styles()
      {
        global $typenow;
        global $current_screen;

        if ( 'post' == $typenow && 'post' == $current_screen->post_type ) {
          wp_enqueue_script( 'syc_js', plugins_url( 'inc/selectycoAjax.js', __FILE__ ) );
          wp_enqueue_script('jquery-ui-datepicker');
          wp_localize_script( 'syc_js', 'wp_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'ajaxnonce' => wp_create_nonce( 'syc_validation' ) ) );
          
          // CSS
          wp_register_style('selectyco-wp-plugin', plugins_url( 'inc/style.css', __FILE__ ));
          wp_enqueue_style('selectyco-wp-plugin');
          wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
          wp_enqueue_style( 'wpb-fa', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
        }
        else {
          wp_enqueue_script( 'syc_js', plugins_url( 'inc/selectycoAjax.js', __FILE__ ) );
          wp_enqueue_script('jquery-ui-datepicker');
          wp_localize_script( 'syc_js', 'wp_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'ajaxnonce' => wp_create_nonce( 'syc_validation' ) ) );
        }
      }
      
      
      public function sycApiGetItems() {
        $sycOptions = get_option('selectyco_options');
        $verificationKey = $sycOptions['authKey'];
        
        $ch = curl_init(SELECTYCO_HOST.'/items?year='.$_POST['sycItemsOfYear']);
         
        curl_setopt_array($ch, array(
          CURLOPT_SSL_VERIFYPEER => FALSE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
          'X-SELECTYCO-AUTH: '.$verificationKey,
          'Content-Type: application/json'
          )
        ));

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info['http_code'] === 200) {
          $responseData = json_decode($response);
          wp_send_json_success( array('sycReqType' => 'apiGetItems', 'success' => __( 'alles ok', 'selectyco' ), 'items' => $responseData) );
          return $responseData;
        }
        else {
          wp_send_json_error( array('sycReqType' => 'apiGetItems', 'error' => __( 'an error occured', 'selectyco' )) );
        }
      }
      
      
   
      public function sycApiGetItem($sycItem, $referrer) {
        $sycOptions = get_option('selectyco_options');
        $verificationKey = $sycOptions['authKey'];
        
        $ch = curl_init(SELECTYCO_HOST.'/items/'.$sycItem);
         
        curl_setopt_array($ch, array(
          CURLOPT_SSL_VERIFYPEER => FALSE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
          'X-SELECTYCO-AUTH: '.$verificationKey,
          'Content-Type: application/json'
          )
        ));

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info['http_code'] === 200) {
          $responseData = json_decode($response);
          return $responseData;
        }
        else {
         if($referrer == "merchant") {
            wp_send_json_error( array('sycReqType' => 'apiGetItem', 'error' => __( 'read item data failed', 'selectyco' )) );
          }
          else {
            return null;
          }
        }
      }
   
      public function sycApiInsertItem() 
      {
        check_ajax_referer( 'syc_validation', 'ajaxnonce' );
        
        $today = date("Y-m-d");
        $sycDateValidTo = date("Y-m-d", strtotime($_POST['sycValidTo']));
        
        if( empty( $_POST['sycItemName'] ) ) {
           wp_send_json_error( array('error' => __( 'itemname missing','selectyco')) );
        }
				else if(!ctype_digit($_POST['sycTeaserLen'])) {
					wp_send_json_error( array('error' => __( 'Teaser word count missing','selectyco')) );
				}
        else if($today > $sycDateValidTo) {
          wp_send_json_error( array('error' => __( 'valid to date invalid', 'selectyco' )) );
        }
        else {
          $postData = array(
              'itemName' => $_POST['sycItemName'],
              'itemType' => $_POST['sycItemType'],
              'priceClass' => $_POST['sycPriceClass'],
              'buyUrl' => $this->checkUrl($_POST['sycBuyUrl']),
              'category' => 6,
              'dateValidFrom' => date('Y-m-d H:i:s'),
              'dateValidTo' => $sycDateValidTo
          );
          
          $sycOptions = get_option('selectyco_options');
          $verificationKey = $sycOptions['authKey'];
          
          $ch = curl_init(SELECTYCO_HOST.'/items');
           
          curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
            'X-SELECTYCO-AUTH: '.$verificationKey,
            'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($postData)
          ));

          $response = curl_exec($ch);
          $info = curl_getinfo($ch);
          curl_close($ch);
          
          $responseData = json_decode($response);
          
          if($info['http_code'] === 201) {
            if(property_exists($responseData, 'itemId') && strlen($responseData->itemId) == 36){
              global $wpdb; 
              $table_name = $wpdb->prefix . 'selectyco';
              
              $insert = "INSERT INTO " . $table_name . " (wpPostId, sycItemId, sycTeaserLen, ts) " 
              . "VALUES ('" .$_POST['sycWpPostId']. "','" .$responseData->itemId. "','" .$_POST['sycTeaserLen']. "', now())";
              
              $wpdb->query( $insert );
              wp_send_json_success( array('sycReqType' => 'insert_IntoWPTable', 'success' => __( 'Item created successfully', 'selectyco' )) );
            }
            else {
              wp_send_json_error( array('error' => __( 'Item could not be created', 'selectyco' )) );
            }
          }
          else if($responseData->message === 'Authorization has been denied for this request.') {
              wp_send_json_error( array('error' => __( 'Authentication key invalid,<br />please check your settings.', 'selectyco' )) );
          }
          else if($info['ssl_verify_result'] === 1) {
            wp_send_json_error( array('error' => __( 'SSL error', 'selectyco' )) );
          }
          else {
            wp_send_json_error( array('error' => __( 'an error occured', 'selectyco' )) );
          }
        }
      }
      
		
      public function sycApiDeactivateItem() {
        $sycOptions = get_option('selectyco_options');
        $verificationKey = $sycOptions['authKey'];
        
        if($_POST['sycDeactivateConfirm'] != 'true') {
          wp_send_json_error( array('sycReqType' => 'deactivateItem', 'error' => __( 'delete selectyco button permanently?<br />please confirm with checkbox!', 'selectyco' )) );
        }
        else {
          $ch = curl_init(SELECTYCO_HOST.'/items/'. $_POST['sycItemId']);
           
          curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
            'X-SELECTYCO-AUTH: '.$verificationKey,
            'Content-Type: application/json'
            )
          ));

          curl_exec($ch);
          $info = curl_getinfo($ch);
          curl_close($ch);
          
          if($info['http_code'] === 204) {
            wp_send_json_success( array('sycReqType' => 'deactivateItem', 'success' => __( 'selectyco button deleted', 'selectyco' )) );  
          }
          else {
            wp_send_json_error( array('sycReqType' => 'deactivateItem', 'error' => __( 'deactivate item failed', 'selectyco' )) );
          } 
        }
      }
      
      
      public function sycApiUpdateItem() {
        $today = date("Y-m-d");
        $sycDateValidTo = date("Y-m-d", strtotime($_POST['newSycValidTo']));
        
        if($today > $sycDateValidTo) {
          wp_send_json_error( array('sycReqType' => 'updateItem', 'error' => __( 'valid to date invalid', 'selectyco' )) );
        }
        else {
          $sycOptions = get_option('selectyco_options');
          $verificationKey = $sycOptions['authKey'];
          $postData = array(
              'priceClass' => $_POST['newSycPriceClass'],
              'datevalidto' => $sycDateValidTo,
          );

          $ch = curl_init(SELECTYCO_HOST.'/items/'. $_POST['sycItemId']);
          
          curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
            'X-SELECTYCO-AUTH: '.$verificationKey,
            'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($postData)
          ));

          $response = curl_exec($ch);
          $info = curl_getinfo($ch);
          
          if($info['http_code'] === 200) {
            if(!ctype_digit($_POST['newSycTeaserLen'])) {
              wp_send_json_error( array('sycReqType' => 'updateItem', 'error' => __( 'Teaser word count missing', 'selectyco' )) );
            }
            else {
              global $wpdb;
              $table_name = $wpdb->prefix . 'selectyco';
              $update = "UPDATE " . $table_name . " SET  sycTeaserLen = " .$_POST['newSycTeaserLen']. " WHERE wpPostId  = " .$_POST['sycWpPostId'];
              
              $wpdb->query( $update );
              wp_send_json_success( array('sycReqType' => 'updateItem', 'success' => __( 'item updated', 'selectyco' )) );          
            }
          }
          else {
            $responseData = json_decode($response);
            if($responseData->message === "Price class does not exist for this merchant.") {
              wp_send_json_error( array('sycReqType' => 'updateItem', 'error' => __( 'Price cateogry not available', 'selectyco' )) );
            }
            else {
              wp_send_json_error( array('sycReqType' => 'updateItem', 'error' => __( 'item update failed', 'selectyco' )) );
            }
          }
        }
      }
      
      public function send_licenceRequest() {
        
        check_ajax_referer( 'syc_validation', 'ajaxnonce' );
        
        if (!filter_var($_POST['requestEmail'], FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error( array('sycReqType' => 'send_licenceRequest', 'error' => __( 'email is invalid', 'selectyco' )) );
        }
        else if(empty( $_POST['requestUrl'] ) || empty( $_POST['requestCPerson']) ) {
           wp_send_json_error( array('sycReqType' => 'send_licenceRequest', 'error' => __( 'all fields are mandatory', 'selectyco' )) );
        }
        else {
          $headers = "From:selectyco Wordpress Plugin <office@selectyco.com>\r\n";
          $to = "gg@selectyco.com";
          $subject = "Wordpress-Plugin Licence-Request";
          $site = get_site_url();
          $msg = "requestEmail: " . $_POST['requestEmail'] . "\r\nrequestUrl: " . $_POST['requestUrl'] . "\r\nrequestCPerson: " . $_POST['requestCPerson'] . "\r\nSite: " . $site;
          wp_mail( $to, $subject, $msg, $headers );
          
          $body = file_get_contents('https://www.selectyco.com/media/1134/licence_request.html');
          add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
          wp_mail( $_POST['requestEmail'], $subject, $body, $headers );
          
          wp_send_json_success( array('sycReqType' => 'send_licenceRequest', 'success' => __( 'request successfully sent', 'selectyco' )) );
        }
      }
      

			function add_button_to_post() {

				//posts over-view
				if(is_front_page() && is_home()) {
					return $this->generate_button();
				}
				//single post-view
				elseif(is_single()) {
					//purchasing process
					if(isset($_POST['token'])) {
            $sig_req = $_POST['token'];
            $fsi = new VerificateSycItem();
            $success = $fsi->parse_signed_request($sig_req);
            if($success) {
              return wpautop(get_the_content());
            }
          }
					//direct link
					else {
						return $this->generate_button();
					}
				}
				elseif(is_page()) {
					return wpautop(get_the_content());
				}
			}
			

      function generate_button() 
      {
        global $post;
        $wpPostId = $post->ID;
        $sycWpItem = $this->getSycWpItem($wpPostId);
        
        $excerpt = wpautop(get_the_content());
        
        if($sycWpItem != NULL) {
          $sycApiItem = $this->sycApiGetItem($sycWpItem->sycItemId, "endUser");
          $sycApiItemExists = $sycApiItem != null ? true : false;
          $sycApiItemActive = $sycApiItem->active;
                 
          if($sycApiItemExists && $sycApiItemActive) {
            $today = date("Y-m-d");
            $sycDateValidTo = date("Y-m-d", strtotime($sycApiItem->dateValidTo.'+6 hours'));
            
            if($today < $sycDateValidTo) {
              $sycOptions = get_option('selectyco_options');
              $teaserLength = $sycWpItem->sycTeaserLen;
              $buttonWidth = $sycOptions['buttonWidth'];
              $displayProperty = $sycOptions['displayProperty'];
              $excerpt = '<p>'.$this->wp_trim_words_retain_formatting( $excerpt, $teaserLength ).'</p>';
              
              $excerpt .= '<style> #sycButton iframe {  position: '.$displayProperty.' !important;  } </style>
                           <div id="sycButton">
                              <script src="'.SELECTYCO_HOST.'/scripts/selectyco.loader.min.js" data-width="'.$buttonWidth.'" data-item="'.$sycWpItem->sycItemId.'" data-popup="true" async></script>
                           </div>';
            }
            else {
              return $excerpt;
            }
          }
        }
          return $excerpt;
      }
      
      public function getSycWpItem($wpPostId) {
        global $wpdb; 
        $table_name = $wpdb->prefix . 'selectyco';
        $select = "SELECT wpPostId, sycItemId, sycTeaserLen, ts FROM " . $table_name . " WHERE wpPostId= ".$wpPostId;
        $sii = $wpdb->get_results( $select );
        if(sizeof($sii) > 0)
          return $sii[0];
        else
          return null;
      }
      
      

      function checkUrl($urlStr) {
        $parsed = parse_url($urlStr);
        if (empty($parsed['scheme'])) {
          $urlStr = 'http://' . ltrim($urlStr, '/');
        }
        return $urlStr;
      }
      
			
			function wp_trim_words_retain_formatting( $text, $num_words, $more = null ) {
				if ( null === $more )
						$more = __( '&nbsp; &hellip;' );
				$original_text = $text;
				/* translators: If your word count is based on single characters (East Asian characters),
					 enter 'characters'. Otherwise, enter 'words'. Do not translate into your own language. */
				if ( 'characters' == _x( 'words', 'word count: words or characters?' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
						$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
						preg_match_all( '/./u', $text, $words_array );
						$words_array = array_slice( $words_array[0], 0, $num_words + 1 );
						$sep = '';
				} else {
						$words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
						$sep = ' ';
				}
				if ( count( $words_array ) > $num_words ) {
						array_pop( $words_array );
						$text = implode( $sep, $words_array );
						$text = $text . $more;
				} else {
						$text = implode( $sep, $words_array );
				}

				return apply_filters( 'wp_trim_words', $text, $num_words, $more, $original_text );
		}
		
    // end class
    }
    
  // end-if class exists
  }
  
  
  //Verification Class
  class VerificateSycItem {
    
    private $signed_request = ''; 
    
    function base64_url_decode($input) {
      return base64_decode(strtr($input, '-_', '+/'));
    }
    
    function parse_signed_request($sig_req) {
      $this->signed_request = $sig_req;
      
      list($encoded_sig, $payload) = explode('.', $this->signed_request);
      
      $sycOptions = get_option('selectyco_options');
      $verificationKey = $sycOptions['authKey'];
      
      // decode the data
      $signature = $this->base64_url_decode($encoded_sig);
      
      // confirm the signature
      $expected_signature = hash_hmac('sha256', $payload, $verificationKey, $raw = false);
      
      if($signature !== $expected_signature) {
        return false;
      }
      else {
        $data = json_decode($this->base64_url_decode($payload), true);
        $success = $this->verifyBuy($verificationKey, $data);
        return $success;
      }
    }
    
    function verifyBuy($verificationKey, $data) {

      $postData = array(
          'verificationToken' => $data{'verificationTokenId'}
      );
      
      $ch = curl_init(SELECTYCO_HOST.'/items/verifypurchase');
      curl_setopt_array($ch, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
          'X-SELECTYCO-AUTH: '.$verificationKey,
          'Content-Type: application/json'
          ),
          CURLOPT_POSTFIELDS => json_encode($postData)
      ));
   
      $info = curl_getinfo($ch);
      
      if($info['http_code'] != 400) {
        return true;
      }
      else {
        return false;
      }
      
      curl_close($ch);
    }
  }
  
  
  function syc_init() {
    syc_class::get_instance();
  }
  
  if (class_exists('syc_class')) {
    // Install and Uninstall hooks
    register_activation_hook( __FILE__, array('syc_class', 'syc_activate' ));
    register_deactivation_hook( __FILE__, array('syc_class', 'syc_deactivate' ));
    register_uninstall_hook( __FILE__, array('syc_class', 'syc_uninstall' ));
    
    // init the plugin class
    load_plugin_textdomain( 'selectyco', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    add_action('plugins_loaded', 'syc_init');
  }

?>