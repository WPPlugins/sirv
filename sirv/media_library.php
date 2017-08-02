<?php

    function check_empty_options(){
        $host = get_option('AWS_HOST');
        $bucket = get_option('AWS_BUCKET');
        $key = get_option('AWS_KEY');
        $secret_key = get_option('AWS_SECRET_KEY');

        if(empty($host) || empty($bucket) || empty($key) || empty($secret_key)){
            
            return false;
        }else return true;

    }

   
    if(check_empty_options()){
        wp_enqueue_style('sirv_style', plugins_url('css/wp-sirv.css', __FILE__));
        //wp_enqueue_style('sirv_media_library_style', plugins_url('css/wp-sirv-media-library.css', __FILE__));
        wp_enqueue_script( 'sirv_logic', plugins_url('js/wp-sirv.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), '1.0.0');
        wp_localize_script( 'sirv_logic', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => plugins_url('assets', __FILE__) ) );
        wp_enqueue_script( 'sirv_media_library_logic', plugins_url('js/wp-sirv-media-library.js', __FILE__), array( 'jquery'), '1.0.0');
        wp_enqueue_script( 'sirv_logic-md5', plugins_url('js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');


    include('templates/media_library.html');

    }else{
        wp_enqueue_style('sirv_style', plugins_url('css/wp-sirv.css', __FILE__));
        //echo '<div class="sirv-warning"><a href="admin.php?page=/sirv/sirv/options.php">Enter your Sirv S3 settings</a> to view your images on Sirv.</div>';
        include('templates/login_error.html');
    }
?>