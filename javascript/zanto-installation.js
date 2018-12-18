
/**
 * Wrapper function to safely use $
 * @author Ayebare Mucunguzi
 */
function zwt_install( $ )
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
            if (typeof zwt_install_params != 'undefined') {
                zwt.currentBlogId = zwt_install_params['current_blog_id'];
                zwt.blogArray = zwt_install_params['all_blog_ids'];
                zwt.ajaxPostURL	= $( '#ajaxPostURL' ).val();
                zwt.InitialSelectedLangs= zwt.initialLangSelects();
                zwt.registerEventHandlers();
                zwt.initialisePage();
            }
            else{
                zwt.initialiseSettingsPage();
                zwt.registerSettingsEvents();
            }
			
        //zwt.deactivateLangCodes();
        },
		
        /**
		 * Registers event handlers
		 * @author Zanto Translate
		 */
        registerEventHandlers : function()
        {
            $('.zwt-select-site:checkbox' ).click( zwt.highLigtSelected );
            $('.zwt-select-lang').change(zwt.avoidDuplicateLangs);
            $('#trans_network_name').change(zwt.changeTransNetwork);
            $('.zwt-wiz-submit').click(zwt.finishInstallation);
        },
		
        registerSettingsEvents: function(){
        },
		
        highLigtSelected : function( event )
        {
		 
            var closestTr =$(this).closest('tr');
            var TrId= closestTr.attr('id');
            var langSelect=  $('#select-'+TrId);
            closestTr.toggleClass('zwt-selected');	
		 
            if(closestTr.attr('class')=='zwt-selected')		 
                langSelect.attr('disabled', false);
            else
            {
                langSelect.val("");
                langSelect.attr('disabled', true);
            }
        },
		
        /**
		 * Deactivate languages already in the translation network and place them at the top of the list
		 *
		 */
		 
        avoidDuplicateLangs: function()
        {
            var inputSelected = $(this);
            var newLang = inputSelected.val();
            var inputId= inputSelected.attr('id');
            var idRegex=/[0-9]+/;
            var id=inputId.match(idRegex);
            var duplicateFlag=0;
            $.each(zwt.InitialSelectedLangs, function(key, value) {
                if( (key!= id) && (value==newLang) && (value!='') ){
                    alert(zwt_install_params['duplicate_lang']);
                    inputSelected.val("");
                    duplicateFlag=1;
                }
            });
            if(duplicateFlag)
                zwt.InitialSelectedLangs[id]="";
            else
                zwt.InitialSelectedLangs[id]=newLang;		  
        },
        changeTransNetwork: function()
        {
            var selectedNetworkId= $(this).val();
            $('.zwt-trans-networks').hide();
            $('#translation-network-'+selectedNetworkId).show();
        },
        finishInstallation: function()
        {
            $('#check-select-blog'+zwt.currentBlogId).attr("disabled",false);

        },
        initialisePage: function()
        {
            $(":checkbox").attr("autocomplete", "off");
            $('.zwt-select-lang').attr('disabled', true).attr("autocomplete", "off");						
            $('#check-select-blog'+zwt.currentBlogId).click().attr("checked",true).attr("disabled",true);
            $('#select-blog'+zwt.currentBlogId).val(zwt_install_params['default_lang']);
            $('#trans_network_name').change();						
        },
        initialiseSettingsPage: function()
        {},
		 
        initialLangSelects: function(){
            var blogIdLangs={};
            var y=0;
            var langAray = $.map(zwt.blogArray, function (value) {
                return value;
            });
            while( y < langAray.length ){
                if(langAray[y]==zwt.currentBlogId)
                    blogIdLangs[langAray[y]] = zwt_install_params['default_lang'];
                else
                    blogIdLangs[langAray[y]] = zwt_install_params['select'];	
                y++;
            }
            return blogIdLangs;
        }
		
    }; // end zwt
	
    $( document ).ready( zwt.init );

	
} // end zwt_install()

zwt_install( jQuery );