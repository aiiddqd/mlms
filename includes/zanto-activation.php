<?php
function zwt_initial_activate(){
    
    global $wpdb, $EZSQL_ERROR, $site_id;
	
    require_once(GTP_PLUGIN_PATH . '/includes/language-data.php');
    //defines $langs_names

	
    $charset_collate = '';
    if ( method_exists($wpdb, 'has_cap') && $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset) )
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if ( ! empty($wpdb->collate) )
                    $charset_collate .= " COLLATE $wpdb->collate";
    }    
    
    try{
  		
        // languages table
        $table_name = $wpdb->base_prefix .'zwt_languages';            
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
            $sql = " 
            CREATE TABLE `{$table_name}` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `code` VARCHAR( 7 ) NOT NULL ,
                `english_name` VARCHAR( 128 ) NOT NULL ,            
                `default_locale` VARCHAR( 8 ),
				`custom` INT( 1 ),
                UNIQUE KEY `default_locale` (`default_locale`),
                UNIQUE KEY `english_name` (`english_name`)
            ) ENGINE=MyISAM {$charset_collate}"; 
            $wpdb->query($sql);
            if($e = $wpdb->last_error) throw new Exception($e);
            
            //$langs_names is defined in GTP_PLUGIN_PATH . '/includes/language-data.php'
            foreach($langs_names as $key=>$val){
                if(strpos($key,'Norwegian Bokm')===0){ $key = 'Norwegian Bokm�l'; $lang_codes[$key] = 'nb';} // exception for norwegian
                $default_locale = isset($lang_locales[$lang_codes[$key]]) ? $lang_locales[$lang_codes[$key]] : $lang_codes[$key];
                @$wpdb->insert($wpdb->base_prefix .'zwt_languages', array('english_name'=>$key, 'code'=>$lang_codes[$key],'default_locale'=>$default_locale, 'custom'=>0),array('%s','%s','%s','%d'));
            }        
        }

        // languages translations table
        $add_languages_translations = false;
        $table_name = $wpdb->base_prefix .'zwt_languages_translations';
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
            $sql = "
            CREATE TABLE `{$table_name}` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `language_code`  VARCHAR( 7 ) NOT NULL ,
                `display_language_code` VARCHAR( 7 ) NOT NULL ,            
                `name` VARCHAR( 255 ) CHARACTER SET utf8 NOT NULL,
                UNIQUE(`language_code`, `display_language_code`)            
            ) ENGINE=MyISAM {$charset_collate}"; 
            $wpdb->query($sql);
            if($e = $wpdb->last_error) throw new Exception($e);
            $add_languages_translations = true;
        }
        
        
        if($add_languages_translations){
            foreach($langs_names as $lang=>$val){        
                if(strpos($lang,'Norwegian Bokm')===0){ $lang = 'Norwegian Bokm�l'; $lang_codes[$lang] = 'nb';}
                foreach($val['tr'] as $k=>$display){        
                    if(strpos($k,'Norwegian Bokm')===0){ $k = 'Norwegian Bokm�l';}
                    if(!trim($display)){
                        $display = $lang;
                    }
                    if(!($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE language_code= %s AND display_language_code=%s", $lang_codes[$lang], $lang_codes[$k])))){
					    $locale = isset($lang_locales[$lang_codes[$lang]]) ? $lang_locales[$lang_codes[$lang]] : $lang_codes[$lang];
						$display_locale = isset($lang_locales[$lang_codes[$k]]) ? $lang_locales[$lang_codes[$k]] : $lang_codes[$k];
                        $wpdb->insert($wpdb->base_prefix .'zwt_languages_translations', array('language_code'=>$locale, 'display_language_code'=>$display_locale, 'name'=>$display), array('%s', '%s', '%s'));
                    }
                }    
            }        
        }
	
		
     //translation network table
	 $table_name = $wpdb->base_prefix .'zwt_trans_network';            
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
            $sql = " 
            CREATE TABLE `{$table_name}` (
                `blog_id` INT NOT NULL PRIMARY KEY ,
                `trans_id` INT NOT NULL ,
                `lang_code` VARCHAR(7) NOT NULL ,  				
                 UNIQUE KEY `blog_trans_id` (`blog_id`,`trans_id`),
                 UNIQUE KEY `blog_lang` (`blog_id`,`lang_code`),
				 UNIQUE KEY `trans_lang` (`trans_id`,`lang_code`)
            ) ENGINE=MyISAM {$charset_collate}"; 
            $wpdb->query($sql);
            if($e = $wpdb->last_error) throw new Exception($e);
		}
		
		
		  
			
	}	
	catch(Exception $e) {
        trigger_error($e->getMessage(),  E_USER_ERROR);
        exit;
    }
	
	$short_v = implode('.', array_slice(explode('.', GTP_ZANTO_VERSION), 0, 3));
	$zwt_old_settings = get_metadata('site', $site_id, 'zwt_zanto_settings', $single = true);
	$zwt_new_settings = array('zwt_installed_version' => $short_v);
	
    if($zwt_old_settings  === null || $zwt_old_settings === '' ){
        
        add_metadata('site', $site_id, 'zwt_zanto_settings', $zwt_new_settings, true);		
    }
	// here check for version no. and update accordingly
	else{
	    $version_compare=strcmp($zwt_old_settings['zwt_installed_version'], $short_v);
		switch ($version_compare) {
            case -1:
                update_metadata('site', $site_id, 'zwt_zanto_settings', $zwt_new_settings);
		        break;
            case 0: break;// same version
            case 1: 
		       add_notice(__( 'A newer version of Zanto Translation plugin was previously installed on this site', 'Zanto'),'error');
		       break;
        }
    }
	   
}  

if(isset($_GET['activate'])){
    if(!isset($wpdb)) global $wpdb;
    $table_name = 'zwt_languages';
    if(strtolower($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'")) != strtolower($table_name)){
	        add_notice(__('Zanto cannot create the database tables! Make sure that your mysql user has the CREATE privilege', 'Zanto'),'error');
            zwt_deactivate_zanto();               
        
    }
}	