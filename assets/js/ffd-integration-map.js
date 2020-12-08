var FFD_Liquid = new Liquid();
(function($, Settings, window, Liquid, Cookies){

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


$.fn.tagNameLowerCase = function() {
    return this.prop("tagName").toLowerCase();
};

var gMap = {

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

            gMap.log('initMapReady ( run onetime when data/markers loaded the first time) ');

            // run once when map is ready with data/markers loaded the first time
            // after initialization

            if( gMap.vars.map ){


                if( Settings.lat && Settings.lng && Settings.lat !== 'undefined' &&  Settings.lng !== 'undefined' ){
                    if( Settings.zoom  && Settings.zoom !== 'undefined' ){
                        gMap.vars.map.setZoom(Settings.zoom);
                    }
                    gMap.log('initMapReady Set Center...');
                    gMap.vars.map.setCenter(new google.maps.LatLng(Settings.lat, Settings.lng));
                }

            }

        },
        idle: function(){
            gMap.log('Event Triggered: idle');

            gMap.vars.idle = true;


            //last update event status;
            gMap.vars.eventstatus = 'idle';
        },

        boundschanged: function(){
            gMap.log('Event Triggered: boundschanged');

            gMap.vars.idle = false;
            if( gMap.vars.search_result !== null ){

                //update displayed listings on bounds changed
                gMap.updateListings().then(function(){
                    gMap.log('Listings Updated.');
                });

            }


            //last update event status;
            gMap.vars.eventstatus = 'boundschanged';
        },

        zoomchange: function(){
            gMap.log('Event Triggered: zoomchange');

            gMap.vars.idle = false;

            //last update event status;
            gMap.vars.eventstatus = 'zoomchange';
        },

        dragend: function(){
            gMap.log('Event Triggered: dragend');

            gMap.vars.idle = false;

            //last update event status;
            gMap.vars.eventstatus = 'dragend';
        },

        clusterclick: function(){
            gMap.log('Event Triggered: clusterclick');

            gMap.vars.idle = false;

            //last update event status;
            gMap.vars.eventstatus = 'clusterclick';
        },

        drawingmodechange: function(){
            gMap.log('Event Triggered: drawingmodechange');

            gMap.vars.idle = false;

            if (gMap.vars.drawingmanager.getDrawingMode() != null) {
                gMap.clearPoly();
            }


            //last update event status;
            gMap.vars.eventstatus = 'drawingmodechange';

        },

        overlaycomplete: function(event) {
            gMap.log('Event Triggered: overlaycomplete');

            gMap.vars.idle = false;

            var newShape = event.overlay;
            newShape.type = event.type;
            gMap.vars.shapes.push(newShape);
            gMap.vars.areaChanged = true;

            var len = newShape.getPath().getLength();
            var poly = [];

            for (var i = 0; i < len; i++){
                poly.push(newShape.getPath().getAt(i).toUrlValue(5).replace(",", " "));
            }

            var polydata = poly.join() + "," + poly[0];
            gMap.vars.mapData = "poly=" + polydata;
            gMap.vars.map.fitBounds(event.overlay.getBounds());

            gMap.updateListings().then(function(){
                gMap.log('Listings Updated.');
            });


            if (gMap.vars.drawingmanager.getDrawingMode()) {
                gMap.clearPoly();
                //gMap.vars.drawingmanager.setDrawingMode(null);
            }


            //last update event status;
            gMap.vars.eventstatus = 'overlaycomplete';

        },


    },

    //Entry Point, things start happening here
    init: function(gmap_selector, data, item_selector){


        gMap.vars.debug = true //show logs in browser console

        //initialize map
        gMap.vars.map = gMap.loadMap($(gmap_selector));


        //initialize drawing manager for polygon search support
        gMap.vars.drawingmanager = gMap.loadDrawingManager(gMap.vars.map );


        var init_data = false;
        if( data && typeof data !== 'undefined' && data.length>0 ){
            //render markers on map
            gMap.loadMarkers(gMap.vars.map , data).then(function(){
                gMap.events.initMapReady();
            });
            init_data = true;
        }

        //load Events
        gMap.loadMapEvents();
        var mapLoadedFirstTime = false;

    },

    loadMap: function ($target, map_args) {

            if( $target.length < 1 )
                return null;

            $target.addClass('ffd-integration-gmap');

            //initialize map
            var map;
            map_args = map_args || {};
            map_args = jQuery.extend(true, {
                center: { lat: 37.7749, lng: -122.4194 },
                scrollwheel: false,
                mapTypeControl: true,
                zoom: 12,
                maxZoom: 18
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

        if( gMap.vars.map ){

            gMap.vars.map.addListener('idle', gMap.events.idle);
            gMap.vars.map.addListener('bounds_changed', gMap.events.boundschanged);
            gMap.vars.map.addListener('zoom_changed', gMap.events.zoomchange);
            gMap.vars.map.addListener('dragend', gMap.events.dragend);

            gMap.vars.map.addListener('clusterclick', gMap.events.clusterclick);
        }

        if( gMap.vars.drawingmanager ){

            google.maps.event.addListener(gMap.vars.drawingmanager, "drawingmode_changed", gMap.events.drawingmodechange);
            google.maps.event.addListener(gMap.vars.drawingmanager, 'overlaycomplete', gMap.events.overlaycomplete);

        }

    },

    loadMarkers: function(map, data){

        gMap.log('Loading Markers...');
        var marker_ids = [],
                markers_to_delete,
                bounds = new google.maps.LatLngBounds();

            //data = data.slice(0, 5);
            jQuery.each(data, function (item_index, item) {

                //item = gMap.helpers.dataItem(item);
                var latlng = gMap.helpers.itemLatLng(item);
                var marker,
                    id = item_index,
                    lat = latlng['lat'],
                    lng = latlng['lng'];



                    if (undefined !== lat && undefined !== lng && '' !== lat && '' !== lng ) {

                        lat = parseFloat(lat);
                        lng = parseFloat(lng);

                        if (!isNaN(lat) && !isNaN(lng)) {

                            marker = gMap.addMarkerWithLabel(map, item_index, item);
                            //marker = gMap.addMarker(map, item_index, item);
                            gMap.vars.markers.push(marker);

                            gMap.current.marker = marker;
                            gMap.current.marker_index = id;

                            (function (marker, item_index, content) {
                                google.maps.event.addListener(marker, "click", function (e) {
                                        gMap.iw_content(item_index, item).then(function(content){
                                            gMap.vars.infoWindow.setContent(content);
                                            gMap.vars.infoWindow.open(map, marker);
                                            return content;
                                        });

                                });
                            })(marker, item_index, item);

                            bounds.extend(marker.getPosition());

                        }



                    }

            });



            gMap.vars.bounds = bounds;
            gMap.vars.map.setCenter(bounds.getCenter());
            gMap.vars.map.fitBounds(bounds);

            gMap.vars.markerClusterer = new MarkerClusterer(map, gMap.vars.markers, {maxZoom: 14, zoomOnClick:true, gridSize:20, imagePath: Settings.cluster_img_path});
            //markerClusterer.addMarkers(gMap.vars.markers);
            gMap.log('!!!Loaded Markers!!!');
        return Promise.resolve('Load Markers Done!');

    },

    addMarkerWithLabel: function(map, item_index, item){

                var latlng = gMap.helpers.itemLatLng(item);
                var id = item_index,
                lat = latlng['lat'],
                lng = latlng['lng'],
                price = gMap.helpers.itemKeyValue(item, 'listprice') || gMap.helpers.itemKeyValue(item, 'ffd_listingprice_pb') || '',
                url = '';
                if( price === 'undefined' || price === '' ){
                    p = "&nbsp;"
                } else {
                var p = gMap.helpers.convert_number(price.replace(/,/g, ""), 1);
                    p = "$" + p;
                }

       return  new MarkerWithLabel({
                map: map,
                position: new google.maps.LatLng(lat,lng),
                url: url,
                icon: " ",// hbg.themeurl + '/img/mapicon.png',
                labelContent: "<div class='markerLabelInside'>" + p + "</div>",
                labelInBackground: false,
                labelClass: "markerLabelOutside"
            });

    },

    addMarker: function(map, item_index, item){

        var id = item_index,
                lat = item['lat'],
                lng = item['lng'],
                price = item['ffd_listingprice_pb'] || '',
                url = '';

        var imageUrl = 'http://chart.apis.google.com/chart?cht=mm&chs=24x32&' +
          'chco=FFFFFF,008CFF,000000&ext=.png';

        var markerImage = new google.maps.MarkerImage(imageUrl, new google.maps.Size(24, 32));
        var p = gMap.helpers.convert_number(price.replace(/,/g, ""), 1);

        return  new google.maps.Marker({
            position: new google.maps.LatLng(lat,lng),
            draggable: false,
            label: "$" + p,
            icon: markerImage
          });

    },

    iw_content: function(item_index, item){

            item['image_placeholder'] = Settings.image_placeholder
            var content='';
            var formated_price = parseFloat(item['ffd_listingprice_pb']);
                formated_price = formated_price.toLocaleString('us', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            item['ffd_listingprice_pb'] = formated_price;

            if( jQuery('#ffd-search-iwtemplate').length > 0 ){
                content = jQuery('#ffd-search-iwtemplate').html();
            }

            if( content === '' ) {
                content = '<div class="marker-popup"><div class="popup-container clearfix">' +
                            '<a href="{{the_permalink}}">' +
                            '<div class="thumbnail" style="background-image: url({{the_image}}),url({{image_placeholder}});"> </div>' +
                            '</a>' +
                            '<div class="popup-popup-info">' +
                            '<p class="title" ><a href="{{the_link}}" title="{{the_title}}">{{the_title}}</a></p>' +
                            '<p class="price">${{listing.listprice}}</p>' +
                            '<p class="mls">MLS# {{listing.mlsid}}</p>' +
                            '</div>' +
                        '</div>';
            }

            return Liquid.parseAndRender(content, item);

    },



    closeAllInfoWindows: function(){

       if( gMap.vars.infowindows.length > 0 ){
            jQuery.each(gMap.vars.infowindows, function (index, iw) {
                iw.close();
            });
       }

    },

    clearMarkers: function(){




       gMap.log('ClearMarkers Before:' + gMap.vars.markers.length );

        jQuery.each(gMap.vars.markers, function (i, value) {
            gMap.vars.markers[i].setMap(null);
        });
        gMap.vars.markers = [];

        gMap.log('ClearMarkers After:' + gMap.vars.markers.length );


        if( gMap.vars.markerClusterer !== null ){
            gMap.vars.markerClusterer.clearMarkers();
        }

        return Promise.resolve('Clear Markers Done!');
    },


    clearPoly: function () {
        for (var i = 0; i < gMap.vars.shapes.length; i++) {
            gMap.vars.shapes[i].setMap(null);
        }
        gMap.vars.shapes = [];
    },


    startLoading: function(){
        var ellipsis = '<div class="ffd-ellipsis-wrap"><div class="ffd-ellipsis"><div></div><div></div><div></div><div></div></div></div>';
        if( jQuery('.ffd-search-overlay').length < 1 ){
            jQuery('form[ffd-search-form]').addClass('ffd-search-overlay');
            var $overlay = jQuery('form[ffd-search-form]');
        } else {
            var $overlay = jQuery('.ffd-search-overlay')
        }


        $overlay.find('.ffd-ellipsis').remove();
        $overlay.append(ellipsis);
        $overlay.addClass('loading');
    },

    stopLoading: function(){
        var $overlay = jQuery('.ffd-search-overlay');
        $overlay.removeClass('loading');
    },

    log: function(){

        //var msg = arguments[0] !== undefined ? arguments[0] : "";
        //var type = arguments[1] !== undefined ? arguments[1] : "";


        $.each(arguments, function(i, v){
            console.log(v);
        });



    },

    helpers:{

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
                } else if( key === Key ){
                    result = value;
                    return result;
                }
            });

            return result;
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
        }

    }
}


jQuery(document).on('ffd-gmap-api-ready', function(){



});


/*
*
* on DOM Loaded
*/
$(function(){


    $('[data-gmap]').each(function(e){
        var $this = $(this);
        var gmap_selector = $this.attr('data-gmap') || $this.attr('data-gmap_selector');
        var item_selector = $this.attr('data-item_selector') || '.listing';
        var data = [];

        if( $(gmap_selector).length > 0  && 'form' !== $this.prop('tagName').toLowerCase() ){

            $this.find(item_selector).each(function() {
                var  item = {};
                $.each(this.attributes, function(i, attrib){
                    if( attrib.name.indexOf('data-') !== -1 ){
                        var name = attrib.name;
                        var value = attrib.value;
                        var k = name.replace('data-', '');
                        //split data-KEYNAME by ,KEYNAME, so we can creating array like arra[listing][KEYNAME] and array[meta][KEYNAME];
                        var f = ','+k+',';
                        if( value.indexOf(f) !== -1 ){
                                var p = value.split(f);
                                name = p[0];

                                if( typeof item[p[0]] === 'undefined' ) {
                                    item[p[0]] = {}
                                }


                                item[p[0]][k] = p[1];




                        } else {
                            item[name] = value;
                        }



                    }


                });
                data.push(item);

            });
            console.log(data);
            gMap.init(gmap_selector, data, item_selector);

        }
    });


});
})(jQuery, FFD_Search_Settings, window, FFD_Liquid, Cookies);
