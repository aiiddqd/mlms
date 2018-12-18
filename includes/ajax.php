<?php
/**
 * the ajax SWTICHBOARD that fires specific functions
 * according to the value of Query Var 'admin_fn' for admin and 'public_fn' for the public side
 * @package ZWT_Base
 * @author Zanto Translate
 */
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__)
    die('Access denied.');
global $zwt_site_obj, $blog_id;
$WordPress_language = ZWT_MO::getSingleton();

if (is_admin() && isset($_REQUEST['admin_fn'])) {
    switch ($_REQUEST['admin_fn']) {
        case 'zwt_copy_from_original_ajx':
            $p_id = intval($_REQUEST['p_id']);
            $b_id = intval($_REQUEST['b_id']);
            switch_to_blog($b_id);
            $post = get_post($p_id);
            restore_current_blog();
            $error = false;
            $json = array();
            if (!empty($post)) {
                $json['title'] = $post->post_title;
                if ($_REQUEST['editor_type'] == 'rich') {
                    $json['body'] = htmlspecialchars_decode(wp_richedit_pre($post->post_content));
                } else {
                    $json['body'] = htmlspecialchars_decode(wp_htmledit_pre($post->post_content));
                }
            } else {
                $json['error'] = __('Post not found', 'Zanto');
            }
            do_action('zwt_copy_from_original_ajx', $p_id);

            echo json_encode($json);
            break;

        case 'zwt_fetch_trans_posts':
            $required_posts = array();
            $required_posts['blog_id'] = intval($_REQUEST['blog_id']);
            $required_posts['post_type'] = sanitize_key($_REQUEST['post_type']);
			$oblog_id = $blog_id;
			$fetch_options='<option value="-1">'.__('- Select -', 'Zanto').'</option>';
            switch_to_blog($required_posts['blog_id']);
            query_posts(array('post_type' => ($required_posts['post_type']), 'posts_per_page' => -1));
            if (have_posts()) : while (have_posts()) : the_post();
			
			        if(get_post()->post_status == 'translation'){
						continue;
					}
                    $trans_class = $disabled = ''; 
                    if( $zwt_site_obj->modules['trans_network']->primary_lang_blog == $required_posts['blog_id']){
					    
					    $translation_meta = get_post_meta(get_post()->ID, '_translation_meta_', true);
						if(isset($translation_meta[$oblog_id]['status']) && $translation_meta[$oblog_id]['status'] !== 'translated'){
						    $trans_class = 'good'; 
                            $disabled = 'disabled="true"';		
						}
					    
					}
					
                    $translations = get_post_meta(get_the_ID(), ZWT_Base::PREFIX . 'post_network', true);
					
                    if (is_array($translations))
                        foreach ($translations as $translation) {
                            if (isset($translation['blog_id']) && $translation['blog_id'] == $blog_id)
                                $trans_class = "translated";
                        }
                    $fetch_options.= '<option '.$disabled.' class="' . $trans_class . '" value="' . get_the_ID() . '">' . get_the_title() . '</option>';
                endwhile;
				echo $fetch_options;
            else:
                echo '<option value="-1">' . __('No Posts', 'Zanto') . ' Found</option>';
            endif;
            wp_reset_query();
            restore_current_blog();
            break;

        case 'select2posts':
            $args = [
                'blog'     => (int)$_REQUEST['blog_id'],
                'postType' => sanitize_key($_REQUEST['post_type'])
            ];

            isset($_REQUEST['p']) && $args['curPage'] = (int)$_REQUEST['p'];
            isset($_REQUEST['q']) && $args['s'] = sanitize_text_field($_REQUEST['q']);

            $r = zwt_select2_get_posts($args);

            die(json_encode($r));
            break;

        case 'get_lang_info':

            $nonce = $_POST['_wpnonce'];

            if (wp_verify_nonce($nonce, 'wp_lang_get_lang_info')) {

                $ZWT_Download_MO = new ZWT_Download_MO();
                $lang_code_locale = $_POST['lang'];
                $display_lang_name = $zwt_site_obj->modules['trans_network']->get_display_language_name($lang_code_locale, get_locale());

                try {
                    $mo_available_flag = false;
                    $locales = $ZWT_Download_MO->get_locales($lang_code_locale);

                    $link = admin_url('admin.php?page=zwt_manage_locales&switch_to=1&scope=' . $_POST['scope']);
                    ?>
                    <div style="margin: 30px;">
                        <form id="zwt_mo_actions" method="post" action="<?php echo $link; ?>">
                            <?php wp_nonce_field('zwt_mo_actions_nonce_1', 'zwt_mo_interface_1'); ?>
                            <h2>Choose Locale Actions</h2>
                            <?php
                            if (sizeof($locales) > 1) {
                                echo sprintf(__('We found several alternatives for %s translation. Choose which one you want to use:', 'wordpress-language'), $zwt_site_obj->modules['trans_network']->get_display_language_name($lang_code_locale, get_locale()));

                                $default_locale = $lang_code_locale;
                                ?>
                                <br />
                                <ul style="padding:10px">
                                    <?php
                                    foreach ($locales as $locale) {
                                        $checked = $locale == $default_locale ? ' checked="checked"' : '';

                                        echo '<li><label><input type="radio" name="wp_lang_locale[]" value="' . $locale . '"' . $checked . ' > ' . $locale . '</label>';
                                    }
                                    ?>
                                </ul>
                                <?php
                            }
                            else
                                echo '<input type="hidden"  name="switch_to_locale" value="' . $lang_code_locale . '">';
                            ?>


                            <br>
                            <label><input name="zwt_switch_lang" type="checkbox">&nbsp;<?php _e('Switch language to', 'Zanto');
                    echo ' ', $display_lang_name; ?> </label>
                            <br>
                            <label><input name="zwt_download_mo" checked="checked" type="checkbox">&nbsp;<?php
                    _e('Download', 'Zanto');
                    echo ' ', $display_lang_name, ' ';
                    _e('Admin wordpress .mo file', 'Zanto')
                            ?></label>
                            <br>

                            <input type="hidden"  name="current_scope" value="<?php echo $_POST['scope'] ?>">
                            <br/><br/><br/>

                            <?php
                        } catch (Exception $e) {
                            ?>
                            <span style="color:#f00" ><?php echo $e->getMessage() ?></span>					
                            <a class="button-secondary" href="#" onclick="tb_remove();jQuery('#wp_lang_switch_form').html(original_lang_switch_form);return false;"><?php echo __('Cancel', 'Zanto'); ?></a>
                            <?php
                            die();
                        }
                        ?>

                        <input class="button-primary"  name="interface_1_mo" value="<?php echo __('Submit', 'Zanto') ?>" type="submit"  />

                        <a class="button-secondary" href="#" onclick="tb_remove();jQuery('#wp_lang_switch_form').html(original_lang_switch_form);return false;"><?php echo __('Cancel', 'Zanto'); ?></a>
                    </form>
                </div>

                <?php
            }
            die();

            break;
        //@todo delete
        case 'ajax_install_language':
            $nonce = $_POST['_wpnonce'];
            if (wp_verify_nonce($nonce, 'wp_lang_get_lang_info')) {
                $_POST['action'] = 'wp_handle_sideload';
                $ZWT_Download_MO = new ZWT_Download_MO();
                $ZWT_Download_MO->get_translation_files();

                if (isset($_POST['scope']) && $_POST['scope'] == 'front-end') {
                    $current_locale = get_option('WPLANG');
                } else {
                    $current_locale = get_locale();
                }

                if ($current_locale == 'en_US') {
                    echo '1';
                    exit;
                }

                $current_lang_code = $zwt_site_obj->modules['trans_network']->get_lang_code($current_locale);
                $translations = $ZWT_Download_MO->get_translations($current_locale);

                if ($translations !== false) {
                    echo '1';
                } else {
                    echo '0';
                }
            }
            die();
            break;

        case 'flag_url_change':
            $nonce = $_POST['_wpnonce'];
            if (wp_verify_nonce($nonce, 'zwt_custom_flag')) {
                if (in_array($_POST['flag_ext'], array('png', 'jpg', 'gif'))) {
                    $flag_url = $_POST['flag_url'];
                    $flag_ext = $_POST['flag_ext'];
					$use_custom_flags = isset($_POST['use_custom'])?1:0;
					
                    ZWT_Settings::save_setting('settings', array('lang_switcher' =>
                        array(
                            'custom_flag_url' => $flag_url
                            )));

                    ZWT_Settings::save_setting('settings', array('lang_switcher' =>
                        array(
                            'custom_flag_ext' => $flag_ext
                            )));
					
					ZWT_Settings::save_setting('settings', array('lang_switcher' =>
                        array(
                            'use_custom_flags' => $use_custom_flags
                            )));

                    _e('Success', 'Zanto');
                } elseif ($_POST['flag_url'] == -1) {
                    ZWT_Settings::save_setting('settings', array('lang_switcher' =>
                        array(
                            'custom_flag_url' => 0
                            )));
                    _e('Success! Default flags will be used', 'Zanto');
                }else
                    _e('Operation was not successfull', 'Zanto');
            }

            die();

            break;


        case 'mo_check_for_updates':
            $nonce = $_POST['_wpnonce'];
            if (wp_verify_nonce($nonce, 'wp_lang_get_lang_info')) {
                $ZWT_Download_MO = new ZWT_Download_MO();
                $ZWT_Download_MO->updates_check();
                $wptranslations = $ZWT_Download_MO->get_option('translations');

                if ($_POST['scope'] == 'front-end') {
                    $current_locale = get_option('WPLANG');
                } else {
                    $current_locale = get_locale();
                }

                $current_lang_code = $zwt_site_obj->modules['trans_network']->get_lang_code($current_locale);
                $contents = ob_get_contents();
            }

            die();

            break;

        case 'ajax_show_hide_language_selector':

            $nonce = $_POST['_wpnonce'];
            if (wp_verify_nonce($nonce, 'wp_lang_get_lang_info')) {
                if ($_POST['state'] == 'on') {
                    update_option('wp_language_show_switcher', 'on');
                } else {
                    update_option('wp_language_show_switcher', 'off');
                }
            }

            die();

            break;

        case 'zwt_reset_cache':
            check_ajax_referer('zwt-advanced-tools', '_wpnonce');

            if (isset($_POST['cacheType']) && in_array($_POST['cacheType'], array(1, 2, 3))) {
                if ($_POST['cacheType'] == 1) {
                    $blog_trans_cache = new zwt_cache('translation_network', true);
                    $blog_trans_cache->clear();
                } elseif ($_POST['cacheType'] == 2) {
                    $locale_cache = new zwt_cache('locale_code', true);
                    $lang_name_cache = new zwt_cache('lang_name', true);
                    $locale_cache->clear();
                    $lang_name_cache->clear();
                } elseif ($_POST['cacheType'] == 3) {
                    delete_option('_zwt_cache');
                }
                $zwt_site_obj->clearCachingPlugins();

                echo '<span class="success">' . __('Cache reset', 'Zanto') . ' </span>';
            } else {
                echo '<span class="fail">' . __(' Invalid value supplied!', 'Zanto') . '</span>';
            }
            die();
            break;

        case 'zwt_copy_taxonomy':
            check_ajax_referer('zwt-advanced-tools', '_wpnonce');
            if (isset($_POST['fromBlog']) && intval($_POST['fromBlog']) && isset($_POST['taxonomy'])) {
                $transnet_blogs = $zwt_site_obj->modules['trans_network']->transnet_blogs;
                $clean = false;
                foreach ($transnet_blogs as $transblog) {
                    if ($_POST['fromBlog'] == $transblog['blog_id']) {
                        $clean = true;
                        break;
                    }
                }
                if (!$clean) {
                    echo '<span class="fail">' . __('Invalid value Suplied!', 'Zanto') . '</span>';
                    die();
                }

                if ($blog_id != $_POST['fromBlog']) {
                    $from_blog = $_POST['fromBlog'];
                    $source_tax_meta = get_blog_option($_POST['fromBlog'], 'zwt_taxonomy_meta');
                    $tax_name = $_POST['taxonomy'];
                    if (isset($source_tax_meta[$tax_name])) {
                        $tax_array = $source_tax_meta[$tax_name];
                        $imported_tax = array();
                        foreach ($tax_array as $source_term_id => $term_translations) {

                            foreach ($term_translations as $c_blog_id => $term_id) {
                                if ($c_blog_id == $blog_id) { // get blog and term id from translations of source blog
                                    $c_term_id = $term_id;
                                } else {   //the rest of the term translations make the new term translations
                                    $new_translations[$c_blog_id] = $term_id;
                                }
                            }

                            if (isset($c_term_id)) {
                                $new_translations[$from_blog] = $source_term_id; // add this source blog term to translations
                                $imported_tax[$c_term_id] = $new_translations;
                                unset($c_term_id);
                                unset($new_translations);
                            }
                        }
                    }

                    if (!empty($imported_tax)) {
                        $blog_tax_meta = get_option('zwt_taxonomy_meta');
                        if (isset($blog_tax_meta[$tax_name])) {
                            $blog_tax_array = $blog_tax_meta[$tax_name];
                        } else { // a case where no term exists as translated
                            $blog_tax_array = array();
                        }
                        $new_tax_array = empty($blog_tax_array) ? $imported_tax : array_replace_recursive($blog_tax_array, $imported_tax);
                        $blog_tax_meta[$tax_name] = $new_tax_array;
                        update_option('zwt_taxonomy_meta', $blog_tax_meta, false);
                        echo '<span class="success">' . __(' Import successful!', 'Zanto') . '</span>';
                    } else {
                        echo '<span class="fail">' . __('There was nothing to import!', 'Zanto') . '</span>';
                    }
                }
            }
            die();
            break;

        case 'zwt_reset_zanto':
            check_ajax_referer('zwt-advanced-tools', '_wpnonce');
            $defaults = ZWT_Settings::getDefaultSettings();
            delete_option(ZWT_Base::PREFIX . 'zanto_settings');
            $update = update_option(ZWT_Base::PREFIX . 'zanto_settings', $defaults);
            ZWT_Settings::save_setting('settings', array('setup_status' =>
                array(
                    'setup_wizard' => 'complete',
                    'setup_interface' => 'four'
                    )));
            if ($update) {
                echo '<span class="success">' . __('reset successful!', 'Zanto') . '</span>';
            } else {
                echo '<span class="fail">' . __('reset failed!', 'Zanto') . '</span>';
            }
            die();
            break;

        case 'remove_trans_site':
            check_ajax_referer('zwt_update_transnetwork_nonce', '_wpnonce');
            if (isset($_POST['blog_id']) && $d_blog_id = intval($_POST['blog_id'])) {// used = to assign d_blog $_POST value
                global $wpdb, $site_id;


                if (get_current_blog_id() == $d_blog_id) {

                    add_notice('You are not allowed to change the current site', 'error');
                    die();
                }
                switch_to_blog($d_blog_id);
                $transnet_id = $zwt_site_obj->modules['trans_network']->get_trans_id(true);
                $transnet_blogs = $zwt_site_obj->modules['trans_network']->get_transnet_blogs(true);

                if ($wpdb->delete($wpdb->base_prefix . 'zwt_trans_network', array('blog_id' => $d_blog_id), array('%d'))) {
                    add_notice(__('Zanto Translation Network was successfuly updated','Zanto'));
                    ZWT_Settings::save_setting('settings', array('setup_status' =>
                        array(
                            'setup_wizard' => 'incomplete',
                            'setup_interface' => 'two'
                            )));
                } else { //@todo make it persistent
                    add_notice(__('There was an error updating the Trans Network table','Zanto'), 'error');
                    die();
                }

                if (count($transnet_blogs) < 2) {
                    if (!$wpdb->delete($wpdb->base_prefix . 'usermeta', array('meta_key' => 'zwt_installed_transnetwork', 'meta_value' => $transnet_id), array('%s', '%d'))) {
                        add_notice(__('There was an error deleting the zwt_trans_network value from usermeta table','Zanto'), 'error');
                    }

                    // delete zwt_network_vars from site meta
                }

                if ($zwt_site_obj->modules['trans_network']->get_primary_lang_blog(true) == $d_blog_id) {
                    delete_metadata('site', $site_id, 'zwt_' . $transnet_id . '_network_vars');
                }

                $zwt_global_cache = get_metadata('site', $site_id, 'zwt_' . $transnet_id . '_site_cache', true);
                if (isset($zwt_global_cache[$d_blog_id])) {
                    unset($zwt_global_cache[$d_blog_id]);
                    update_metadata('site', $site_id, 'zwt_' . $transnet_id . '_site_cache', $zwt_global_cache);
                }

                zwt_clean_blog_tax($d_blog_id);

                restore_current_blog();

                foreach ($transnet_blogs as $trans_blog) {
                    if ($trans_blog == $d_blog_id)
                        continue;
                    switch_to_blog($trans_blog['blog_id']);
                    $c_trans_net_cache = new zwt_cache('translation_network', true);
                    $c_trans_net_cache->clear();
                    zwt_clean_blog_tax($d_blog_id);
                    restore_current_blog();
                }
                _e('success', 'Zanto');
            }
            die();
            break;
        case 'ztm_translator_search':
            check_ajax_referer('zanto_qs_nonce', '_wpnonce');
            $search_term = $_REQUEST['term'];
            $users = get_users(array(
                'orderby' => 'post_count',
                'order' => 'DESC',
                    ));
            $translators = $not_translators = array();
            foreach ($users as $user) {
                if (isset($user->caps['translate']) && $user->caps['translate']) {
                    continue;
                } elseif (false !== stripos($user->user_login, $search_term) || false !== stripos($user->display_name, $search_term)) {
                    $not_translators[] = array('label' => $user->display_name . ' (' . $user->first_name . ' ' . $user->last_name . ')', 'value' => $user->user_login);
                }
                if (count($not_translators) == 100)
                    break;
            }


            if (!empty($not_translators)) {
                $json = $not_translators;
            } else {
                $json = array('label' => __('No Matches','Zanto'), 'value' => '');
            }
            echo json_encode($json);
            die();
            break;

        case 'ztm_save_trnsln'://[CHECK FOR POST TYPE]
            check_ajax_referer('zantoajaxsave', '_wpnonce');
            if (empty($_POST['target_pid']))
                wp_die(__('You are not allowed to edit this post.','Zanto'));
            $current_uid = get_current_user_id();
            $data = '';
            $id = $revision_id = 0;
            $source_pid = (int) $_POST['source_pid'];
            $target_bid = (int) $_POST['target_bid'];
            $target_pid = (int) $_POST['target_pid'];
            $_POST['ID'] = $_POST['post_ID'] = $target_pid;

            if (!current_user_can('translate'))
                wp_die(__('You are not allowed to edit this page.'));
            $translation_meta = get_post_meta($source_pid, '_translation_meta_', true);
            if (isset($translation_meta[ZTM_TARGET_BLOG]['translator']))
                if ($translation_meta[ZTM_TARGET_BLOG]['translator'] !== $current_uid && $translation_meta[ZTM_TARGET_BLOG]['translator'] !== -1) {// prevent translator subotage
                    $json['error'] = __('There was a problem verifying the translator', 'Zanto');
                    echo json_encode($json);
                    die();
                }

            switch_to_blog($target_bid);
            $post = get_post($target_pid);

            if ('auto-draft' == $post->post_status)
                $_POST['post_status'] = 'translation';
            if (isset($_POST['target_excerpt']) && $_POST['target_excerpt'] !== 'NULL') {
                $_POST['excerpt'] = $_POST['target_excerpt'];
            }
            if (!empty($_POST['ajax_save'])) {
                if ('auto-draft' == $post->post_status || 'translation' == $post->post_status) {
                    // Drafts and auto-drafts are just overwritten by autosave for the same user if the post is not locked
                    $id = ztm_edit_post();
                } else {
                    // Non drafts or other users drafts are not overwritten. The autosave is stored in a special post revision for each user.
                    $revision_id = wp_create_post_autosave($post->ID);
                    if (is_wp_error($revision_id))
                        $id = $revision_id;
                    else
                        $id = $post->ID;
                }

                if (!is_wp_error($id)) {
                    /* store translations in deletable meta value and calculate percatage translation */
                    $draft_translations = array();
                    $term_errors = array();
                    
                    if (isset($_POST['postTerms']) && !empty($_POST['postTerms'])) {
                        $draft_translations['terms'] = $_POST['postTerms'];
                    }

                    if (isset($_POST['postTermDesc']) && !empty($_POST['postTermDesc'])) {
                        $draft_translations['term_desc'] = $_POST['postTermDesc'];                      
                    }

                    if (isset($_POST['postMeta']) && !empty($_POST['postMeta'])) {
                        $draft_translations['meta'] = $_POST['postMeta'];                        
                    }

                    if (isset($_POST['postComments']) && !empty($_POST['postComments'])) {
                        $draft_translations['comments'] = $_POST['postComments'];                        
                    }
                    $translation_meta[ZTM_TARGET_BLOG]['TID'] = $id;
                    $translation_meta[ZTM_TARGET_BLOG]['progress'] = intval($_POST['progress']);
                    $translation_meta[ZTM_TARGET_BLOG]['status'] = 'in_progress';
                    $translation_meta[ZTM_TARGET_BLOG]['translator'] = $current_uid;
					$translation_meta[ZTM_TARGET_BLOG]['date'] = current_time('mysql');
                    ztm_save_draft_translations($id, $draft_translations);
                    restore_current_blog();
                    update_post_meta($source_pid, '_translation_meta_', $translation_meta);
                } else {
                    $json['error'] = __('There was a problem saving the post', 'Zanto');
                    echo json_encode($json);
                    die();
                }
                $json['date'] = $translation_meta[ZTM_TARGET_BLOG]['date'];
				$json['progress'] = $translation_meta[ZTM_TARGET_BLOG]['progress'];
                $json['body'] = __('Translations Saved', 'Zanto');
                echo json_encode($json);
            }
            die();
            break;
        case 'ztm_send2_review':
        case 'ztm_send2_transln':
            check_ajax_referer('zantoajaxsave', '_wpnonce');
            if (empty($_POST['target_pid']))
                wp_die(__('You are not allowed to edit this post.'));
            $current_uid = get_current_user_id();
            $source_pid = (int) $_POST['source_pid'];
            $target_pid = (int) $_POST['target_pid'];
            $target_bid = (int) $_POST['target_bid'];

            if (!current_user_can('translate')) {
                $json['error'] = __('This procedure requires translation previlages to be perfomed', 'Zanto');
                echo json_encode($json);
                die();
            }

            $translation_meta = get_post_meta($source_pid, '_translation_meta_', true);
            $user_job = ($_REQUEST['admin_fn'] == 'ztm_send2_review') ? 'translator' : 'reviewer';
            $user_job_txt = __($user_job, 'Zanto');
            $receiver = ($user_job == 'translator') ? __('reviewer', 'Zanto') : __('translator', 'Zanto');

            if (isset($translation_meta[ZTM_TARGET_BLOG][$user_job])) {
                if ($translation_meta[ZTM_TARGET_BLOG][$user_job] !== $current_uid && $translation_meta[ZTM_TARGET_BLOG][$user_job] !== -1) {
                    $json['error'] = sprintf(__('There was a problem verifying the %s', 'Zanto'), $user_job_txt);
                    echo json_encode($json);
                    die();
                } else {

                    $translation_meta[ZTM_TARGET_BLOG]['status'] = ($user_job == 'translator') ? 'pending_review' : 'pending';
                    $translation_meta[ZTM_TARGET_BLOG][$user_job] = $current_uid;
                    update_post_meta($source_pid, '_translation_meta_', $translation_meta);

                    if ($user_job == 'reviewer') { //update notes from reviewer
                        switch_to_blog($target_bid);
                        $draft_translations = get_post_meta($target_pid, '_draft_translations', true);
                        $draft_notes = (isset($draft_translations['notes']) && is_array($draft_translations['notes'])) ? $draft_translations['notes'] : array();
                        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
                            $draft_notes = $_POST['notes'];
                            foreach ($draft_notes as $index => $ndata) {
                                if (empty($ndata)) {
                                    unset($draft_notes[$index]);
                                }
                            }
                        }
                        $draft_translations['notes'] = $draft_notes;
                        update_post_meta($target_pid, '_draft_translations', $draft_translations);
                        restore_current_blog();
                    }
                }
            } else {
                $json['error'] = sprintf(__('This post has not been assigned a %s', 'Zanto'), $user_job_txt);
                echo json_encode($json);
                die();
            }

            $json['body'] = sprintf(__('The review notes have been sent to the %s', 'Zanto'), $receiver);
            echo json_encode($json);
            die();
            break;

        case 'ztm_edit_translator':
            global $wpdb;
            check_ajax_referer('inline-edit-translators', '_wpnonce');
            $translator_ID = $_POST['translator_ID'];
            $transnet_blogs = $zwt_site_obj->modules['trans_network']->transnet_blogs;

            if ($user = get_userdata($_POST['translator_ID'])) {

                $translation_caps = array();

                if (isset($_POST['ztm_cap']) && !empty($_POST['ztm_cap'])) {
                    $caps_array = $_POST['ztm_cap'];
                    if (isset($caps_array['cap-translator'])) {
                        $user->add_cap('translate');
                        $translation_caps['translate'] = true;
                    } else {
                        $user->remove_cap('translate');
                        $translation_caps['translate'] = false;
                    }
                    if (isset($caps_array['cap-manager'])) {
                        $user->add_cap('manage_translations');
                        $translation_caps['manage_translations'] = true;
                    } else {
                        $user->remove_cap('manage_translations');
                        $translation_caps['manage_translations'] = false;
                    }
                }

                if (isset($_POST['ztm_lp']) && !empty($_POST['ztm_lp'])) {

                    $lang_pairs = get_user_meta($user->ID, $wpdb->prefix . 'language_pairs', true);
                    $lp_array = $_POST['ztm_lp'];
                    $lang_pairs = array();

                    foreach ($transnet_blogs as $trans_blog) {
                        if (isset($lp_array[$trans_blog['lang_code']])) {
                            if (!in_array($trans_blog['lang_code'], $lang_pairs)) {
                                $lang_pairs[] = $trans_blog['lang_code'];
                            }
                        }
                    }
                    update_user_meta($user->ID, $wpdb->prefix . 'language_pairs', $lang_pairs);
                } else {
                    _e('Please assign a lang pair', 'Zanto');
                    die();
                }
                $wp_list_table = new ZTM_Translators_List();


                $name = ($user->first_name . $user->last_name == '') ? $user->display_name : $user->first_name . ' ' . $user->last_name;
                $item = array('ID' => $user->ID, 'email' => $user->user_email, 'login' => $user->user_login, 'name' => $name, 'display_name' => $user->display_name, 'lang_pairs' => $lang_pairs, 'role' => $translation_caps);

                $wp_list_table->single_row($item);
            } else {
                _e('user selection error', 'Zanto');
            }

            die();
            break;
        case 'ztm_assign_job':
            check_ajax_referer('inline-assign-jobs', '_wpnonce');
            $post_ID = $_POST['post_ID'];
            $user_work = array();
            if (isset($_POST['inline-translator']) && $_POST['inline-translator'] !== '-2') {
                $user_work['translator'] = intval($_POST['inline-translator']);
            }
            if (isset($_POST['inline-reviewer']) && $_POST['inline-reviewer'] !== '-2') {
                $user_work['reviewer'] = intval($_POST['inline-reviewer']);
            }

            $translation_meta = get_post_meta($post_ID, '_translation_meta_', true);
            $update = false;
            if (!empty($user_work)) {
                foreach ($user_work as $job => $user_ID) {
                    if ($user_ID !== -1) {
                        $chosen_user = get_userdata($user_ID);
                        if (!$chosen_user || !$chosen_user->has_cap('translate')) {
                            echo sprintf(__('Invalid %s Chosen', 'Zanto'), $job);
                            die();
                        }
                    }

                    if (!isset($translation_meta[ZTM_TARGET_BLOG][$job]) || $translation_meta[ZTM_TARGET_BLOG][$job] == -1) {
                        $translation_meta[ZTM_TARGET_BLOG][$job] = $user_ID;

                        $tm_settings = ztm_Plugin_Settings::getSingleton()->get_settings();
                        if ($user_ID !== -1 && isset($tm_settings['notify_translator']) && check_internet_connection()) {
                            $mail['to'] = $chosen_user->user_email;
                            $mail['subject'] = get_bloginfo('name');
                            $mail['body'] = sprintf($tm_settings['notify_translator_text'], get_the_title($post_ID), __($job, 'Zanto')) . "\n\n" . $tm_settings['email_signature'];
                            add_mail($mail);
                        }
                        $update = true;
                    }
                }
            } else {
                echo __('Empty Assignment', 'Zanto');
                die();
            }

            ($update) ? update_post_meta($post_ID, '_translation_meta_', $translation_meta) : die();

            $wp_list_table = new ZTM_Translation_List();

            $progress = isset($translation_meta[ZTM_TARGET_BLOG]['progress']) ? $translation_meta[ZTM_TARGET_BLOG]['progress'] : 0;
            $status_id = (isset($translation_meta[ZTM_TARGET_BLOG]['status']) && 'resigned' !== $translation_meta[ZTM_TARGET_BLOG]['status']) ? $translation_meta[ZTM_TARGET_BLOG]['status'] : 'pending';
            $translator = $wp_list_table->map_translator_name(isset($translation_meta[ZTM_TARGET_BLOG]['translator']) ? $translation_meta[ZTM_TARGET_BLOG]['translator'] : null);
            $reviewer = $wp_list_table->map_translator_name(isset($translation_meta[ZTM_TARGET_BLOG]['reviewer']) ? $translation_meta[ZTM_TARGET_BLOG]['reviewer'] : null);
            $target_id = isset($translation_meta[ZTM_TARGET_BLOG]['TID']) ? $translation_meta[ZTM_TARGET_BLOG]['TID'] : null;
            $item = array(
                'ID' => $post_ID,
                'title' => '<a class="row-title" href="#">' . get_the_title($post_ID) . '</a>',
                'translator' => $translator,
                'reviewer' => $reviewer,
                'progress' => $progress,
                'status' => $wp_list_table->render_status($status_id),
                'TID' => $target_id
            );

            $wp_list_table->single_row($item);

            die();
            break;

        default:
            $output = 'No function specified, check your jQuery.ajax() call';
            break;
    }
} elseif (isset($_REQUEST['public_fn'])) {

    switch ($_REQUEST['public_fn']) {
        case 'get_browser_language':

            $browser_langs = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $browser_lang = explode("-", $browser_langs[0]);
            echo isset($browser_lang[1]) ? $browser_lang[0] . '_' . strtoupper($browser_lang[1]) : $browser_lang[0];
            die();
            break;

        default:
            $output = 'No function specified, check your jQuery.ajax() call';
            break;
    }
}

die();