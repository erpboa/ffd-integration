var FFD_Liquid = new Liquid();
(function($, Settings, History, Liquid, Cookies){



var WP_LOOP = {};

function human_time_diff(from, to){ /*accept unix-datetime integer, Date object or date string, fallback is current date*/
    "use strict";

    var now, diff;

    /*---*/
    function difference(diff){
      var
           MINUTE_IN_SECONDS = 60
         , HOUR_IN_SECONDS   = 60  * MINUTE_IN_SECONDS
         , DAY_IN_SECONDS    = 24  * HOUR_IN_SECONDS
         , MONTH_IN_SECONDS  = 30  * DAY_IN_SECONDS
         , YEAR_IN_SECONDS   = 365 * DAY_IN_SECONDS
         , since
         , milliseconds, seconds, minutes, hours, days, months, years
         ;

      if(0 === diff){
        since = "0 seconds";
      }
      else if(diff > 0 && diff < 1){
        milliseconds = Math.trunc(diff * 1000);
        since = milliseconds + " " + (1 === milliseconds ? "millisecond" : "milliseconds");
      }
      else if(diff >= 1 && diff < MINUTE_IN_SECONDS){
        seconds = Math.trunc(diff);
        seconds = Math.max(diff, 1);
        since = seconds + " " + (1 === seconds ? "second" : "seconds");

        diff = diff - (seconds);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }
      else if(diff >= MINUTE_IN_SECONDS && diff < HOUR_IN_SECONDS){
        minutes = Math.trunc(diff / MINUTE_IN_SECONDS);
        minutes = Math.max(minutes, 1);
        since = minutes + " " + (1 === minutes ? "minute" : "minutes");

        diff  = diff - (minutes * MINUTE_IN_SECONDS);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }
      else if(diff >= HOUR_IN_SECONDS && diff < DAY_IN_SECONDS){
        hours = Math.trunc(diff / HOUR_IN_SECONDS);
        hours = Math.max(hours, 1);
        since = hours + " " + (1 === hours ? "hour" : "hours");

        diff  = diff - (hours * HOUR_IN_SECONDS);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }
      else if(diff >= DAY_IN_SECONDS && diff < MONTH_IN_SECONDS){
        days = Math.trunc(diff / DAY_IN_SECONDS);
        days = Math.max(days, 1);
        since = days + " " + (1 === days ? "day" : "days");

        diff  = diff - (days * DAY_IN_SECONDS);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }
      else if(diff >= MONTH_IN_SECONDS && diff < YEAR_IN_SECONDS){
        months = Math.trunc(diff / MONTH_IN_SECONDS);
        months = Math.max(months, 1);
        since = months + " " + (1 === months ? "month" : "months");

        diff  = diff - (months * MONTH_IN_SECONDS);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }
      else if (diff >= YEAR_IN_SECONDS){
        years = Math.trunc(diff / YEAR_IN_SECONDS);
        years = Math.max(diff, 1);
        since = years + " " + (1 === years ? "year" : "years");

        diff  = diff - (years * YEAR_IN_SECONDS);
        if(diff > 0)
          since = since + ", " + difference(diff);                             /* calculate leftover time, recursively */
      }

      return since;
    }
    /*---*/

    now  = new Date();

    from = ("number" === typeof from)                                                   ? Math.max(from, 0)      :
           ("string" === typeof from)                                                   ? Number(new Date(from)) :
           ("object" === typeof from && "date" === from.constructor.name.toLowerCase()) ? Number(from)           : Number(now)
                                                                                        ;

    to   = ("number" === typeof to)                                                     ? Math.max(to, 0)        :
           ("string" === typeof to)                                                     ? Number(new Date(to))   :
           ("object" === typeof to && "date" === to.constructor.name.toLowerCase())     ? Number(to)             : Number(now)

    if("nan" === String(from).toLowerCase())  throw new Error("Error While Converting Date (first argument)" );
    if("nan" === String(to).toLowerCase())    throw new Error("Error While Converting Date (second argument)");

    diff = Math.abs(from - to);

    return difference(diff);
  }

$.fn.ffdSerializeObject = function(){

    var self = this,
        json = {},
        push_counters = {},
        patterns = {
            "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
            "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
            "push":     /^$/,
            "fixed":    /^\d+$/,
            "named":    /^[a-zA-Z0-9_]+$/
        };


    this.build = function(base, key, value){
        base[key] = value;
        return base;
    };

    this.push_counter = function(key){
        if(push_counters[key] === undefined){
            push_counters[key] = 0;
        }
        return push_counters[key]++;
    };

    $.each($(this).serializeArray(), function(){

        // skip invalid keys
        if(!patterns.validate.test(this.name)){
            return;
        }



        var k,
            keys = this.name.match(patterns.key),
            merge = this.value,
            reverse_key = this.name;

        while((k = keys.pop()) !== undefined){

            // adjust reverse_key
            reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

            // push
            if(k.match(patterns.push)){
                merge = self.build([], self.push_counter(reverse_key), merge);
            }

            // fixed
            else if(k.match(patterns.fixed)){
                merge = self.build([], k, merge);
            }

            // named
            else if(k.match(patterns.named)){
                merge = self.build({}, k, merge);
            }
        }

        json = $.extend(true, json, merge);
    });

    return json;
};

function FFD_Search(){

var Search = {

    vars: {
        map: false,
        bounds: false,
        idle: false,
        markers: [],
        clusterMarkers: [],
        useClusterer: false,
        clusterCondition: '',
        markerClusterer:null,
        infoWindow: null,
        clusterInfoWindow: null,
        infowindows: {},
        last_visible_iw: false,
        drawingmanager: {},
        shapes: [],
        mapData: "",
        dataloading: false,
        eventstatus: '',
        doingsearch: false,
        data:null,
        search_result:null,
        form: false,
        formid:'',
        postedfields:{},
        debug: false,
        result_meta:{},
        paginator : '',
        page : 1,
        number_properties : 0,
        data_search: [],
        data_map: null,
        data_geoco: null
    },

    current: {
        marker: false,
        iw: false,
        marker_index:false,
        iw_index:false,
        visible_iw:false
    },

    events: {
        initMapReady:function(e){

            Search.log('initMapReady ( run onetime when data/markers loaded the first time) ');

            // run once when map is ready with data/markers loaded the first time
            // after initialization

            if( Search.vars.map ){


                if( Settings.lat && Settings.lng && Settings.lat !== undefined &&  Settings.lng !== undefined ){
                    if( Settings.zoom  && Settings.zoom !== undefined ){
                        Search.vars.map.setZoom(Settings.zoom);
                    }
                    Search.log('initMapReady Set Center...');
                    Search.vars.map.setCenter(new google.maps.LatLng(Settings.lat, Settings.lng));
                }

            }

        },
        idle: function(){
            Search.log('Event Triggered: idle');

            Search.vars.idle = true;


            //last update event status;
            Search.vars.eventstatus = 'idle';
        },

        boundschanged: function(){
            Search.log('Event Triggered: boundschanged');

            Search.vars.idle = false;
            if( Search.vars.search_result !== null && Search.vars.form.hasClass('filter-on-boundschanged')){

                //update displayed listings on bounds changed
                Search.updateListings().then(function(){
                    Search.log('Listings Updated.');
                });

            }


            //last update event status;
            Search.vars.eventstatus = 'boundschanged';
        },

        zoomchange: function(){
            Search.log('Event Triggered: zoomchange');

            Search.vars.idle = false;

            //last update event status;
            Search.vars.eventstatus = 'zoomchange';
        },

        dragend: function(){
            Search.log('Event Triggered: dragend');

            Search.vars.idle = false;

            //last update event status;
            Search.vars.eventstatus = 'dragend';
        },

        clusterclick: function(){
            Search.log('Event Triggered: clusterclick');

            Search.vars.idle = false;

            //last update event status;
            Search.vars.eventstatus = 'clusterclick';
        },

        drawingmodechange: function(){
            Search.log('Event Triggered: drawingmodechange');

            Search.vars.idle = false;

            if (Search.vars.drawingmanager.getDrawingMode() != null) {
                Search.clearPoly();
            }


            //last update event status;
            Search.vars.eventstatus = 'drawingmodechange';

        },

        overlaycomplete: function(event) {
            Search.log('Event Triggered: overlaycomplete');

            Search.vars.idle = false;

            var newShape = event.overlay;
            newShape.type = event.type;
            Search.vars.shapes.push(newShape);
            Search.vars.areaChanged = true;

            var len = newShape.getPath().getLength();
            var poly = [];

            for (var i = 0; i < len; i++){
                poly.push(newShape.getPath().getAt(i).toUrlValue(5).replace(",", " "));
            }

            var polydata = poly.join() + "," + poly[0];
            Search.vars.mapData = "poly=" + polydata;
            Search.vars.map.fitBounds(event.overlay.getBounds());

            Search.updateListings().then(function(){
                Search.log('Listings Updated.');
            });


            if (Search.vars.drawingmanager.getDrawingMode()) {
                Search.clearPoly();
                //Search.vars.drawingmanager.setDrawingMode(null);
            }


            //last update event status;
            Search.vars.eventstatus = 'overlaycomplete';

        }

    },



    //Entry Point, things start happening here
    init: function(data, $_form){
        //console.log('$_form',$_form,'data',data);
         //html form object
         Search.vars.form = $_form; //console.log('init form', $_form, 'data', data);
         Search.vars.formid = $_form.attr('id');


        var gmap_selector = Search.vars.form.attr('data-gmap_selector');
            gmap_selector = (gmap_selector !== undefined && gmap_selector !== '' ) ? gmap_selector :  Search.vars.form.attr('data-gmap');

        var item_selector = Search.vars.form.attr('data-item_selector') || '.listing';

        Search.vars.debug = false //show logs in browser console

        //initialize map
        Search.vars.useClusterer = ( Search.vars.form.data('use_clusterer') === 'yes' ) ? true : false;

        var clustercondition = Search.vars.form.data('cluster_condition');
        Search.vars.clusterCondition = ( typeof clustercondition !== 'undefined' ) ? clustercondition : '';

        var map_args = Search.vars.form.data('gmap_args');
            map_args = Search.helpers.parse_data_args(map_args);
        Search.vars.map = Search.loadMap($(gmap_selector), map_args);






        //initialize drawing manager for polygon search support
        Search.vars.drawingmanager = Search.loadDrawingManager(Search.vars.map );


        var init_data = false;
        if( data && typeof data !== undefined && data.length>0 ){
            //render markers on map
            Search.loadData(data).then(function(){
                Search.events.initMapReady();
            });
            init_data = true;
        }

        //load Events
        Search.loadMapEvents();


        var mapLoadedFirstTime = false;
        Search.helpers.fillFormValues().then(function(data){

            Search.loadFormEvents();
            //Search.vars.form.on('form_ready', function(e){
                var load = Search.vars.form.attr('data-load') || false;
                //whether to load listings data on form_ready
                if( load === 'no' || load === '' ){
                    load = false;
                }
                Search.vars.form.attr('data-ffdsearchform', "");



                if( init_data === false &&  load !== false && load !== undefined){
                    //load data using ajax
                    Search.startLoading();
                    Search.getSearchResults().then(function(payload){
                        return null;
                    }).catch(function(error){

                        Search.vars.form.find('.ffd-search-result-text').html('');
                        var $listings_container =  Search.vars.form.find('.ffd-search-listing-items');
                        var $limitfield = Search.vars.form.find('[name="limit"][data-fieldtype="ffdsearch"]');
                        if( Search.vars.form.find('[data-listingscontainer]').length > 0 )
                            $listings_container = Search.vars.form.find('[data-listingscontainer]');


                        $listings_container.html('');
                        Search.vars.form.find('.ffd-search-total').text('');
                        Search.vars.form.find('.ffd-search-showing').text('');

                        $listings_container.html('<p>'+error+'</p>');

                    });


                Search.vars.form.on('result_loaded', function(){

                        //only trigger if this is first load map
                        if( !mapLoadedFirstTime ){

                            if(Search.vars.form.hasClass('__has-query-params') ){
                                Search.FilterResults();
                            } else {
                                Search.events.initMapReady();
                            }
                            mapLoadedFirstTime = true;
                        }
                    });



                }
            //});

        });



        Search.load3rdPartyEvents();
    },

    loadFormEvents: function(){

            $(document).on('click', '.ffd-searchfilter-tag .ffd-close-searchfilter-tag', function(e){
                e.preventDefault();

                var $field, $tag, data, $single, $option, $check;
                $tag = $(this).closest('.ffd-searchfilter-tag');
                data = $tag.data();
                tagValue = data['tagfieldvalue'];
                $field = $check = $option = Search.vars.form.find('[name="'+data['tagfieldname']+'"]');

                if( $field.length > 1 && $field.is(':checkbox') || $field.is(':radio') ){
                    $check = Search.vars.form.find('[name="'+data['tagfieldname']+'"][data-fieldvalue="'+tagValue+'"]');
                    if( typeof $check === 'undefined' || $check.length < 1 ){
                        $check = Search.vars.form.find('[name="'+data['tagfieldname']+'"][value="'+tagValue+'"]');
                    }
                } else if( $field.is('select') ){
                    $option = Search.vars.form.find('[name="'+data['tagfieldname']+'"] option[data-fieldvalue="'+tagValue+'"]');
                    if( typeof $option === 'undefined' || $option.length < 1 ){
                        $option = Search.vars.form.find('[name="'+data['tagfieldname']+'"] option[value="'+tagValue+'"]');
                    }
                }

                $tag.remove();

                if( $field.length > 0 ){
                    if( $field.is(':checkbox') || $field.is(':radio') ){

                        $check.prop('checked', false);

                    } else if( $field.is('select') ){

                        $option.prop("selected", false);

                    } else {
                        $field.val('');
                    }

                    Search.vars.form.submit();

                    //$field.trigger('change');
                }


            });

            $('#nav-profile-tab', Search.vars.form).on('click', function(e){ //bvp

                // for (var i = 0; i < Search.vars.data_search.length; i++) {
                //   var a = Search.vars.data_search[i]
                //     Search.codeAddress_second(Search.vars.data_geoco, Search.vars.data_map , a);
                // }
                // Search.vars.data_search = []
            });

            $('.ffd-savesearch-btn', Search.vars.form).on('click', function(e){
            e.preventDefault();

            var $button = $(this);
            var $form =$button.closest('form');
            var $container =$button.closest('#ffd-savesearch-container');
            var $status = $container.find('.save-search-status');

            var $searchName = $form.find('#savesearch-name');
            var $searchId = $form.find('#savesearch-id');

            var urlparams = jQuery(':input[value!=""]', Search.vars.form).filter(function(index, element) {
                return $(element).val() != '';
            }).serialize();

            var params = Search.getSearchParams();
            var search = Search.getSearchQuery(params, 'savesearch');

            var name = $searchName.val();
            if( name === '' ){
                $status.text('Please enter search name.');
                return false;
            }

            if( search['where'] === '' ||  _.isEmpty(search['where'])){
                $status.text('No Search filter selected.');
                return false;
            }



            /*
            * ------------------
            * AJAX Save search
            */


            var current_href = $(location).attr('href');
            var current_title = $(document).attr('title')

            var urlPath = Settings.page_url;
            urlPath = urlPath + '?' + urlparams;
            var data = { action:'ffd/integration/savesearch', name:name, page_url:Settings.page_url, url:urlPath, 'params':urlparams, 'query':search['where']};
            var command = '';
            if( $searchId.val() !== '' ){
                command = 'update';
                data['searchid'] = $searchId.val();
            } else {
                command = 'add'
            }
            //command:command,
            data['command'] = command;



            if( $button.hasClass('busy') ){
                return false;
            }

            $button.prop('disabled', true).addClass('busy');
            $status.text('Saving...');

            $.ajax({
                url: Settings.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: data,
            })
            .done(function (response) {


                var data = response.data;

                if( data !== 'undefined' && data.hasOwnProperty('id') ){
                    $status.text(data.message);
                    $searchId.val(data.id);
                    //$searchName.val('');
                } else {
                    if( data.message !== 'undefined' ){
                        $status.text(data.message);
                    } else {
                        $status.text("Error saving search.");
                    }

                }

            })
            .fail(function () {
                $status.text('Error: Please try again later.');
            })
            .always(function () {
                $button.prop('disabled', false).removeClass('busy');
            });


        });



        $('input[data-fieldautocomplete="yes"]', Search.vars.form).autocomplete({
            source: function( request, response ) {

             var $element = this.element;
             var args = $element.data();
             var params = {};

             params[$element.attr('name')] = $element.attr('value');

             $element.closest('form').addClass('ffd-jqui');
             if( !$element.hasClass('ffd-doing-autocomplete') ){

                    $element.addClass('ffd-doing-autocomplete');

                    $.ajax({
                        'type': 'POST',
                        url: Settings.ajaxurl,
                        dataType: "json",
                        data: {
                        action:'ffd/integration/autocomplete',
                        term: request.term,
                        'keys': args['fieldkeys'] || '',
                        'condition': args['fieldcondition'] || '',
                        'query' : Search.getSearchQuery(params)
                        }
                    } ).done(function(data){
                        $element.removeClass('ffd-doing-autocomplete');
                        response( data);

                    }).fail(function(){
                        $element.removeClass('ffd-doing-autocomplete');
                        response( [] );
                    }).always(function(){


                    });


                } else {
                    response( [] );
                }
            },
            minLength: 3,
            appendTo: '#' + Search.vars.form.attr('id')
        });

        Search.vars.form.on('submit', function(e){ //console.log('llega al submit!!!!!!!', e, 'Search', Search.vars);


            e.preventDefault();

            var urlPath = Settings.page_url;
			var current_href = $(location).attr('href');
            var current_title = $(document).attr('title');
            var use_history = Search.vars.form.attr('data-historyapi') || false;


            if( use_history === 'no' || use_history === '' ){
                use_history = false;
            }

            if( use_history !== false && typeof use_history !== 'undefined' ){
                var url_params = jQuery(':input[value!=""]', Search.vars.form).filter(function(index, element) {
                    return $(element).val() != '';
                }).serialize();

                History.pushState({path: urlPath}, current_title, urlPath + '?' + url_params); // When we do this, History.Adapter will also execute its contents.
            }

            Search.log('Search Form Submitted, doing search?' + Search.vars.doingsearch);
            if( !Search.vars.doingsearch ){
                Search.startLoading();

                //if( Search.vars.data !== null  && Search.vars.data.length > 0 ){
                    //Search.FilterResults();
                //} else {
                    Search.getSearchResults().then(function(payload){
                        return null;
                    });
                //}
            }
        });

        Search.vars.form.find('[data-clearfields]').on('click', function(){
            //Search.vars.form.find('[data-fieldtype="ffdsearch"]').each(function(){
                $(':input', Search.vars.form)
                .not(':button, :submit, :reset, :hidden')
                .val('')
                .prop('checked', false)
                .prop('selected', false);
            //});

            Search.vars.form.submit();
        });

        Search.vars.form.find('[data-resetfields]').on('click', function(){
            Search.vars.form.find('[data-fieldtype="ffdsearch"]').each(function(){

            });
        });

        Search.vars.form.find('.ffd-formsubmit-btn').on('click', function(e){

            if( $(this).attr('type').toLowerCase() !== 'submit' ){
                e.preventDefault();
                Search.vars.form.submit();
            }
        });

        Search.vars.form.find('input[data-fieldbind],select[data-fieldbind]').each(function(e){
            var eventname = $(this).attr('data-fieldbind') || '';

            if(eventname !== '' && typeof eventname !== 'undefined' ){
                $(this).on(eventname, function(e){
                    if( !$(this).hasClass('ffd-autocomplete') ){
                        Search.vars.form.submit();
                    }
                });
            }
        });


        //var ffdevents = {};
        Search.vars.form.find('[data-ffdevent]').each(function(e){

            var $this  =  $(this);
            var data = $this.data();
            var event = data['ffdevent'];
            var target = data['ffdtarget'];
            var action = data['ffdaction'];
            var trigger = data['ffdtrigger'];
            var $selector = data['ffdselector'];
            var element = data['ffdelement'], $element;


            if( typeof action === 'undefined' ){
                action = 'value';
            }

            if( typeof element === 'undefined' ){
                $element = $this
            } else {
                $element = $this.find(element);
                if( typeof $element !== 'undefined' && typeof $selector === 'undefined' ){
                    $selector = $element;
                }
            }

            if( typeof $selector === 'undefined' ){
                $selector = $this
            }


            if( typeof $element !== 'undefined' ) {

                $element.on(event, function(e){

                    var $self = $(this);
                    var $parent = $self.closest('[data-ffdevent]');

                    var value = ( ( typeof $self.attr('data-value') !== 'undefined') ? $self.attr('data-value') : $self.text().replace(/^\s+|\s+$/g, ''));
                    value = Search.helpers.check_input_value(value);

                    $parent.find(''+$parent.attr('data-ffdelement') + '').removeClass('ffdelement-selected');
                    $self.addClass('ffdelement-selected');

                    switch (action) {
                        //if action is to set value of target
                            case 'value':
                                Search.vars.form.find(target).val(value);
                                Search.vars.form.find(target).trigger(trigger)
                            break;

                            case 'fieldvalue':
                                Search.vars.form.find(target).attr('data-fieldvalue', value);
                                Search.vars.form.find(target).trigger(trigger)
                            break;
                        break;
                    }

                });

            }



        });







        //triggered when results are filtered based on user input
        Search.vars.form.on('response_ready', function(e, listings, type){

            Search.closeAllInfoWindows();
            Search.vars.mapData = "";

            //clear all (if) existing makers from the map
            Search.clearMarkers().then(function(){

                return Promise.resolve(listings);
                //return Search.helpers.prepareData(response, type);


            }).then(function(listings){

                //load markers using new data
                var updateData = ( type == 'ajax') ? 'yes' : 'no';

                return Search.loadData(listings, updateData);

            }).then(function(){
                //last set doingsearch to false
                Search.vars.doingsearch = false;
                Search.stopLoading();
                //console.log('Click');
                $('[data-fieldtype="ffdsearch_link"]').on('click', function(event, item){

                    console.log('event', event, 'item', $(this).attr('id'));
                    var ident = $(this).attr('id');
                    //alert('event '+Settings.ajaxurl+' '+ident+' '+$(this).attr('link'));
                    var link = $(this).attr('link');
                    jQuery.ajax({
                        type: "POST",
                        url: Settings.ajaxurl,
                        data: {
                            action:'ffd/integration/verify',
                            id: ident
                        },
                        dataType: 'json',
                        cache: false
                    }).done(function(response) {
                        console.log('response', response);
                        if(response.status == 'logout'){
                            window.location.href = response.href;
                        }else{
                            window.location.href = link;
                        }

                    }).fail(function(){
                        console.log('fail');
                    }).always(function(){

                    });
                });

                Search.vars.form.trigger('result_loaded');
            });




        });

    },

    load3rdPartyEvents: function(){

        $('[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                // e.target // newly activated tab
                // e.relatedTarget // previous active tab
               if( $('.ffd-gmap-is-hidden').length > 0  || $(this).hasClass('has-gmap')){

                    if( $(this).attr('href') !== undefined ){
                        target = $(this).attr('href');
                    } else {
                        target = $(this).attr('data-target');
                    }
                    map = $(target).find('.ffd-integration-gmap');
                    if( map.length > 0 ){
                        var zoom = map.attr('data-zoom');
                        if( Search.vars.map && Search.vars.map.getZoom() < zoom ){
                            zoom = Number(zoom);
                            if( typeof zoom !== NaN){
                                Search.vars.map.setZoom(zoom);

                                bounds = Search.vars.bounds;
                                Search.vars.map.setCenter(bounds.getCenter());
                                Search.vars.map.fitBounds(bounds);
                                map.removeClass('ffd-gmap-is-hidden');
                            }

                        }
                    }
                }

          });

    },

    loadMap: function ($target, map_args) {

            if( $target.length < 1 ){
                Search.log('No gmap target found');
                return null;
            }

            $target.addClass('ffd-integration-gmap');
            if( $target.is(":hidden") ){
                $target.addClass('ffd-gmap-is-hidden');
            }



            //initialize map
            map_args = ( map_args !== undefined && typeof map_args === 'object' ) ? map_args : {};
            map_args = _.extend({
                center: { lat: 26.7180067, lng: -80.0549716 },
                scrollwheel: false,
                mapTypeControl: true,
                zoom: 8,
                maxZoom: 16
            }, map_args);


           $target.attr('data-zoom', map_args['zoom']);
           $target.attr('data-maxZoom', map_args['maxZoom']);


            /*  Zoom Level
                1: World
                5: Landmass/continent
                10: City
                15: Streets
                20: Buildings
            */
           return  new google.maps.Map($target.get(0), map_args);

    },

    loadData: function(){

        Search.log("Loading Data...");

        var data = arguments[0] !== undefined ? arguments[0] : "";
        var setData = arguments[1] !== undefined ? arguments[1] : "yes";


        Search.vars.loadingData = true;

        return new Promise(function(resolve,reject){

                if( setData === 'yes' ){
                    Search.log('Data is Updated.');
                    Search.vars.data = Search.vars.search_result = data;
                } else {
                    Search.vars.search_result = data;
                }

                Search.log('Data Count: ' +  data.length);

                if( Search.vars.infoWindow === null ){
                    Search.vars.infoWindow = new google.maps.InfoWindow();
                }

                return Search.loadMarkers(Search.vars.map , data).then(function(){
                    return Search.loadListings(data).then(function(){
                        Search.vars.loadingData = false;
                        Search.log("Loading Data Done!");
                        return resolve("Loading Data Done!");
                    });
                });
        });



    },

    loadListings: function(data){

            Search.log('Loading Listings, Count:', data.length);
            //clear listing cards html
            Search.vars.form.find('.ffd-search-result-text').html('');
            var $listings_container =  Search.vars.form.find('.ffd-search-listing-items');
            var $limitfield = Search.vars.form.find('[name="limit"][data-fieldtype="ffdsearch"]');
            if( Search.vars.form.find('[data-listingscontainer]').length > 0 )
                $listings_container = Search.vars.form.find('[data-listingscontainer]');


            $listings_container.html('');
            Search.vars.form.find('.ffd-search-total').text('');
            Search.vars.form.find('.ffd-search-showing').text('');

            if( Search.vars.form.find('[data-listingtemplate]').length > 0 && data.length <= 0 ){

                Search.vars.form.find('.ffd-search-result-text').html('');
                $listings_container.html('<p>No results found.</p>');

                Search.vars.form.find('.ffd-search-showing').each(function(){
                    var noresults = ( typeof $(this).attr('data-ffdnoresults') !== 'undefined' ? $(this).attr('data-ffdnoresults') : '' );

                    $(this).text( noresults );
                });

                return  Promise.resolve('Load Listings No Results!');;
            }

            Search.vars.form.find('.ffd-search-result-text').html('<p>Showing ' + data.length + ' of Total ' + Search.vars.data.length);
            Search.vars.form.find('.ffd-search-total').text( ( Search.vars.data.length ? Search.vars.data.length : 0));
            Search.vars.form.find('.ffd-search-showing').each(function(){
                var $showing = $(this);

                var prepend = ( (typeof $showing.attr('data-ffdprepend')  !== 'undefined' ) ? $showing.attr('data-ffdprepend') : '' );
                var append = ( (typeof $showing.attr('data-ffdappend')  !== 'undefined' ) ? $showing.attr('data-ffdappend') : '' );
                var resultcount = ( ( typeof data.length !== 'undefined' ) ? data.length : 0);
                var restultext = prepend + ( resultcount ) + append;

                if( typeof $showing.attr('data-ffdshowing')  !== 'undefined' ){

                    Liquid.parseAndRender($showing.attr('data-ffdshowing'), {'showing':resultcount}).then(function(content){
                        $showing.text( restultext );
                    });

                } else {

                    if( $limitfield.length > 0 && resultcount < $limitfield.val() ){
                        restultext = restultext.replace( resultcount + '+', resultcount);
                    }

                    $showing.text( restultext );
                }
            });

            //if( data.length > 50 ){
                //data = data.slice(0, 50);
            //}

            var total_listings = data.length;
            var listing_rendered = 0;

            return new Promise(function(resolve) {
                jQuery.each(data, function (item_index, item) {
                    var content;
                    if( item.hasOwnProperty('image') && item['image'] != '' ){
                        item['image'] = item['image'];
                    } else {
                        item['image'] = Settings.image_placeholder;
                    }



                    if( item.hasOwnProperty('permalink') && item['permalink'] != '' && item.hasOwnProperty('link')){
                        item['fulllink'] = Settings.homeurl  + item['link'];
                    } else if( item.hasOwnProperty('link') ) {
                        item['permalink'] = Settings.homeurl + item['link'];
                    }

                    item['image_placeholder'] = Settings.image_placeholder;
                    content = Search.vars.form.find('[data-listingtemplate]').html();

                    Liquid.parseAndRender(content, item).then(function(content){

                        $listings_container.each(function(){
                            var $content = $(content);
                            var content_class = $content.attr('class');
                            var append_class = $(this).attr('data-appendlistingclass') || '';
                            var listing_class = $(this).attr('data-listingclass') || '';
                            if( append_class !== '' ){
                                $content.attr('class', content_class + ' ' + append_class);
                            } else if( listing_class !== '' ){
                                $content.attr('class', listing_class);
                            }

                            $(this).append($content.prop("outerHTML"));
                        });
                        listing_rendered++;
                        if( listing_rendered >= total_listings ){
                            Search.vars.form.trigger('listings_rendered');
                            Search.log('Load Listings Done!');
                            resolve('Load Listings Done!');
                        }
                    });

                });
            });


    },

    updateListings: function(){ console.log('updateListings', Search.vars.search_result);

        Search.log("Updating Listings...");

        var bounds =  Search.vars.map.getBounds();
        var data = [],  latlng;
        jQuery.each(Search.vars.search_result, function (item_index, item) {

            //item = Search.helpers.dataItem(item);

            var latlng = Search.helpers.itemLatLng(item);

            if( latlng.hasOwnProperty('lat') && latlng.hasOwnProperty('lng')
                && latlng['lat'] !== '' && latlng['lng'] != ''
            ){

                latlng = new google.maps.LatLng(latlng['lat'], latlng['lng']);
                if( bounds.contains(latlng) ){
                    data.push(item);
                }
            }

        });

        return new Promise(function(resolve, reject){

            if( data.length > 0 ){
                Search.loadListings(data).then(function(){
                    resolve('Update Listings Done!');
                });
            } else {
                resolve('Update Listings Done!');
            }
        });


    },

    loadDrawingManager: function(map, drawingmanager_args){

        var drawingmanager;
        drawingmanager_args = drawingmanager_args || {}; ;

        drawingmanager_args = jQuery.extend(true, {
            drawingMode: null,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [
                    google.maps.drawing.OverlayType.POLYGON
                ]
            }
        }, drawingmanager_args);

        drawingmanager = new google.maps.drawing.DrawingManager(drawingmanager_args);
        drawingmanager.setMap(map);

        if (!google.maps.Polygon.prototype.getBounds) {

            google.maps.Polygon.prototype.getBounds = function () {
                var bounds = new google.maps.LatLngBounds();
                this.getPath().forEach(function (element, index) { bounds.extend(element) });
                return bounds;
            }

        }

        return drawingmanager;
    },



    loadMapEvents: function(){

        if( Search.vars.map ){

            Search.vars.map.addListener('idle', Search.events.idle);
            Search.vars.map.addListener('bounds_changed', Search.events.boundschanged);
            Search.vars.map.addListener('zoom_changed', Search.events.zoomchange);
            Search.vars.map.addListener('dragend', Search.events.dragend);

            //Search.vars.map.addListener('clusterclick', Search.events.clusterclick);
        }

        if( Search.vars.drawingmanager ){

            google.maps.event.addListener(Search.vars.drawingmanager, "drawingmode_changed", Search.events.drawingmodechange);
            google.maps.event.addListener(Search.vars.drawingmanager, 'overlaycomplete', Search.events.overlaycomplete);

        }

    },


    loadClusterEvents: function(){

        if( Search.vars.markerClusterer ){

            google.maps.event.addListener(Search.vars.markerClusterer, "click", function(cluster){

                var markers = cluster.getMarkers();
                var total = 0;
                var loaded = 0;

                var mapzoom = Search.vars.map.getZoom();
                var clusterMaxZoom = Search.vars.markerClusterer.getMaxZoom();

                var infowindow_contents = '', item=null;



                if( mapzoom < clusterMaxZoom - 1 ){


                    (function(markerCluster){

                        var theBounds, mz;

                        // Zoom into the cluster.
                        mz = markerCluster.getMaxZoom();
                        theBounds = cluster.getBounds();
                        markerCluster.getMap().fitBounds(theBounds);
                        // There is a fix for Issue 170 here:
                        setTimeout(function () {
                            markerCluster.getMap().fitBounds(theBounds);
                        // Don't zoom beyond the max zoom level
                        if (mz !== null && (markerCluster.getMap().getZoom() > mz)) {
                            markerCluster.getMap().setZoom(mz + 1);
                        }
                        }, 100);

                        //map.setCenter(cluster.getCenter());
                        //map.setZoom(map.getZoom()+1);
                        //map.setZoom(map_args['maxZoom'] || 18 );

                    })(Search.vars.markerClusterer);

                    return;

                }

                var loadContent = new Promise(function(resolve) {



                    jQuery.each(markers, function(item_index, marker){

                        total++;
                        item  = marker['listingitem'];


                        if( typeof item !== 'undefined' ){
                            Search.iw_content(item_index, item).then(function(content){
                                //Search.vars.infoWindow.setContent(content);
                                //Search.vars.infoWindow.open(map, marker);
                                infowindow_contents += content;
                                loaded++;
                                if( loaded == total ){
                                    resolve(infowindow_contents);
                                }
                                return content;
                            });
                        }

                    });
                });

                loadContent.then(function(content){

                    if( ! Search.vars.infoWindow ){
                        Search.vars.infoWindow = new google.maps.InfoWindow();
                    }
                    Search.vars.infoWindow.setContent('<div class="cluster-popup">'+content+'</div>');
                    Search.vars.infoWindow.setPosition(cluster.getCenter());
                    Search.vars.infoWindow.setOptions({maxWidth:200});
                    Search.vars.infoWindow.open(Search.vars.map);

                });

            });

        }

    },

    loadMarkers: function(map, data){

        if( !map )
            return Promise.resolve('Done!');

        Search.log('Loading Markers...');
        var marker_ids = [],
                markers_to_delete,
                bounds = new google.maps.LatLngBounds();

            //data = data.slice(0, 5);
            jQuery.each(data, function (item_index, item) {

                //item = Search.helpers.dataItem(item);
                var latlng = Search.helpers.itemLatLng(item);
                var marker,
                    id = item_index,
                    lat = latlng['lat'],
                    lng = latlng['lng'];


                    if (undefined !== lat && undefined !== lng && '' !== lat && '' !== lng ) {

                        lat = parseFloat(lat);
                        lng = parseFloat(lng);

                        if (!isNaN(lat) && !isNaN(lng)) {

                            marker = Search.addMarkerWithLabel(map, item_index, item);
                            //marker = Search.addMarker(map, item_index, item);
                            Search.vars.markers.push(marker);

                            Search.current.marker = marker;
                            Search.current.marker_index = id;

                            (function (marker, item_index, content) {
                                google.maps.event.addListener(marker, "click", function (e) {
                                        Search.iw_content(item_index, item).then(function(content){
                                            Search.vars.infoWindow.setContent(content);
                                            Search.vars.infoWindow.open(map, marker);
                                            return content;
                                        });

                                });
                            })(marker, item_index, item);

                            bounds.extend(marker.getPosition());

                        }



                    }else{

                      geocoder = new google.maps.Geocoder();
                      Search.codeAddress(geocoder, map, item.the_title); //bvp
                    }

            });



            Search.vars.bounds = bounds;
            // Search.vars.map.setCenter(bounds.getCenter()); bvp
            // Search.vars.map.fitBounds(bounds); bvp



            if( Search.vars.useClusterer ){

                if( Search.vars.clusterCondition !== '' ){

                    Search.vars.clusterMarkers = _.filter(Search.vars.markers, function(marker){
                        return Search.vars.clusterCondition == marker['proptype'];
                    });



                    if( !_.isEmpty(Search.vars.clusterMarkers) ){
                        var clusterOptions = {maxZoom: 16, zoomOnClick:false, averageCenter:true, minimumClusterSize:2, imagePath: Settings.cluster_img_path};

                        var cluster_args = Search.vars.form.data('cluster_args');
                        cluster_args = Search.helpers.parse_data_args(cluster_args);
                        cluster_args = ( cluster_args !== undefined && typeof cluster_args === 'object' ) ? cluster_args : {};

                        clusterOptions = _.extend(clusterOptions, cluster_args);



                        Search.vars.markerClusterer = new MarkerClusterer(map, Search.vars.clusterMarkers, clusterOptions);

                        //load Events
                        Search.loadClusterEvents();


                    }

                }

            }
            Search.vars.form.data('mapobj', Search.vars.map);
            Search.log('!!!Loaded Markers!!!');
        return Promise.resolve('Load Markers Done!');

    },

    codeAddress: function(geocoder, map, address) {
            var infowindow = new google.maps.InfoWindow();
            geocoder.geocode({'address': address}, function(results, status) {
              if (results==null) {
                Search.vars.data_search.push(address)
                  // Search.codeAddress(geocoder, map, address)
              }else{
              if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
                var marker = new google.maps.Marker({
                  map: map,
                  position: results[0].geometry.location
                });
                google.maps.event.addListener(marker, "click", function () {
                  infowindow.setContent(
                    "<div><b>Address Direction: </b><strong>" +
                      results[0].formatted_address +
                      "</strong>"+
                      "</div>"
                  );
                  infowindow.open(map, this);
                });

              }
            }
            });
            Search.vars.data_map = map
            Search.vars.data_geoco = geocoder
    },

    codeAddress_second: function(geocoder, map, address) {
            var infowindow = new google.maps.InfoWindow();
            geocoder.geocode({'address': address}, function(results, status) {
              if (results==null) {
                setTimeout(function () {
                  Search.codeAddress_second(geocoder, map, address)
                }, 50 * i);

              }else{
              if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
                var marker = new google.maps.Marker({
                  map: map,
                  position: results[0].geometry.location
                });
                google.maps.event.addListener(marker, "click", function () {
                  infowindow.setContent(
                    "<div><b>Address Direction: </b><strong>" +
                      results[0].formatted_address +
                      "</strong>"+
                      "</div>"
                  );
                  infowindow.open(map, this);
                });

              }
            }
            });
    },
    addMarkerWithLabel: function(map, item_index, item){

                var latlng = Search.helpers.itemLatLng(item);
                var id = item_index,
                lat = latlng['lat'],
                lng = latlng['lng'],
                price = item['listing']['listprice'] || '',
                status = item['listing']['status'] || '',
                type = item['listing']['proptype'] || '',
                url = '';

                var p = Search.helpers.convert_number(price.replace(/,/g, ""), 1);

       return  new MarkerWithLabel({
                map: map,
                position: new google.maps.LatLng(lat,lng),
                url: url,
                status : status,
                proptype : type,
                icon: " ",// hbg.themeurl + '/img/mapicon.png',
                labelContent: "<div class='markerLabelInside'>$" + p + "</div>",
                labelInBackground: false,
                labelClass: "markerLabelOutside " + status + ' ' + type,
                listingitem: item
            });

    },

    addMarker: function(map, item_index, item){

        var latlng = Search.helpers.itemLatLng(item);
                var id = item_index,
                lat = latlng['lat'],
                lng = latlng['lng'],
                price = item['listing']['listprice'] || '',
                status = item['listing']['status'] || '',
                type = item['listing']['proptype'] || '',
                url = '';

        var imageUrl = 'http://chart.apis.google.com/chart?cht=mm&chs=24x32&' +
          'chco=FFFFFF,008CFF,000000&ext=.png';

        var markerImage = new google.maps.MarkerImage(imageUrl, new google.maps.Size(24, 32));
        var p = Search.helpers.convert_number(price.replace(/,/g, ""), 1);

        return  new google.maps.Marker({
            position: new google.maps.LatLng(lat,lng),
            draggable: false,
            label: "$" + p,
            listingstatus : status,
            proptype : type,
            icon: markerImage
          });

    },

    iw_content: function(item_index, item){

            item['image_placeholder'] = Settings.image_placeholder
            var content='';
            var formated_price = parseFloat(item['ffd_listingprice_pb']);
                formated_price = formated_price.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            item['ffd_listingprice_pb'] = formated_price;

            if( Search.vars.form.find('[data-iwtemplate]').length > 0 ){
                content = Search.vars.form.find('[data-iwtemplate]').html();
            }

            if( content === '' ) {
                content = Search.templates.iw_content;
            }

            return Liquid.parseAndRender(content, item);

    },



    closeAllInfoWindows: function(){

       if( Search.vars.infowindows.length > 0 ){
            jQuery.each(Search.vars.infowindows, function (index, iw) {
                iw.close();
            });
       }

    },

    clearMarkers: function(){




       Search.log('ClearMarkers Before:' + Search.vars.markers.length );

        jQuery.each(Search.vars.markers, function (i, value) {
            Search.vars.markers[i].setMap(null);
        });
        Search.vars.markers = [];

        Search.log('ClearMarkers After:' + Search.vars.markers.length );


        if( Search.vars.markerClusterer !== null ){
            Search.vars.markerClusterer.clearMarkers();
        }

        return Promise.resolve('Clear Markers Done!');
    },


    clearPoly: function () {
        for (var i = 0; i < Search.vars.shapes.length; i++) {
            Search.vars.shapes[i].setMap(null);
        }
        Search.vars.shapes = [];
    },

    /*
    * remove use of result_loaded and response ready
    * instead use this function and make use of promises
    * Search::doSearch().then(function(){ });
     */
    doSearch: function(searchType){
        if(searchType=='getSearchResults')
            return  Search.getSearchResults();
        else
            return  Search.FilterResults();
    },

    generateSearchTags: function(query){



        var tag = '<span class="ffd-searchfilter-tag {{tagClass}}" {{tagData}}>{{tagLabel}} <span class="ffd-close-searchfilter-tag">x</span></span>';
        var tagHtml, tagsHtml = '', $field, $element, taglabel, tagvalue, tagclass, tagdata, tagname, tagkey, hrtagvalue, tagtext, optiontext;
        var parsedata;
        var valuePieces;
        var parsetag;
        var parsetags = [];
        var filtertaglabel;
        var subfieldtaglabel;
        $.each(Search.vars.postedfields, function(name, field){

            valuePieces = [];
            parsedata = field;

            if( query.hasOwnProperty(name) || ( query.hasOwnProperty('where') && query['where'].hasOwnProperty(field['fieldkey']) ) ){

                $field = Search.vars.form.find('[name="'+name+'"]');
                tagvalue = field['value'];
                valuePieces.push(tagvalue);

                if( tagvalue.indexOf(',') !== -1 && ( $field.is(':radio') || $field.is(':checkbox') ) ){
                    valuePieces = tagvalue.split(',');
                }

                jQuery.each(valuePieces, function(valuePieceIndex, tagvalue){

                    var $subfield = $field;

                    if( $field.length > 1 && $field.is(':checkbox') || $field.is(':radio') ){
                        $subfield = Search.vars.form.find('[name="'+name+'"][data-fieldvalue="'+tagvalue+'"]');
                        if( typeof $subfield === 'undefined' || $subfield.length < 1 ){
                            $subfield = Search.vars.form.find('[name="'+name+'"][value="'+tagvalue+'"]');
                        }
                    } else if( $field.is('select') ){
                        $subfield = Search.vars.form.find('[name="'+name+'"] option[data-fieldvalue="'+tagvalue+'"]');
                        if( typeof $subfield === 'undefined' || $subfield.length < 1 ){
                            $subfield = Search.vars.form.find('[name="'+name+'"] option[value="'+tagvalue+'"]');
                        }
                    }

                    multivaluetag =
                    tagclass = 'ffd-searchtagname-' + name + ' ' + 'ffd-searchtagkey-' + field['fieldkey'];

                    tagdata = 'data-tagfieldname="'+name+'" data-tagfieldkey="'+field['fieldkey']+'" data-tagfieldvalue="'+tagvalue+'" ';
                    $element = Search.vars.form.find('[data-ffdtarget="'+ Search.helpers.jq_escape('[name='+name+']') +'"]'); //data-ffdtarget="[name=minsqft]"

                    if( $field.find('option').length > 0 ){
                        parsedata['optiontext'] = $field.find('option:selected').text();
                    }

                    if( $field.closest('label').length > 0 ){
                        parsedata['labeltext'] = $field.closest('label').text().replace(/\r?\n/g, '');
                    }
                    if( $element.length > 0 ){
                        parsedata['elementtext'] = $.trim($element.find('.ffdelement-selected' ).text().replace(/\r?\n/g, '') );
                    }


                    tagtext = $field.text();
                    tagname = Search.helpers.human_readable(name);
                    tagkey = Search.helpers.human_readable(field['fieldkey']);
                    hrtagvalue = Search.helpers.human_readable(tagvalue);
                    if( tagname == 'order' && hrtagvalue.indexOf(',') !== -1 ){
                        var pieces = hrtagvalue.split(',');
                        hrtagvalue = pieces[0]
                    }

                    if(  $field.attr('data-fieldhastag') !== 'no' && $field.attr('data-fieldhastag') !== 'false' ){

                        taglabel = hrtagvalue;
                        filtertaglabel = $field.attr('data-filtertaglabel');

                        if( ( $field.is(':checkbox') || $field.is(':radio') || $field.is('select') ) ){
                            subfieldtaglabel = $subfield.attr('data-filtertaglabel');
                            if( typeof subfieldtaglabel !== 'undefined' && '' !== subfieldtaglabel ){
                                filtertaglabel = subfieldtaglabel;
                            }
                        }

                        if( typeof filtertaglabel !== 'undefined' && '' !== filtertaglabel ){
                            taglabel = filtertaglabel;
                        }

                        parsedata['tagname'] = tagname;
                        parsedata['tagkey'] = tagkey;
                        parsedata['tagvalue'] = hrtagvalue;
                        parsedata['tagtext'] = tagtext.replace(/\r?\n/g, '');


                        if( tagvalue !== '' ){
                                tagHtml = tag
                                    .replace('{{tagClass}}', tagclass)
                                    .replace('{{tagLabel}}', taglabel)
                                    .replace('{{tagData}}', tagdata);

                                parsetag = Liquid.parseAndRender(tagHtml, parsedata);
                                parsetags.push(parsetag);

                                //Search.vars.form.find('.ffd-selected-filtertags-html').append(tagHtml);
                                //tagsHtml += tagHtml;
                        }

                    }

                });
            }

        });


        Promise.all(parsetags).then(function(parservalues) {
            Search.vars.form.find('.ffd-selected-filtertags-html').html('');
           $.each(parservalues, function(index, html){
                Search.vars.form.find('.ffd-selected-filtertags-html').append(html);
           });
           //Search.vars.form.find('.ffd-selected-filtertags-html').html(tagsHtml);
        });

        //Liquid.parseAndRender(tagsHtml, parsedata).then(function(tagsHtml){

        //});




    },

    getSearchResults: function(){

        return new Promise(function(resolve,reject){

                var params = Search.getSearchParams();
                var query = Search.getSearchQuery(params);
                var $keywords_input = Search.vars.form.find('[data-keywordsearch]');
                if( $keywords_input.length > 0  ){
                    if( $keywords_input.val() !== '' ){
                        $('.ffd-keywords-search').text($keywords_input.val());
                    }
                }

                /*
                * ------------------
                * AJAX search
                */

                Search.generateSearchTags(query);

                query.action = "ffd/integration/search";
                Search.vars.doingsearch = true;

                let queryUrl = new URLSearchParams(location.search);
                const queryObject = [...queryUrl.entries()].reduce((obj, [key, value]) => ({...obj, [key]: value }), {})
                query.queryObject = queryObject;


                query.page = Search.vars.page;

                //console.log('QUERY', query, query.where, (query.where).lenght);

                jQuery.ajax({
                    type: "POST",
                    url: Settings.ajaxurl,
                    data: query,
                    dataType: 'json',
                    cache: false
                })
                .done(function(response) {
                    Search.vars.result_meta = response.meta;
                    Search.vars.paginator = response.paginator;
                    var payload = [response.listings, 'ajax'];

                    if( Search.vars.number_properties != response.number_properties ){
                        Search.vars.page = 1;
                    }

                    Search.vars.form.find('.ffd-search-paginator').html(Search.vars.paginator);

                    Search.vars.form.find('[data-paginator]').each(function (e) {
                        var $self = $(this);
                        var eventname = $self.attr('data-ffdevent') || '';

                        if (eventname !== '' && typeof eventname !== 'undefined') {
                            $self.on(eventname, function (e) {
                                if (!$(this).hasClass('ffd-autocomplete')) {
                                    Search.vars.page = $self.attr('data-value');
                                    Search.vars.form.submit();
                                }
                            });
                        }
                    });
                    $('html, body').animate({scrollTop:0}, 'fast');
                    Search.vars.number_properties = response.number_properties;


                    resolve(payload);console.log('payload', payload);
                    Search.vars.form.trigger('response_ready', payload);

                }).fail(function(){
                    Search.vars.doingsearch = false;
                    Search.stopLoading();
                    reject('Error: Could not fetch data.');
                }).always(function(){

                });
        });

    },

    FilterResults: function(){
        return new Promise(function(resolve,reject){

            var params = Search.getSearchParams();
            var r, data = Search.vars.data;


            Search.vars.doingsearch = true;

            data = _.filter(data, function(item) {

                r = false

                jQuery.each(params, function (_key, value) {



                        if( value !== '' && value !== null && value !== undefined ){

                            switch (_key) {

                                default:

                                    r = (item[_key].toLowerCase() == value.toLowerCase());

                                break;
                            }

                            if( !r){
                                return false;
                            }

                        }

                });

                return r;

            });

            resolve('Filtered Results');
            Search.vars.form.trigger('response_ready', [data, 'filtered']);

        });
    },



    getSearchQuery: function(params, context){

        if( typeof context === 'undefined' ){
            context = 'search';
        }
        var query = {}, $field='', fieldtype, fieldkeys;

        if( !params.hasOwnProperty('page') ){
            params['page'] = 1;
        }

        if(  Settings.limit != '' && !params.hasOwnProperty('limit') ){
            params['limit'] = Settings.limit;
        }

        if(  Settings.offset != '' && Settings.offset != '0'  ){
            params['offset'] = Settings.offset;
        }


        Search.log('getSearchQuery params:', params);
        //strip empty or 0 value params if necessary
         params = _.pick(params, function(value, name){

            if( name === undefined || value === undefined ) return false;

            //var key = Search.vars.postedfields[name]['fieldkey'];
            //$field = ( Search.vars.form.find('[data-fieldkey="'+key+'"]').length > 0 ) ? Search.vars.form.find('[data-fieldkey="'+key+'"]') : Search.vars.form.find('[name="'+key+'"]');
            $field = Search.vars.form.find('[name="'+name+'"]');
            var condition = $field.attr('data-fieldcondition') || '';



            if( condition !== '' ){

                if( (condition === 'ne' || condition === 'eq') && value === '' ){
                    return true;
                } else if( (condition.indexOf('gt') !== -1 || condition.indexOf('lt') !== -1) && value === '0' ){
                    return true;
                } else if( value == 0 || value == ''){
                    return false;
                }

            } else if( value == 0 || value == ''){

                return false;
            }

            return true;
        });


        query['where']={};
       /*  query['order'] = '';
        query['limit'] = '';
        query['offset'] = ''; */

        jQuery.each(params, function (name, value) {



            //$field = ( Search.vars.form.find('[data-fieldkey="'+key+'"]').length > 0 ) ? Search.vars.form.find('[data-fieldkey="'+key+'"]') : Search.vars.form.find('[name="'+key+'"]');

            $field = Search.vars.form.find('[name="'+name+'"]');

            switch (name) {
                case 'order':
                        if( value == $field.val() || value == $field.attr('data-fieldvalue') ){
                            query['order'] = value
                        }
                    break;
                case 'limit':
                        query['limit'] = value
                    break;
                case 'page':
                        query['offset'] = params['limit'] * ( params['page'] - 1);
                    break;

                default:

                    /* if( $field.attr('data-fieldname') ){

                    } else {

                    } */
                    var _key = ( Search.vars.postedfields[name] !== undefined ) ? Search.vars.postedfields[name]['fieldkey'] : false;


                        //convert value to format "value|compare|data_type|relation"
                        // relation = OR, AND


                        fieldtype = $field.attr('data-fieldtype') || '';
                        fieldkeys = $field.attr('data-fieldkeys') || '';

                        if( fieldtype == 'ffdsearch'){

                            var condition = $field.attr('data-fieldcondition') || '';
                            if( $field.is(':radio') || $field.is(':checkbox') ){
                                var radioCondition = Search.vars.form.find('[name="'+name+'"][value="'+value+'"]').attr('data-fieldcondition');
                                if( radioCondition !== undefined && radioCondition !== '' ){
                                    condition = radioCondition;
                                }
                            }

                            if( condition === '' ){
                                condition = value + '|' + 'eq';
                            } else {
                                condition = value + '|' + condition;
                            }




                            if('savesearch' !== context && fieldkeys !== '' && fieldkeys.indexOf(',') !== -1 ){

                                var pieces = fieldkeys.split(',');
                                var first_sub_clause = true;
                                var v,c,d,r;
                                $.each(pieces, function(piece_i, piece_v){
                                    var cformat = condition.split('|');
                                    var ccondition = '';

                                    if(  first_sub_clause == true ){
                                        if( typeof cformat[3] !== 'undefined' ){
                                            cformat[3] = "AND";
                                        }
                                        first_sub_clause = false;
                                    }

                                    ccondition = cformat.join('|');
                                    query['where'][piece_v] = ccondition
                                });


                            } else if(_key){


                                if(  query['where'][_key] !== undefined ){

                                    if( _.isArray(query['where'][_key]) ){
                                        query['where'][_key].push(condition);
                                    } else {
                                        var tmp = query['where'][_key];
                                        query['where'][_key] = [];
                                        query['where'][_key].push(tmp);
                                        query['where'][_key].push(condition);
                                    }

                                } else {
                                    query['where'][_key] = condition

                                }



                            }

                        }

                    break;
            }
        });

        //fill defaults from shortcode if needed
        if( Settings.where !== '' ){

            if( !query.hasOwnProperty('where') ) {
                query['where']={};
            }

            jQuery.each(Settings.where, function(index, value){
                if( typeof query['where'][index] === 'undefined' ){
                    query['where'][index] = value;
                }
            });

        }


        Search.log('getSearchQuery Final:', query);

        return query;
    },

    getSearchParams: function () {

        //var a = Search.vars.form.ffdSerializeObject();
        var a = Search.vars.form.serializeObject();
        //console.log('----', a);
        //return a;
        //console.log('aaaaa',a);
        var $formField;
        var params = {}, _key='';

        //_.each(a, function(value, key) {
        jQuery.each(a, function (name, value) {//console.log('name',name, 'value', value);

            if( value !== '' && typeof value !== 'undefined' && value !== null && value !== NaN ){

                if( _.isArray(value) ){
                    value = value.join(',');
                }

                var n,v, _key=false;
                Search.vars.form.find('input,select').each(function(){
                    n = $(this).prop('name'), v = $(this).val();
                    if( n==name && v == value ){
                        $formField = $(this);
                        _key = $(this).attr('data-fieldkey');
                        return;
                    }
                });
                if( _key === '' || _key === 'undefined'){
                    _key = $formField.data('fieldkey') || '';
                }

                var fieldobj={};

                fieldobj['fieldkey'] = name;
                fieldobj['value'] = value;
                if( _key && _key !== '' && _key !== undefined ){
                    fieldobj['fieldkey'] = _key;
                    Search.vars.postedfields[name] = fieldobj;
                } else {
                    Search.vars.postedfields[name] = fieldobj;
                }
                //else {
                    params[name] = value;
                //}
            }

        });



        if (Search.mapData != "") {
            Search.mapData += "";
            var mapDataArr = Search.mapData.split('&');
            for (i = 0; i < mapDataArr.length; i++) {
                if( typeof mapDataArr[i] !== undefined ){
                    var temp = mapDataArr[i].split('=');
                    if( temp.length > 0 && temp[0] !== undefined && params[temp[0]] !== undefined && temp[1] !== undefined ){
                        params[temp[0]] = temp[1];
                    }
                }
            }
        }


        Search.log('###SearchParams###', params);

        return params;



    },

    templates: {

        'iw_content' : '<div class="marker-popup">'+
                            '<div class="marker-popup-inner">' +
                                '<a href="{{the_permalink}}">' +
                                '<div class="thumbnail" style="background-image: url({{the_image}}),url({{image_placeholder}});"> </div>' +
                                '</a>' +
                                '<div class="popup-popup-info">' +
                                    '<p class="title" ><a href="{{the_link}}" title="{{the_title}}">{{the_title}}</a></p>' +
                                    '<p class="price">${{listing.listprice}}</p>' +
                                    '<p class="mls">MLS# {{listing.mlsid}}</p>' +
                                '</div>' +
                            '</div>' +
                        '</div>'

    },

    startLoading: function(){
        var ellipsis = '<div class="ffd-ellipsis-wrap"><div class="ffd-ellipsis"><div></div><div></div><div></div><div></div></div></div>';

        if( !Search.vars.form.hasClass('ffd-search-overlay') ){
            Search.vars.form.addClass('ffd-search-overlay');
            Search.vars.form.append(ellipsis);
        }
        Search.vars.form.addClass('loading');
    },

    stopLoading: function(){
        Search.vars.form.removeClass('loading');
    },

    log: function(){

        //var msg = arguments[0] !== undefined ? arguments[0] : "";
        //var type = arguments[1] !== undefined ? arguments[1] : "";

        if(Search.vars.debug ){
            $.each(arguments, function(i, v){
                console.log(v);
            });
        }



    },

    Key: function($dtype, item){

        //var str = arguments[0] !== undefined ? arguments[0] : "";
        //var item = arguments[1] !== undefined ? arguments[1] : "";
        var Key = '';
        switch (dtype) {
            case 'lat':
                if( item !== undefined && item.hasOwnProperty('ffd_latitude_pb') ){
                    Key = 'ffd_latitude_pb';
                } else if( item !== undefined && item.hasOwnProperty('lat') ){
                    return 'lat';
                }
            break;
            case 'lng':
                if( item !== undefined && item.hasOwnProperty('ffd_longitude_pb') ){
                    Key = 'ffd_longitude_pb';
                } else if( item !== undefined && item.hasOwnProperty('lng') ){
                    return 'lng';
                }
            break;
        }

        return Key;
    },


    helpers: {

        jq_escape:function( selector ) {

            return selector.replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" );

        },

        human_readable: function(text){

            text = text.replace('ffd_', '');
            text = text.replace('_pb', '');
            text = text.replace('_', ' ');

            return text;
        },

        check_input_value: function (str){
            var check = $.trim(""+str.toLowerCase());
            switch(check){
                case'any':
                case'all':
                    str = "";
                break;
            }

            return str;
        },

        parse_data_args:function(data){

            var data_props = {}, n,v,m, t;
            if( data ) {
                data = data.split('|');
                $.each(data, function(index, d){
                    d = d.split(',');
                    n = $.trim(d[0]);
                    v = $.trim(d[1]);
                    m = v.split(':');
                    if( m.length > 1 ){

                        v = (function(data){
                            var data_props = {}, n,v,m, t;

                            $.each(data, function(index, d){
                                d = d.split(';');
                                n = $.trim(d[0]);
                                v = $.trim(d[1]);
                                t = Number(v);
                                if( t !== NaN )
                                    v = t;

                                    data_props[n] = v;
                            });
                            console.log(data_props);
                            return data_props;
                        })(m);

                    } else {
                        t = Number(v);
                        if( t !== NaN )
                            v = t;
                    }
                    data_props[n] = v;
                });
            }
            else {
                data_props = {};
            }
            return data_props;

        },

        prepareData: function(data, type){

            if( type == 'ajax' ){
                var i=0;
                jQuery.each(data, function (data_key, item) {


                    item =  Search.helpers.parseStr(item, 'dataitem');


                    if( item['lat'] && item['lng'] && !isNaN(parseFloat(item['lat'])) && !isNaN(parseFloat(item['lat'])) ){
                        data[i] = item;
                        i++;
                    }

                });
            }

            return Promise.resolve(data);
        },

        itemLatLng: function(obj){
            var result = {'lat':'', 'lng':''},
                _lat='',
                _lng='';


            jQuery.each(obj, function(key, value){
                if( key === 'listing' && value.hasOwnProperty('lat') &&  value.hasOwnProperty('lng') ){
                    result['lat'] = value['lat'];
                    result['lng'] = value['lng'];
                    return result;
                } else if( key === 'meta' &&
                ( value.hasOwnProperty('ffd_latitude_pb') &&  value.hasOwnProperty('ffd_longitude_pb') || value.hasOwnProperty('lat') &&  value.hasOwnProperty('lng')) ){
                    result['lat'] = value['ffd_latitude_pb'] || value['lat'];
                    result['lng'] = value['ffd_longitude_pb'] || value['lng'];
                    return result;
                } else if( key === 'lat' ){
                    _lat = value;
                } else if( key === 'lat' ){
                    _lng = value;
                }
            });

            if( result['lat'] === '' && result['lat'] === '' && _lat !== '' && _lng !== '' ){
                result['lat'] = _lat;
                result['lng'] = _lng;
            }

            return result;
        },

        itemKeyValue: function(Item, Key){
            var result = false;

            jQuery.each(Item, function(key, value){
                if( key === 'listing' && value.hasOwnProperty(Key) ){
                    result = value[Key];
                    return result;
                } else if( key === 'meta' && value.hasOwnProperty(Key) ){
                    result = value[Key];
                    return result;
                } else if( key === 'post' && value.hasOwnProperty(Key) ){
                    result = value[Key];
                    return result;
                } else if( key === Key ){
                    result = value;
                    return result;
                }
            });

            return result;
        },


        dataItem: function(item){
            return Search.helpers.parseStr(item);
        },
        ObjectFilter: function( obj, predicate) {
            var result = {}, key;
            // ---------------^---- as noted by @CMS,
            //      always declare variables with the "var" keyword

            for (key in obj) {
                if (obj.hasOwnProperty(key) && !predicate(obj[key])) {
                    result[key] = obj[key];
                }
            }

            return result;
        },

        fillFormValues: function(){

            var values = Search.helpers.getQueryStringParams();
            if( values ){
                Search.log('Fill Form Values', values);
                var params = Search.vars.form.ffdSerializeObject();
                var $formField;
                jQuery.each(params, function(key, value){
                    $formField = Search.vars.form.find('[name="'+key+'"]')
                    if( values.hasOwnProperty(key) && values[key] !== '' && $formField.val() === '' ){
                        $formField.val(decodeURIComponent(values[key]));
                    }
                });

                return Promise.resolve('fillFormValues');

            } else {
                return Promise.resolve('fillFormValues');
            }

        },
        getQueryStringParams: function(queryString) {
            var query = (queryString || window.location.search); // delete ?
            if( query[0] === '?' ){
                query = query.substring(1);
            }

            if (!query) {
                return false;
            }

            return Search.helpers.parseStr(query);
        },

        parseStr: function(query, querytype){

            return _
            .chain(query.split('&'))
            .map(function(params) {
                var p = params.split('=');
                if( querytype && querytype == 'dataitem'){
                    var k = Settings.keynames;
                    k = k[p[0]];
                } else {
                    var k = p[0];
                }

                return [k, decodeURIComponent(p[1].replace(/\+/g, ' '))];
            })
            .object()
            .value();

        },

        compressQueryParams: function(data){
                var q = {}, ret = "";
                data = decodeURIComponent(data);
                data.replace(/([^=&]+)=([^&]*)/g, function(m, key, value){
                    q[key] = (q[key] ? q[key] + "," : "") + value;
                });
                for ( var key in q )
                    ret = (ret ? ret + "&" : "") + key + "=" + q[key];
                return ret;
        },

        render_content: function(content, values){






            /* jQuery.each(values, function (key, value) {
                content = content.replace('{{'+key+'}}', value);
            }); */

            var regex = /{{(.*?)}}/g;
            var matches = Search.helpers.preg_match_all(regex, content);


            if( matches.length > 0 ){
                matches[1].forEach(function (varname, i) {
                    if( typeof values[varname] !== undefined ){

                        content = content.replace(matches[0][i], values[varname] + '');
                    } else {
                        content = content.replace(matches[0][i], '');
                    }
                });
            }

            return content;

        },

        preg_match_all: function(regex, str){

            var m;
            var matches=[];

            while ((m = regex.exec(str)) !== null) {
            // This is necessary to avoid infinite loops with zero-width matches
            if (m.index === regex.lastIndex) {
                regex.lastIndex++;
            }

            // The result can be accessed through the `m`-variable.
            m.forEach(function (match, groupIndex) {

                if( typeof matches[groupIndex] === 'undefined'){
                    matches.push(groupIndex);
                    matches[groupIndex] = [];
                }
                matches[groupIndex].push(match);

            });
            }



            return matches;
        },

        number_format: function (number, decimals, decimal_sep, thousands_sep) {
            if( typeof number === 'undefined' )
                number = '';

            number =  number.replace('$', '');
            number = number.replace(/,/g, '');
            number = ( isNaN( parseInt(number) ) ) ? '' : parseInt(number) + '';

            var n = number,
                c = isNaN(decimals) ? 0 : Math.abs(decimals),
                d = decimal_sep || '.',
                t = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                sign = (n < 0) ? '-' : '',
                i = parseInt(n = Math.abs(n).toFixed(c)) + '',

                j = ((j = i.length) > 3) ? j % 3 : 0;

            return sign + (j ? i.substr(0, j) + t : '') +
                i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) +
                (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
        },

        abbreviate_number:function(num, fixed) {

            num =  num.replace('$', '');
            num = num.replace(/,/g, '');
            num =  parseInt(num);

            if (num === null) { return null; } // terminate early
            if (num === 0) { return '0'; } // terminate early
            fixed = (!fixed || fixed < 0) ? 0 : fixed; // number of decimal places to show
            var b = (num).toPrecision(2).split("e"), // get power
                k = b.length === 1 ? 0 : Math.floor(Math.min(b[1].slice(1), 14) / 3), // floor at decimals, ceiling at trillions
                c = k < 1 ? num.toFixed(0 + fixed) : (num / Math.pow(10, k * 3) ).toFixed(1 + fixed), // divide by power
                d = c < 0 ? c : Math.abs(c), // enforce -0 is 0
                e = d + ['', 'K', 'M', 'B', 'T'][k]; // append power
            return e;
        },

        currency: function (number, decimals, decimal_sep, thousands_sep) {

            return '$' + Search.helpers.number_format(number, decimals, decimal_sep, thousands_sep);
        },

        //  Format currency in us dollar notation
        money:function(value){
            return value.toString().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
        },

        convert_number: function(num, digits) {
            var si = [
                { value: 1E18, symbol: "E" },
                { value: 1E15, symbol: "P" },
                { value: 1E12, symbol: "T" },
                { value: 1E9, symbol: "G" },
                { value: 1E6, symbol: "M" },
                { value: 1E3, symbol: "k" }
            ], rx = /\.0+$|(\.[0-9]*[1-9])0+$/, i;
            for (i = 0; i < si.length; i++) {
                if (num >= si[i].value) {
                    return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
                }
            }
            num = parseFloat(num);
            return num.toFixed(digits).replace(rx, "$1");
        },

        isSerializable: function(obj) {
                if (_.isUndefined(obj) ||
                    _.isNull(obj) ||
                    _.isBoolean(obj) ||
                    _.isNumber(obj) ||
                    _.isString(obj)) {
                    return true;
                }

                if (!_.isPlainObject(obj) &&
                    !_.isArray(obj)) {
                    return false;
                }

                for (var key in obj) {
                    if (!Search.helpers.isSerializable(obj[key])) {
                    return false;
                    }
                }

                return true;
        },

        maybe_unserialize: function (data) {

            if (!Search.helpers.isSerializable(data) ) {
                return data;
            }

            var $global = (typeof window !== undefined ? window : global)

            var utf8Overhead = function (str) {
            var s = str.length
            for (var i = str.length - 1; i >= 0; i--) {
                var code = str.charCodeAt(i)
                if (code > 0x7f && code <= 0x7ff) {
                s++
                } else if (code > 0x7ff && code <= 0xffff) {
                s += 2
                }
                // trail surrogate
                if (code >= 0xDC00 && code <= 0xDFFF) {
                i--
                }
            }
            return s - 1
            }
            var error = function (type,
            msg, filename, line) {
            throw new $global[type](msg, filename, line)
            }
            var readUntil = function (data, offset, stopchr) {
            var i = 2
            var buf = []
            var chr = data.slice(offset, offset + 1)

            while (chr !== stopchr) {
                if ((i + offset) > data.length) {
                error('Error', 'Invalid')
                }
                buf.push(chr)
                chr = data.slice(offset + (i - 1), offset + i)
                i += 1
            }
            return [buf.length, buf.join('')]
            }
            var readChrs = function (data, offset, length) {
            var i, chr, buf

            buf = []
            for (i = 0; i < length; i++) {
                chr = data.slice(offset + (i - 1), offset + i)
                buf.push(chr)
                length -= utf8Overhead(chr)
            }
            return [buf.length, buf.join('')]
            }
            function _unserialize (data, offset) {
            var dtype
            var dataoffset
            var keyandchrs
            var keys
            var contig
            var length
            var array
            var readdata
            var readData
            var ccount
            var stringlength
            var i
            var key
            var kprops
            var kchrs
            var vprops
            var vchrs
            var value
            var chrs = 0
            var typeconvert = function (x) {
                return x
            }

            if (!offset) {
                offset = 0
            }
            dtype = (data.slice(offset, offset + 1)).toLowerCase()

            dataoffset = offset + 2

            switch (dtype) {
                case 'i':
                typeconvert = function (x) {
                    return parseInt(x, 10)
                }
                readData = readUntil(data, dataoffset, ';')
                chrs = readData[0]
                readdata = readData[1]
                dataoffset += chrs + 1
                break
                case 'b':
                typeconvert = function (x) {
                    return parseInt(x, 10) !== 0
                }
                readData = readUntil(data, dataoffset, ';')
                chrs = readData[0]
                readdata = readData[1]
                dataoffset += chrs + 1
                break
                case 'd':
                typeconvert = function (x) {
                    return parseFloat(x)
                }
                readData = readUntil(data, dataoffset, ';')
                chrs = readData[0]
                readdata = readData[1]
                dataoffset += chrs + 1
                break
                case 'n':
                readdata = null
                break
                case 's':
                ccount = readUntil(data, dataoffset, ':')
                chrs = ccount[0]
                stringlength = ccount[1]
                dataoffset += chrs + 2

                readData = readChrs(data, dataoffset + 1, parseInt(stringlength, 10))
                chrs = readData[0]
                readdata = readData[1]
                dataoffset += chrs + 2
                if (chrs !== parseInt(stringlength, 10) && chrs !== readdata.length) {
                    error('SyntaxError', 'String length mismatch')
                }
                break
                case 'a':
                readdata = {}

                keyandchrs = readUntil(data, dataoffset, ':')
                chrs = keyandchrs[0]
                keys = keyandchrs[1]
                dataoffset += chrs + 2

                length = parseInt(keys, 10)
                contig = true

                for (i = 0; i < length; i++) {
                    kprops = _unserialize(data, dataoffset)
                    kchrs = kprops[1]
                    key = kprops[2]
                    dataoffset += kchrs

                    vprops = _unserialize(data, dataoffset)
                    vchrs = vprops[1]
                    value = vprops[2]
                    dataoffset += vchrs

                    if (key !== i) {
                    contig = false
                    }

                    readdata[key] = value
                }

                if (contig) {
                    array = new Array(length)
                    for (i = 0; i < length; i++) {
                    array[i] = readdata[i]
                    }
                    readdata = array
                }

                dataoffset += 1
                break
                default:
                error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype)
                break
            }
            return [dtype, dataoffset - offset, typeconvert(readdata)]
            }

            return _unserialize((data + ''), 0)[2]
            }

    }


};


return Search;

}



// Content update and back/forward button handler
History.Adapter.bind(window, 'statechange', function() {
    var State = History.getState();
    // Do ajax

    // Log the history object to your browser's console
    History.log(State);
});

Liquid.registerFilter('money', function(input, decimal){
    if( typeof decimal === 'undefined' || '' === decimal ){
        decimal = 0;
    }
    return FFD_Search().helpers.number_format(input, decimal, '.', ',');
});

Liquid.registerFilter('format_number', function(input, decimal, decimal_sep, thousands_sep){
    if( typeof decimal === 'undefined' || '' === decimal ){
        decimal = 0;
    }

    if( typeof decimal_sep === 'undefined' || '' === decimal_sep ){
        decimal_sep = '.';
    }

    if( typeof thousands_sep === 'undefined' || '' === thousands_sep ){
        thousands_sep = '.';
    }

    return FFD_Search().helpers.number_format(input, decimal, decimal_sep, thousands_sep);
});

Liquid.registerFilter('abbreviate_number', function(input, decimal){
    if( typeof decimal === 'undefined' || '' === decimal ){
        decimal = 0;
    }

    input =  FFD_Search().helpers.abbreviate_number(input, decimal);

    return input;
});




Liquid.registerFilter('append_if', function(input, string){
    if( input === '' )
        return ''
    else
        return input + string;

});


Liquid.registerFilter('prepend_if', function(input, string){
    if( input === '' )
        return ''
    else
        return string + input;

});

Liquid.registerFilter('appendattr', function (input, charlimit) {



    if( input === '' )
        return '';


    if( typeof charlimit === 'undefined' || charlimit < 1 ){
        charlimit = 50;
    }



    var atts = '';
    jQuery.each(input, function(key, value){

        if( value.length <= charlimit )
            $atts += ' data-'+key+'="'+value+'" ';

    });



    return atts;

});



jQuery(document).on('ffd-gmap-api-ready', function(){



});

/*
*
* on DOM Loaded
*/
$(function(){
    $('form[data-ffdsearch]').each(function(e){

       FFD_Search().init(null, $(this));
    });
});
})(jQuery, FFD_Search_Settings, window.History, FFD_Liquid, Cookies);
