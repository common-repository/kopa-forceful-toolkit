<?php

/*
 * enqueue script and style
 */
add_action('wp_enqueue_scripts', 'kopa_forceful_plugin_enqueue_scripts');

function kopa_forceful_plugin_enqueue_scripts() {
    if (is_single()) {
        wp_enqueue_script('forceful-plugin-rating-script', plugins_url('/js/kopa-user-rating.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_style('forceful-plugin-rating-style',plugins_url('/css/post-rating.css', __FILE__));
    }
    if(!is_admin()){
		wp_enqueue_style('forceful-plugin-weather-style',plugins_url('/css/awesome-weather.css', __FILE__));
		wp_enqueue_style('forceful-plugin-shortcode-style',plugins_url('/css/shortcode.css', __FILE__));
		wp_enqueue_script('forceful-plugin-shortcode-script', plugins_url('/js/shortcodes.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('jquery', 'kopa_front_variable', forceful_plugin_localize_script());
    }
}
add_action('admin_enqueue_scripts', 'kopa_forceful_plugin_admin_scripts', 10, 1);

function kopa_forceful_plugin_admin_scripts($hook) {
    if ('post-new.php' == $hook || 'post.php' == $hook) {
        wp_enqueue_script('kopa-post-rating-script', plugins_url('/js/post-rating.js', __FILE__), array('jquery'), NULL, TRUE);
    }
}

function kopa_plugin_get_domain(){
return 'forceful_toolkit';
}