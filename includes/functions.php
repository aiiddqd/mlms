<?php
/**
 * Get Translation Network admin
 *
 * Retrieves the user data of the person who setup the translation network
 *
 * @since 0.1.0
 * @param string Translation Network ID
 * @return WP_User|bool WP_User object on success, false on failure.
 */
function get_trans_network_admin($trans_id) {
    global $wpdb;
	$sql="SELECT user_id, meta_value FROM {$wpdb->base_prefix}usermeta WHERE meta_key = 'zwt_installed_transnetwork'";
    $trans_owner_array = $wpdb->get_results($sql, ARRAY_A);
    foreach ($trans_owner_array as $trans_owner) {
        if ($trans_owner['meta_value'] == $trans_id) {
            $admin_id = $trans_owner['user_id'];
            break;
        }
    }
    return $user_details = get_userdata($admin_id);
}

/**
 * No Script Notice
 *
 * Used to display an error message when functions that require a javascript enabled browser find javascript disabled
 *
 * @since 0.1.0
 * @return void
 */
 
function zwt_noscript_notice() {
    ?>
    <noscript>
    <div class="error">
        <?php echo __('This Zanto admin screen requires JavaScript in order to display properly. JavaScript is currently off in your browser!', 'Zanto') ?>
    </div>
    </noscript>
    <?php
}

/**
 * Check Connectivity
 *
 * Checks if user is connected to the internet by trying to access google
 * @since 0.1.0
 * @param string site to ping
 * @return bool true on success, false on failure.
 */
function check_internet_connection($sCheckHost = 'www.google.com') {
    $connectivity = (bool) @fsockopen($sCheckHost, 80, $iErrno, $sErrStr, 5);
	return apply_filters('check_internet_connection',$connectivity);
}

/**
 * Add metadata to an object in a specified blog
 *
 * Adds meta data to any WordPress object that has metadata storage capability for a specified blog in the multisite 
 *
 * @since 0.1.0
 * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
 * @param int $object_id ID of the object metadata is for
 * @param string $meta_key Metadata key
 * @param mixed $meta_value Metadata value. Must be serializable if non-scalar.
 * @param int $blog_id the id of blog the object belongs to
 * @param bool $unique Optional, default is false. Whether the specified metadata key should be
 * 		unique for the object. If true, and the object already has a value for the specified
 * 		metadata key, no change will be made
 * @return int|bool The meta ID on success, false on failure.
 */
function zwt_add_metadata($meta_type, $object_id, $meta_key, $meta_value, $blog_id, $unique = false) {
    if (!$meta_type || !$meta_key)
        return false;

    if (!$object_id = absint($object_id))
        return false;

    if (!$table = _zwt_get_meta_table($meta_type, $blog_id))
        return false;

    global $wpdb;

    $column = esc_sql($meta_type . '_id');

// expected_slashed ($meta_key)
    $meta_key = stripslashes($meta_key);
    $meta_value = stripslashes_deep($meta_value);
    $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type);

    $check = apply_filters("zwt_add_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $unique);
    if (null !== $check)
        return $check;

    if ($unique && $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id)))
        return false;

    $_meta_value = $meta_value;
    $meta_value = maybe_serialize($meta_value);

    do_action("zwt_add_{$meta_type}_meta", $object_id, $meta_key, $_meta_value);

    $result = $wpdb->insert($table, array(
        $column => $object_id,
        'meta_key' => $meta_key,
        'meta_value' => $meta_value
            ));


    if (!$result)
        return false;

    $mid = (int) $wpdb->insert_id;

    wp_cache_delete($object_id, $meta_type . '_meta');

    do_action("zwt_added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value);

    return $mid;
}
/**
 * Updates site links in the global cache
 *
 * Commonly used site links are stored in the global catche to eliminate use of switch_to_blog resource intensive function in the front end
 * @since 0.3.2
 * @param int $trans_id
 * @return void
 */
function zwt_update_site_links($trans_id) {
    if (did_action('admin_init') !== 1)
        return;
    global $site_id, $blog_id;
    $update_flag = 0;
    $home_url = zwt_home_url();
    $zwt_global_cache = get_metadata('site', $site_id, 'zwt_' . $trans_id . '_site_cache', true);
    if (isset($zwt_global_cache[$blog_id])) {
        if ($zwt_global_cache[$blog_id]['site_url'] != $home_url) {
            $zwt_global_cache[$blog_id]['site_url'] = $home_url;
            $update_flag = 1;
        }
        if ($zwt_global_cache[$blog_id]['admin_url'] != admin_url()) {
            $zwt_global_cache[$blog_id]['admin_url'] = admin_url();
            $update_flag = 1;
        }
        if (apply_filters('zwt_update_site_links',$update_flag, $trans_id, $zwt_global_cache)) {
            $zwt_global_cache[$blog_id]['site_url'] = $home_url;
            $zwt_global_cache[$blog_id]['admin_url'] = admin_url();
            update_metadata('site', $site_id, 'zwt_' . $trans_id . '_site_cache', $zwt_global_cache);
//            add_notice('blog meta options created and updated in global catche');
        }
    } else { //first time creation of global cache for this blog
        if (!is_array($zwt_global_cache)) {
            $zwt_global_cache = [];
        }

        $zwt_global_cache[$blog_id]['site_url'] = $home_url;
        $zwt_global_cache[$blog_id]['admin_url'] = admin_url();
        $zwt_global_cache[$blog_id]['lang_url_format'] = 0;
        update_metadata('site', $site_id, 'zwt_' . $trans_id . '_site_cache', $zwt_global_cache);
    }
	do_action('zwt_global_cache_update', $zwt_global_cache, $update_flag, $trans_id);
}

/**
 * Retrieve the home url for the current site.
 *
 * Returns the 'home' option with the appropriate protocol, 
 * @since 0.2.0
 *
 * @uses WordPress home_url() 
 *
 * @return string Home url.
*/
function zwt_home_url() {
    if (function_exists('domain_mapping_siteurl')) {// support for domain mapping
        return domain_mapping_siteurl(false);
    } else {
        return apply_filters('zwt_home_url',home_url());
    }
}

/**
 * Add commonly used site links and link formats (site_url, admin_url, lang_url_format) to the global cache
 *
 * @since 0.1.0
 * @param int $blog_id , $trans_id, $lang_url_format
 *
 * @return void
*/
function zwt_add_links($blog_id, $trans_id, $lang_url_format) {
    if (!absint($blog_id) || !absint($trans_id))
        return;
    global $site_id;

    $home_url = zwt_home_url();
    $zwt_global_cache = get_metadata('site', $site_id, 'zwt_' . $trans_id . '_site_cache', true);
    $zwt_global_cache[$blog_id]['site_url'] = $home_url;
    $zwt_global_cache[$blog_id]['admin_url'] = admin_url();
    $zwt_global_cache[$blog_id]['lang_url_format'] = $lang_url_format;
    update_metadata('site', $site_id, 'zwt_' . $trans_id . '_site_cache', $zwt_global_cache);
}

/**
 * Update metadata for the specified object in a specified blog If no value already exists for the specified object
 * ID and metadata key, the metadata will be added.
 *
 * @since 0.1.0
 * @uses WordPress update_metadata function
 *
 * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
 * @param int $object_id ID of the object metadata is for
 * @param string $meta_key Metadata key
 * @param mixed $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed $prev_value Optional. If specified, only update existing metadata entries with
 * 		the specified value. Otherwise, update all entries.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
 
function zwt_update_metadata($meta_type, $object_id, $meta_key, $meta_value, $blog_id, $prev_value = '') {
    if (!$meta_type || !$meta_key)
        return false;

    if (!$object_id = absint($object_id))
        return false;

    if (!$table = _zwt_get_meta_table($meta_type, $blog_id))
        return false;

    global $wpdb;

    $column = esc_sql($meta_type . '_id');
    $id_column = 'user' == $meta_type ? 'umeta_id' : 'meta_id';

// expected_slashed ($meta_key)
    $meta_key = stripslashes($meta_key);
    $passed_value = $meta_value;
    $meta_value = stripslashes_deep($meta_value);
    $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type);

    $check = apply_filters("zwt_update_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value);
    if (null !== $check)
        return (bool) $check;


// Compare existing value to new value if no prev value given and the key exists only once.
    if (empty($prev_value)) {
        $old_value = get_metadata($meta_type, $object_id, $meta_key);
        if (count($old_value) == 1) {
            if ($old_value[0] === $meta_value)
                return false;
        }
    }


    if (!$meta_id = $wpdb->get_var($wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id)))
        return zwt_add_metadata($meta_type, $object_id, $meta_key, $passed_value, $blog_id);

    $_meta_value = $meta_value;
    $meta_value = maybe_serialize($meta_value);

    $data = compact('meta_value');
    $where = array($column => $object_id, 'meta_key' => $meta_key);

    if (!empty($prev_value)) {
        $prev_value = maybe_serialize($prev_value);
        $where['meta_value'] = $prev_value;
    }

    do_action("zwt_update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

    if ('post' == $meta_type)
        do_action('zwt_update_postmeta', $meta_id, $object_id, $meta_key, $meta_value);

    $wpdb->update($table, $data, $where);

    wp_cache_delete($object_id, $meta_type . '_meta');

    do_action("zwt_updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

    if ('post' == $meta_type)
        do_action('zwt_updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value);

    return true;
}
/**
 * Get a WordPress table name for a specified blog in the multisite
 *
 * @since 0.1.0
 * @param string $type the wordpress table name without the prefix for example options, terms, postmeta
 * @return string table name with the right prefix
 */
function _zwt_get_meta_table($type, $blog_id) {
    global $wpdb;
    $blog_prefix = $wpdb->get_blog_prefix($blog_id);
    $table_name = $blog_prefix . $type . 'meta';

    if (empty($table_name))
        return false;

    return $table_name;
}
/**
 * Creates a select field value with posts from the specified blog in the attr array
 *
 * @uses new WP_Query object
 * @since 0.1.0
 * @param  $attr an array containing the blog ID to retrieve posts from and query arguments to use 
 * @return a string with the pre constructed select element
 */
function zwt_get_post_select_options($attr) {
    global $blog_id, $post;
    $c_blog = $blog_id;
    $defaults = array(
        'c_blog' => $c_blog, // blog id to check in
        'post_type' => '',
        'order' => 'ASC',
        'orderby' => 'title',
        'posts_per_page' => -1,
        'selected' => '', // the id of post marked as checked
        'include' => '',
        'exclude' => '',
        'empty_option' => true     // if an empty option should start the select
    );

    $p = $post;
    $options = shortcode_atts($defaults, $attr);
    $options['posts_per_page'] = 1000;
    switch_to_blog($options['c_blog']);
    $loop = new WP_Query($options);
    if ($options['empty_option'])
        $output = "<option value=''></option>";

    if ($loop->have_posts()) {
        while ($loop->have_posts()) {
            $loop->the_post();
			
			if(get_post()->post_status == 'translation'){
						continue;
					}
            $trans_class = $disabled = $good_class = '';     
                    //if( class_exists( 'Ztm_Plugin_Base' ) ){
					    
					    $translation_meta = get_post_meta(get_post()->ID, '_translation_meta_', true);
						if(isset($translation_meta[$c_blog]['status']) && $translation_meta[$c_blog]['status'] !== 'translated'){
						    $good_class = ' good'; 
                            $disabled = 'disabled="true"';							
						}
					//}
					
            $translations = get_post_meta(get_the_ID(), ZWT_Base::PREFIX . 'post_network', true);
            
            if (is_array($translations))
                foreach ($translations as $translation) {
                    if (isset($translation['blog_id']) && $translation['blog_id'] == $c_blog)
                        $trans_class = 'translated'.$good_class;
                }

            $output .= the_title('<option '.$disabled.' class="' . $trans_class . '" value="' . get_the_ID() . '"' . selected($options['selected'], get_the_ID()) . ' > ', ' </option>', false);
        }
    } else {
        $output = '<option value="">' . __('No Posts Found', 'Zanto') . '</option>';
    }
    restore_current_blog();
    $post = $p;
    return apply_filters('zwt_get_post_select_options', $output, $c_blog);
}
/**
 * Adds post metadata containing a copy of post translation data 
 * to all the posts that are transaltions of each other in the translation data.
 *
 * array(
 *       array('blog_id' => 1, 'post_id' => 77),
 *       array('blog_id' => 2, 'post_id' => 84);
 * )
 * the example above goes to show the format of the translation data, post with ID 77 from blog with ID 1 is a translation 
 * of post with ID 84 from blog with ID 2
 * 
 * @uses zwt_update_metadata
 * @since 0.1.0
 * @param  $post_transnetwork an array containing arrays of post ID's togather with their Blog ID's of posts that are translations of each other
 * @return void
 */
function zwt_broadcast_post_network($post_transnetwork) {
    foreach ($post_transnetwork as $index => $pnet_details) {
        if (isset($pnet_details['post_id']))
            zwt_update_metadata('post', $pnet_details['post_id'], ZWT_Base::PREFIX . 'post_network', $post_transnetwork, $pnet_details['blog_id']);
    }
	do_action('zwt_broadcast_post_network',$post_transnetwork);
}

/**
 * Removes an individual post from the post translation data
 * Removes a post from the post translation network data and updates the new post data 
 * to all remaining posts in the post translation network
 * @uses zwt_broadcast_post_network function to update the new transaltions 
 * @since 0.1.0
 * @param  int $blog_id blog id of the post to be removed
 * @param array $post_transnetwork an array containing arrays of post ID's togather with their Blog ID's of posts that are translations of each other
 * @return a string with the pre constructed select element
 */
function zwt_detach_post($blog_id, $post_transnetwork) {
    if (!is_numeric($blog_id) || !is_array($post_transnetwork)) {
        add_notice('Wrong data received by zwt_detach_post() ', 'error');
        return;
    }
	do_action('zwt_pre_detach_post',$post_transnetwork);
	
    foreach ($post_transnetwork as $index => $pnet_details) {
        if ($pnet_details['blog_id'] == $blog_id) {
            unset($post_transnetwork[$index]);
            if (isset($pnet_details['post_id']))
                zwt_update_metadata('post', $pnet_details['post_id'], ZWT_Base::PREFIX . 'post_network', '', $blog_id);
        }
    }
    if (count($post_transnetwork) < 2) {
        foreach ($post_transnetwork as $index => $pnet_details)
            zwt_update_metadata('post', $pnet_details['post_id'], ZWT_Base::PREFIX . 'post_network', '', $pnet_details['blog_id']);
    } else {
        zwt_broadcast_post_network($post_transnetwork);
    }
	do_action('zwt_detach_post',$post_transnetwork);
}

/**
 * Gets required data stored in the global cache
 *
 * data is stored here to prevent use of switch_to_blog resource intensive function in the front end to fetch them
 * 
 * @since 0.2.0
 * @param string $req_info the data 
 * @param int  $blog_id the blog ID for the data required
 * @return required data
 */

function zwt_get_global_data($req_info, $blog_id) {
    global $site_id, $zwt_site_obj;
    $transnet_id = $zwt_site_obj->modules['trans_network']->transnet_id;
    $blog_parameters = get_metadata('site', $site_id, 'zwt_' . $transnet_id . '_site_cache', true);
    if (!isset($blog_parameters[$blog_id]))
        return;
    switch ($req_info) {
        case 'site_url':
            $info = $blog_parameters[$blog_id]['site_url'];
            if (!$info || $info == '') {
                switch_to_blog($blog_id);
                $info = get_option('siteurl');
                zwt_update_site_links($transnet_id);
                restore_current_blog();
            }
            break;
        case 'admin_url':
            $info = $blog_parameters[$blog_id]['admin_url'];
            if (!$info || $info == '') {
                switch_to_blog($blog_id);
                $info = admin_url();
                zwt_update_site_links($transnet_id);
                restore_current_blog();
            }
            break;
        case 'lang_url_format':
            $info = $blog_parameters[$blog_id]['lang_url_format'];
            break;
        default:
            return false;
    }
    return $info;
}
/**
 * Gets the translated url
 *
 * Used to get the short-link url to the translated object of the provided ID
 * 
 * @since 0.1.0
 * @param string $obj_type type of object for which the url of translation is required
 * @param int obj_blog the blog ID where the translation should come from
 * @param int $obj_id the object ID for which the url of traslation is required if its not provided, the server request uri will be returned
 * @return string url short-link of translation
 */
function zwt_get_trans_url($obj_type, $obj_blog, $obj_id=null) {
    global $site_id, $wpdb, $blog_id;
	$target_site = zwt_get_global_data('site_url', $obj_blog);
	$trans_link = '#';
	/**
	 * Filter the value of an existing option before it is retrieved.
	 *
	 *
	 * Passing a truthy value to the filter will short-circuit retrieving
	 * the option value, returning the passed value instead.
	 *
	 * @since 0.3.2
	 *
	 * @param bool|string $pre_url Value to return instead of the short-link value.
	 *                               Default false to skip it.
	 */
	$pre = apply_filters( 'zwt_pre_trans_url', false, $obj_type, $obj_blog, $obj_id );
	
	if ( false !== $pre )
		return $pre;
		
	if ($obj_blog == $blog_id) {
        return $_SERVER['REQUEST_URI'];
    }
		
    if (isset($obj_type))
        switch ($obj_type) {
            case 'post':
                $trans_link = $target_site . '?p=' . $obj_id;
                break;
            case 'category':
                $trans_link = $target_site . '?cat=' . $obj_id;
				break;
            case 'post_tag':
                $b_prefix = $wpdb->get_blog_prefix($obj_blog);
                $term_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$b_prefix }terms WHERE term_id = %d", $obj_id));
                $trans_link = $target_site . '?tag=' . $term_slug;
                break;
            default:
                $b_prefix = $wpdb->get_blog_prefix($obj_blog);
                $term_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$b_prefix }terms  WHERE term_id = %d", $obj_id));
                $trans_link = $target_site . '?' . $obj_type . '=' . $term_slug;
                break;
        }

    $trans_link = apply_filters('zwt_trans_url', $trans_link, $obj_type, $obj_blog, $obj_id);

    return $trans_link;
}

function zwt_merge_atts($pairs, $atts) {
    $atts = (array) $atts;
    $out = array();
    foreach ($pairs as $name => $default) {
        if (array_key_exists($name, $atts)) {
            $atts = array_shift($atts);
            foreach ($atts as $attr_key => $attr_value)
                foreach ($default as $key => $value) {
                    if ($attr_key == $key)
                        $default[$key] = $attr_value;
                }
        }

        $out[$name] = $default;
    }
    return $out;
}

/**
 * construct flag image html elements for the backend end
 *
 * @since 0.1.0
 * @param $locale the locale of the required flag
 * @return string of the image html element of the required flag
 */

function zwt_get_flag($locale) {
    $flag = '<img src="' . GTP_PLUGIN_URL . 'images/flags/' . $locale . '.png" width="16" height="11" alt="' . $locale . '" />';
    do_action('zwt_get_locale_flag', $locale, $flag);
    return apply_filters('zwt_get_flag', $flag, $locale);
}

/**
 * construct flag urls for the front end
 *
 * @since 0.1.0
 * @param $locale the locale of the required flag
 * @return string of the image src of the required flag image element
 */
function zwt_get_site_flags($locale) {
    global $zwt_site_obj;
    $custom_url = $zwt_site_obj->modules['settings']->settings['lang_switcher']['custom_flag_url'];
	$use_custom_flags = $zwt_site_obj->modules['settings']->settings['lang_switcher']['use_custom_flags'];
    $flag_ext = $zwt_site_obj->modules['settings']->settings['lang_switcher']['custom_flag_ext'];
    if ($custom_url !== 0 && $use_custom_flags) {
        $flag_url = content_url().$custom_url . '/' . $locale . '.' . $flag_ext;
    } else {
        $flag_url = GTP_PLUGIN_URL . 'images/flags/' . $locale . '.png';
    }
    return apply_filters('zwt_front_flag', $flag_url, $locale);
}


/**
 * removes taxonomy terms of a  blog that is no-longer part of the translation network 
 * from the taxonomy terms translation array
 *
 * @since 0.1.0
 * @param $blog the blog whose taxonomy terms should be removed
 * @return void
 */

function zwt_clean_blog_tax($blog) {
    $tax_meta = get_option('zwt_taxonomy_meta');
    if (is_array($tax_meta)) {
        foreach ($tax_meta as $tax => $t_array) {
            foreach ($t_array as $term => $blog_tax) {
                if (isset($blog_tax[$blog])) {
                    unset($tax_meta[$tax][$term][$blog]);
                }
                if (empty($tax_meta[$tax][$term])) {
                    unset($tax_meta[$tax][$term]);
                }
            }
        }
        update_option('zwt_taxonomy_meta', $tax_meta, false);
    }
    return;
}

/**
 * Gets or Updates variables that are used by the entire translation network
 *
 * @since 0.1.0
 *
 * @param int $trans_net_id the translation network ID of the translation network that shares the variable
 * @param string $action can be either get to return the variable or update to update it.
 * @param $var the variable to either update or retrieve depending on the $action value
 * @value the value of the variable, should not be null when action is 'update'
 * @return string|false value of retrieved element, or false on failure
 */
function zwt_network_vars($trans_net_id, $action, $var, $value=null) {
    global $blog_id, $switched, $site_id;
    switch ($action) {
        case 'get':
            $network_vars = get_metadata('site', $site_id, 'zwt_' . $trans_net_id . '_network_vars', true);
            if (isset($network_vars[$var])) {
                return $network_vars[$var];
            } else {
                return false;
            }

            break;

        case 'update':
            if ($value === null) {
                add_notice('No value received by zwt_network_vars() ', 'error');
                return false;
            }
            $network_vars = get_metadata('site', $site_id, 'zwt_' . $trans_net_id . '_network_vars', true);
            $network_vars[$var] = $value;
            update_metadata('site', $site_id, 'zwt_' . $trans_net_id . '_network_vars', $network_vars);
            break;
    }
}

/**
 * gzdecode implementation
 *
 * @see http://hu.php.net/manual/en/function.gzencode.php#44470
 * 
 * @param string $data
 * @param string $filename
 * @param string $error
 * @param int $maxlength
 * @return string
 */
function zwt_gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
    $len = strlen($data);
    if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
        $error = "Not in GZIP format.";
        return null; // Not GZIP format (See RFC 1952)
    }
    $method = ord(substr($data, 2, 1)); // Compression method
    $flags = ord(substr($data, 3, 1)); // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    // NOTE: $mtime may be negative (PHP integer limitations)
    $mtime = unpack("V", substr($data, 4, 4));
    $mtime = $mtime [1];
    $xfl = substr($data, 8, 1);
    $os = substr($data, 8, 1);
    $headerlen = 10;
    $extralen = 0;
    $extra = "";
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $extralen = unpack("v", substr($data, 8, 2));
        $extralen = $extralen [1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false; // invalid
        }
        $extra = substr($data, 10, $extralen);
        $headerlen += 2 + $extralen;
    }
    $filenamelen = 0;
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos(substr($data, $headerlen), chr(0));
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr($data, $headerlen, $filenamelen);
        $headerlen += $filenamelen + 1;
    }
    $commentlen = 0;
    $comment = "";
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $commentlen = strpos(substr($data, $headerlen), chr(0));
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false; // Invalid header format
        }
        $comment = substr($data, $headerlen, $commentlen);
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
        $headercrc = unpack("v", substr($data, $headerlen, 2));
        $headercrc = $headercrc [1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false; // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack("V", substr($data, - 8, 4));
    $datacrc = sprintf('%u', $datacrc [1] & 0xFFFFFFFF);
    $isize = unpack("V", substr($data, - 4));
    $isize = $isize [1];
    // decompression:
    $bodylen = $len - $headerlen - 8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr($data, $headerlen, $bodylen);
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
            case 8 :
                // Currently the only supported compression method:
                $data = gzinflate($body, $maxlength);
                break;
            default :
                $error = "Unknown compression method.";
                return false;
        }
    } // zero-byte body content is allowed
    // Verifiy CRC32
    $crc = sprintf("%u", crc32($data));
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen($data);
    if (!$lenOK || !$crcOK) {
        $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}


/**
 * Checks if the provided blog is part of the active translation network
 *
 * @since 0.3.0
 *
 * @param int blog id for the blog to verify
 * @return boolean true if the blog is part of the active translation network and false otherwise
 */
 
function zwt_is_transnet_blog($bid) {
    global $zwt_site_obj;
    $transnet_blogs = $zwt_site_obj->modules['trans_network']->transnet_blogs;
    $in_transnet = false;
    foreach ($transnet_blogs as $transblog) {
        if ($bid == $transblog['blog_id'])
            $in_transnet = true;
    }
    return $in_transnet;
}

/**
 * Retrieve the language the blog represents in the translation network
 * This is more convinient when you don't want to use switch_to_blog resource intensive function just to get a blog language
 * @since 0.3.0
 *
 * @param int $bid blog id for the blog whose language is needed
 * @return string|bool return the locale of the blog if found and false otherwise
 */

function zwt_get_blog_lang($bid) {
    global $zwt_site_obj;
    $transnet_blogs = $zwt_site_obj->modules['trans_network']->transnet_blogs;
    foreach ($transnet_blogs as $transblog) {
        if ($bid == $transblog['blog_id']) {
            return $transblog['lang_code'];
        }
    }
    return false;
}

/**
 * The main function responsible for returning Zanto WP Translation Settings object
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 *
 * @since 0.3.2
 * @return object The Zanto Translation Settings object Instance
 */
 
function Zanto_WTS(){
  return Zanto_WT()->modules['settings'];
}


/**
 * The main function responsible for returning Zanto WP Translation Network object
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 *
 * @since 0.3.2
 * @return object The Zanto Translation Network object Instance
 */
 
function Zanto_WTN(){
  return Zanto_WT()->modules['trans_network'];
}

/**
 * Retrieve the translation network ID of the active blog
 * Should be called during or after init hook runs to call it after before init, set $data_base to true
 * @since 0.3.2
 *
 * @param bool $data_base allows retrieval of the ID from the database when called before init.
 * @return int the translation network ID
 */
function zwt_get_transnet_id($data_base = false){
    global $blog_id,  $wpdb;
	
	$trans_network = Zanto_WTN();
    $trans_net_id =  $trans_network->transnet_id;
	
	if (!is_numeric($trans_net_id) && isset($trans_network->zwt_trans_cache['zwt_trans_network_cache'])) {
                    $trans_net_id = $trans_network->zwt_trans_cache['zwt_trans_network_cache']->get('trans_net_id' . $blog_id);
        } 
				
	if (!is_numeric($trans_net_id) && $data_base) {
                    $trans_net_id = $wpdb->get_var($wpdb->prepare(
                                    "SELECT  trans_id 
					FROM {$wpdb->base_prefix}zwt_trans_network
                    WHERE  blog_id =%d", $blog_id));

                    if (isset($this->zwt_trans_cache['zwt_trans_network_cache'])) {
                        $this->zwt_trans_cache['zwt_trans_network_cache']->set('trans_net_id' . $blog_id, $trans_net_id);
                    }
                }
	return $trans_net_id;
}


/**
 * Add a translation to a post
 * 
 * @since 0.3.0
 *
 * @param int $source_pid id for the post we want to add a translation to
 * @param int $target_pid id for the post we want to add as a translation for the source
 * @param int $target_bid id for the blog to which the target post(translation) belongs
 * @return void
 */
function zwt_add_single_transln($source_pid, $target_pid, $target_bid) {
    global $zwt_site_obj, $blog_id;
    $primary_blog = $zwt_site_obj->modules['trans_network']->primary_lang_blog;
    $post_transnetwork = array();

    /* check if the current blog is the primary blog language */
    if ($primary_blog == $blog_id) {
        $old_pnetwork = get_post_meta($source_pid, ZWT_Base::PREFIX . 'post_network', true); //Get old post network from source post
        $post_transnetwork[] = array('blog_id' => $target_bid, 'post_id' => $target_pid);

        if (is_array($old_pnetwork)) {
            $post_transnetwork = array_merge($post_transnetwork, $old_pnetwork);
        } else {
            /* add current blog to the metadata since no post translatin network exists */
            $post_transnetwork[] = array('blog_id' => $blog_id, 'post_id' => $source_pid);
        }
        zwt_broadcast_post_network($post_transnetwork);
    } else {
        switch_to_blog($target_bid);
        $target_pnetwork = get_post_meta($target_pid, ZWT_Base::PREFIX . 'post_network', true);
        restore_current_blog();

        if (is_array($target_pnetwork)) {
            /* add post to existing network */
            $new_pvalue = array('blog_id' => $blog_id, 'post_id' => $source_pid);
            if (!in_array($new_pvalue, $target_pnetwork)) {
                $target_pnetwork[] = $new_pvalue;
                zwt_broadcast_post_network($target_pnetwork);
            }
        } else {
            /* create new network and add it to the posts */
            $post_transnetwork[] = array('blog_id' => $target_bid, 'post_id' => $target_pid);
            $post_transnetwork[] = array('blog_id' => $blog_id, 'post_id' => $source_pid);
            zwt_broadcast_post_network($post_transnetwork);
        }
    }
}

function zwt_select2_get_posts($args = [])
{
    global $wpdb;

    $r = [
        'items'       => [],
        'total_count' => 0
    ];

    $default = [
        'blog'     => 1,
        'postType' => 'post',
        'curPage'  => 1,
        'perPage'  => 100,
        's'        => '',
        'order'    => ' p.post_title ASC '
    ];
    $opt = array_merge($default, $args);


    switch_to_blog($opt['blog']);

    $offset = ($opt['curPage'] - 1) * $opt['perPage'];

    $searchWhere = !empty($opt['s'])
        ? " LOWER(p.post_title) LIKE '%" . $opt['s'] . "%' AND "
        : '';

    $sqlBase = " FROM " . $wpdb->get_blog_prefix($opt['blog']) . "posts p WHERE
                p.post_title <> '' AND
                p.post_status <> 'translation' AND
                $searchWhere
                p.post_type = '" . $opt['postType'] . "'
                ORDER BY " . $opt['order'];
    $sqlLimit = " LIMIT " . $offset . ", " . $opt['perPage'] . " ";

    $sql =  "SELECT p.ID, p.post_title " . $sqlBase . $sqlLimit;

    $sqlCount = "SELECT count(0) " . $sqlBase;

    $posts = $wpdb->get_results($sql, ARRAY_A);
    empty($posts) && $posts = [];

    foreach ($posts as $post) {
        $postHasTranslation = 0;
        $translations       = get_post_meta($post['ID'], ZWT_Base::PREFIX . 'post_network', true);

        if (is_array($translations))
            foreach ($translations as $translation) {
                if (isset($translation['blog_id']) && $translation['blog_id'] == $opt['blog'])
                    $postHasTranslation = 1;
            }

        $r['items'][] = [
            'id'         => $post['ID'],
            'text'       => $post['post_title'],
            'translated' => $postHasTranslation
        ];
    }

    $r['total_count'] = (int) $wpdb->get_var($sqlCount);

    restore_current_blog();

    return $r;
}