(function($, Settings, History, Liquid, Cookies){

// Content update and back/forward button handler
History.Adapter.bind(window, 'statechange', function() {
    var State = History.getState();
    // Do ajax

    // Log the history object to your browser's console
    History.log(State);
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
    console.log(from,to,diff);
    return difference(diff);
  }

$.fn.serializeObject = function(){

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

var Search = {

    vars: {
        map: false,
        bounds: false,
        idle: false,
        markers: [],
        markerClusterer:null,
        infoWindow: null,
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
        debug: false
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
            // run once when map is ready with data/makers loaded the first time
            // after initialization

            if( Search.vars.map ){

                Search.vars.map.setZoom(12);
                Search.vars.map.setCenter(new google.maps.LatLng('37.4161156','-122.1213172'));

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
            if( Search.vars.search_result !== null ){

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

        },


    },
    //Entry Point, things start happening here
    init: function(data){

        this.vars.debug = true //show logs in browser console

        //initialize map
        this.vars.map = this.loadMap(jQuery('#ffd-search-gmap'));

         //initialize map
         this.vars.form = jQuery('#ffd-search-form');

        //initialize drawing manager for polygon search support
        this.vars.drawingmanager = this.loadDrawingManager(this.vars.map );


        var init_data = false;
        if( data && typeof data !== 'undefined' && data.length>0 ){
            //render makers on map
            Search.loadData(data).then(function(){
                Search.events.initMapReady();
            });
            init_data = true;
        }

        //load Events
        this.loadMapEvents();

        var mapLoadedFirstTime = false;

        Search.vars.form.on('form_ready', function(e){
            Search.vars.form = $form_obj;
            Search.loadFormEvents();

            if( init_data === false ){
                //load data using ajax
                Search.startLoading();
                Search.getSearchResults().then(function(payload){
                    return null;
                });


                $form_obj.on('result_loaded', function(){

                    //only trigger if this is first load map
                    if( !mapLoadedFirstTime ){

                        if( $form_obj.hasClass('has-query-params') ){
                            Search.FilterResults();
                        } else {
                            Search.events.initMapReady();
                        }
                        mapLoadedFirstTime = true;
                    }
                });



            }
        });
    },

    loadFormEvents: function(){



        var $form = Search.vars.form;
        $form.on('submit', function(e){

            Search.log('Search Form Submitted');

            e.preventDefault();

            var urlPath = ajax_obj.page_url;
			var current_href = $(location).attr('href');
			var current_title = $(document).attr('title');
        	History.pushState({path: urlPath}, current_title, urlPath + '?' + $form.serialize()); // When we do this, History.Adapter will also execute its contents.

            if( !Search.vars.doingsearch ){
                Search.startLoading();
                Search.FilterResults();
            }
        });

        //triggered when results are filtered based on user input
        $form.on('response_ready', function(e, response, type){



            Search.closeAllInfoWindows();
            Search.vars.mapData = "";

            //clear all (if) existing makers from the map
            Search.clearMarkers().then(function(){


                return Search.helpers.prepareData(response, type);


            }).then(function(data){

                //load markers using new data
                var updateData = ( type == 'ajax') ? 'yes' : 'no';

                return Search.loadData(data, updateData);

            }).then(function(){

                //last set doingsearch to false
                Search.vars.doingsearch = false;
                Search.stopLoading();
                Search.vars.form.trigger('result_loaded');
            });


        });

    },

    loadMap: function ($target, map_args) {
            //initialize map
            var map;
            map_args = map_args || {};
            map_args = jQuery.extend(true, {
                center: { lat: 37.7749, lng: -122.4194 },
                scrollwheel: false,
                mapTypeControl: true,
                zoom: 10,
                maxZoom: 17
            }, map_args);

            /*  Zoom Level
                1: World
                5: Landmass/continent
                10: City
                15: Streets
                20: Buildings
            */
            var map = new google.maps.Map($target.get(0), map_args);
            return map;
    },

    loadData: function(){

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


        //clear listing cards html
        jQuery('#ffd-mapsearch-status').html('');
        jQuery('#map_search_listings').html('');


            if( jQuery('#ffd_listing_template').length > 0 && data.length <= 0 ){

                jQuery('#ffd-mapsearch-status').html('<p>No results found.</p>');

                return  Promise.resolve('Load Listings No Results!');;
            }

            jQuery('#ffd-mapsearch-status').html('<p>Showing ' + data.length + ' of Total ' + Search.vars.data.length);

            if( data.length > 50 ){
                data = data.slice(0, 50);
            }

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

                    item['price'] = item['listprice'] || '';
                    item['area'] = item['neighborhood'] || '';
                    item['image_placeholder'] = Settings.image_placeholder;
                    content = jQuery('#ffd_listing_template').html();

                    Liquid.parseAndRender(content, item).then(function(content){

                        jQuery('#map_search_listings').append(content);
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

    updateListings: function(){

        var bounds =  Search.vars.map.getBounds();
        var data = [],  latlng;
        jQuery.each(Search.vars.search_result, function (item_index, item) {

            //item = Search.helpers.dataItem(item);

            if( item.hasOwnProperty('lat') && item.hasOwnProperty('lng')
                && item['lat'] !== '' && item['lng'] != ''
            ){

                latlng = new google.maps.LatLng(item['lat'], item['lng']);
                if( bounds.contains(latlng) ){
                    data.push(item);
                }
            }

        });

        return new Promise(function(resolve, reject){

            Search.loadListings(data).then(function(){
                resolve('Update Listings Done!');
            });
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

            Search.vars.map.addListener('clusterclick', Search.events.clusterclick);
        }

        if( Search.vars.drawingmanager ){

            google.maps.event.addListener(Search.vars.drawingmanager, "drawingmode_changed", Search.events.drawingmodechange);
            google.maps.event.addListener(Search.vars.drawingmanager, 'overlaycomplete', Search.events.overlaycomplete);

        }

    },

    loadMarkers: function(map, data){


        var marker_ids = [],
                markers_to_delete,
                bounds = new google.maps.LatLngBounds();

            //data = data.slice(0, 5);
            jQuery.each(data, function (item_index, item) {

                //item = Search.helpers.dataItem(item);

                var marker,
                    id = item_index,
                    lat = item['lat'],
                    lng = item['lng'],
                    price = item['listprice'] || '',
                    url = '';


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



                    }

            });



            Search.vars.bounds = bounds;
            Search.vars.map.setCenter(bounds.getCenter());
            Search.vars.map.fitBounds(bounds);

            Search.vars.markerClusterer = new MarkerClusterer(map, Search.vars.markers, {maxZoom: 14, zoomOnClick:true, averageCenter:false, minimumClusterSize:2, imagePath: ajax_obj.cluster_img_path});
            //markerClusterer.addMarkers(Search.vars.markers);

        return Promise.resolve('Load Markers Done!');

    },

    addMarkerWithLabel: function(map, item_index, item){

                var id = item_index,
                lat = item['lat'],
                lng = item['lng'],
                price = item['listprice'] || '',
                url = '';

                var p = Search.helpers.convert_number(price.replace(/,/g, ""), 1);

       return  new MarkerWithLabel({
                map: map,
                position: new google.maps.LatLng(lat,lng),
                url: url,
                icon: " ",// hbg.themeurl + '/img/mapicon.png',
                labelContent: "<div class='markerLabelInside'>$" + p + "</div>",
                labelInBackground: false,
                labelClass: "markerLabelOutside"
            });

    },

    addMarker: function(map, item_index, item){

        var id = item_index,
                lat = item['lat'],
                lng = item['lng'],
                price = item['listprice'] || '',
                url = '';

        var imageUrl = 'http://chart.apis.google.com/chart?cht=mm&chs=24x32&' +
          'chco=FFFFFF,008CFF,000000&ext=.png';

        var markerImage = new google.maps.MarkerImage(imageUrl, new google.maps.Size(24, 32));
        var p = Search.helpers.convert_number(price.replace(/,/g, ""), 1);

        return  new google.maps.Marker({
            position: new google.maps.LatLng(lat,lng),
            draggable: false,
            label: "$" + p,
            icon: markerImage
          });

    },

    iw_content: function(item_index, item){

            item['image_placeholder'] = Settings.image_placeholder
            var content;
            var formated_price = parseFloat(item['listprice']);
                formated_price = formated_price.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            item['listprice'] = formated_price;

            if( jQuery('#ffd_iw_template').length > 0 ){
                content = jQuery('#ffd_iw_template').html();
            } else {
                content = '<div class="marker-popup"><div class="marker-popup-container clearfix">' +
                            '<a href="{{link}}">' +
                            '<div class="marker-popup-thumbnail" style="background-image: url({{image}}),url({{image_placeholder}});"> </div>' +
                            '</a>' +
                            '<div class="popup-popup-info">' +
                            '<p class="title" title="{{post_title}}"><a href="{{link}}">{{post_title}}</a></p>' +
                            '<p class="price">${{listprice}}</p>' +
                            '<p class="mls">MLS# {{mls_id}}</p>' +
                            '</div>' +
                        '</div>';
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

    getSearchResults: function(){

        return new Promise(function(resolve,reject){
                var params = Search.getSearchParamsPlus();
                /*
                * ------------------
                * AJAX search
                */
                params.action = "ffdl/listings/mapsearch";
                Search.vars.doingsearch = true;

                jQuery.ajax({
                    type: "POST",
                    url: ajax_obj.ajaxurl,
                    data: params,
                    dataType: 'json',
                    cache: false
                })
                .done(function(response) {
                    var payload = [response, 'ajax'];
                    resolve(payload);
                    Search.vars.form.trigger('response_ready', payload);

                }).fail(function(){
                    reject('error getSearchResults');
                }).always(function(){

                });
        });

    },

    FilterResults: function(){
        return new Promise(function(resolve,reject){

            var params = Search.getSearchParams();
            var r, data = Search.vars.data;

            Search.log(params);
            Search.vars.doingsearch = true;

            data = _.filter(data, function(item) {

                r = false

                jQuery.each(params, function (_key, value) {



                        if( value !== '' && value !== null && value !== 'undefined' ){

                            switch (_key) {
                                case 'keywords':
                                        value = value.toLowerCase();
                                    r = (    (item['city'] && item['city'].toLowerCase().indexOf(value) !== -1)
                                                || (item['mls_id'] && item['mls_id'].toLowerCase().indexOf(value)  !== -1)
                                                || ( item['address'] && item['address'].toLowerCase().indexOf(value)  !== -1 )
                                                || ( item['postalcode'] && item['postalcode'].toLowerCase().indexOf(value) !== -1)
                                                || ( item['neighborhood'] && item['neighborhood'].toLowerCase().indexOf(value) !== -1)
                                            );
                                            (item['mls_id'], (item['mls_id'] && item['mls_id'].toLowerCase().indexOf(value)), r);
                                    break;

                                case 'baths':
                                case 'beds':
                                case 'minlistprice':
                                case 'minprice':
                                case 'minsize':
                                case 'minyearbuilt':
                                case 'minsqft':

                                    if( _key==='beds' && value == 0 || value=='studio' ){

                                        r = ( item[_key] && parseInt(item[_key]) === 0);

                                    } else {

                                        _key = _key.replace('min', '');
                                        r = ( item[_key] && parseFloat(item[_key]) >= parseFloat(value));

                                    }



                                    break;
                                case 'maxlistprice':
                                case 'maxprice':
                                case 'maxsize':
                                case 'maxyearbuilt':
                                case 'maxsqft':

                                    _key = _key.replace('max', '');
                                    r = (item[_key] && parseFloat(item[_key]) <= parseFloat(value));

                                    break;
                                case 'proptype':



                                    if( item[_key] ){
                                        r = _.indexOf(value, item[_key]);
                                        r = ( r !== -1 );
                                    } else {
                                        r = false;
                                    }



                                    break;

                                case 'status':
                                        if( item[_key] && value.indexOf(',') !== -1 ){
                                            r = _.indexOf(value.split(','), item[_key]);
                                            r = ( r !== -1 );
                                        } else {
                                            r = ( item[_key] && item[_key].toLowerCase() == value.toLowerCase());
                                        }

                                break;

                                case 'poly':

                                        var bounds =  Search.vars.map.getBounds();
                                        if( item.hasOwnProperty('lat') && item.hasOwnProperty('lng')
                                            && item['lat'] !== '' && item['lng'] != ''
                                        ){

                                            latlng = new google.maps.LatLng(item['lat'], item['lng']);
                                            r = google.maps.geometry.poly.containsLocation(latlng, bermudaTriangle);

                                        } else {

                                            r = false;
                                        }

                                break;

                                default:

                                    r = (item[_key] && item[_key].toLowerCase() == value.toLowerCase());

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

    getSearchParams: function () {



        var $form = Search.vars.form;
        var formData = $form.serializeObject()



        var params = {
            keywords: formData['search_txt'] || '',
            beds: formData['beds'] || '',
            baths: formData['baths'] || '',

            minlistprice: formData['min_price'] || '',
            maxlistprice: formData['max_price'] || '',
            minsize: formData['lotsize-min'] || '',
            maxsize: formData['lotsize-max'] || '',
            minyearbuilt: formData['yearbuilt-min'] || '',
            maxyearbuilt: formData['yearbuilt-max'] || '',
            minsqft: formData['sqft-min'] || '',
            maxsqft: formData['sqft-max'] || '',

            proptype: formData['property_type_filter'] || '',
            status: formData['status'] || '',
            style: formData['style_type_filter'] || '',
            view: formData['view'] || '',
            parking: formData['parking'] || '',
            city: formData['city_type_filter'] || '',
            complex: formData['complex'] || '',
            poly: formData['poly'] || ''
        }

        params = _.extend({}, params, formData);


        if (Search.mapData != "") {
            Search.mapData += "";
            var mapDataArr = Search.mapData.split('&');
            for (i = 0; i < mapDataArr.length; i++) {
                if( typeof mapDataArr[i] !== 'undefined' ){
                    var temp = mapDataArr[i].split('=');
                    if( temp.length > 0 ){
                        params[temp[0]] = temp[1];
                    }
                }
            }
        }

        //strip empty or 0 value params
        params = _.pick(params, function(value, key){

            if( value == '' || value == 0 || value === 'undefined' || key === 'undefined')
                return false;
            else
                return true;
        });

        return params;



    },

    getSearchParamsPlus: function(){

        var params = Search.getSearchParams();
        var $form = Search.vars.form;

        var result = {};
        jQuery.each(params, function (key, value) {

            switch (key) {
                case 'limit':
                        result['limit'] = value
                    break;
                case 'page':
                        result['offset'] = value * ( params['page'] - 1);
                    break;

                default:
                    var _key = key;

                        if( typeof result['where'] === 'undefined' ) {
                            result['where']={};
                        }

                        //convert value to format "value|compare|data_type|relation"
                        // relation = OR, AND

                        var fieldtype = $form.find('[name="'+_key+'"]').attr('data-fieldtype') || '';

                        if( $fieldtype == 'ffdsearch'){

                            switch (_key) {
                                case 'keywords':
                                    result['where']['city'] = value + '|LIKE|CHAR|OR';
                                    result['where']['mls_id'] = value  + '|LIKE|NUMERIC|OR';
                                    result['where']['address'] = value  + '|LIKE|CHAR|OR';
                                    result['where']['postalcode'] = value  + '|LIKE|NUMERIC|OR';
                                    result['where']['neighborhood'] = value  + '|LIKE|CHAR|OR';
                                    break;

                                case 'minprice':
                                case 'minsize':
                                case 'minyearbuilt':
                                case 'minsqft':
                                    _key = _key.replace('min', '');
                                    result['where'][_key] = value + '|' + '>=';
                                    break;
                                case 'maxprice':
                                case 'maxsize':
                                case 'maxyearbuilt':
                                case 'maxsqft':
                                    _key = _key.replace('max', '');
                                    result['where'][_key] = value + '|' + '<=';
                                    break;
                                case 'proptype':
                                    result['where'][_key] = value + '|' + 'IN';
                                    break;
                                default:
                                    var condition = $form.find('[name="'+_key+'"]').attr('data-filtercondition') || '';
                                    if( condition === '' ){
                                        result['where'][_key] = value + '|' + '=';
                                    } else {
                                        result['where'][_key] = value + '|' + condition;
                                    }
                                    break;
                            }

                        }

                    break;
            }
        });

        //fill defaults from shortcode if needed
        if( Settings.where !== '' ){

            if( !result.hasOwnProperty('where') ) {
                result['where']={};
            }

            jQuery.each(Settings.where, function(index, value){
                if( typeof result['where'][index] === 'undefined' ){
                    result['where'][index] = value;
                }
            });

        }

        if(  Settings.limit != '' && !result.hasOwnProperty('limit') ){
            result['limit'] = Settings.limit;
        }

        if(  Settings.offset != '' && !result.hasOwnProperty('offset') ){
            result['offset'] = Settings.offset;
        }

        return result;
    },

    startLoading: function(){

        jQuery('.ffd-mapsearch-outer').addClass('loading');
    },

    stopLoading: function(){

        jQuery('.ffd-mapsearch-outer').removeClass('loading');
    },

    log: function(){

        var msg = arguments[0] !== undefined ? arguments[0] : "";
        var type = arguments[1] !== undefined ? arguments[1] : "";

        if( Search.vars.debug ){
            console.log(msg);
        }


    },

    helpers: {
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

        getQueryParams: function(queryString) {
            var query = (queryString || window.location.search); // delete ?
            if( query[0] === '?' ){
                query = query.substring(1);
            }

            if (!query) {
                return false;
            }

            return Search.helper.parseStr(query);
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
                return [k, decodeURIComponent(p[1])];
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
                    if( typeof values[varname] !== 'undefined' ){

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

            number = number.replace('$', '');
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

        currency: function (number, decimals, decimal_sep, thousands_sep) {

            return '$' + Search.helpers.number_format(number, decimals, decimal_sep, thousands_sep);
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

            var $global = (typeof window !== 'undefined' ? window : global)

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

jQuery('#ffd-integration-search').on('listings_rendered', function(e){

    var i=0, id, link, properties_nav=[];
    $('[data-listings-html] [data-listing-item] a[data-property_id]').each(function(e){

        var id = $(this).attr('data-property_id');
        var link = $(this).attr('href');
        properties_nav[i] = {'id':id, link:link};
        i++;
    });

    console.log('properties_nav', properties_nav);

    Cookies.remove('properties_nav');
    Cookies.set('properties_nav', properties_nav, { expires: 1 });

});

FFD_Map_Search.init();




/*
*
* on DOM Loaded
*/
$(function(){




});
})(jQuery, FFD_Search_Settings, window.History, new Liquid(), Cookies);
