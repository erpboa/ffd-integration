<?php
//$fields = array('listprice', 'status', 'listtype', 'proptype', 'dom', 'lat', 'lng', 'beds', 'baths', 'size', 'totalarea', 'city', 'address', 'ID', 'post_title');

$select = isset($select) ? (array) $select : array(); // name of fields to select
$where = isset($where) ? (array) $where : array(); // filter result based on fields value
$order = isset($order) ? (string) $order : '';
$limit = isset($limit) ? $limit : '';
$offset = isset($offset) ? $offset : '';

$listing_template = isset($listing_template) ? $listing_template : '';
$iw_template = isset($iw_template) ? $iw_template : '';

if( !empty($listing_template) ){
    $listing_template = RebrandingShortcodes()->get_ui_template_html($listing_template);
}

if( !empty($iw_template) ){
    $iw_template = RebrandingShortcodes()->get_ui_template_html($iw_template);
}

$listing_class = isset($listing_class) ? $listing_class : '';
$iw_class = isset($iw_class) ? $iw_class : '';

$wrapper_class = isset($wrapper_class) ? $wrapper_class : '';


//$json_data = query_ffd_map_search_data($select, $where, $order, $limit, $offset);
//ffd_debug($json_data, true);
/* $error = '';
if( is_wp_error($json_data) ){
    $error = $json_data->get_error_messages();
    $error = implode("<br>", $error);
    $json_data = '';
} */

?>
<!-- begin map search -->
<style>

.ffd-ellipsis {
  display: inline-block;
  position: relative;
  width: 64px;
  height: 64px;
}
.ffd-ellipsis div {
  position: absolute;
  top: 27px;
  width: 11px;
  height: 11px;
  border-radius: 50%;
  background: #7a7a7a;
  animation-timing-function: cubic-bezier(0, 1, 1, 0);
}
.ffd-ellipsis div:nth-child(1) {
  left: 6px;
  animation: ffd-ellipsis1 0.6s infinite;
}
.ffd-ellipsis div:nth-child(2) {
  left: 6px;
  animation: ffd-ellipsis2 0.6s infinite;
}
.ffd-ellipsis div:nth-child(3) {
  left: 26px;
  animation: ffd-ellipsis2 0.6s infinite;
}
.ffd-ellipsis div:nth-child(4) {
  left: 45px;
  animation: ffd-ellipsis3 0.6s infinite;
}
@keyframes ffd-ellipsis1 {
  0% {
    transform: scale(0);
  }
  100% {
    transform: scale(1);
  }
}
@keyframes ffd-ellipsis3 {
  0% {
    transform: scale(1);
  }
  100% {
    transform: scale(0);
  }
}
@keyframes ffd-ellipsis2 {
  0% {
    transform: translate(0, 0);
  }
  100% {
    transform: translate(19px, 0);
  }
}


.ffd-ellipsis {
    display: inline-block;
    position: relative;
    width: 64px;
    height: 64px;
    left: 50%;
    top: 50%;
}

.ffd-ellipsis-wrap{ 
    display:none; 
    background-color: #fff;
    background-color: rgba(255, 255, 255, 0.7);
    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 111;

}
.ffd-mapsearch-outer { position: relative; }
.ffd-mapsearch-outer.loading .ffd-ellipsis-wrap{ 
    display:block;
}

#map_search_container {
    height: 100%;
    max-width: 100%;
    margin-bottom:20px;
}

#map_search_container .marker-popup{
    width:300px;
    overflow: hidden;
}

#map_search_container .gm-style .gm-style-iw,
#map_search_container .gm-style .gm-style-iw-d{
    width:300px  !important;;
    overflow: hidden !important;
    background-color:transparent !important;
}
#map_search_container .gm-style .gm-style-iw-c{
    box-shadow: none;
    border-radius: 0;
}

#map_search_container .marker-popup .marker-popup-container{
    width:280px;
    background-color:#fff;
    overflow: hidden;
}

#map_search_container .marker-popup .popup-popup-info{
    padding:10px;
}

#map_search_container .marker-popup .marker-popup-thumbnail{
    width: 280px;
    height: 140px;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
}


#map_search_map {
    height: 600px;
}

.ffd-map_search_listings {
    height: 600px;
    overflow: auto;
}

.ffd-map_search_listings > div{
    margin-bottom:20px;
}


@media (min-width: 768px) {
    .ffd-map_search_container {
        
        width: calc(100vw - 390px);
        height: calc(100% - 157px)
    }
}

@media (min-width: 1200px) {
    .ffd-map_search_container {
        
        height: calc(100% - 107px)
    }
}

@media only screen and (min-width: 1500px) {
    .ffd-map_search_container {
        width:calc(100vw - 765px)
    }
}

</style>
<?php if(!empty($listing_template)): ?>
    <div id="ffd_listing_template" style="display:none;" class="<?php echo $wrapper_class; ?>"><div class="<?php echo $listing_class; ?>"><?php echo do_shortcode($listing_template); ?></div></div>
<?php endif; ?>

<?php if(!empty($iw_template)): ?>
    <div id="ffd_iw_template" style="display:none;"><div class="<?php echo $iw_class; ?>"><?php echo do_shortcode($iw_template); ?></div></div>
<?php endif; ?>

<div class="ffd-mapsearch-outer">

<div class="ffd-ellipsis-wrap">
    <div class="ffd-ellipsis"><div></div><div></div><div></div><div></div></div>
</div>

<div id="ffd-mapsearch-status" class="ffd-mapsearch-status"></div>
<div class="row">
    <div class="col-md-8 col-xl-9">
        <div id="map_search_container" class="ffd-map_search_container ffd-twbs">
            <div class="map_error"><?php echo $error; ?></div>
            <div id="map_search_map"></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-3">
        <div id="map_search_listings" class="ffd-map_search_listings ffd-twbs">
            
        </div>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/history.js/1.8/bundled-uncompressed/html5/jquery.history.js" integrity="sha256-jp+tJB05Hy6uCBeakCpqpoTvk/MxTjMaiQ9kp74BSQk=" crossorigin="anonymous"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/promise-polyfill.min.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/liquid.min.js"></script>
<script>
<?php 
$keynames = metakey_names_ffd_map_search();
$keynames = array_keys($keynames);
$keynames = array_merge($keynames, array('post_title', 'ID', 'link'));

$l10n = array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'homeurl' => home_url(),
    'templateurl' => get_template_directory_uri(),
    'where' => $where,
    'select' => $select,
    'offset' => $offset,
    'limit' => $limit,
    'image_placeholder' => ( isset($image_placeholder) ? $image_placeholder : ffdl_get_img_placeholder() ),
    'keynames' => $keynames,
);
foreach ( (array) $l10n as $key => $value ) {
    if ( ! is_scalar( $value ) ) {
        continue;
    }

    $l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
}

echo "var ffd_mapsearch_vars = " . wp_json_encode( $l10n ) . ';';


?>
var History = window.History;
// Content update and back/forward button handler
History.Adapter.bind(window, 'statechange', function() {
    var State = History.getState(); 
    // Do ajax
    
    // Log the history object to your browser's console
    History.log(State);
});

var FFD_Liquid = new Liquid();

FFD_Liquid.registerFilter('append_if', function(input, string){
    if( input === '' )
        return ''
    else
        return input + string;
    
});


FFD_Liquid.registerFilter('prepend_if', function(input, string){
    if( input === '' )
        return ''
    else
        return string + input;
    
});

var FFD_Map_Search = {

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

            if( FFD_Map_Search.vars.map ){

                FFD_Map_Search.vars.map.setZoom(12);
                FFD_Map_Search.vars.map.setCenter(new google.maps.LatLng('37.4161156','-122.1213172'));

            }

        },
        idle: function(){
            FFD_Map_Search.log('Event Triggered: idle');

            FFD_Map_Search.vars.idle = true;
            
            
            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'idle';
        },
        
        boundschanged: function(){
            FFD_Map_Search.log('Event Triggered: boundschanged');

            FFD_Map_Search.vars.idle = false;
            if( FFD_Map_Search.vars.search_result !== null ){
            
                //update displayed listings on bounds changed
                FFD_Map_Search.updateListings().then(function(){
                    FFD_Map_Search.log('Listings Updated.');
                });
            
            }
            

            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'boundschanged';
        },

        zoomchange: function(){
            FFD_Map_Search.log('Event Triggered: zoomchange');
            
            FFD_Map_Search.vars.idle = false;

            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'zoomchange';
        },

        dragend: function(){
            FFD_Map_Search.log('Event Triggered: dragend');

            FFD_Map_Search.vars.idle = false;

            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'dragend';
        },

        clusterclick: function(){
            FFD_Map_Search.log('Event Triggered: clusterclick');

            FFD_Map_Search.vars.idle = false;

            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'clusterclick';
        },

        drawingmodechange: function(){
            FFD_Map_Search.log('Event Triggered: drawingmodechange');

            FFD_Map_Search.vars.idle = false;

            if (FFD_Map_Search.vars.drawingmanager.getDrawingMode() != null) {
                FFD_Map_Search.clearPoly();
            }

            
            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'drawingmodechange';

        },

        overlaycomplete: function(event) {
            FFD_Map_Search.log('Event Triggered: overlaycomplete');

            FFD_Map_Search.vars.idle = false;

            var newShape = event.overlay;
            newShape.type = event.type;
            FFD_Map_Search.vars.shapes.push(newShape);
            FFD_Map_Search.vars.areaChanged = true;

            var len = newShape.getPath().getLength();
            var poly = [];

            for (var i = 0; i < len; i++){
                poly.push(newShape.getPath().getAt(i).toUrlValue(5).replace(",", " "));
            }

            var polydata = poly.join() + "," + poly[0];
            FFD_Map_Search.vars.mapData = "poly=" + polydata;
            FFD_Map_Search.vars.map.fitBounds(event.overlay.getBounds());

            FFD_Map_Search.updateListings().then(function(){
                FFD_Map_Search.log('Listings Updated.');
            });
            

            if (FFD_Map_Search.vars.drawingmanager.getDrawingMode()) {
                FFD_Map_Search.clearPoly();
                //FFD_Map_Search.vars.drawingmanager.setDrawingMode(null);
            } 


            //last update event status;
            FFD_Map_Search.vars.eventstatus = 'overlaycomplete';

        },


    },
    //Entry Point, things start happening here
    init: function(data){
        
        this.vars.debug = true //show logs in browser console

        //initialize map
        this.vars.map = this.loadMap(jQuery('#map_search_map'));

        //initialize drawing manager for polygon search support
        this.vars.drawingmanager = this.loadDrawingManager(this.vars.map );

      
        var init_data = false;
        if( data && typeof data !== 'undefined' && data.length>0 ){
            //render makers on map
            FFD_Map_Search.loadData(data).then(function(){
                FFD_Map_Search.events.initMapReady();
            });
            init_data = true;
        }

        //load Events
        this.loadMapEvents();

        var mapLoadedFirstTime = false; 

        jQuery(document).on('map_search_filters_ready', function(e, $form_obj){
            FFD_Map_Search.vars.form = $form_obj;
            FFD_Map_Search.loadFormEvents();

            if( init_data === false ){
                //load data using ajax
                FFD_Map_Search.startLoading();
                FFD_Map_Search.getSearchResults().then(function(payload){
                    return null;
                });

               
                $form_obj.on('result_loaded', function(){

                    //only trigger if this is first load map
                    if( !mapLoadedFirstTime ){

                        if( $form_obj.hasClass('has-query-params') ){
                            FFD_Map_Search.FilterResults();
                        } else {
                            FFD_Map_Search.events.initMapReady();
                        }
                        mapLoadedFirstTime = true;
                    }
                });


                
            }
        });
    },

    loadFormEvents: function(){

        
        
        var $form = FFD_Map_Search.vars.form;
        $form.on('submit', function(e){

            FFD_Map_Search.log('Search Form Submitted');

            e.preventDefault();

            var urlPath = ajax_obj.page_url;
			var current_href = $(location).attr('href');
			var current_title = $(document).attr('title');
        	History.pushState({path: urlPath}, current_title, urlPath + '?' + $form.serialize()); // When we do this, History.Adapter will also execute its contents. 

            if( !FFD_Map_Search.vars.doingsearch ){
                FFD_Map_Search.startLoading();
                FFD_Map_Search.FilterResults();
            }
        });

        //triggered when results are filtered based on user input
        $form.on('response_ready', function(e, response, type){

           

            FFD_Map_Search.closeAllInfoWindows();
            FFD_Map_Search.vars.mapData = "";

            //clear all (if) existing makers from the map
            FFD_Map_Search.clearMarkers().then(function(){

               
                return FFD_Map_Search.helpers.prepareData(response, type);
                

            }).then(function(data){

                //load markers using new data
                var updateData = ( type == 'ajax') ? 'yes' : 'no';

                return FFD_Map_Search.loadData(data, updateData);

            }).then(function(){

                //last set doingsearch to false
                FFD_Map_Search.vars.doingsearch = false;
                FFD_Map_Search.stopLoading();
                FFD_Map_Search.vars.form.trigger('result_loaded');
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

        FFD_Map_Search.vars.loadingData = true;

        return new Promise(function(resolve,reject){
                    
                if( setData === 'yes' ){
                    FFD_Map_Search.log('Data is Updated.');
                    FFD_Map_Search.vars.data = FFD_Map_Search.vars.search_result = data;
                } else {
                    FFD_Map_Search.vars.search_result = data;
                }

                FFD_Map_Search.log('Data Count: ' +  data.length);

                if( FFD_Map_Search.vars.infoWindow === null ){
                    FFD_Map_Search.vars.infoWindow = new google.maps.InfoWindow();
                }

                return FFD_Map_Search.loadMarkers(FFD_Map_Search.vars.map , data).then(function(){
                    return FFD_Map_Search.loadListings(data).then(function(){
                        FFD_Map_Search.vars.loadingData = false;
                        FFD_Map_Search.log("Loading Data Done!");
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

            jQuery('#ffd-mapsearch-status').html('<p>Showing ' + data.length + ' of Total ' + FFD_Map_Search.vars.data.length);

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
                        item['image'] = ffd_mapsearch_vars.image_placeholder;
                    }

                    item['price'] = item['listprice'] || '';
                    item['area'] = item['neighborhood'] || '';
                    item['image_placeholder'] = ffd_mapsearch_vars.image_placeholder;
                    content = jQuery('#ffd_listing_template').html();

                    FFD_Liquid.parseAndRender(content, item).then(function(content){
                        
                        jQuery('#map_search_listings').append(content);
                        listing_rendered++;
                        if( listing_rendered >= total_listings ){
                            FFD_Map_Search.vars.form.trigger('listings_rendered');
                            FFD_Map_Search.log('Load Listings Done!');
                            resolve('Load Listings Done!');
                        }
                    });

                });
            });
           
        
    },

    updateListings: function(){

        var bounds =  FFD_Map_Search.vars.map.getBounds();
        var data = [],  latlng;
        jQuery.each(FFD_Map_Search.vars.search_result, function (item_index, item) { 

            //item = FFD_Map_Search.helpers.dataItem(item);

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

            FFD_Map_Search.loadListings(data).then(function(){
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

        if( FFD_Map_Search.vars.map ){

            FFD_Map_Search.vars.map.addListener('idle', FFD_Map_Search.events.idle);
            FFD_Map_Search.vars.map.addListener('bounds_changed', FFD_Map_Search.events.boundschanged);
            FFD_Map_Search.vars.map.addListener('zoom_changed', FFD_Map_Search.events.zoomchange);
            FFD_Map_Search.vars.map.addListener('dragend', FFD_Map_Search.events.dragend);
          
            FFD_Map_Search.vars.map.addListener('clusterclick', FFD_Map_Search.events.clusterclick);
        }  

        if( FFD_Map_Search.vars.drawingmanager ){

            google.maps.event.addListener(FFD_Map_Search.vars.drawingmanager, "drawingmode_changed", FFD_Map_Search.events.drawingmodechange);
            google.maps.event.addListener(FFD_Map_Search.vars.drawingmanager, 'overlaycomplete', FFD_Map_Search.events.overlaycomplete);

        }

    },

    loadMarkers: function(map, data){

      
        var marker_ids = [],
                markers_to_delete,
                bounds = new google.maps.LatLngBounds();

            //data = data.slice(0, 5);
            jQuery.each(data, function (item_index, item) { 
                
                //item = FFD_Map_Search.helpers.dataItem(item);
               
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

                            marker = FFD_Map_Search.addMarkerWithLabel(map, item_index, item);
                            //marker = FFD_Map_Search.addMarker(map, item_index, item);
                            FFD_Map_Search.vars.markers.push(marker);

                            FFD_Map_Search.current.marker = marker;
                            FFD_Map_Search.current.marker_index = id;
                        
                            (function (marker, item_index, content) {
                                google.maps.event.addListener(marker, "click", function (e) {
                                        FFD_Map_Search.iw_content(item_index, item).then(function(content){
                                            FFD_Map_Search.vars.infoWindow.setContent(content);
                                            FFD_Map_Search.vars.infoWindow.open(map, marker);
                                            return content;
                                        });
                                   
                                });
                            })(marker, item_index, item);

                            bounds.extend(marker.getPosition());
                            
                        }

                        

                    }
                    
            });

       
       
            FFD_Map_Search.vars.bounds = bounds;
            FFD_Map_Search.vars.map.setCenter(bounds.getCenter());
            FFD_Map_Search.vars.map.fitBounds(bounds);
        
            FFD_Map_Search.vars.markerClusterer = new MarkerClusterer(map, FFD_Map_Search.vars.markers, {maxZoom: 14, zoomOnClick:true, averageCenter:false, minimumClusterSize:2, imagePath: ajax_obj.cluster_img_path});
            //markerClusterer.addMarkers(FFD_Map_Search.vars.markers);
        
        return Promise.resolve('Load Markers Done!');
        
    },

    addMarkerWithLabel: function(map, item_index, item){

                var id = item_index,
                lat = item['lat'],
                lng = item['lng'],
                price = item['listprice'] || '',
                url = '';
                
                var p = FFD_Map_Search.helpers.convert_number(price.replace(/,/g, ""), 1);

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
        var p = FFD_Map_Search.helpers.convert_number(price.replace(/,/g, ""), 1);

        return  new google.maps.Marker({
            position: new google.maps.LatLng(lat,lng),
            draggable: false,
            label: "$" + p,
            icon: markerImage
          });

    }, 

    iw_content: function(item_index, item){

            item['image_placeholder'] = ffd_mapsearch_vars.image_placeholder
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

            return FFD_Liquid.parseAndRender(content, item);

    },

    
    
    closeAllInfoWindows: function(){
       
       if( FFD_Map_Search.vars.infowindows.length > 0 ){
            jQuery.each(FFD_Map_Search.vars.infowindows, function (index, iw) {
                iw.close();
            });
       }

    },
    
    clearMarkers: function(){

        


       FFD_Map_Search.log('ClearMarkers Before:' + FFD_Map_Search.vars.markers.length );

        jQuery.each(FFD_Map_Search.vars.markers, function (i, value) { 
            FFD_Map_Search.vars.markers[i].setMap(null);
        });
        FFD_Map_Search.vars.markers = [];

        FFD_Map_Search.log('ClearMarkers After:' + FFD_Map_Search.vars.markers.length );
         

        if( FFD_Map_Search.vars.markerClusterer !== null ){
            FFD_Map_Search.vars.markerClusterer.clearMarkers();
        }
        
        return Promise.resolve('Clear Markers Done!');
    },


    clearPoly: function () {
        for (var i = 0; i < FFD_Map_Search.vars.shapes.length; i++) {
            FFD_Map_Search.vars.shapes[i].setMap(null);
        }
        FFD_Map_Search.vars.shapes = [];
    },

    /* 
    * remove use of result_loaded and response ready
    * instead use this function and make use of promises
     */
    doSearch: function(searchType){
        if(searchType=='getSearchResults')
            return  FFD_Map_Search.getSearchResults();
        else
            return  FFD_Map_Search.FilterResults();
    },

    getSearchResults: function(){

        return new Promise(function(resolve,reject){
                var params = FFD_Map_Search.getSearchParamsPlus();
                /* 
                * ------------------
                * AJAX search
                */
                params.action = "ffdl/listings/mapsearch";
                FFD_Map_Search.vars.doingsearch = true;
                
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
                    FFD_Map_Search.vars.form.trigger('response_ready', payload);
                    
                }).fail(function(){
                    reject('error getSearchResults');
                }).always(function(){
                    
                });
        });

    },

    FilterResults: function(){
        return new Promise(function(resolve,reject){

            var params = FFD_Map_Search.getSearchParams();
            var r, data = FFD_Map_Search.vars.data;
            
            FFD_Map_Search.log(params);
            FFD_Map_Search.vars.doingsearch = true;
            
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

                                        var bounds =  FFD_Map_Search.vars.map.getBounds();
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
            FFD_Map_Search.vars.form.trigger('response_ready', [data, 'filtered']);
        
        });
    },

    getSearchParams: function () {

       

        var $form = FFD_Map_Search.vars.form;
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

        

        if (FFD_Map_Search.mapData != "") {
            FFD_Map_Search.mapData += "";
            var mapDataArr = FFD_Map_Search.mapData.split('&');
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

        var params = FFD_Map_Search.getSearchParams();

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
                                result['where'][_key] = value + '|' + '=';
                                break;
                        }

                    break;
            }
        });

        //fill defaults from shortcode if needed
        if( ffd_mapsearch_vars.where !== '' ){

            if( !result.hasOwnProperty('where') ) {
                result['where']={};
            }

            jQuery.each(ffd_mapsearch_vars.where, function(index, value){
                if( typeof result['where'][index] === 'undefined' ){
                    result['where'][index] = value;
                }
            });

        }

        if(  ffd_mapsearch_vars.limit != '' && !result.hasOwnProperty('limit') ){
            result['limit'] = ffd_mapsearch_vars.limit;
        }

        if(  ffd_mapsearch_vars.offset != '' && !result.hasOwnProperty('offset') ){
            result['offset'] = ffd_mapsearch_vars.offset;
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

        if( FFD_Map_Search.vars.debug ){
            console.log(msg);
        }


    },

    helpers: {
        prepareData: function(data, type){

            if( type == 'ajax' ){
                var i=0;
                jQuery.each(data, function (data_key, item) { 

                   
                    item =  FFD_Map_Search.helpers.parseStr(item, 'dataitem');
                    

                    if( item['lat'] && item['lng'] && !isNaN(parseFloat(item['lat'])) && !isNaN(parseFloat(item['lat'])) ){
                        data[i] = item;
                        i++;
                    }
                
                });    
            }

            return Promise.resolve(data);
        },

        dataItem: function(item){
            return FFD_Map_Search.helpers.parseStr(item);
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
            
            return FFD_Map_Search.helper.parseStr(query);
        },

        parseStr: function(query, querytype){

            return _
            .chain(query.split('&'))
            .map(function(params) {
                var p = params.split('=');
                if( querytype && querytype == 'dataitem'){
                    var k = ffd_mapsearch_vars.keynames;
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
            var matches = FFD_Map_Search.helpers.preg_match_all(regex, content);
            
            
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
            
            return '$' + FFD_Map_Search.helpers.number_format(number, decimals, decimal_sep, thousands_sep);
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
                    if (!FFD_Map_Search.helpers.isSerializable(obj[key])) {
                    return false;
                    }
                }

                return true;
        },

        maybe_unserialize: function (data) {
            
            if (!FFD_Map_Search.helpers.isSerializable(data) ) {
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



(function($){
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
})(jQuery);

jQuery('#searchForm').on('listings_rendered', function(e){
            
        var i=0, id, link, properties_nav=[];
        $('#map_search_listings .rb-listing-card a[data-property_id]').each(function(e){
            
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

</script>
<!-- end map search -->