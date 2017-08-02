<?php
function sirv_gallery($atts){

    wp_enqueue_style('sirv_gallery', plugins_url('/css/wp-sirv-gallery.css', __FILE__));
    wp_enqueue_script( 'sirv_gallery-viewer', plugins_url('/js/wp-sirv-gallery.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script( 'sirv', '//scripts.sirv.com/sirv.js', array(), '1.0.0');

    extract(shortcode_atts( array('id' => ''), $atts));

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $row =  $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

    if (empty($row)) return;

    
    require_once 'sirv-gallery.php';

    $images_data = unserialize($row['images']);

    $images = array();
    $captions = array();

    foreach ($images_data as $image) {
        array_push($images, $image['url']);
        array_push($captions, stripslashes($image['caption']));
    }

    $gallery = new Sirv_Gallery(
        array(
            'width'=> (int) $row['width'],
            'height'=> 'auto',
            'link_image'=> filter_var($row['link_image'], FILTER_VALIDATE_BOOLEAN),
            'profile'=> $row['profile'],
            'show_caption'=> filter_var($row['show_caption'], FILTER_VALIDATE_BOOLEAN),
            'is_gallery'=> filter_var($row['use_as_gallery'], FILTER_VALIDATE_BOOLEAN),
            'apply_zoom'=> filter_var($row['use_sirv_zoom'], FILTER_VALIDATE_BOOLEAN),
            'thumbnails_height' => $row['thumbs_height'],
            'gallery_styles' => $row['gallery_styles'],
            'gallery_align' => $row['align']
        ), 
        $images, $captions);


return $gallery->render();

}

add_shortcode( 'sirv-gallery', 'sirv_gallery' );
?>