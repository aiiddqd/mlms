/**
 * @author Zanto Translate
 */
/**
 * Wrapper function to safely use $
 * @author Zanto Translate
 */

function zwt_main($) {
    var zwt = {
        /**
         * Main entry point
         * @author Zanto Translate
         */
        init: function () {
            zwt.prefix = 'zwt_';
            zwt.templateURL = $('#templateURL').val();

            if (adminpage == 'zanto_page_zwt_trans_network') {
                zwt.transIdArray = $('#zwt_remove_elements #zwt_trans_id').clone();
                zwt.langArray = $('#zwt_remove_elements #update_lang_blog').clone();
                zwt.transNetworkPageStart();
                zwt.transNetEventHandlers();
            }
            if (adminpage == 'post-php' || adminpage == 'post-new-php') {
                zwt.postPageStart();
                zwt.PostEventHandlers();
                zwt.selectSet = new Array();
            }
            if (adminpage == 'zanto_page_zwt_advanced_tools') {
                zwt.AToolsEventHandlers();
            }
			if (adminpage == 'zanto_page_zwt_settings') {
			    zwt.initialiseStgsPage();
                zwt.stgsEventHandlers();
            }
			zwt.ZUI_Events();


            zwt.hasSelect2 = false;

            if(typeof jQuery.fn.select2 !== "undefined") {

                try{
                    zwt.select2adapter = jQuery.fn.select2.amd.require('select2/data/extended-ajax');

                    if(typeof jQuery.fn.select2.amd.require._defined['select2/data/extended-ajax'] !== "undefined") {
                        zwt.hasSelect2 = true;
                    }
                } catch (e) {
                    console.log(e);
                }
            }

            zwt.hasSelect2 && zwt_initSelects2(zwt);
        },

        /**
         * Registers reusable Zanto User interface event handlers. can be resused by addonds 
         */
		ZUI_Events: function (){ //these can be used through out zanto and zanto addons provided the right class is used for the selectors
		    $('.zui-toggle-show').click(zwt.hideShow);//  hides/shows siblings of elements with class 'zwt-hide'
		},
        transNetEventHandlers: function () {
            $('a.add_to_network').click(zwt.showUpdateTransInputs);
            $('a.remove_from_network').click(zwt.removeFromNetwork);
        },

        PostEventHandlers: function (event) {
            $('.transln_method_mthds').change(zwt.transln_method_change);
			$('#zwt_select_primary').change(zwt.overwriteAlert);
			$('.zwt_select_secondary').change(zwt.overwriteAlert);
			$('a#zwt_cfo.zwt_cfp').click(zwt.copy4rmOriginal);
        },

        AToolsEventHandlers: function (event) {
            $('#zwt_reset_cache').click(zwt.advancedToolsAjax);
            $('#zwt_copy_taxonomy').click(zwt.advancedToolsAjax);
            $('#zwt_reset_zanto').click(zwt.advancedToolsAjax);
        },
		stgsEventHandlers: function (event){
		    var tmSet=$('#ztm_active');
		    if(tmSet.length!=0 && tmSet[0].value =='1'){
                $('#primary-lang').change(zwt.primaryLangAlert);
            }
		},
		
		initialiseStgsPage: function()
        {		   
            $( "#sortable" ).sortable({
                update: function(event, ui) {
                    var LangOrder = $(this).sortable('toArray').toString();
                    $('#zwt_lang_order').attr('value',LangOrder);
                }
            });
            $( "#sortable" ).disableSelection();
			
        },
		hideShow: function (){
		    $(this).parent().find('.zui-target').fadeToggle();
		},
        removeFromNetwork: function(event){
            var buttonSelected = $(this);
            var buttonId = buttonSelected.attr('id');
			var buttonTitle = buttonSelected.attr('title');
            var idRegex = /[0-9]+/;
            var id = parseInt(buttonId.match(idRegex), 10);
            event.preventDefault();
            var proceed = confirm(buttonTitle+':\n\n'+zwt_main_i8n[1]);
            if(!proceed){
                return;
            }else{
                data = {
                    action: 'zwt_all_ajax',
                    admin_fn : 'remove_trans_site',
                    blog_id: id,
                    _wpnonce: $('#zwt_updatetrans_nonce').val()
                };
					
            }
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                beforeSend: function () {
                    $('#'+buttonId).after('<span class="zwt_ajax_response" style="margin-left:10px"><img src="'+zwt_pluginUrl+'/images/spin-small.gif"></span>');                       
                },
                success: function (data) {
                        location.reload(true);
                }
            });
        },

        showUpdateTransInputs: function (event) {
            var c_user = zwt_main_i8n[0];
            buttonSelected = $(this);
            var classRegex = /active/;
            if (buttonSelected.attr('class').search(classRegex) != -1) {
                $('.zwt_show_dash').text('-');
                $('.add_to_network').addClass('button').text(zwt_main_i8n[2]).removeClass('active').blur();
                $('#zwt_get_blogid').attr('value', '');
                event.preventDefault();
            } else {
                $('.zwt_show_dash').text('-');
                $('.add_to_network').addClass('button').text(zwt_main_i8n[2]).removeClass('active');


                var buttonId = buttonSelected.attr('id');
                var idRegex = /[0-9]+/;
                var id = parseInt(buttonId.match(idRegex), 10); 
                var transIdPos = $('#2zwt_elements' + id);
                var langArrayPos = $('#1zwt_elements' + id);
                var userPos = $('#3zwt_elements' + id);
                transIdPos.text('');
                langArrayPos.text('');
                userPos.text('');
                transIdPos.prepend(zwt.transIdArray);
                langArrayPos.prepend(zwt.langArray);
                userPos.text(c_user);
                buttonSelected.removeClass('button').text(zwt_main_i8n[3]).addClass('active').blur();
                //add the selected ID to submit button value for identification of selected ID
                $('#zwt_get_blogid').attr('value', id);
                return false;
            }
        },
        advancedToolsAjax: function(event){
            var selected = $(this).attr('name');
            var data;
		 
            if(selected == 'zwt_reset_cache'){
                data = {
                    action: 'zwt_all_ajax',
                    admin_fn : selected,
                    cacheType: $('input[name="zwt_clear_cache"]:checked').val(),
                    _wpnonce: $('#zwt_advanced_tools').val()
                };
					
            }else if(selected == 'zwt_copy_taxonomy'){
                data = {
                    action: 'zwt_all_ajax',
                    admin_fn: selected,
                    fromBlog: $('#zwt_from_blog :selected').val(),
                    taxonomy: $('#zwt_taxonomy_name :selected').val(),
                    _wpnonce: $('#zwt_advanced_tools').val()
                };
					
            }else if(selected== 'zwt_reset_zanto'){
                if(!$('#zwt_reset_settings').attr('checked')){
                    alert(zwt_main_i8n[4]);
                    return;
                }
                var r=confirm(zwt_main_i8n[5]);
                if (!r==true){
                    return;
                }
                data = {
                    action: 'zwt_all_ajax',
                    admin_fn :selected,
                    _wpnonce: $('#zwt_advanced_tools').val()
                };
            }
		 
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                beforeSend: function () {
					$('#'+selected).after('<div class="zwt_ajax_response" style="display: inline-block; margin-left:10px"><span class="spinner" style="display: block; float: none;"></span></div>');                       
                },
                success: function (data) {
                    $('.zwt_ajax_response').html(data).fadeOut(1000, function(){});   
                }
            });
        },
		
		primaryLangAlert:function(){
            if(!confirm(zwt_settings_params[0])){
                for(var i=0; i<this.length; i++){
                    if(this[i].defaultSelected){
                        this.selectedIndex=i;
                    }
                }
            }
        },
		
        transNetworkPageStart: function () {
            $('#zwt_remove_elements').remove();
        },
        postPageStart: function () {
            $('.transln_method input').hide();
        },
        transln_method_change: function (event) {

            var inputSelected = $(this);
            var inputSelectedVal = inputSelected.val();
            var current_blog = inputSelected.attr('id');
            var idRegex = /[0-9]+/;
            var id = parseInt(current_blog.match(idRegex), 10);
            $('#transln_methd_div' + id + ' input').hide().attr('value', '');
            $('#transln_methd_div' + id + ' select').hide().attr('value', '');
            $('#transln_method_img' + id).hide();
            if (inputSelectedVal == 1) {
                $('#transln_method_img' + id).show();
            } else if (inputSelectedVal == 2) {
                var transSelectPost = $('#zwt_select_secondary' + id);
				var transSelectPostDiv = $('#zwt_select_secondary_div' + id);

				transSelectPost.show();
			    transSelectPostDiv.css('display','inline-block');

                if(!zwt.hasSelect2) {
                    if (zwt.selectSet[id] != 1) {

                        var data = {
                            action: 'zwt_all_ajax',
                            admin_fn: 'zwt_fetch_trans_posts',
                            blog_id: id,
                            post_type: typenow
                        };
                        $.get(ajaxurl, data, function (data) {
                            transSelectPost.prepend(data);
                            transSelectPostDiv.find('.spinner').hide();
                        });
                        zwt.selectSet[id] = 1;
                    }
                }
               
            } else if (inputSelectedVal == 3) {
                $('#transln_method_text' + id).show().attr('value', 'http://');
            }
        },
		
		overwriteAlert: function(e){
		if(this[this.selectedIndex].className=="translated"){
		    if(!confirm(zwt_main_i8n[6])){
				        this[this.selectedIndex].selected=false;
				        return false;
				   }
		}
		
		var cfp = $('.zwt_cfp');
			if(cfp.length!=0)
			   cfp.show();
		},
		
		copy4rmOriginal: function(){
		zwt_copy_from_original(document.getElementById('zwtprimaryblog').value, document.getElementById('zwt_select_primary').value);
		return false;
		}

    }; // end zwt

    $(document).ready(zwt.init);

} // end zwt_main()

zwt_main(jQuery);

function zwt_initSelects2(zwt)
{
    if(typeof zwt_preload === "undefined") {
        return;
    }

    document.querySelectorAll('select[id^=zwt_select_secondary]').forEach(function(s){
        var id = parseInt(s.id.match(/[0-9]+/), 10);

        if(typeof zwt_preload.preload[id] === "undefined") {
            return;
        }

        zwt_initSelect2(zwt, s, id, zwt_preload.preload[id]);
    });

    document.querySelectorAll('select#zwt_select_primary').forEach(function(s){
        if(typeof zwt_preload.preload[1] === "undefined") {
            return;
        }

        zwt_initSelect2(zwt, s, 1, zwt_preload.preload[1]);

    });
}

function zwt_initSelect2(zwt, node, blogId, preload) {
    node.innerHTML = '';

    jQuery(node).select2({
        dataAdapter: zwt.select2adapter,
        defaultResults: preload,
        minimumInputLength: 3,
        dropdownAutoWidth : true,
        width: 'auto',
        placeholder: "Выберите запись",
        allowClear: true,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                return {
                    action: 'zwt_all_ajax',
                    admin_fn: 'select2posts',
                    blog_id: blogId,
                    post_type: typenow,
                    q: params.term,
                    p: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 100) < data.total_count
                    }
                };
            }
        },
        escapeMarkup: function (markup) {
            return markup;
        },
        templateResult: function(post){
            if (post.loading) {
                return post.text;
            }
            var tranlsated = post.translated ? ' style="color: #888" ' : '';

            return '<div class="select2-option"><span  ' + tranlsated + '>' + post.text + '</span></div>';
        }
    });
}

function zwt_copy_from_original(blog_id, post_id) {
    //jQuery('#zwt_cfo').after(zwt_ajxloaderimg).attr('disabled', 'disabled');
    var copyButton = jQuery('#zwt_cfo');
	if(copyButton.attr('disabled')== 'disabled'){
	return false;
	}

    if (typeof tinyMCE != 'undefined' && (ed = tinyMCE.activeEditor) && !ed.isHidden()) {
        var editor_type = 'rich';
    } else {
        var editor_type = 'html';
    }

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'zwt_all_ajax',
            'admin_fn': 'zwt_copy_from_original_ajx',
            'b_id': blog_id,
            'p_id': post_id,
            'editor_type': editor_type,
            '_zwt_nonce': jQuery('#_zwt_nonce_cfo_' + post_id).val(),
            'type': typenow
        },
        dataType: 'JSON',
        success: function (msg) {
            if (msg.error) {
                alert(msg.error);
            } else {
                try { // we may not have the content 
                    if (typeof tinyMCE != 'undefined' && (ed = tinyMCE.activeEditor) && !ed.isHidden()) {
                        ed.focus();
                        if (tinymce.isIE)
                            ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
                        ed.execCommand('mceInsertContent', false, msg.body);
                    } else {
                        if (typeof wpActiveEditor == 'undefined') wpActiveEditor = 'content';
                        edInsertContent(edCanvas, msg.body);
                    }
					jQuery('#title').focus().attr('value', msg.title);
                } catch (err) {
                ;
                }
                copyButton.attr('disabled', true);

            }
        //   jQuery('#zwt_cfo').next().fadeOut();

        }
    });
    return false;
}