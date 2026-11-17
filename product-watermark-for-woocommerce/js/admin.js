var br_saved_timeout;
var br_savin_ajax = false;
(function ($){
    function updateComparisonLogo($editor, url) {
        var $logo = $editor.find('.br-watermark-comparison-logo');
        if (url) {
            $logo.attr('src', url);
        } else {
            $logo.removeAttr('src');
        }
        $logo.prop('hidden', !url);
    }

    function updateComparisonPlacement($editor) {
        var id = $editor.find('.br_watermark').data('id');
        var width = Number($editor.find('.' + id + '_width_input').val());
        var height = Number($editor.find('.' + id + '_height_input').val());
        var left = Math.min(Number($editor.find('.' + id + '_left_input').val()), 100 - width);
        var top = Math.min(Number($editor.find('.' + id + '_top_input').val()), 100 - height);
        var keepRatio = $editor.find('.br-watermark-ratio').prop('checked');
        // Match the renderers' alignment of an aspect-ratio-preserving logo within its box.
        var position = width < 100 && height < 100
            ? (100 * left / (100 - width)) + '% ' + (100 * top / (100 - height)) + '%'
            : 'center';
        $editor.find('.br-watermark-comparison-logo').css({
            width: width + '%',
            height: height + '%',
            left: left + '%',
            top: top + '%',
            objectFit: keepRatio ? 'contain' : 'fill',
            objectPosition: position
        });
    }

    function showSmallLogoWarning($editor, isSmall) {
        $editor.toggleClass('br-watermark-small-logo', isSmall);
        $editor.find('.br-watermark-logo-warning').prop('hidden', !isSmall);
    }

    $(document).ready( function () {
        // Display-only: never submit this choice or trigger image processing.
        $('.br-watermark-preview').each(function () {
            var $editor = $(this);
            updateComparisonLogo($editor, $editor.find('.berocket_image_value').val());
            updateComparisonPlacement($editor);
        });
        $('.br-watermark-preview-controls, .br-watermark-transparency-presets').prop('hidden', false);
        $(document).on('click', '.br-watermark-comparison button', function () {
            var mode = $(this).data('mode');
            $(this).closest('.br-watermark-comparison').attr('data-mode', mode).find('button').each(function () {
                var active = $(this).data('mode') === mode;
                $(this).attr('aria-pressed', String(active)).toggleClass('button-primary', active);
            });
        });
        $(document).on('change', '.br-watermark-ratio', function () {
            updateComparisonPlacement($(this).closest('.br-watermark-preview'));
        });
        $(document).on('click', '.br-watermark-transparency-presets button', function () {
            $(this).closest('.br_field').find('.br-watermark-transparency:enabled')
                .val($(this).data('transparency')).trigger('input').trigger('change');
        });
        $(document).on('change', '.br-watermark-preview .berocket_image_value', function () {
            var $input = $(this);
            var $editor = $input.closest('.br-watermark-preview');
            var $details = $editor.find('.br-watermark-image-details');
            var pending = $input.data('br-details-request');
            // Forget the old request before aborting: its callback must not overwrite a new choice.
            $input.removeData('br-details-request');
            if (pending) {
                pending.abort();
            }
            var url = $input.val();
            updateComparisonLogo($editor, url);
            showSmallLogoWarning($editor, false);
            $details.text(url ? berocketImageWatermark.detailsLoading : '').prop('hidden', !url);
            if (!url) {
                return;
            }
            var request = $.post(ajaxurl, {
                action: 'berocket_watermark_image_details',
                _ajax_nonce: berocketImageWatermark.detailsNonce,
                url: url
            });
            $input.data('br-details-request', request);
            request.done(function (response) {
                if ($input.data('br-details-request') === request) {
                    var details = response && response.success && response.data;
                    $details.text(details && typeof details.label === 'string'
                        ? details.label : berocketImageWatermark.detailsUnavailable);
                    showSmallLogoWarning($editor, Boolean(details && typeof details.width === 'number' && details.width < 64));
                }
            }).fail(function () {
                if ($input.data('br-details-request') === request) {
                    $details.text(berocketImageWatermark.detailsUnavailable);
                }
            }).always(function () {
                if ($input.data('br-details-request') === request) {
                    $input.removeData('br-details-request');
                }
            });
        });
        $(document).on('change', '.br-watermark-preview-background', function () {
            var background = $(this).val();
            if ($.inArray(background, ['white', 'dark', 'checkerboard']) === -1) {
                background = 'white';
                $(this).val(background);
            }
            $(this).closest('.br-watermark-preview').attr('data-background', background);
        });
        function destroy_br_saved() {
            $('.br_saved').addClass('br_saved_remove');
            var $get = $('.br_saved');
            setTimeout( function(){$get.remove();}, 200 );
        }
        jQuery(document).on('click', '.attachments .attachment', function() {
            if( !br_savin_ajax ) {
                var data = $('.attachments-browser .attachments .attachment.selected');
                $('.berocket_image_watermark_restore').data('length', data.length);
                BeRocket_set_watermark_restore_position(data.length);
            }
        });
        function berocket_watermarks_ajax_start() {
            br_savin_ajax = true;
            clearTimeout(br_saved_timeout);
            destroy_br_saved();
            $('body').append('<span class="br_saved"><i class="fa fa-refresh fa-spin"></i></span>');
            $('.berocket_image_watermark_restore .berocket_watermark_ready').hide();
            $('.berocket_image_watermark_restore .berocket_watermark_error').hide();
            $('.berocket_image_watermark_restore .berocket_watermark_spin').show();
        }
        function berocket_watermarks_ajax_stop_error(data) {
            $('.berocket_image_watermark_restore .berocket_watermark_spin').hide();
            $('.berocket_image_watermark_restore .berocket_watermark_error').show();
            if($('.br_saved').length > 0) {
                $('.br_saved').addClass('br_not_saved').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-times');
            } else {
                $('body').append('<span class="br_saved br_not_saved"><i class="fa fa-times"></i></span>');
            }
            br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
            if( data && data.responseText ) {
                $('.br_save_error').text(data.responseText);
            }
            br_savin_ajax = false;
        }
        $(document).on( 'click', '.br_create_restore_image.global', function(event) {
            event.preventDefault();
            var url = ajaxurl;
            if( !br_savin_ajax ) {
                berocket_watermarks_ajax_start();
                var image_list = $(this).data('image_list');
                var generation = $(this).data('generation');
                var form_data = {
                    action: 'berocket_get_watermark_images',
                    generation: image_list,
                    _ajax_nonce: berocketImageWatermark.nonce
                };
                if( jQuery('.berocket_watermark_error_messages').length ) {
                    jQuery('.berocket_watermark_error_messages').html('');
                }
                $.post(url, form_data, function (data) {
                    var data_length;
                    if ( typeof data == 'object' ) {
                        data_length = Object.keys(data).length;
                    } else {
                        data_length = data.length;
                    }
                    $('.berocket_image_watermark_restore').data('length', data_length);
                    BeRocket_set_watermark_restore_position(data_length);
                    $('.berocket_watermark_load').show();
                    berocket_restore_images(data, generation);
                }, 'json').fail(function(data) {
                    berocket_watermarks_ajax_stop_error();
                });
            }
        });
        $(document).on( 'click', '.br_create_restore_image.media', function(event) {
            event.preventDefault();
            if( !br_savin_ajax ) {
                if( jQuery('.berocket_watermark_error_messages').length ) {
                    jQuery('.berocket_watermark_error_messages').html('');
                }
                berocket_watermarks_ajax_start();
                var generation = $(this).data('generation');
                var data = [];
                $('.attachments-browser .attachments .attachment.selected').each(function() {
                    data.push($(this).data('id'));
                });
                $('#posts-filter #the-list .check-column input[name="media[]"]:checked').each(function() {
                    data.push($(this).val());
                });
                $('.berocket_image_watermark_restore').data('length', data.length);
                BeRocket_set_watermark_restore_position(data.length);
                $('.berocket_watermark_load').show();
                berocket_restore_images(data, generation);
            }
        });
        var berocket_restore_create_stop = false;
        $(document).on( 'click', '.br_create_restore_image_stop', function(event) {
            berocket_restore_create_stop = true;
        });
        function berocket_restore_images(images, generation) {
            if ( typeof images == 'object') {
                images = Object.values(images)
            }
            var image_id = images.pop();
            if( typeof(image_id) == 'undefined' || berocket_restore_create_stop ) {
                berocket_restore_create_stop = false;
                $('.berocket_image_watermark_restore .berocket_watermark_spin').hide();
                $('.berocket_image_watermark_restore .berocket_watermark_ready').show();
                if($('.br_saved').length > 0) {
                    $('.br_saved .fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-check');
                } else {
                    $('body').append('<span class="br_saved"><i class="fa fa-check"></i></span>');
                }
                br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                br_savin_ajax = false;
            } else {
                var form_data = {
                    action: 'berocket_single_image',
                    id: image_id,
                    generation: generation,
                    _ajax_nonce: berocketImageWatermark.nonce
                };
                $.post(ajaxurl, form_data, function (response) {
                    BeRocket_set_watermark_restore_position(images.length);
                    berocket_restore_images(images, generation);
                    if(
                        jQuery('.berocket_watermark_error_messages').length
                        && response
                        && response.data
                        && response.data.message
                    ) {
                        jQuery('<div>').text(response.data.message).appendTo('.berocket_watermark_error_messages');
                    }
                }).fail(function(data) {
                    BeRocket_set_watermark_restore_position(images.length);
                    berocket_restore_images(images, generation);
                });
            }
        }
        function BeRocket_set_watermark_restore_position(images_left) {
            var images_count = $('.berocket_image_watermark_restore').data('length');
            var images_ready = images_count - images_left;
            position = 100 / images_count * images_ready;
            $('.berocket_watermark_load .berocket_line').finish().css('width', position+'%');
            $('.berocket_watermark_load .berocket_watermark_action').text(images_ready+' / '+images_count);
        }
        $(document).on('change', '.br_wm_img_count', br_wm_img_count_hide);
        function br_wm_img_count_hide() {
            $('.br_wm_img_count').each(function() {
                var wmimg = $(this).data('wmimg');
                    console.log('.'+wmimg+'.berocket_image_count_'+i);
                for(var i = 0; i < 5; i++) {
                    if( i > $(this).val() ) {
                        $('.'+wmimg+'.berocket_image_count_'+i).hide();
                    } else {
                        $('.'+wmimg+'.berocket_image_count_'+i).show();
                    }
                }
            });
        }
        br_wm_img_count_hide();
    });

    function drop_for_10(value) {
        if( value % 10 >= 5 ) {
            value += 10 - value % 10;
        } else {
            value -= value % 10;
        }
        return value;
    }
    $(document).ready( function () {
        if( jQuery( ".br_watermark" ).length ) {
            jQuery( ".br_watermark" )
            .resizable({
                containment: "parent",
                grid: 10,
                aspectRatio: 1 / 1,
                minHeight: 30,
                minWidth: 30,
                stop: function( event, ui ) {
                    var height = ui.size.height;
                    var width = ui.size.width;
                    var id = $(ui.element).data('id');
                    var parent = $(ui.element).parent();
                    var parent_height = parent.height();
                    var parent_width = parent.width();
                    height = drop_for_10(height);
                    width = drop_for_10(width);
                    parent_height = drop_for_10(parent_height);
                    parent_width = drop_for_10(parent_width);
                    var width_p = parseInt(width / parent_width * 100);
                    var height_p = parseInt(height / parent_height * 100);
                    $('.'+id+'_width').text(width_p);
                    $('.'+id+'_height').text(height_p);
                    $('.'+id+'_width_input').val(width_p);
                    $('.'+id+'_height_input').val(height_p);
                    updateComparisonPlacement($(this).closest('.br-watermark-preview'));
                }
            })
            .draggable({
                containment: "parent", 
                scroll: false,
                grid: [10, 10],
                stop: function( event, ui ) {
                    var top = ui.position.top;
                    var left = ui.position.left;
                    var id = $(this).data('id');
                    var parent = $(this).parent();
                    var parent_top = parent.height();
                    var parent_left = parent.width();
                    top = drop_for_10(top);
                    left = drop_for_10(left);
                    parent_top = drop_for_10(parent_top);
                    parent_left = drop_for_10(parent_left);
                    var top_p = parseInt(top / parent_top * 100);
                    console.log(parent_top);
                    var left_p = parseInt(left / parent_left * 100);
                    $('.'+id+'_top').text(top_p);
                    $('.'+id+'_left').text(left_p);
                    $('.'+id+'_top_input').val(top_p);
                    $('.'+id+'_left_input').val(left_p);
                    updateComparisonPlacement($(this).closest('.br-watermark-preview'));
                }
            });
        }
    });
})(jQuery);
