/**
 * @author Zanto Translate
 */


/**
 * Wrapper function to safely use $
 * @author Zanto Translate
 */

var original_lang_switch_form = jQuery('#wp_lang_switch_form').html();
 
function zwt_mo_mgt( $ )
{
    var zwt = 
    {
        /**
		 * Main entry point
		 * @author Zanto Translate
		 */
        init : function()
        {
            zwt.prefix			= 'zwt_';
            zwt.pluginUrl= zwt_mo_params['plugin_url'];
            zwt.adminUrl = zwt_mo_params['admin_url'];
            zwt.langSwitcherShow = zwt_mo_params['wp_language_show_switcher'];
            zwt.currentScope = zwt_mo_params['current_scope'];
            zwt.registerEventHandlers();
            zwt.initialisePage();
			
        //zwt.deactivateLangCodes();
        },
		
        /**
		 * Registers event handlers
		 * @author Zanto Translate
		 */
        registerEventHandlers : function()
        {
			
			
            $('#wp_lang_change_lang_cancel').live('click', function(){
                $('#wp_lang_change_lang').slideUp(function(){
                    $('#wp_lang_change_lang_button').fadeIn();    
                }); 
                return false;    
            })
			
            $('#wp_lang_change_lang_button').live('click', function(){
                $('#wp_lang_change_lang_button').fadeOut(function(){
                    $('#wp_lang_change_lang').slideDown();
                });
                return false;    
            })
			
            $('.wp_lang_thickbox a').click(zwt.dowloadOperations);
            $('#front_mo_download').change(zwt.frontScopeLanguage);
			$('#zwt_flag_url_change').click(zwt.flagUrlChange);
			$('#zwt_use_custom_flags').click(zwt.enableCustomFlags);
			$('#zwt_default_dir').click(zwt.defaultFlagPath);
			
        },
		
        /**
		 * Example event handler
		 * @author Zanto Translate
		 * @param object event
		 */
		
		
        dowloadOperations: function()
        {
            var lang = zwt.getUrlVars($(this).attr('href'))['switch_to'];
            var wp_lang_ajx_spinner = '<img  src="'+zwt.pluginUrl+'/images/spin-big.gif" / style="border: 2px solid rgb(221, 221, 221);">';                    
            // hide the language popup
            var refresh = function(i, el){ // force the browser to refresh the tabbing index
                var node = $(el), tab = node.attr('tabindex');
                if ( tab )
                    node.attr('tabindex', '0').attr('tabindex', tab);
            };
                                
            var target = $(this).parent().parent().parent().parent();
            target.closest('.hover').removeClass('hover').children('.ab-item').focus();
            target.siblings('.ab-sub-wrapper').find('.ab-item').each(refresh);
			//$('#wp_lang_switch_form').html(wp_lang_ajx_spinner);

            if (lang != 'undefined') {
                                
                var data = {
                    action: 'zwt_all_ajax',
                    admin_fn :'get_lang_info',
                    lang: lang,
                    _wpnonce: $('#wp_lang_get_lang_info').val(),
                    scope:zwt.currentScope
                };
								
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: data,
                    cache: false,
                    beforeSend: function () {
                                            
                    },
                    success: function (data) {
                        $('#wp_lang_switch_form').html(data);
                    }
                });
            }
		 
		
        },
		
		frontScopeLanguage: function(){
                var LangSelected = $(this).val();
				if(LangSelected == 'null'){
				  $('#wp_front_lang_change').removeClass('thickbox');
				}
				 else{
				     $('#wp_front_lang_change').addClass('thickbox');
				 }
				 
                $('#wp_front_lang_change').attr('href','#TB_inline?height=255&width=750&inlineId=wp_lang_switch_popup&modal=true&switch_to='+LangSelected+'&scope=front-end');
        },
		
		flagUrlChange: function(event){
		 event.preventDefault();
		 var data = {
                    action: 'zwt_all_ajax',
                    admin_fn :'flag_url_change',
                    flag_url: $('#zwt_flag_url').val(),
					use_custom: $('#zwt_use_custom_flags').attr('checked'), 
                    _wpnonce: $('#zwt_custom_flags').val(),
					flag_ext:$('#zwt_flag_ext').val()
                };
								
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: data,
                    cache: false,
                    beforeSend: function () {
                       $('#zwt_flag_url_change').after('<span class="zwt_ajax_response" style="margin-left:10px"><img src="'+zwt_pluginUrl+'/images/spin-small.gif"></span>');                       
                    },
                    success: function (data) {
                        $('.zwt_ajax_response').html(data).fadeOut(2000, function(){});   
                    }
                });
		},
		
		flagUrlSelect: function(){
		   if($('#zwt_flag_url').val() != -1){
		        $('#zwt_flag_ext_span').show();
		   }else{
		       $('#zwt_flag_ext_span').hide();
		   }
		},
        enableCustomFlags: function(){
		if ($(this).attr('checked')) {
		        $('#zwt_flag_url').attr('disabled',false);
				$('#zwt_flag_ext').attr('disabled',false);
				$('#zwt_default_dir').attr('disabled',false);
				 
             }else{
			    $('#zwt_flag_url').attr('disabled',true);
			    $('#zwt_flag_ext').attr('disabled',true);
				$('#zwt_default_dir').attr('disabled',true);
			 }
		},
		
		defaultFlagPath: function (){
		    $('#zwt_flag_url').attr('value',($('#zwt_default_url').attr('value')));
		},
		
	    getUrlVars: function(href) {
                var vars = {};
                var parts = href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                    vars[key] = value;
                });
                return vars;
            },
		
        initialisePage: function()
        {		   
            //var original_lang_switch_form = null;
            if (zwt.langSwitcherShow != 'on') {
                $('#wp-admin-bar-WP_LANG_lang').hide();
            }
        }
		 		
    }; // end zwt

    $( document ).ready(
	zwt.init );

	
} // end zwt_mo_mgt()

zwt_mo_mgt( jQuery );

function wp_lang_check_for_updates() {
    jQuery('#zwt_manage_locales_update_to_date').hide();
    jQuery('#zwt_manage_locales_check_for_updates').show();
    var data={
        action:'zwt_all_ajax',
		admin_fn:'mo_check_for_updates',
        _wpnonce:jQuery('#wp_lang_get_lang_info').val(),
        scope:zwt_mo_params['current_scope']
							  
    };                               
    jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        data: data,
        cache: false,
        beforeSend: function () {
                                                                
        },
        success: function (data) {
            jQuery('#wp_language_translation_state').html(data);
        }
    });
                                                        
}
function downloadMo(){

    var data={
        action:'zwt_all_ajax',
		admin_fn:'ajax_install_language',
        _wpnonce:jQuery('#wp_lang_get_lang_info').val(),
        scope:zwt_mo_params['current_scope']
    };

    jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        data: data,
        cache: false,
        beforeSend: function () {
                                                                            
        },
        success: function (data) {
            if (data == '1') {
                jQuery('#wp_language_downloading').hide();
                jQuery('#wp_language_download_complete').fadeIn('slow');
                window.location = zwt_mo_params['admin_url']+'&scope='+ zwt_mo_params['current_scope']+'&download_complete=1';
            } else {
                window.location =zwt_mo_params['admin_url']+'&scope='+zwt_mo_params['current_scope']+'&no_translation_available=1';
            }
        }
    });

}

function wp_lang_show_hide_selector() {
                        var state = jQuery('#wp_lang_show_hide_selector:checked').val();
                        if (state == 'on') {
                            jQuery('#wp-admin-bar-WP_LANG_lang').show();
                        } else {
                            jQuery('#wp-admin-bar-WP_LANG_lang').hide();
                        }
                        var data={
						 action:'zwt_all_ajax',
						 admin_fn:'ajax_show_hide_language_selector',
						 state:state,
						 _wpnonce:jQuery('#wp_lang_get_lang_info').val()
						};
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            data: data,
                            cache: false,
                            beforeSend: function () {
                                
                            },
                            success: function (data) {
                            }
                        });
                        
                    }
	

function wp_lang_lang_switch() {
                var wp_lang_switch_target = zwt.adminUrl;
                var locale = jQuery('input[name="wp_lang_locale\\[\\]"]:checked').val();
                window.location = wp_lang_switch_target + '&switch_to=' + locale + '&scope=<?php echo $this->current_scope ?>' + '&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val();
            }