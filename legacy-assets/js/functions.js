jQuery(document).ready(function () {

    jQuery('.show-contact-form').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade contact-popup',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });

    jQuery('.ffd-mfp-show-popup').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade ffd-mfp-popup',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });


    jQuery('.ffd-mfp-private-popup').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade ffd-mfp-popup',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false,
        closeOnContentClick:false,
        closeOnBgClick: false,
        closeBtnInside: false,
        showCloseBtn: false,
        enableEscapeKey: false
    });

    jQuery('a[href="#contact-popup-container"]').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade contact-popup',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });
    jQuery('a[href="#menu-contact-popup-container"]').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade contact-popup',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });
    jQuery('.show-contact-form-mini').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade contact-popup-mini',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });

    jQuery('.mortgage-cal-btn').magnificPopup({
        disableOn: 200,
        type: 'inline',
        mainClass: 'mfp-fade contact-popup-mini',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false
    });
    
    jQuery('a.virtualtourpopup').magnificPopup({
        type: 'inline',
        mainClass: 'mfp-fade virtualtouriframe contact-popup',
        preloader: true,
        callbacks: {
            open: function() {
                var selector = this.currItem.src;
                
                $content = jQuery(selector);
                var $item = $content.find('#ffd-popup-iframe');
                var html='<iframe ';
                jQuery.each($item.data(), function(key, value){
                    html+= ' ' + key + '="' + value + '" '
                });
                html +='</iframe>';

                var contentHTML = this.content[0].innerHTML;
                contentHTML = jQuery(contentHTML)
                contentHTML.find("#ffd-popup-iframe").html(html);

                //$content.html(html);
                this.content[0].innerHTML = contentHTML.html();

            },
            close: function() {
               var selector = this.items[0].src;
               $content = jQuery(selector);
               $content.find('#ffd-popup-iframe').html('');

            },
        }
        
    });

    jQuery("#apply-filters").click(function (event) {
        event.preventDefault();
        jQuery(".advance-search-menu-overlay").show();
        jQuery(".advance-search-filters-container").addClass("menushow");
    });

    //jQuery(".advance-search-filters-container.menushow .close-button, .advance-search-menu-overlay").click(function(){

    jQuery(".close-button, .advance-search-menu-overlay").on('click', function () {
        jQuery(".advance-search-filters-container").removeClass("menushow");
        jQuery(".advance-search-menu-overlay").hide();
    });
    //jQuery('body').on("click", ".selectcon",function(e){
    //     jQuery(this).parent('.niceSelect').children('.niceselect').trigger("click");
    // });

    jQuery('.scrollTo').click(function () {
        jQuery('html, body').animate({
            scrollTop: jQuery("#" + jQuery(this).data('scroll-to')).offset().top
        }, 1000);
    });


   

    jQuery('#ihf-map-toggle-button').on('click', function(){
        
        var $this = jQuery(this);
        var target = $this.data('toggletarget');
        var height = $this.data('toggleheight');
        jQuery(target).toggleClass('tk-hidden-mobile')

       
    });

});

function searchform_clear(e) {
    if (window.location.href.indexOf("?") > 0)
        window.location = "/find-property";

    clearPoly();
    mapData = "";

    $('#search_form input').each(function () {
        var type = $(this).attr('type');
        if ('checkbox' == type || 'radio' == type) {
            $(this).prop('checked', false);
        } else if ('hidden' == $(this).attr('type')) {

        } else {
            $(this).val('');
        }
    });
    $('#search_form select').each(function () {
        $(this).val(0);
    });
    $("#search_form input[name='status[]'][value='Active']").prop('checked', true);;
    $("#search_form input[name='status[]'][value='Contingent']").prop('checked', true);
    $('#currentpage').val(0);
    $(".scroll-pane").scrollTop(0);
    more_results_to_load = true
    update_search_results(e);
}
if (typeof initMap !== 'function') {
    function initMap() {
        jQuery(document).ready(function () {
            // console.log("reached here: " + typeof showMap);
            if (typeof showMap === 'function') {
                // console.log("here too");
                showMap();
            }
        });
    }
}
(function ($) {
    $.fn.hasClassRegEx = function (regex) {
        var classes = $(this).attr('class');

        if (!classes || !regex) { return false; }

        classes = classes.split(' ');
        var len = classes.length;

        for (var i = 0; i < len; i++) {
            if (classes[i].match(regex)) { return true; }
        }

        return false;
    };
})(jQuery);


jQuery(function ($) {

    function adjust_single_proper_slider_image() {

        var single_proper_slides = jQuery(document).find('.single-property .property-slider-container .gallery-top .swiper-slide');
        var slide_image, slide_width, image_width, half_width;
        single_proper_slides.each(function () {
            slide_image = jQuery(this).find('img');
            slide_width = jQuery(this).width();
            image_width = slide_image.width();
            half_width = slide_width / 2;
            //slide_image.attr('data-width', image_width + ', ' + image_width + ', ' + half_width)
            if (image_width < half_width) {
                slide_image.removeClass('slider-img-full').addClass('slider-img-portrait').css('visibility', 'visible');
            } else {
                slide_image.removeClass('slider-img-portrait').addClass('slider-img-full').css('visibility', 'visible');
            }


        });

    }

    adjust_single_proper_slider_image();


     function ffdl_get_uri_param(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
          return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
          return uri + separator + key + "=" + value;
        }
      }

      function ffdl_adjust_embeds(){
        
        jQuery('iframe[data-src]').each(function(e){
            var $iframe = jQuery(this);
            var iframe_url = $iframe.attr('data-src');
            if( iframe_url && iframe_url !== 'undefined' ){
                $iframe.attr('src', iframe_url);
            }
        });
      }
      ffdl_adjust_embeds();





    jQuery(window).on('resize', function () {

        adjust_single_proper_slider_image();

         
    });
});



jQuery(function($){

   var videos  = $(".video-element");

    $(document).on('click', '.video-element', function(){
        var elm = $(this),
            conts   = elm.contents(),
            le      = conts.length,
            ifr     = null;

        for(var i = 0; i<le; i++){
        if(conts[i].nodeType == 8) ifr = conts[i].textContent;
        }

        elm.addClass("player loading").html(ifr);
        elm.off("click");
    });


if( $('.ui-content-gallery').length > 0 ){
    $('.ui-content-gallery').magnificPopup({
        delegate: 'a',
        type: 'image',
        tLoading: 'Loading image #%curr%...',
        mainClass: 'mfp-ui-content-img',
        gallery: {
        enabled: true,
        navigateByImgClick: true,
        preload: [0,1] // Will preload 0 - before current, and 1 after the current image
        },
        image: {
        tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
        titleSrc: function(item) {
            //return item.el.attr('title') + '<small>by Marsel Van Oosten</small>';
        }
        }
    });
}

function ffd_swiper_carousel_controls(instance1, instance2){

    instance1.controller.control = instance2;
    instance2.controller.control = instance1;

}

function ffd_swiper_slider_with_data_atts(){

    if (jQuery('body').find('.swiper-atts-slider').length > 0) {

        var swiperAttsSliderInstances = {};
        var swiperAttsSliderNames = {};

        $('body').find(".swiper-atts-slider").each(function (index, element) {

            var $this = $(this);
            var atts = $(this).data();
            var name = atts['name'];

            $this.addClass("atts-slider-instance-" + index);
           
           
            var slidesPerView = atts['slides_perview'] || 5;
            var loopedSlides = atts['looped_slides'] || null;

            var slidesPerGroup = atts['slides_pergroup'] || 1;
            var slidesOffsetBefore = atts['slides_offsetbefore'] || 0;
            var slidesOffsetAfter = atts['slides_offsetafter'] || 0;
            var spaceBetween = atts['space_between'] || 0;
            var lazy = atts['lazy'] || false;
            var touchRatio = atts['touchRatio'] || 0.2;

            var centeredSlides = false;
           if(  atts['centered_slides'] == 'yes' ||  atts['centered_slides'] == 1 ){
            centeredSlides = true;
           }
           

           var slideToClickedSlide = false;
           if(  atts['click_to_slide'] == 'yes' ||  atts['click_to_slide'] == 1 ){
               loop = true;
           }

            var loop = true;
            if(  atts['loop'] == 'no' ||  atts['loop'] == 0 ){
                loop = false;
            }
            
            var show_nav = false;
           if(  atts['show_nav'] == 'yes' ||  atts['show_nav'] == 1 ){
               show_nav = true;
           }

           var free_mode =  false;
           if(  atts['free_mode'] == 'yes' ||  atts['free_mode'] == 1 ){
                free_mode = true;
            }

            var loop_fill =  false;
            if(  atts['loop_fill'] == 'yes' ||  atts['loop_fill'] == 1 ){
                loop_fill = true;
             }

             var grab_cursor =  false;
            if(  atts['grab_cursor'] == 'yes' ||  atts['grab_cursor'] == 1 ){
                grab_cursor = true;
             }

             

           var md_slides_perview = atts['md_slides_perview'] || slidesPerView;
           var sm_slides_perview = atts['sm_slides_perview'] || md_slides_perview || 4 ;
           var xs_slides_perview = atts['xs_slides_perview'] || sm_slides_perview || 3;

           var md_space_between = atts['md_space_between'] || spaceBetween;
           var sm_space_between = atts['sm_space_between'] || md_space_between || 0;
           var xs_space_between = atts['xs_space_between'] || sm_space_between || 0;

           var breakpoints = {};

           breakpoints['767'] = { slidesPerView : xs_slides_perview, spaceBetween : xs_space_between}
           breakpoints['991'] = { slidesPerView : sm_slides_perview, spaceBetween : sm_space_between}
           breakpoints['1024'] = { slidesPerView : md_slides_perview, spaceBetween : md_space_between}
          


            $this.closest('.swiper-atts-slider').find(".swiper-button-prev").addClass("atts-slider-btn-prev-" + index);
            $this.closest('.swiper-atts-slider').find(".swiper-button-next").addClass("atts-slider-btn-next-" + index);

            var settings = {
                slideToClickedSlide:slideToClickedSlide,
                centeredSlides: centeredSlides,
                slidesPerView: slidesPerView,
                loopedSlides: loopedSlides,
                slidesPerGroup: slidesPerGroup,
                slidesOffsetBefore: slidesOffsetBefore,
                slidesOffsetAfter: slidesOffsetAfter,
                spaceBetween: spaceBetween,
                loop: loop,
                lazy: lazy,
                freeMode: free_mode,
                loopFillGroupWithBlank: loop_fill,
                grabCursor: grab_cursor,
                touchRatio: touchRatio,
                breakpoints: breakpoints
            };
            
            if( show_nav ){

                settings['navigation'] = {
                    nextEl: ".atts-slider-btn-next-" + index,
                    prevEl: ".atts-slider-btn-prev-" + index,
                    clickable: true,
                };
            }

            swiperAttsSliderInstances[index] = new Swiper(".atts-slider-instance-" + index, settings);

            if( name !== '' ){
                swiperAttsSliderNames[name] = index;
            }

        });

        $('body').find(".swiper-atts-slider").each(function (index, element) {

            var $this = $(this);
            var atts = $(this).data();
            var name = atts['name'] || '';
            var controller = atts['controller'] || '';

            if( name !== '' && controller != ''){

                if(typeof(swiperAttsSliderNames[name]) !== "undefined" && typeof(swiperAttsSliderNames[controller])  !== "undefined"  ){
                    var instanceIndex1 = swiperAttsSliderNames[name];
                    var instanceIndex2 = swiperAttsSliderNames[controller];

                    var instance1 = swiperAttsSliderInstances[instanceIndex1] ;
                    var instance2 = swiperAttsSliderInstances[instanceIndex2] ;

                    ffd_swiper_carousel_controls(instance1, instance2);
                }
              

            }

        });


       
    }


}
ffd_swiper_slider_with_data_atts();
    
jQuery('body').on('ffd_refresh_atts_slider', function(){
    ffd_swiper_slider_with_data_atts();
});




jQuery(document).on('click', '.ffd-save-favorite', function (e) {
var $_this = jQuery(this);
var iconClass  = $_this.data('iconclass');
var favClass  = $_this.data('favClass');
var toggleClass = $_this.data('toggleclass');

if( ffdl_vars.logged_in != '1' ){
    var loginurl = ffdl_vars.login_url;
    if( $_this.data('loginurl') !== 'undefined' )
        loginurl = $_this.data('loginurl') || '/login';

    window.location = loginurl;
    return false;
}

if( typeof iconClass === 'undefined' || iconClass === '' )
    iconClass = 'fa-heart';

if( typeof favClass === 'undefined' || favClass === '' )
    favClass = 'fa-heartbeat';

if( typeof toggleClass === 'undefined' || toggleClass === '' )
    toggleClass = 'fa-spinner fa-spin';


var text = $_this.text();
if ($_this.hasClass('btn')) {
    if ($_this.find('.label').text() == 'Loading...' || $_this.hasClass('link-login')) {
        return;
    } else {
        $_this.find('.label').text('Loading...');
    }
} else {
    if ($_this.hasClass('icon')) {

        var $i2svg = $_this.children('.svg-inline--fa');

        if ( $i2svg.length > 0 ) {
            if ($i2svg.hasClass('fa-heartbeat')) {
                $i2svg.attr('data-icon', 'spinner');
                $i2svg.toggleClass('fa-heartbeat fa-spin');
            } else {
                $i2svg.attr('data-icon', 'spinner');
                $i2svg.toggleClass('fa-heart fa-spin');
            }

        } else {
        
            if ($_this.children('i').hasClass(favClass)) {
                $_this.children('i').toggleClass( favClass + ' ' + toggleClass);
            }
            else {
                $_this.children('i').toggleClass( iconClass + ' ' + toggleClass);
            }
        }
    }
    else {
        $_this.text("...");
    }
}

var data = {
    'prop_id': $_this.attr('data-prop-id'),
    'action': 'ffd_set_favorite'
};
jQuery.ajax({
    url: ffdl_vars.ajaxurl,
    type: 'POST',
    cache: false,
    data: data,
    success: function (response) {
        if (true == response.success) {
            if (true == response.data.favorite) {
                if ($_this.hasClass('icon')) {
                    var $i2svg = $_this.children('.svg-inline--fa');
                    if ( $i2svg.length > 0 ) {
                        $i2svg.attr('data-icon', 'heartbeat');
                        $i2svg.toggleClass('fa-heartbeat fa-spin')
                    } else {
                        $_this.children('i').toggleClass( favClass + ' ' + toggleClass );
                    }    
                    $_this.attr('title', 'Remove from Favorites');
                }
                else {
                    $_this.find('.bt-heart').removeClass('btm');
                    $_this.find('.bt-heart').addClass('bts');
                    $_this.text(text);
                    //console.log('is favorite');
                }
            } else {
                if ($_this.hasClass('icon')) {
                    var $i2svg = $_this.children('.svg-inline--fa');
                    if ( $i2svg.length > 0 ) {
                        $i2svg.attr('data-icon', 'heart');
                        $i2svg.toggleClass('fa-heart fa-spin')
                    } else {
                        $_this.children('i').toggleClass(iconClass + ' ' + toggleClass);
                    }
                    $_this.attr('title', 'Add to Favorites');
                }
                else {
                    $_this.find('.bt-heart').removeClass('bts');
                    $_this.find('.bt-heart').addClass('btm');
                    $_this.text(text);
                    //console.log('not favorite');
                }
            }
        }
    },
    error: function (errorThrown) {
        $_this.parents('.item').removeClass('busy');

        if ($_this.hasClass('icon')) {
            var $i2svg = $_this.children('.svg-inline--fa');
            if ( $i2svg.length > 0 ) {
                $i2svg.attr('data-icon', 'heart');
                $i2svg.toggleClass('fa-heart fa-spin')
            } else {
                $_this.children('i').toggleClass(iconClass + ' ' + toggleClass);
            }
            $_this.attr('title', 'Add to Favorites');
        }
        else {
            $_this.find('.bt-heart').removeClass('bts');
            $_this.find('.bt-heart').addClass('btm');
            $_this.text(text);
            //console.log('not favorite');
        }
        
    },
    complete: function () {
        $_this.parents('.item').removeClass('busy');
    }
});

return false;

});

function ffd_update_savesearch_list(data) {
    if( jQuery('.ffd-savesearch-list').length > 0 && data !== '' && data !== 'undefined'){
        var list = '';
        for (var i = 0; i < data.length; i++) {
            list += '<li><a href="' +
                jQuery('.ssearch-list').attr('data-search-url') +
                '?' + data[i].search_options +
                '">' + data[i].name +
                '</a>' +
                /*'<a class="ssearch-delete" data-name="' + 
                data[i].name + '" title="Delete this saved search">x</a>' +*/
                '</li>';
        }
        jQuery('.ffd-savesearch-list').html(list);
    }

}



function ffd_savesearch(action, value, name, date, callback){

    
    var data = null;
    if( action == 'save'){
        
        if( !date || date=== 'undefined' ){
            date = new Date();
            date = date.toLocaleString();
        }

        
        if( !name || name=== 'undefined' ){
            name = 'savesearch-' + date;
        }
        
        data = {
            'name': name,
            'form_data': value,
            'added': date,
            'action': 'ffd_savesearch',
            'command':  action
        }

        
    } else if(action == 'delete' && name !== '' && name !== 'undefined'){

        data = {
            'name': name,
            'action': 'ffd_savesearch',
            'command': action
        }

    } else if(action == 'del' && value !== '' && value !== 'undefined'){

        data = {
            'id': value,
            'action': 'ffd_savesearch',
            'command': action
        }

    }

    if( !data || data === null)
        return;

    jQuery.ajax({
        url: ffdl_vars.ajaxurl,
        type: 'POST',
        cache: false,
        data: data,
        success: function (response) {
            if (true == response.success) {
                ffd_update_savesearch_list(response.data);
            } else {
                console.log('response.success expected to be true:');
                console.log(response);
            }
            jQuery(document).trigger('ffd_savesearch_response', [action, response]);
            if( typeof callback ==='function' ){
                callback(action, response);
            }
        },
        error: function (errorThrown) {
            jQuery(document).trigger('ffd_savesearch_error', [action, errorThrown]);
            if( typeof callback ==='function' ){
                callback(action, errorThrown);
            }
            console.log('Ajax error:', errorThrown);
        }
    });

}

jQuery(document).on('ffd_savesearch', function(action, value, name, date, callback){

    ffd_savesearch(action, value, name, date, callback);

});

jQuery(document).on('click', '#ffd-save-search', function(e){
    e.preventDefault();
    var $this = $(this);
    if( $this.attr('data-form') !== 'undefined' && $($this.attr('data-form')).length > 0 ){

        var $form = $($this.attr('data-form'));
        var data = $form.serialize();
        var date = new Date();
        date = date.toLocaleString();

        var name = $('#ffd-savesearch-name').val();
        ffd_savesearch('save', data, name, date, function(){

        });
    
    }

    return false;
});

jQuery('.ffd-savesearch-delete').click(function (e) {
    e.preventDefault();
    var $this = $(this);
    if( $this.attr('data-name') !== 'undefined'  ){
        var name = $this.attr('data-name')
        ffd_savesearch('delete', null, name, null, function(){

        });
    
    } else if( $this.attr('data-id') !== 'undefined'  ){
        var id = $this.attr('data-id')
        ffd_savesearch('del', id, null, null, function(){
            $this.closest('tr').remove();
        });

    }

    return false;
});


});