<?php

  require_once SELECTYCO_DIR. '/selectyco-general-settings.php';

  add_action('admin_menu', 'sycPlugin_admin_add_page');

  function sycPlugin_admin_add_page() {
    add_menu_page('selectyco', 'selectyco', 'manage_options', 'selectcoPluginSlugName', 'selectyco_general_settings', plugins_url( 'images/adminIcon.png', __FILE__ ) );
    syc_register_settings();
  }

  function syc_register_settings() {
    register_setting( 'selectyco-settings-group', 'selectyco_options', 'sycPlugin_options_validate' );
    wp_register_style( 'selectyco-wp-plugin-admin', plugins_url( 'inc/style.css', __FILE__ ) );
    wp_enqueue_style( 'selectyco-wp-plugin-admin' );
  }
  

  function sycPlugin_options_validate($input) {
    $options = get_option('selectyco_options');
    $options['authKey'] = checkInputTypeAndValue('authKey', trim($input['authKey']), 172, 172, 'authentication key invalid!');
    $options['buttonWidth'] = checkInputTypeAndValue('buttonWidth', trim($input['buttonWidth']), 160, 280, 240);
    $options['displayProperty'] = $input['displayProperty'];
    
    return $options;
  }

  function checkInputTypeAndValue($key, $val, $min, $max, $default) {
    if($key == 'buttonWidth') {
      if(ctype_digit($val)) {
        return ($val >= $min && $val <= $max ) ? $val : $default;
      }
      else {
        return $default;
      }
    }
    else if($key == 'authKey') {
      return (strlen($val) >= $min && strlen($val) <= $max ) ? $val : $default;
    }
  }
  
?>