<?php

/*
Plugin Name: Kopa Forceful Toolkit
Plugin URI: http://kopatheme.com
Description: A specific plugin use in Forceful Theme to generate shortcodes, add specific widgets and allow user rate the posts.
Version: 1.0.0
Author: Kopatheme
Author URI: http://kopatheme.com
License: GPLv3

Kopa Forceful Toolkit plugin, Copyright 2014 Kopatheme.com
Kopa Forceful Toolkit is distributed under the terms of the GNU GPL
*/

function kopa_forceful_toolkit_init(){
    load_plugin_textdomain( 'kopa-forceful-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

add_action('plugin_loaded','kopa_forceful_toolkit_init');

/*
 * Enqueue script and style
 */
 require plugin_dir_path( __FILE__ ) . 'kopa-enqueue.php';
/*
 * Register shortcodes
 */
require plugin_dir_path( __FILE__ ) . 'kopa-shortcodes.php';

/*
 * Register Widget
 */

require plugin_dir_path( __FILE__ ) . 'kopa-widgets.php';

/*
 * Register Rating
 */

require plugin_dir_path( __FILE__ ) . 'kopa-post-rating.php';