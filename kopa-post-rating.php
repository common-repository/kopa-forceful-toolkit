<?php
/**
 * Editor Post Rating Metabox
 * @subpackage Forceful
 * @since Forceful 1.0
 */


/*
 * localization 
 */

function forceful_plugin_localize_script() {
    global $post;
    $kopa_variable = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'post_id' => $post->ID
    );
    return $kopa_variable;
}

/*
 * post rating metabox
 */

add_action('add_meta_boxes', 'kopa_post_rating_meta_box_add');

function kopa_post_rating_meta_box_add() {
    add_meta_box('kopa-post-rating-edit', __('Editor Rating', kopa_plugin_get_domain()), 'kopa_meta_box_post_rating_cb', 'post', 'normal', 'high');
}

function kopa_meta_box_post_rating_cb($post) {
    $kopa_editor_post_rating = get_post_meta($post->ID, 'kopa_editor_post_rating_' . kopa_plugin_get_domain(), true);

    wp_nonce_field('kopa_post_rating_meta_box_nonce', 'kopa_post_rating_meta_box_nonce');
    ?>

    <div id="kopa-rating-wrapper">
        <!-- Dynamic content area for post rating fields -->
    <?php
    if ($kopa_editor_post_rating) {
        foreach ($kopa_editor_post_rating as $index => $rating) {
            ?>
                <p id="kopa-rating-field-<?php echo $index; ?>" class="kopa-field-wrapper" data-id="<?php echo $index; ?>">
                    <label for=""><?php _e('Rating Name:', kopa_plugin_get_domain()); ?> </label>
                    <input name="kopa_editor_post_rating[<?php echo $index; ?>][name]" type="text" value="<?php echo $rating['name']; ?>">
                    <select name="kopa_editor_post_rating[<?php echo $index; ?>][value]">
                            <?php
                            $kopa_rating_options = array(1, 2, 3, 4, 5);
                            foreach ($kopa_rating_options as $value) {
                                ?>

                            <option value="<?php echo $value; ?>" <?php selected($value, $rating['value']); ?>>
                <?php echo $value . __(' Star(s)', kopa_plugin_get_domain()); ?>
                            </option>

                <?php } ?>
                    </select>
                    <button class="button kopa-remove-rating"><?php _e('Remove', kopa_plugin_get_domain()); ?></button>
                </p> <!-- .kopa-field-wrapper -->
        <?php
        } // endforeach
    } // endif 
    ?>
    </div> <!-- #kopa-rating-wrapper -->

    <p class="meta-options">
        <button id="kopa-rating-add" class="button button-primary"><?php _e('Add', kopa_plugin_get_domain()); ?></button>
        <button id="kopa-rating-remove-all" class="button"><?php _e('Remove All', kopa_plugin_get_domain()); ?></button>
    </p>

    <?php
}

add_action('save_post', 'kopa_save_post_rating_data');

function kopa_save_post_rating_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['kopa_post_rating_meta_box_nonce']) || !wp_verify_nonce($_POST['kopa_post_rating_meta_box_nonce'], 'kopa_post_rating_meta_box_nonce')) {
        return;
    }
    if (isset($_POST['kopa_editor_post_rating'])) {
        $kopa_editor_post_rating = $_POST['kopa_editor_post_rating'];
        foreach ($kopa_editor_post_rating as $index => $rating) {
            if (empty($rating['name'])) {
                unset($kopa_editor_post_rating[$index]);
            }
        }

        if (empty($kopa_editor_post_rating)) {
            delete_post_meta($post_id, 'kopa_editor_post_rating_' . kopa_plugin_get_domain());
            delete_post_meta($post_id, 'kopa_editor_total_rating_' . kopa_plugin_get_domain());
            delete_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain());
            delete_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain());
            delete_post_meta($post_id, 'kopa_user_total_all_rating_' . kopa_plugin_get_domain());
        } else {
            $kopa_editor_total_rating = 0;
            foreach ($kopa_editor_post_rating as $rating) {
                $kopa_editor_total_rating += $rating['value'];
            }
            $kopa_editor_total_rating = $kopa_editor_total_rating / count($kopa_editor_post_rating);

            // get user post rating and unset rating indexes that are not in editor rating indexes
            $kopa_user_post_rating = get_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain(), true);
            $kopa_user_total_rating = get_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain(), true);

            if (!empty($kopa_user_post_rating) && !empty($kopa_user_total_rating)) {
                foreach ($kopa_user_post_rating as $rating_index => $rating) {
                    if (!isset($kopa_editor_post_rating[$rating_index])) {
                        unset($kopa_user_post_rating[$rating_index]);
                    }
                }
                foreach ($kopa_user_total_rating as $rating_index => $value) {
                    if (!isset($kopa_user_post_rating[$rating_index])) {
                        unset($kopa_user_total_rating[$rating_index]);
                    }
                }
            } // endif
            // recalculate total all rating indexes of all users
            $total_user_all_rating = 0;
            if (!empty($kopa_user_total_rating)) {
                foreach ($kopa_user_total_rating as $value) {
                    $total_user_all_rating += $value;
                }
                $total_user_all_rating = $total_user_all_rating / count($kopa_user_total_rating);
            }

            // calculate editor and users ratings
            $kopa_editor_user_total_all_rating = $kopa_editor_total_rating;
            if (!empty($total_user_all_rating)) {
                $kopa_editor_user_total_all_rating = ( $kopa_editor_user_total_all_rating + $total_user_all_rating ) / 2;
            }

            update_post_meta($post_id, 'kopa_editor_post_rating_' . kopa_plugin_get_domain(), $kopa_editor_post_rating);
            update_post_meta($post_id, 'kopa_editor_total_rating_' . kopa_plugin_get_domain(), $kopa_editor_total_rating);

            if (!empty($kopa_user_post_rating) && !empty($kopa_user_total_rating) && !empty($total_user_all_rating)) {
                update_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain(), $kopa_user_post_rating);
                update_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain(), $kopa_user_total_rating);
                update_post_meta($post_id, 'kopa_user_total_all_rating_' . kopa_plugin_get_domain(), $total_user_all_rating);
            }
            // delete if empty
            // case 1: no users rated (in the beginning of time)
            // case 2: rated but editor(admin) deletes 1 or more rating indexes -> make it empty
            else {
                delete_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain());
                delete_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain());
                delete_post_meta($post_id, 'kopa_user_total_all_rating_' . kopa_plugin_get_domain());
            }

            update_post_meta($post_id, 'kopa_editor_user_total_all_rating_' . kopa_plugin_get_domain(), $kopa_editor_user_total_all_rating);
        }
    } else {
        delete_post_meta($post_id, 'kopa_editor_post_rating_' . kopa_plugin_get_domain());
        delete_post_meta($post_id, 'kopa_editor_total_rating_' . kopa_plugin_get_domain());
        delete_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain());
        delete_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain());
        delete_post_meta($post_id, 'kopa_user_total_all_rating_' . kopa_plugin_get_domain());
    }
}

/*
 * load ajax post rating
 */

if (!function_exists('kopa_ajax_set_user_rating')) {

    add_action('wp_ajax_kopa_set_user_rating', 'kopa_ajax_set_user_rating');
    add_action('wp_ajax_nopriv_kopa_set_user_rating', 'kopa_ajax_set_user_rating');

    function kopa_ajax_set_user_rating() {
        try {
            if (!wp_verify_nonce($_POST['wpnonce'], 'kopa_set_user_rating')) {
                throw new Exception(__('Sorry an error has occurred.', kopa_plugin_get_domain()));
                exit();
            }

            $post_id = $_POST['post_id'];
            $rating_index = $_POST['ratingIndex'];
            $rating_value = $_POST['ratingValue'];

            $kopa_user_post_rating = get_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain(), true);
            $kopa_user_total_rating = get_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain(), true);

            // save user rating for each rating index
            if (empty($kopa_user_post_rating)) {
                $kopa_user_post_rating = array();
            }

            $user_data = array(
                'rating_value' => $rating_value,
                'client_ip' => $_SERVER['REMOTE_ADDR']
            );
            // prevents duplicate client ip
            if (isset($kopa_user_post_rating[$rating_index])) {
                $current_rating_index_arr = $kopa_user_post_rating[$rating_index];
                foreach ($current_rating_index_arr as $data) {
                    if ($user_data['client_ip'] == $data['client_ip']) {
                        throw new Exception(__('You cannot vote twice.', kopa_plugin_get_domain()));
                        die();
                    }
                }
                $kopa_user_post_rating[$rating_index][] = $user_data;
            } // endif
            else {
                $kopa_user_post_rating[$rating_index][] = $user_data;
            }

            // save user total rating for each rating index 
            // and all rating indexes
            if (empty($kopa_user_total_rating)) {
                $kopa_user_total_rating = array();
            }


            $current_rating_index_arr = $kopa_user_post_rating[$rating_index];
            $total_current_rating_index_value = 0;
            foreach ($current_rating_index_arr as $rating) {
                $total_current_rating_index_value = $total_current_rating_index_value + $rating['rating_value'];
            }

            // calculate total rating for current rating index
            $total_current_rating_index_value = $total_current_rating_index_value / count($current_rating_index_arr);
            $kopa_user_total_rating[$rating_index] = $total_current_rating_index_value;

            // calculate total rating for all rating indexes of all users
            $total_all_rating_value = 0;
            foreach ($kopa_user_total_rating as $index => $value) {
                $total_all_rating_value = $total_all_rating_value + $value;
            }

            $total_all_rating_value = $total_all_rating_value / count($kopa_user_total_rating);

            // calculate editor and users ratings
            $kopa_editor_user_total_all_rating = 0;
            $kopa_editor_total_rating = get_post_meta($post_id, 'kopa_editor_total_rating_' . kopa_plugin_get_domain(), true);
            $kopa_editor_user_total_all_rating = ( $kopa_editor_total_rating + $total_all_rating_value ) / 2;


            // update all post meta data
            update_post_meta($post_id, 'kopa_user_post_rating_' . kopa_plugin_get_domain(), $kopa_user_post_rating);
            update_post_meta($post_id, 'kopa_user_total_rating_' . kopa_plugin_get_domain(), $kopa_user_total_rating);
            update_post_meta($post_id, 'kopa_user_total_all_rating_' . kopa_plugin_get_domain(), $total_all_rating_value);
            update_post_meta($post_id, 'kopa_editor_user_total_all_rating_' . kopa_plugin_get_domain(), $kopa_editor_user_total_all_rating);

            // send data back to client side
            $responses_data = array(
                'total_current_rating' => $total_current_rating_index_value,
                'total_all_rating' => $total_all_rating_value,
                'total_current_rating_title' => sprintf(__('Rated %.2f out of 5', kopa_plugin_get_domain()), $total_current_rating_index_value),
                'total_all_rating_title' => sprintf(__('Rated %.2f out of 5', kopa_plugin_get_domain()), $total_all_rating_value),
                'status' => 'success'
            );

            echo json_encode($responses_data);
            die();
        } catch (Exception $e) {
            $error_responses_data = array(
                'status' => 'error',
                'error_message' => $e->getMessage()
            );
            echo json_encode($error_responses_data);
            die();
        }
    }

}

/*
 * Show rating on single post
 */

function kopa_show_rating($result) {

    $kopa_editor_post_rating = get_post_meta(get_the_ID(), 'kopa_editor_post_rating_' . kopa_plugin_get_domain(), true);
    $kopa_editor_total_rating = get_post_meta(get_the_ID(), 'kopa_editor_total_rating_' . kopa_plugin_get_domain(), true);
    $kopa_user_post_rating = get_post_meta(get_the_ID(), 'kopa_user_post_rating_' . kopa_plugin_get_domain(), true);
    $kopa_user_total_rating = get_post_meta(get_the_ID(), 'kopa_user_total_rating_' . kopa_plugin_get_domain(), true);
    $kopa_user_total_all_rating = get_post_meta(get_the_ID(), 'kopa_user_total_all_rating_' . kopa_plugin_get_domain(), true);
    $html = '';
    if (!empty($kopa_editor_post_rating)) {
        $html.='<div class="row-fluid kopa-rating-container">
        <div class="span6">
            <ul class="kopa-rating-box kopa-editor-rating-box">
                <li>' . __('Editor Rating', kopa_plugin_get_domain()) . '</li>';

        foreach ($kopa_editor_post_rating as $rating) {

            $html.=' <li class="clearfix">
                        <span>' . $rating['name'] . '</span>';

            $html.='<ul class="kopa-rating clearfix" title="' . __('Rated'. $rating['value'].' out of 5', kopa_plugin_get_domain())  . '">';

            for ($i = 0; $i < $rating['value']; $i++) {
                $html.='<li>' . KopaIcon::getIcon('star', 'span') . '</li>';
            } // endfor 

            for ($i = 0; $i < 5 - $rating['value']; $i++) {
                $html.='<li>' . KopaIcon::getIcon('star2', 'span') . '</li>';
            } // endfor 

            $html.='</ul>';

            $html.='</li>';
        } // endforeach 

        $html.=' <li class="total-score clearfix">
                    <span>' . __('Total score', kopa_plugin_get_domain()) . '</span>';
        $html.='<ul class="kopa-rating clearfix" title="' . __('Rated '.$kopa_editor_total_rating.' out of 5', kopa_plugin_get_domain()) . '">';
        $kopa_editor_total_rating = round($kopa_editor_total_rating);
        for ($i = 0; $i < $kopa_editor_total_rating; $i++) {
            $html.=' <li>' . KopaIcon::getIcon('star', 'span') . '</li>';
        } // endfor 

        for ($i = 0; $i < 5 - $kopa_editor_total_rating; $i++) {
            $html.='<li>' . KopaIcon::getIcon('star2', 'span') . '</li>';
        } // endfor 
        $html.=' </ul>
                </li>
            </ul>
        </div>';
        $html.='<div class="span6">
            <ul class="kopa-rating-box kopa-user-rating-box">
                <li>' . __('User Rating', kopa_plugin_get_domain()) . '</li>';

        foreach ($kopa_editor_post_rating as $rating_index => $rating) {
            if (isset($kopa_user_total_rating[$rating_index])) {
                $current_total_rating = round($kopa_user_total_rating[$rating_index]);
            } else {
                $current_total_rating = 0;
            }

            $html.='<li class="clearfix">
                    <span>' . $rating['name'] . '</span>';
            $html.='<ul class="kopa-user-rating kopa-rating clearfix" data-current-rating="' . $current_total_rating . '" data-rating-index="' . $rating_index . '">';

            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $current_total_rating) {
                    $active = 'fa-star';
                } else {
                    $active = 'fa-star-o';
                }
                $html.='<li><span class="fa ' . $active . '" href="javascript:void(0)" ></span></li>';
            }

            $html.=' </ul>
                </li>';
        } // endforeach 
        $html.='<li class="total-score clearfix">
                    <span>' . __('Total score', kopa_plugin_get_domain()) . '</span>';
        if (empty($kopa_user_total_all_rating)) {
            $kopa_user_total_all_rating = 0;
        }

        if (0 != $kopa_user_total_all_rating) {
            $all_rating_title = sprintf(__('Rated %.2f out of 5', kopa_plugin_get_domain()), $kopa_user_total_all_rating);
        } else {
            $all_rating_title = '';
        }

        $kopa_user_total_all_rating = round($kopa_user_total_all_rating);

        $html .= '<ul id="kopa-user-total-rating" class="kopa-rating clearfix" title="' . $all_rating_title . '">';

        for ($i = 0; $i < $kopa_user_total_all_rating; $i++) {
            $html.='<li>' . KopaIcon::getIcon('star', 'span') . '</li>';
        } // endfor 

        for ($i = 0; $i < 5 - $kopa_user_total_all_rating; $i++) {
            $html.='<li>' . KopaIcon::getIcon('star2', 'span') . '</li>';
        }
        $html.='</ul>
                </li>
            </ul>
        </div>
    </div>';
    } // endif
    return $result . $html;
}

add_filter('the_content', 'kopa_show_rating');


add_filter('kopa_icon_get_icon', 'forceful_plugin_kopa_icon_get_icon', 10, 3);

function forceful_plugin_kopa_icon_get_icon($html, $icon_class, $icon_tag) {
    $classes = '';
    switch ($icon_class) {
        case 'star':
            $classes = 'fa fa-star';
            break;
        case 'star2':
            $classes = 'fa fa-star-o';
            break;
    }
    return KopaIcon::createHtml($classes, $icon_tag);
}
