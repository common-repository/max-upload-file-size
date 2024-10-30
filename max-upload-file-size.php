<?php
/*
  Plugin Name: Max Upload File Size
  Description: Increase your file upload size.
  Author: alexander909
  Version: 1.0.0
  License: GPL2
  Text Domain: max-upload-file-size
 */

// main plugin class
class Max_upload_file_size
{
  static function init()
  {
    if (is_admin()) {
      add_action('admin_menu', array(__CLASS__, 'mufs_upload_max_file_size_add_pages'));
      add_filter('install_plugins_table_api_args_featured', array(__CLASS__, 'mufs_featured_plugins_tab'));
      add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'mufs_plugin_action_links'));
      add_filter('plugin_row_meta', array(__CLASS__, 'mufs_plugin_meta_links'), 10, 2);
      add_filter('mufs_admin_footer_text', array(__CLASS__, 'mufs_admin_footer_text'));
      
      if (isset($_POST['upload_max_file_size_field']) 
          && wp_verify_nonce($_POST['upload_max_file_size_nonce'], 'upload_max_file_size_action')
          && is_numeric($_POST['upload_max_file_size_field'])) {
          $max_size = (int) $_POST['upload_max_file_size_field'] * 1024 * 1024;
          update_option('max_file_size', $max_size);
          wp_safe_redirect(admin_url('?page=upload_max_file_size&max-size-updated=true'));
      }
    }
      
    add_filter('upload_size_limit', array(__CLASS__, 'mufs_upload_max_increase_upload'));
  } // init
  
  
  // get plugin version from header
  static function mufs_get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');

    return $plugin_data['version'];
  } // get_plugin_version
  
  
  // test if we're on plugin's page
  static function mufs_is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'toplevel_page_upload_max_file_size') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page
  
  
  // add settings link to plugins page
  static function mufs_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=upload_max_file_size') . '" title="Adjust Max File Upload Size Settings">Settings</a>';

    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // add links to plugin's description in plugins table
  static function mufs_plugin_meta_links($links, $file) {
    $support_link = '<a target="_blank" href="mailto:reevs2020@hotmail.com" title="Get help">Support</a>';


    if ($file == plugin_basename(__FILE__)) {
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links
  
  
  // additional powered by text in admin footer; only on plugin's page
  static function mufs_admin_footer_text($text) {
    if (!self::mufs_is_plugin_page()) {
      return $text;
    }

    $text = '<i>Max Upload File Size v' . self::mufs_get_plugin_version() . ' ' . $text;

    return $text;
  } // admin_footer_text


  /**
   * Add menu pages
   *
   * @since 1.4
   * 
   * @return null
   * 
   */
  static function mufs_upload_max_file_size_add_pages()
  {
      // Add a new menu on main menu
      add_menu_page('Increase Max Upload File Size', 'Max Upload File Size', 'manage_options', 'upload_max_file_size', array(__CLASS__, 'mufs_upload_max_file_size_dash'), 'dashicons-upload');
  } // upload_max_file_size_add_pages


  /**
   * Get closest value from array
   *
   * @since 1.4
   * 
   * @param int search value
   * @param array to find closest value in
   * 
   * @return int in MB, closest value
   * 
   */
  static function mufs_get_closest($search, $arr)
  {
      $closest = null;
      foreach ($arr as $item) {
          if ($closest === null || abs($search - $closest) > abs($item - $search)) {
              $closest = $item;
          }
      }
      return $closest;
  } // get_closest


  /**
   * Dashboard Page
   *
   * @since 1.4
   * 
   * @return null
   * 
   */
  static function mufs_upload_max_file_size_dash()
  {
    echo '<style>';
    echo '.wrap, .wrap p { font-size: 15px; } .form-table th { width: 230px; }';
    echo '.gray-box { display: inline-block; padding: 15px; background-color: #e6e6e6; }';
    echo '</style>';
    
    if (isset($_GET['max-size-updated'])) {
        echo '<div class="notice-success notice is-dismissible"><p>Max Upload File Size Saved!</p></div>';
    }

    $ini_size = ini_get('upload_max_filesize');
    if (!$ini_size) {
        $ini_size = 'unknown';
    } elseif (is_numeric($ini_size)) {
        $ini_size .= ' bytes';
    } else {
        $ini_size .= 'B';
    }

    $wp_size = wp_max_upload_size();
    if (!$wp_size) {
        $wp_size = 'unknown';
    } else {
        $wp_size = round(($wp_size / 1024 / 1024));
        $wp_size = $wp_size == 1024 ? '1GB' : $wp_size . 'MB';
    }

    $max_size = get_option('max_file_size');
    if (!$max_size) {
        $max_size = 64 * 1024 * 1024;
    }
    $max_size = $max_size / 1024 / 1024;


    $upload_sizes = array(50, 100, 200, 512, 1024);

    $current_max_size = self::mufs_get_closest($max_size, $upload_sizes);

    echo '<div class="wrap">';
    echo '<h1><span class="dashicons dashicons-upload" style="font-size: inherit; line-height: unset;"></span> Max Upload File Size Settings</h1><br>';

//    echo '<p class="gray-box"><b>Do you like the plugin?</b>: If yes, give us a <a href="#">five star rating</a>. If no, <u class="let_us_know">let us know</u><a class="form_show"></a> how we can make it better.';

    echo '<p>Maximum upload file size, set by your hosting provider: ' . $ini_size . '.<br>';
    echo 'Maximum upload file size, set by WordPress: ' . $wp_size . '.</p>';
    
    echo '<form method="post">';
    settings_fields("header_section");
    echo '<table class="form-table"><tbody><tr><th scope="row"><label for="upload_max_file_size_field">Choose Maximum Upload File Size</label></th><td>';
    echo '<select id="upload_max_file_size_field" name="upload_max_file_size_field">';
    foreach ($upload_sizes as $size) {
        echo '<option value="' . $size . '" ' . ($size == $current_max_size ? 'selected' : '') . '>' . ($size == 1024 ? '1GB' : $size . 'MB') . '</option>';
    }
    echo '</select>';
    echo '</td></tr></tbody></table>';
    echo wp_nonce_field('upload_max_file_size_action', 'upload_max_file_size_nonce');
    submit_button();
    echo '</form>';	
	echo '<br>';
	echo '<b>Note:</b> If Maximum upload file size, set by your hosting provider is lesser, please contact your hosting provider to increase it.';
	echo '<br>';

	echo '<div class="let_us_know_form" id="let_us_know_form">';
	echo '<Form action ="" method="POST">';
	echo 'Your name: <input name="nam" type="text"><br>';
	echo 'Your email: <input name="email" type="email"><br>';
	echo 'Feedback: <textarea name="message" style="position: relative; left: 8px; width: 166px;"></textarea><br>';
	echo '<input type="submit" class="accept_feedback button button-primary" style="position: relative; left: 130px;">';
	echo '</div>';
	
	if(isset($_POST['nam']) && isset($_POST['email']) && isset($_POST['message'])){
		$name = sanitize_text_field($_POST['nam']);
		$email = sanitize_text_field($_POST['email']);
		$message = sanitize_text_field($_POST['message']);
		$send_message = "Message received from: ".$email."<br><br>Message: ".$message;
		$subject = "Feedback received from ". $name ." for 'Max Upload File Size' plugin.";
		$to = 'alexander0012020@outlook.com';
		wp_mail($to, $subject, $send_message);
	}
    

    echo '</div>';
  } // upload_max_file_size_dash


  /**
   * Filter to increase max_file_size
   *
   * @since 1.4
   * 
   * @return int max_size in bytes
   * 
   */
  static function mufs_upload_max_increase_upload()
  {
      $max_size = (int) get_option('max_file_size');
      if (!$max_size) {
          $max_size = 64 * 1024 * 1024;
      }

      return $max_size;
  } // upload_max_increase_upload
  
  
  // add our plugins to recommended list
  static function mufs_plugins_api_result($res, $action, $args) {
    remove_filter('mufs_plugins_api_result', array(__CLASS__, 'mufs_plugins_api_result'), 10, 3);

    $res = self::mufs_add_plugin_favs('under-construction-page', $res);
    $res = self::mufs_add_plugin_favs('wp-reset', $res);
    $res = self::mufs_add_plugin_favs('eps-301-redirects', $res);

    return $res;
  } // plugins_api_result
  
  
  // helper function for adding plugins to fav list
  static function mufs_featured_plugins_tab($args) {
    add_filter('mufs_plugins_api_result', array(__CLASS__, 'mufs_plugins_api_result'), 10, 3);

    return $args;
  } // featured_plugins_tab


  // add single plugin to list of favs
  static function mufs_add_plugin_favs($plugin_slug, $res) {
    if (!empty($res->plugins) && is_array($res->plugins)) {
      foreach ($res->plugins as $plugin) {
        if (is_object($plugin) && !empty($plugin->slug) && $plugin->slug == $plugin_slug) {
          return $res;
        }
      } // foreach
    }

    if ($plugin_info = get_transient('wf-plugin-info-' . $plugin_slug)) {
      array_unshift($res->plugins, $plugin_info);
    } else {
      $plugin_info = plugins_api('plugin_information', array(
        'slug'   => $plugin_slug,
        'is_ssl' => is_ssl(),
        'fields' => array(
            'banners'           => true,
            'reviews'           => true,
            'downloaded'        => true,
            'active_installs'   => true,
            'icons'             => true,
            'short_description' => true,
        )
      ));
      if (!is_wp_error($plugin_info)) {
        $res->plugins[] = $plugin_info;
        set_transient('wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7);
      }
    }

    return $res;
  } // add_plugin_favs
} // class Max_upload_file_size

add_action('init', array('Max_upload_file_size', 'init'));

?>