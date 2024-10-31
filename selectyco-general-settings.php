<?php

/**
 * Saving and display of selectyco settings on the options page
 */
 
 function selectyco_general_settings() {
	$options = get_option('selectyco_options');
  
  if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
  
  ?>

  <div id="selectyco-admin">
    <div id="sycIntro">
      <h1><?php _e('Welcome to the selectyo Plugin!','selectyco'); ?></h1>
      <h2><?php _e('In three easy steps you can start selling your single digital content, directly on your website, via selectyco.','selectyco'); ?>
      <br><?php _e('Should you have further questions, you will find more information in the description section or send an email to <a href="mailto:support@selectyco.com"/> support@selectyco.com</a>.','selectyco'); ?>
      </h2>
    </div>
    
    <form action="options.php" method="post" id="selectyco-conf">
      <?php settings_fields( 'selectyco-settings-group' ); ?>
      <?php do_settings_sections( 'selectyco-settings-group' ); ?>
      
      <div id="sycStep1" class="steps">
        <span class="step"><?php _e('Step','selectyco'); ?> 1:</span><?php _e('Please fill out the fields below. The licence agreement (and form) will be sent to you via email.<br />After receipt of the signed licence agreement, you will receive the countersigned agreement and the authentication key.','selectyco'); ?>
        <div class="stepContainer">
          <div class="left">
            E-Mail
            <br /><?php _e('Your website','selectyco'); ?>
            <br /><?php _e('Contact person','selectyco'); ?>
          </div>
          <div class="content">
            <input type="text" id="sycId_eMail" name="eMail" placeholder="office@example.com" />
            <br /><input type="text" id="sycId_url" name="url" placeholder="www.example.com" />
            <br /><input type="text" id="sycId_cPerson" name="cPerson" placeholder="<?php _e('firstname, lastname'); ?>" />
          </div>
          <div class="right">
            <span id="licenceStatus"></span><br /><br /><br />
            <input type="button" id="sendLicenceRequest" class="button button-primary" value="<?php _e('send mail','selectyco')?>" />
          </div>
          <div class="clr"></div>
        </div>
      </div>
      
      <div id="sycStep2" class="steps">
        <span class="step"><?php _e('Step','selectyco'); ?> 2:</span><?php _e('Please paste the authentication key that was sent to you via email below.','selectyco'); ?>
        <div class="stepContainer">
          <div class="left"><?php _e('Authentication key','selectyco'); ?></div>
          <div class="content"><textarea rows="3" id="sycId_authKey" name="selectyco_options[authKey]"><?php echo $options['authKey']; ?></textarea></div>
          <div class="right"><div class="submit-button"><?php submit_button(null, "primary", "authKey", false); ?></div></div>
          <div class="clr"></div>
        </div>
      </div>

      <div id="sycStep3" class="steps">
        <span class="step"><?php _e('Step','selectyco'); ?> 3:</span> <?php _e('Choose the selectyco button size (Please note: the displayed button text changes depending on the content type, i.e. if it is a video: "Dieses Video um € 0,60 ansehen")','selectyco'); ?>
        <div class="stepContainer">
          <div class="left"><input id="sycButtonPreviewSlider" type="range" min="160" max="280" step="1" /></div>
          <div class="content">
            <input size="5" readonly type="text" id='sycId_buttonWidth' name='selectyco_options[buttonWidth]' value="<?php echo $options['buttonWidth'] ?>" /> pixel
            <img style="vertical-align:middle; padding-left:50px" width="<?php echo $options['buttonWidth'] ?>" id="sycButtonPreview" src="<?php echo plugins_url( 'images/selectyco-button.png', __FILE__ )?>">
          </div>
          <div class="right"><div class="submit-button"><?php submit_button(null, "primary", "buttonWidth", false); ?></div></div>
          <div class="clr"></div>
        </div>
        <div class="stepContainer">
          <div class="left">
            CSS Display Propterty
          </div>
          <div class="content">
            <?php 
              $displayPropertys = array('absolute','relative','fixed','sticky');
            ?>
            <select style="width:100px" name="selectyco_options[displayProperty]" >
              <?php 
                  foreach($displayPropertys as $displayProperty) {
                    if($options['displayProperty'] === $displayProperty) {
                      echo "<option value=".$displayProperty." selected>".$displayProperty."</option>";  
                    }
                    else {
                      echo "<option value=".$displayProperty.">".$displayProperty."</option>";  
                    }
                  }
              ?>
            </select>
          </div>
          <div class="right"><div class="submit-button"><?php submit_button(null, "primary", "displayProperty", false); ?></div></div>
          <div class="clr"></div>
        </div>
      </div>
      
      <div id="sycStep4" class="steps">
        <span class="step">
        <?php _e('Show all items from one year.','selectyco'); ?>
        </span>
        <div class="right">
          <select style="width:65px" id="sycItemsOfYear" >
              <?php 
                  for($i=date('Y'); $i>=2015; $i--)
                  {
                      echo '<option value='.$i.'>'.$i.'</option>';
                  }
              ?>
          </select>
          <input type="button" id="sycGetItems" class="button button-primary" value="<?php _e('show itemlist','selectyco')?>" />
        </div>
        <pre id="apiGetItemsList"></pre>
      </div>
    </form>
  </div>
  
  <?php
 }
 
?>