<?php
global $post;
$search_page_id = $post->ID;

$time_one = time();


    $result = mls_search_page_init_listings();
    
    if( !isset($_GET["district"]) && isset($_GET["neighborhood"]) && $_GET["neighborhood"] != ""){
        $_GET["district"]=$_GET["neighborhood"];
    }
    //set default city
    if( !isset($_GET['city']) ){
        $_GET['city'] = 'San Francisco';
    }

    do_action('ffdl_before_search_filters');
    $default_values = FFDL_Helper::get_filters_default_values();

    $current=1;
    //ffdl_debug$result);
    $page = $result->query['paged'];
    $totalPages = $result->max_num_pages;
    $maxPrice= $default_values['maxPrice']; //FFDL_Helper::get_max("pba__listingprice_pb__c");
    $maxParking= $default_values['maxParking']; //FFDL_Helper::get_max("parkingspaces__c");
    $maxHOADues= $default_values['maxHOADues']; //FFDL_Helper::get_max("hoa_dues__c");
    $maxBeds= $default_values['maxBeds']; //FFDL_Helper::get_max("pba__bedrooms_pb__c"); 

    $property_types = FFDL_Helper::get_property_types();
    $districts = FFDL_Helper::get_districts_by_city();

    $default_sort = FFDL_Helper::get_default_sort_meta_query();
    if( !empty($default_sort) && !empty($default_sort['name']) ){
        $default_sort_key = $default_sort['name'];
        $default_sort_order = isset($default_sort['order']) ? $default_sort['order'] : 'DESC';
        if( !isset($_GET["orderby"]) ){
            $_GET["orderby"] = strtolower( $default_sort_key . ':' . $default_sort_order );
        }
    }

    /* $property_views = FFDL_Helper::get_property_views(); */

$time_two = time();


//$show_default_title = get_post_meta(get_the_ID(), '_et_pb_show_title', true);
//$search_params=FFDL_Helper::get_search_params();


?>
<?php 
    ffdl_get_template_part('search-filters', 
        apply_filters('ffdl_search_filters_param_values',
            array(
                "result" => $result,
                'maxParking' => $maxParking,
                'maxHOADues' => $maxHOADues,
                'maxBeds' => $maxBeds,
                'property_types' => $property_types,
                'districts' => $districts,
                'search_page_id' => $search_page_id
            )   
        )
    );
?>

<input id="ulPrice" value="<?php echo $maxPrice?>" type="hidden" />
<input id="ulParking" value="<?php echo $maxParking?>" type="hidden" />
<input id="ulHOADues" value="<?php echo $maxHOADues?>" type="hidden" />
<input id="ulBeds" value="<?php echo $maxBeds?>" type="hidden" />
<div class="container-fluid advance-search-content-section" data-loadtime="<?php echo $time_two - $time_one; ?>">
    <div class="row">
        <div class="col-md-6 col-sm-6">
            <span class="total-results-info">
                Showing <span id="current-results"><?php echo count($result->posts); ?></span> result(s) out of <span id="total-results"><?php echo $result->found_posts ?></span> in total
            </span>

            <div class="toggle-map-wrapper">
                <button id="ihf-map-toggle-button" class="btn gray-button hidden-md hidden-lg hidden-xlg" type="button" data-toggletarget="#mls-search-map" data-toggleheight="530px;">
                    <i class="glyphicon glyphicon-map-marker"></i> Map Search
                </button>
            </div>

            <!-- map wrap
            =============== -->
           
            <div class="map-wrap" id="mls-search-map">
                <label class="search-move-container">
                    <input type="checkbox" value="off" id="search-move" />
                    <span>Search as I move the map</span>
                </label>
                <div style="" id="map" class="">
                    <!--<img src="https://s3-us-west-2.amazonaws.com/propbmedia/JacksonFuller/606357-1.jpg" alt="">-->
                </div>
            </div>
            <!-- End map wrap -->
        </div>
        <div class="col-md-6 col-sm-6">
            <!-- Begin property wrap 
            ======================= -->
            <div class="property-wrap list-property">
                <!--<div class="breadcum-link">
                    <a href="#">Property for Sale</a>
                    <a href="#">Western Cape</a>
                    <a href="#">Cape Town</a>
                    <a href="#" class="active">Oranjezicht</a>
                </div>-->
                <!--<h3>Property for Sale in Oranjezicht</h3>-->
                <div class="property-seacrh-wrap">
                    <div class="property-search">
                        <div class="proprety-selection-wrap">
                                <select id="orderby" name="orderby" class="form-control niceSelect">
                                    <option <?php echo isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="listed_date:desc"?"selected":"" ?> value="listed_date:desc">Date - Newest</option>    
                                    <option <?php echo isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="listed_date:asc"?"selected":"" ?> value="listed_date:asc">Date - Oldest</option>    
                                    <option <?php echo !isset($_GET["orderby"]) || ( isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="days_on_market:asc") ? "selected":"" ?> value="days_on_market:asc">Market Days - Newest</option>    
                                    <option <?php echo isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="days_on_market:desc"?"selected":"" ?> value="days_on_market:desc">Market Days - Oldest</option>  
                                    <option <?php echo isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="listing_price:asc"?"selected":"" ?> value="listing_price:asc">Price - Low to High</option>
                                    <option <?php echo (isset($_GET["orderby"]) && strtolower($_GET["orderby"])=="listing_price:desc")?"selected":"" ?> value="listing_price:desc">Price - High to Low</option>
                                </select>
                        </div>
                        <div class="property-display-type-icons-container">
                            <ul>
                                <li class="listing-type-list"><a href="#" onclick="return app.advanceSearch.switchPropertyDisplay('list');"><i class="fa fa-list-ul"></i></a></li>
                                <li class="listing-type-grid active"><a href="#" onclick="return app.advanceSearch.switchPropertyDisplay('grid');"><i class="fa fa-th-large"></i></a></li>
                            </ul>
                        </div>
                        <div class="filters-tags">
                            <span class="btn btn-info search-filter-tag keyword-tag" type="button">
                                <span class="search-filter-tag-text">123456</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag district-tag" type="button">
                                <span class="search-filter-tag-text">Hamakua</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag property-type-tag" type="button">
                                <span class="search-filter-tag-text">Business</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag beds-range-tag" type="button">
                                <span class="search-filter-tag-text">0 to 12 beds</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag baths-tag" type="button">
                                <span class="search-filter-tag-text">5 Baths</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag frontage-tag" type="button">
                                <span class="search-filter-tag-text"></span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag community-tag" type="button">
                                <span class="search-filter-tag-text"></span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag featured-tag" type="button">
                                <span class="search-filter-tag-text"></span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag favorites-tag" type="button">
                                <span class="search-filter-tag-text"></span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                            <span class="btn btn-info search-filter-tag price-range-tag" type="button">
                                <span class="search-filter-tag-text">$0 to $320,000</span> <span class="fa fa-close remove-filter-tag"></span>
                            </span>
                        </div>
                    </div>
                    <div class="property-item-wrap">
                        <div class="row properties-container">
                            <?php
                            if(count($result->posts)>0)
                            {
                                foreach ($result->posts as $listing) {
                                    ffdl_get_template_part(
                                        "listing-card",
                                                array(
                                                    "listing" => $listing,
                                                    //"search_params" => $search_params,
                                                )
                                    );
                                }
                            }
                            else
                            { ?>
                                <div class="col-md-12 topPad bottomPad">
                                    <div class="alert alert-info fade in alert-dismissable">
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                        <strong>We're sorry.</strong>  We cannot find any matches for your search.
                                        <ul class="clear-search-action-container" style="width: 110px;display: inline-block;margin-left: 10px;"> 
                                            <li>
                                                <label class="clear-search-action" style="color: #fff;background: #14b5ea;" title="Clear Your Search">Clear <span class="customhidden"> Search</span></label>
                                                
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php }
                            ?>
                        </div>
                        <div class="page-load-status" style="display:none">
                            <div class="loader-ellips infinite-scroll-request">
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                            </div>
                        </div>
                        <div class="adv_search_disclaimer">MLS listing information is deemed reliable but is not guaranteed by the service.</div>
                    </div>
                    <div class="pagination-wrap">
                        <ul id="pager">
                            <!--<span id="pagerPrev" class="disabled">Previous</span>-->
                            <!--<a class="pagination__next" href="#">Next page</a>-->
                            <input type="hidden" id="currentpage" value="<?php echo $page; ?>" />
                            <input type="hidden" id="nextpage" value="<?php echo($page < $totalPages ? ($page+1) : $page); ?>" />
                            <input type="hidden" id="has_more" value="<?php echo($page < $totalPages ? 'true' : 'false'); ?>" />
                        </ul>
                    </div>
                </div>
            </div>
            <!-- End Begin property wrap -->
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function($){

        app.advanceSearch.initScripts();
        app.common.initScripts();
        app.init.initJRangeSlider(".range-slider");
        jQuery('.range-slider-single').each(function(e){
            var $this = jQuery(this);
           
            var params = {from: parseInt($this.attr('data-from')), to: parseInt($this.attr('data-to')), format:$this.attr('data-format')}
            app.init.initJRangeSingle($this, params);


           
        });

        jQuery('.range-slider-multi').each(function(e){
            var $this = jQuery(this);
           
            var params = {current_val:$this.attr('data-current_val'), from: parseInt($this.attr('data-from')), to: parseInt($this.attr('data-to')), format:$this.attr('data-format')}
            app.init.initJRangeMulti($this, params);


           
        });

        app.advanceSearch.initPropertyResultsSlider(".properties-container .property-item .swiper-container", "initialized");
        //app.advanceSearch.initInfiniteScroller('.properties-container');
        app.advanceSearch.initPageScroll('.properties-container');
        //jQuery(".property-item").mouseover(function () {app.advanceSearch.search_result_hovered(jQuery(this)) });
        app.advanceSearch.registerSearchFormEvents();


        toInt = function( n ) { 
            n = n.replace('$', '');
            n = n.replace(/,/g, '');
            n = ( isNaN( parseInt(n) ) ) ? '' : parseInt(n);
            return n;
        }
        
        function getNumVal (val) {
            try {
                multiplier = val.substr(-1).toLowerCase();
                if (multiplier == "k")
                    return "$" + (parseFloat(val) * 1000);
                else if (multiplier == "m")
                    return "$" + (parseFloat(val) * 1000000);
                else
                    return val;
            } catch (exception) {
            }
        }

        jQuery(document).on('change', '#minprice_show, #maxprice_show', function(e){

            var price_from = toInt( getNumVal( $('#minprice_show').val() ) );
            var price_to = toInt( getNumVal( $('#maxprice_show').val() ) );
            var prices = jQuery(".range-slider").jRange("getOptions");
            var max_limit = parseInt(jQuery("#ulPrice").val());

         

            if( price_from == '' || price_from <= 0 )
                price_from = prices.from;
            
            if( price_to == '' || price_to <= 0 || price_to > max_limit)
                price_to = prices.to;

            jQuery("#minprice").val( price_from );
            jQuery("#maxprice").val( price_to );
            
        
            jQuery("#maxprice").trigger("change"); 

            var newPrices = price_from + "," + price_to;
            jQuery(".range-slider").jRange("setValue", newPrices);

        });

        google.maps.event.addListenerOnce(app.advanceSearch.vars.search_map, 'idle', function () {
            // do something only the first time the map is loaded
            jQuery('#mls-search-map').addClass('tk-hidden-mobile');
        });
        
    });
</script>

<div class="full-page-overlay">
        <div class="page-load-status">
            <div class="loader-ellips infinite-scroll-request">
                <span class="loader-ellips__dot"></span>
                <span class="loader-ellips__dot"></span>
                <span class="loader-ellips__dot"></span>
                <span class="loader-ellips__dot"></span>
            </div>
        </div>
    </div>
 <?php  get_template_part('template-parts/contact', 'popup'); ?>