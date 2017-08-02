<?php

/**
 * Plugin Name: Sirv
 * Plugin URI: http://sirv.com
 * Description: Instantly resize or crop images to any size. Add watermarks, titles, text and image effects. Embed them as images, galleries, zooms or 360 spins. Serve them from the fast CDN. Responsive, to perfectly fit the screen. Use the "Add Sirv Media" button on posts and pages. <a href="admin.php?page=sirv/sirv/options.php">Settings</a>
 * Version: 1.4.6
 * Author: sirv.com
 * Author URI: sirv.com
 * License: GPLv2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// load shortcodes
require_once (dirname (__FILE__) . '/sirv/shortcodes.php');

//create shortcode's table on plugin activate
register_activation_hook( __FILE__, 'sirv_activation_callback' );

function sirv_activation_callback(){
    sirv_create_plugin_tables();
    register_sirv_settings();

    $notices= get_option('sirv_admin_notices', array());
    $notices[]= 'Congratulations! You\'ve just installed Sirv for WordPress! Now connect to Sirv account <a href="admin.php?page=sirv/sirv/options.php">here</a> so you can start using it.';
    update_option('sirv_admin_notices', $notices);
}


//show message on plugin activation
add_action('admin_notices', 'sirv_admin_notices');

function sirv_admin_notices() {
  if ($notices= get_option('sirv_admin_notices')) {
    foreach ($notices as $notice) {
      echo "<div class='updated'><p>$notice</p></div>";
    }
    delete_option('sirv_admin_notices');
  }
}

function sirv_create_plugin_tables(){
    global $wpdb;

    $table_shortcodes_name = $wpdb->prefix . 'sirv_shortcodes';
    $sql_shortcodes = "CREATE TABLE $table_shortcodes_name (
      id int unsigned NOT NULL auto_increment,
      width varchar(20) DEFAULT 'auto',
      thumbs_height varchar(20) DEFAULT NULL,
      gallery_styles varchar(255) DEFAULT NULL,
      align varchar(30) DEFAULT '',
      profile varchar(100) DEFAULT 'false',
      link_image varchar(10) DEFAULT 'false',
      show_caption varchar(10) DEFAULT 'false',
      use_as_gallery varchar(10) DEFAULT 'false',
      use_sirv_zoom varchar(10) DEFAULT 'false',
      images text DEFAULT NULL,PRIMARY KEY (id)) 
      ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    
    $table_images_name = $wpdb->prefix . 'sirv_images';
    $sql_sirv_images = "CREATE TABLE $table_images_name (
      id int unsigned NOT NULL auto_increment,  
      attachment_id int(11) NOT NULL,
      wp_path varchar(255) DEFAULT NULL,
      size int(10) DEFAULT NULL,
      sirvpath varchar(255) DEFAULT NULL,
      sirv_image_url varchar(255) DEFAULT NULL,
      sirv_folder varchar(255) DEFAULT NULL,
      timestamp datetime DEFAULT NULL,
      timestamp_synced datetime DEFAULT NULL,
      PRIMARY KEY (`id`)) 
      ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_shortcodes );
    dbDelta( $sql_sirv_images );
}

register_deactivation_hook(__FILE__, 'sirv_drop_plugin_tables');

function sirv_drop_plugin_tables(){
/*    global $wpdb;
    
    $table_name = $wpdb->prefix . 'sirv_images';
    $sql = "DROP TABLE IF EXISTS $table_name"; 

    $wpdb->query($sql);*/

    delete_sirv_settings();
}


$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'sirv_plugin_settings_link' );

function sirv_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=sirv/sirv/options.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 

//add button Sirv Media near Add Media
add_action('media_buttons','sirv_button', 11);

function sirv_button($editor_id = 'content') {
    wp_enqueue_script( 'sirv_modal', plugins_url('/sirv/js/wp-sirv-bpopup.min.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script( 'sirv_modal-logic', plugins_url('/sirv/js/wp-sirv-modal.js', __FILE__), array('jquery'), '1.0.0');
    wp_localize_script( 'sirv_modal-logic', 'modal_object', array('media_add_url' =>  plugins_url('/sirv/templates/media_add.html', __FILE__), 'login_error_url' => plugins_url('/sirv/templates/login_error.html', __FILE__), 'featured_image_url' => plugins_url('/sirv/templates/featured_image.html', __FILE__)) );
    //wp_enqueue_style('sirv_media_buttons', plugins_url('/sirv/css/wp-sirv-media-buttons.css', __FILE__));
    wp_enqueue_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
    wp_enqueue_script( 'sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), '1.0.0');
    wp_localize_script( 'sirv_logic', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => plugins_url('/sirv/assets', __FILE__)) );
    wp_enqueue_script( 'sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');

    
    echo '<a href="#" class="button sirv-modal-click" title="Sirv add/insert images"><span class="dashicons dashicons-format-gallery" style="padding-top: 2px;"></span> Add Sirv Media</a><div class="sirv-modal"><div class="modal-content"></div></div>';
}



//create menu for wp plugin and register settings
add_action("admin_menu", "sirv_create_menu", 0);

function sirv_create_menu(){
    $settings_item = '/sirv/sirv/options.php';
    $library_item = '/sirv/sirv/media_library.php';

    add_menu_page('Sirv Menu', 'Sirv', 'manage_options', $library_item, NULL, plugins_url('/sirv/sirv/assets/icon.png'));
    add_submenu_page( $library_item, 'Sirv Media Library', 'Media Library', 'publish_pages', $library_item);
    add_submenu_page( $library_item, 'Sirv Settings', 'Settings', 'manage_options', $settings_item);
    
    //add_action( 'admin_init', 'register_sirv_settings' );
}


//include plugin for tinyMCE to show sirv gallery shortcode in visual mode
add_filter('mce_external_plugins', 'sirv_tinyMCE_plugin_shortcode_view');

function sirv_tinyMCE_plugin_shortcode_view () {
     return array('sirvgallery' => plugins_url('sirv/js/wp-sirv-shortcode-view.js', __FILE__));
}


//add styles for tinyMCE plugin
add_action('admin_init', 'sirv_tinyMCE_plugin_shortcode_view_styles');

function sirv_tinyMCE_plugin_shortcode_view_styles(){
    add_editor_style( plugins_url('/sirv/css/wp-sirv-shortcode-view.css', __FILE__) );
}


function register_sirv_settings(){
    register_setting( 'sirv-settings-group', 'AWS_KEY' );
    register_setting( 'sirv-settings-group', 'AWS_SECRET_KEY' );
    register_setting( 'sirv-settings-group', 'AWS_HOST' );
    register_setting( 'sirv-settings-group', 'AWS_BUCKET' );
    register_setting( 'sirv-settings-group', 'WP_FOLDER_ON_SIRV');
    register_setting( 'sirv-settings-group', 'WP_USE_SIRV_CDN');
    register_setting( 'sirv-settings-group', 'WP_SIRV_NETWORK');

/*  add_option( 'AWS_KEY' );
    add_option( 'AWS_SECRET_KEY' );
    add_option( 'AWS_HOST' );
    add_option( 'AWS_BUCKET' );
    add_option( 'WP_USE_SIRV_CDN' );
    add_option( 'WP_FOLDER_ON_SIRV' );*/

    if(!get_option('AWS_HOST')) update_option('AWS_HOST', 's3.sirv.com');
    if(!get_option('WP_FOLDER_ON_SIRV')) update_option('WP_FOLDER_ON_SIRV', 'WP_MediaLibrary');
    if(!get_option('WP_SIRV_NETWORK')) update_option('WP_SIRV_NETWORK', '1');

}


function delete_sirv_settings(){
/*  delete_option( 'AWS_KEY' );
    delete_option( 'AWS_SECRET_KEY' );
    delete_option( 'AWS_HOST' );
    delete_option( 'AWS_BUCKET' );
    delete_option( 'WP_USE_SIRV_CDN' );*/
    //delete_option( 'WP_FOLDER_ON_SIRV' );

}


// create new tab Sirv
//add_filter( 'media_upload_tabs', 'sirv_tab' );

    /*function sirv_tab( $tabs ) {
    $tabs['sirv'] = "Insert from Sirv";
    return $tabs;
}*/


/*// upload scripts, css and iframe with content when tab selected
add_action( 'media_upload_sirv', 'sirv_tab_content' );

function sirv_tab_content() {

    wp_enqueue_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
    wp_enqueue_script( 'sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), '1.0.0');
    wp_localize_script( 'sirv_logic', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => plugins_url('/sirv/assets', __FILE__) ) );
    wp_enqueue_script( 'sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');

    wp_iframe( 'sirv_frame' );
}


// load iframe
function sirv_frame(){

    include('sirv/template.php');
}*/


// remove http(s) from host in sirv options
add_action( 'admin_notices', 'sirv_check_option');

function sirv_check_option(){
    global $pagenow;
    if ($pagenow == 'admin.php' && $_GET['page'] =='sirv/sirv/options.php') {
        if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {
            update_option('AWS_HOST', preg_replace('/(http|https)\:\/\/(.*)/ims', '$2', get_option('AWS_HOST')));
        }
    }
}



add_action('init', 'sirv_init');
function sirv_init(){
    if (get_option('WP_USE_SIRV_CDN') == 1){
        $GLOBALS['sirv_wp_additional_image_sizes'] = $GLOBALS['_wp_additional_image_sizes'];

        add_filter( 'wp_get_attachment_image_src', 'sirv_wp_get_attachment_image_src', 10, 4 );
        add_filter( 'wp_get_attachment_url', 'sirv_wp_get_attachment_url', 10, 2 );
        add_filter( 'wp_calculate_image_srcset', 'sirv_add_custom_image_srcset', 10, 5 );
        add_filter('vc_wpb_getimagesize', 'vc_wpb_filter', 10,3);
    }
}


function sirv_wp_get_attachment_image_src($image, $attachment_id, $size, $icon) {
    require_once 'sirv/options-service.php';
    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    $root_images_path = get_home_path() . 'wp-content/uploads';
    $root_url_images_path = get_site_url() . '/wp-content/uploads';
    
    //check if get_option('siteurl') return http or https
    if (stripos(get_option('siteurl'), 'https://') === 0) {
       $root_url_images_path = str_replace('http:','https:',$root_url_images_path);
    }

    $bucket = getValue::getOption('AWS_BUCKET');
    $folder_on_sirv = getValue::getOption('WP_FOLDER_ON_SIRV');

    //disable in admin area
    if(is_admin()) return $image;

    $image_url = $image[0];
    $image_width = $image[1];
    $image_height = $image[2];


    $sirv_domain = (get_option('WP_SIRV_NETWORK') == '1') ? '-cdn.sirv.com/' : '.sirv.com/';
    $sirv_root_path = 'https://'. $bucket . $sirv_domain . $folder_on_sirv;

    if(stripos($image_url, $root_url_images_path) === false) {
        if(stripos($image_url, $sirv_root_path) !== false) {
            $original_image_data = get_original_image($image_url, $sirv_root_path, $root_images_path);

            $image[0] = scale_image($original_image_data['original_image_url'], $image_width, $image_height, $size, $original_image_data['original_image_path']);
        }
    }else{
        $cdn_image_url = get_cdn_image($image_url, $attachment_id, $image_width, $image_height);
        if(!empty($cdn_image_url)){
            $original_image_data = get_original_image($cdn_image_url, $sirv_root_path, $root_images_path);
            $image[0] = scale_image($original_image_data['original_image_url'], $image_width, $image_height, $size, $original_image_data['original_image_path']);
        }
    }

    return $image;
}


function vc_wpb_filter($img, $img_id, $attributes){
    if(in_array($attributes['thumb_size'], array_values(get_intermediate_image_sizes()))) return $img;

    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    //print_r($attributes);
    //echo htmlspecialchars($img['thumbnail']);

    $bucket = get_option('AWS_BUCKET');
    $folder_on_sirv = get_option('WP_FOLDER_ON_SIRV');
    $root_images_path = get_home_path() . 'wp-content/uploads';
    $sirv_domain = (get_option('WP_SIRV_NETWORK') == '1') ? '-cdn.sirv.com/' : '.sirv.com/';
    $sirv_root_path = 'https://'. $bucket . $sirv_domain . $folder_on_sirv;

    preg_match('/(\d{2,4})x(\d{2,4})/is', $attributes['thumb_size'], $sizes);
    $img_sizes = array();
    $img_sizes['width'] = $sizes[1];
    $img_sizes['height'] = $sizes[2];

    $original_image_url = preg_replace('/\?scale.*/is', '', $img['p_img_large'][0]);
    $original_image_path =  str_replace($sirv_root_path, $root_images_path, $original_image_url);

    $scale_pattern = get_scale_pattern($original_image_path, $img_sizes['width'], $img_sizes['height'], true);
    $img['thumbnail'] = preg_replace('/-'.$sizes[0].'(\.[jpg|jpeg|png|gif]*)/is', '$1'.$scale_pattern, $img['thumbnail']);
    $img['p_img_large'][0] = $original_image_url;

    return $img;

}


function get_image_size($size){
    $sizes = array();
    $sizes['width'] = get_option( "{$_size}_size_w'");
    $sizes['heigh'] = get_option( "{$_size}_size_h'");
    $sizes['crop'] = (bool)get_option( "{$_size}_crop'");
}

function get_image_sizes() {
    global $_wp_additional_image_sizes;

    if(!empty($GLOBALS['sirv_wp_additional_image_sizes'])) $_wp_additional_image_sizes = $GLOBALS['sirv_wp_additional_image_sizes'];

    $sizes = array();

    foreach ( get_intermediate_image_sizes() as $_size ) {
        if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
            $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
            $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
            $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
        } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
            $sizes[ $_size ] = array(
                'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
                'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
            );
        }
    }

    return $sizes;
}


function get_original_image($image_url, $sirv_root_path, $root_images_path){
    $pattern = '/(.*?)[-|-]\d{1,4}x\d{1,4}(\.[a-zA-Z]{2,5})/is';
    $tested_image = preg_replace($pattern, '$1$2', $image_url);
    $image_path_on_disc = str_replace($sirv_root_path, $root_images_path, $tested_image);
    $orig_image = array();
    if(file_exists($image_path_on_disc)){
        $orig_image['original_image_url'] = $tested_image;
        $orig_image['original_image_path'] = $image_path_on_disc;

    }else{
        $orig_image['original_image_url'] = $image_url;
        $orig_image['original_image_path'] = str_replace($sirv_root_path, $root_images_path, $image_url);
    }
    return $orig_image;
}


function get_original_sizes($original_image_path){
    $image_dimensions = getimagesize($original_image_path);
    $sizes = Array('width' => 0, 'height' => 0);
    
    $sizes['width'] = $image_dimensions[0];
    $sizes['height'] = $image_dimensions[1];

    return $sizes;

}


function scale_image($image_url, $image_width, $image_height, $size, $original_image_path, $isCrop=false){
    $sizes = get_image_sizes();

    //fix if width or height received from sirv_wp_get_attachment_image_src == 0
    //if($image_width == '0' || $image_height == '0'){
    if(!empty($sizes) && in_array($size, array_keys($sizes))){
        $image_width = $sizes[$size]['width'];
        $image_height = $sizes[$size]['height'];
    }
    //}


    $isCrop = false;

    if(in_array($size, array_keys($sizes))){
        $isCrop = (bool)$sizes[$size]['crop'];
    }


    return $image_url . get_scale_pattern($original_image_path, $image_width, $image_height, $isCrop);
}

function get_scale_pattern($original_image_path, $image_width, $image_height, $isCrop){
    $pattern_crop_portrait = '?scale.width='.$image_width.'&crop.width='.$image_width.'&crop.height='.$image_height.'&crop.x=center&crop.y=center';
    $pattern_crop_landscape = '?scale.height='.$image_height.'&crop.width='.$image_width.'&crop.height='.$image_height.'&crop.x=center&crop.y=center';
    $pattern_crop_square = '?scale.width='.$image_width.'&crop.width='.$image_width.'&crop.height='.$image_height.'&crop.x=center&crop.y=center';
    $pattern_scale = '?scale.width='.$image_width .'&scale.height='.$image_height;
    $scale_width = '?scale.width=' . $image_width;
    $scale_height = '?scale.height=' . $image_height;

    //sometimes wp has strange giant image sizes
    if ($image_width > 3000) return $scale_height;
    if ($image_height > 3000) return $scale_width;
    if ($image_height > 3000 && $image_width > 3000) return '';

    if($isCrop){
        $original_image_sizes = get_original_sizes($original_image_path);
        $orientation = test_orientation($original_image_sizes);

        if($orientation == 'landsape'){
            if($image_width <= $image_height) return $pattern_crop_landscape;  
            if($image_width > $image_height) return $pattern_crop_portrait;  
        }
        if($orientation == 'portrait'){
             if($image_width <= $image_height)  return $pattern_crop_portrait;
             if($image_width > $image_height) return $pattern_crop_landscape; 
        }
        if($orientation == 'square') return $pattern_crop_square;
    }else{
        return $pattern_scale;
    }
}


function test_orientation($sizes){
    if ($sizes['width'] > $sizes['height']) return 'landsape';
    if ($sizes['width'] < $sizes['height']) return 'portrait';
    if ($sizes['width'] == $sizes['height']) return 'square';
}


function sirv_wp_get_attachment_url($url, $attachment_id ) {
    if(is_admin()) return $url;

    $cdn_image_url = get_cdn_image($url, $attachment_id);
    if(get_option('WP_SIRV_NETWORK') == '1') $cdn_image_url = str_replace('.sirv.com', '-cdn.sirv.com', $cdn_image_url);
        if(!empty($cdn_image_url)){
            $url = $cdn_image_url;
        }
        return $url;
} 


function sirv_add_custom_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id){
    //if url not from sirv return original array
    if(stripos($image_src, 'sirv.com') === false) return $sources;

    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    $bucket = get_option('AWS_BUCKET');
    $folder_on_sirv = get_option('WP_FOLDER_ON_SIRV');
    $root_images_path = get_home_path() . 'wp-content/uploads';
    $sirv_domain = (get_option('WP_SIRV_NETWORK') == '1') ? '-cdn.sirv.com/' : '.sirv.com/';
    $sirv_root_path = 'https://'. $bucket . $sirv_domain . $folder_on_sirv;

    global $wpdb;
    $table_name = $wpdb->prefix . 'sirv_images';

    $sql_result = $wpdb->get_row("SELECT * FROM " . $table_name ." WHERE attachment_id='".$attachment_id."'", ARRAY_A);
    if(get_option('WP_SIRV_NETWORK') == '1') $sql_result['sirv_image_url'] = str_replace('.sirv.com', '-cdn.sirv.com', $sql_result['sirv_image_url']);

    if(!empty($sql_result)){
        $original_image_path = str_replace($sirv_root_path, $root_images_path, $sql_result['sirv_image_url']);
        $image_sizes = array_keys($sources);
        foreach ($image_sizes as $size) {
            $size_name = get_size_name($size, $image_meta['sizes']);
            $sources[$size]['url'] = scale_image($sql_result['sirv_image_url'], $size, $size, $size_name, $original_image_path, true);
        }
    }
    return $sources;
}


function get_size_name($size, $array_of_sizes){
    foreach ($array_of_sizes as $size_name_key => $size_name_value) {
        if($size_name_value['width'] == $size) return $size_name_key;
    }
}


function get_cdn_image($image_url, $attachment_id, $image_width=NULL, $image_height=NULL) {

    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    $root_images_path = get_home_path() . 'wp-content/uploads';
    $root_url_images_path = get_site_url() . '/wp-content/uploads';
    
    //check if get_option('siteurl') return http or https
    if (stripos(get_option('siteurl'), 'https://') === 0) {
       $root_url_images_path = str_replace('http:','https:',$root_url_images_path);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'sirv_images';
    
    //require_once 'sirv/sirv_api.php';
    require_once 'sirv/options-service.php';

/*    $host = getValue::getOption('AWS_HOST');
    $bucket = getValue::getOption('AWS_BUCKET');
    $key = getValue::getOption('AWS_KEY');
    $secret_key = getValue::getOption('AWS_SECRET_KEY');*/
    $folder_on_sirv = getValue::getOption('WP_FOLDER_ON_SIRV');

    $image_base_name = str_replace($root_url_images_path, '', $image_url);
    $image_base_path = str_replace(basename($image_base_name), '', $image_base_name);
    $image_path_on_disc = $root_images_path . $image_base_name;
    $sirv_path = $folder_on_sirv.$image_base_path;
    $sirv_full_path = $folder_on_sirv . $image_base_name;
    
    //exit if file doesn't exist on disc
    if (!file_exists($root_images_path . $image_base_name)) {
        return '';
    }

    $image_size = filesize($root_images_path . $image_base_name);
    $image_created_timestamp = date("Y-m-d H:i:s", filemtime($root_images_path . $image_base_name));

    $sql_result = $wpdb->get_row("SELECT * FROM " . $table_name ." WHERE sirvpath='".$sirv_full_path."' AND size='".$image_size."' AND timestamp='".$image_created_timestamp."'", ARRAY_A);

    if(empty($sql_result)){
        //$sirv_image_url = upload_file($bucket, $sirv_path, $s3client, $image_path_on_disc);
        require_once 'sirv/aws-s3-helper.php';

        $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());
        $sirv_image_url_array = $s3object->uploadFile($sirv_full_path, $image_path_on_disc, $web_accessible = true);

        if(!empty($sirv_image_url_array)){
            //$sirv_image_url = urldecode(str_replace('s3.', '', $sirv_image_url));
            $sirv_image_url = $sirv_image_url_array['full_url'];

            $data = Array();
            $data['attachment_id'] = $attachment_id; 
            $data['wp_path'] = $image_base_name; 
            $data['size'] = $image_size;
            $data['sirvpath'] = $folder_on_sirv . $image_base_name; 
            $data['sirv_image_url'] = $sirv_image_url; 
            $data['sirv_folder'] = $folder_on_sirv;
            $data['timestamp'] = $image_created_timestamp;
            $data['timestamp_synced'] = date("Y-m-d H:i:s");

            if($wpdb->get_row("SELECT * FROM ". $table_name . " WHERE attachment_id='".$attachment_id."'")){
                $wpdb->update($table_name, $data, array('attachment_id' => $attachment_id));
            }else{
                $wpdb->insert($table_name, $data);
            }
            return $sirv_image_url;

        }else{
            //return original image
            return '';
        }
    }else{
        //return cached image
        return $sql_result['sirv_image_url'];
    }
}


//---------------------------------------------YOAST SEO fixes for og images-----------------------------------------------------------------------//

add_filter( 'wpseo_opengraph_image', 'sirv_wpseo_opengraph_image', 10, 1 );
add_filter( 'wpseo_twitter_image', 'sirv_wpseo_opengraph_image', 10, 1 );


function sirv_wpseo_opengraph_image($img){
    if(stripos($img, '-cdn.sirv') != false) $img = str_replace('-cdn', '', $img);

    return $img;
}

//---------------------------------------------YOAST SEO meta fixes for og images END ------------------------------------------------------------------//


//-------------------------------------------------------------Ajax requests-------------------------------------------------------------------------//

function get_params_array($key=null, $secret_key=null, $bucket=null, $host=null){
    require_once 'sirv/options-service.php';

    $host       = is_null($host) ? 's3.sirv.com' : $host;
    $bucket     = is_null($bucket) ? getValue::getOption('AWS_BUCKET') : $bucket;
    $key        = is_null($key) ? getValue::getOption('AWS_KEY') : $key;
    $secret_key = is_null($secret_key) ? getValue::getOption('AWS_SECRET_KEY') : $secret_key;

    return Array(
        'host'       => $host, 
        'bucket'     => $bucket, 
        'key'        => $key, 
        'secret_key' => $secret_key
    );
}

//use ajax request to get count and size of cached images from sirv_images
add_action( 'wp_ajax_sirv_get_cached_data', 'sirv_get_cached_data_callback' );
function sirv_get_cached_data_callback(){
    if(!(is_array($_POST) && isset($_POST['get_cached_data']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_images';
    $row = $wpdb->get_results("SELECT COUNT(*) AS count, SUM(size) AS sum_size FROM $table_name", ARRAY_A);
    echo json_encode($row);

    wp_die();
}


//ajax request to clear image cache
add_action( 'wp_ajax_sirv_clear_cache', 'sirv_clear_cache_callback' );
function sirv_clear_cache_callback(){
    if(!(is_array($_POST) && isset($_POST['clean_cache_type']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    $clean_cache_type = $_POST['clean_cache_type'];

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_images';

    if($clean_cache_type == 'full'){

        require_once 'sirv/aws-s3-helper.php';

        $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());


        $files = array_column($wpdb->get_results("SELECT sirvpath FROM $table_name", ARRAY_N), 0);
        $result = $s3object->deleteMultipleObjects($files);
        
    }

    $delete = $wpdb->query("TRUNCATE TABLE $table_name");

    echo "successful";

    wp_die();
}


//use ajax request to show data from sirv
add_action( 'wp_ajax_sirv_get_aws_object', 'sirv_get_aws_object_callback' );

function sirv_get_aws_object_callback() {

    if (isset($_POST['path'])){
        $sirv_path = $_POST['path'];
        if (empty($sirv_path)) $sirv_path = '/';
    }

    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());
    
    $obj = $s3object->getBucketContents(urlencode($sirv_path));


    //alphanumeric sort object by keys(image name)
    $content = $obj["contents"];
    $dirs = $obj['dirs'];

    usort($content, function($a, $b){return strnatcasecmp($a['Key'],$b['Key']);});
    usort($dirs, function($a, $b){return strnatcasecmp($a['Prefix'],$b['Prefix']);});
    
    $obj['contents'] = $content;
    $obj['dirs'] = $dirs;


    echo json_encode($obj);

    wp_die(); // this is required to terminate immediately and return a proper response
}


//use ajax to upload images on sirv.com
add_action('wp_ajax_sirv_upload_files', 'sirv_upload_files_callback');

function sirv_upload_files_callback(){

    if(!(is_array($_POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    $current_dir = $_POST['current_dir'];

    $current_dir = $current_dir == '/'? '' : $current_dir;


    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());

    for($i=0; $i<count($_FILES); $i++) {

      $filename = $current_dir . basename( $_FILES[$i]["name"]);
      $file = $_FILES[$i]["tmp_name"];

      //echo upload_web_file($bucket, $s3client, $filename, $file);
      $result = $s3object->uploadFile($filename, $file, $web_accessible = true, $headers = null);
      if(!empty($result)) echo json_encode($result);

    }

    wp_die();

}


//use ajax to store gallery shortcode in DB
add_action('wp_ajax_sirv_save_shortcode_in_db', 'sirv_save_shortcode_in_db');

function sirv_save_shortcode_in_db(){

    if(!(is_array($_POST) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $data = $_POST['shortcode_data'];
    $data['images'] = serialize($data['images']);

    $wpdb->insert($table_name, $data);

    echo $wpdb->insert_id;


    wp_die();
}


//use ajax to get data from DB by id
add_action('wp_ajax_sirv_get_row_by_id', 'sirv_get_row_by_id');

function sirv_get_row_by_id(){

    if(!(is_array($_POST) && isset($_POST['row_id']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $id = intval($_POST['row_id']);

    $row =  $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

    $row['images'] = unserialize($row['images']);

    echo json_encode($row);

    //echo json_encode(unserialize($row['images']));


    wp_die();
}


//use ajax to save edited shortcode
add_action('wp_ajax_sirv_update_sc', 'sirv_update_sc');

function sirv_update_sc(){

    if(!(is_array($_POST) && isset($_POST['row_id']) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $id = intval($_POST['row_id']);
    $data = $_POST['shortcode_data'];
    $data['images'] = serialize($data['images']);


    $row =  $wpdb->update($table_name, $data, array( 'ID' => $id ));

    echo $row;


    wp_die();
}


//use ajax to add new folder in sirv
add_action('wp_ajax_sirv_add_folder', 'sirv_add_folder');

function sirv_add_folder(){

    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    $current_dir = $_POST['current_dir'];
    $current_dir = $current_dir == '/'? '' : $current_dir;
    $new_dir = $_POST['new_dir'];

    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());

    $s3object->createFolder($current_dir.$new_dir.'/');

    wp_die();
}


//use ajax to check customer login details
add_action( 'wp_ajax_sirv_check_connection', 'sirv_check_connection', 10, 1 );
function sirv_check_connection() {

    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    require_once 'sirv/aws-s3-helper.php';

    $host = $_POST['host'];
    $bucket = $_POST['bucket'];
    $key = $_POST['key'];
    $secret_key = $_POST['secret_key'];  

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array($key, $secret_key, $bucket, $host)); 

    try{
        $test = $s3object->getBucketContents();
        
        if(empty($test['contents']) && empty($test['dirs'])){
            echo "Connection failed. Please check sirv details.";
        }else{
            echo "Connection: OK";
        }
    }catch(Exception $e){
        echo "Connection failed. Please check sirv details.";
    }


    wp_die();
}


function sirv_test_connection($host,$bucket,$key,$secret_key) {
    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array($key, $secret_key, $bucket));

    try{
        
        $test = $s3object->getBucketContents('/');
        
        if(empty($test['contents'])){
            return false;
        };
        return true;
    }catch(Exception $e){
        return false;
    }
    return false;
}

//use ajax to delete files
add_action( 'wp_ajax_sirv_delete_files', 'sirv_delete_files' );

function sirv_delete_files(){
    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    $filenames = $_POST['filenames'];

    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());


    if($s3object->deleteMultipleObjects($filenames)){
        echo 'Files were deleted';
    }else{
        echo 'Files weren\'t deleted';
    }

    wp_die();
}


//use ajax to check if options is empty or not
add_action( 'wp_ajax_sirv_check_empty_options', 'sirv_check_empty_options' );

function sirv_check_empty_options(){

    require_once 'sirv/options-service.php';

    $host = getValue::getOption('AWS_HOST');
    $bucket = getValue::getOption('AWS_BUCKET');
    $key = getValue::getOption('AWS_KEY');
    $secret_key = getValue::getOption('AWS_SECRET_KEY');

    if(empty($host) || empty($bucket) || empty($key) || empty($secret_key)){
        echo false;
    }else{
        echo true;
    }

    wp_die();
}


//use ajax to get sirv profiles
add_action( 'wp_ajax_sirv_get_profiles', 'sirv_get_profiles' );

function sirv_get_profiles(){
    require_once 'sirv/aws-s3-helper.php';

    $s3object = new MagicToolbox_AmazonS3_Helper(get_params_array());

    $obj = $s3object->getBucketContents('Profiles/');


    echo '<option value=" ">None</option>';
    foreach ($obj["contents"] as $value) {
        $tmp = str_replace('Profiles/', '', $value['Key']);
        if (!empty($tmp)){
            $tmp = basename($tmp, '.profile');
            echo "<option value='{$tmp}'>{$tmp}</option>";
        }
    }
    
    wp_die();
}

//use ajax to send message from sirv plugin
add_action( 'wp_ajax_sirv_send_message', 'sirv_send_message' );

function sirv_send_message(){
    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }


    $priority = $_POST['priority'];
    $summary = $_POST['summary'];
    $text = $_POST['text'];
    $name = $_POST['name'];
    $emailFrom = $_POST['emailFrom'];


    $headers = array(
        'From:' . $name . ' <'. $emailFrom . '>'
    );

    //wp_mail( $to, $subject, $message, $headers, $attachments );
    echo wp_mail('support@sirv.com', $summary .' - '. $priority, $text, $headers);

    wp_die();
}


?>