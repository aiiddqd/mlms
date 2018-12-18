jQuery(document).ready(function(){
    if(jQuery.cookie != undefined) {
        // Check if cookie are enabled
        jQuery.cookie('ZWT_Browser_Lang_Redirect_test', '1');
        var cookie_enabled = jQuery.cookie('ZWT_Browser_Lang_Redirect_test') == 1;
        jQuery.removeCookie('ZWT_Browser_Lang_Redirect_test');
        
        if (cookie_enabled) {
            var cookie_params = ZWT_Browser_Lang_Redirect_params.cookie
            var cookie_name = cookie_params.name;
            // Check if we already did a redirect
            
            if (!jQuery.cookie(cookie_name)) {
                // Get page language and browser language
                var pageLanguage = ZWT_Browser_Lang_Redirect_params.pageLanguage;
                var browserData = {
                    action: 'zwt_all_ajax',
                    public_fn :'get_browser_language'
                };
                jQuery.ajax({
				    url: ZWT_Browser_Lang_Redirect_params.ajaxurl,
                    async: false, 
					data : browserData,
                    success: function(ret){browserLanguage = ret}
                });
                
                // Build cookie options
                var cookie_options = {
                    expires: cookie_params.expiration / 24,
                    path: cookie_params.path? cookie_params.path : '/',
                    domain: cookie_params.domain? cookie_params.domain : ''
                };
                
                // Set the cookie so that the check is made only on the first visit
                jQuery.cookie(cookie_name, browserLanguage, cookie_options);

                // Compare page language and browser language
                if (pageLanguage != browserLanguage) {
                    var redirectUrl;
                    // First try to find the redirect url from parameters passed to javascript
                    var languageUrls = ZWT_Browser_Lang_Redirect_params.languageUrls;
                    if (languageUrls[browserLanguage] != undefined) {
                        redirectUrl = languageUrls[browserLanguage];
                    }else{
					     baseLanguageVersion = browserLanguage.substr(0,2);
						 if (languageUrls[baseLanguageVersion] != undefined) {
						     redirectUrl = languageUrls[baseLanguageVersion];
						 }
					}
                    // Finally do the redirect
                    if (redirectUrl != undefined) {
                        window.location = redirectUrl;
                    }    
                }
            }
        }
    }
});