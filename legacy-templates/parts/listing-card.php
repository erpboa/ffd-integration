 <?php 
 
 if(isset($_GET['ffdl_debug']) ){
    ffdl_debug(get_post_meta($listing->ID), true);
 }

 $columns = isset($columns) && !empty($columns) ? $columns : 4; 
 $layout_item_class = isset($layout_item_class) ? $layout_item_class : '';
 ?> 
 <div class="<?php echo $layout_item_class; ?> col-md-<?php echo $columns; ?> col-sm-6 col-xs-12 property-item" data-foundposts="<?php echo $found_posts; ?>">
   
   <?php
    $media=get_post_meta($listing->ID, "ffd_media", true);
    $img="";  //TODO: Default Image
    $isFeatured = get_post_meta($listing->ID, "ffd_featured", true);
    $propertyType = get_post_meta($listing->ID, "ffd_propertytype", true);
    $project = get_post_meta($listing->ID, "project", true);
    $tenure = get_post_meta($listing->ID, "land_tenure", true);
    $landArea = get_post_meta($listing->ID, "land_area_calc", true);
    $yearBuilt = get_post_meta($listing->ID, "ffd_yearbuilt_pb", true);
    $tenureAcronym = getStringAcronym($tenure);
    $beds = get_post_meta($listing->ID, "ffd_bedrooms_pb", true);
    $baths = get_post_meta($listing->ID, "ffd_fullbathrooms_pb", true);
    $halfbathrooms = get_post_meta($listing->ID, "ffd_halfbathrooms_pb", true);
    $daysm = get_post_meta($listing->ID, "days_on_market", true);
    $unit_number =  get_post_meta($listing->ID, "unit_number", true);

    $bathrooms='';
    if(!empty($halfbathrooms) && !empty($baths))
        $bathrooms = $baths.' Full, '.$halfbathrooms.' Half';
    else if(!empty($baths))
        $bathrooms = $baths.' Full';
    else if(!empty($halfbathrooms))
        $bathrooms = $halfbathrooms.' Half';


    $lotSize = get_post_meta($listing->ID, "ffd_lotsize_pb", true);
    $mls = get_post_meta($listing->ID, "ffd_mls_id", true);
    $ListingId = get_post_meta($listing->ID, "ffd_salesforce_id", true);
    $pbid = get_post_meta($listing->ID, "ffd_salesforce_id", true);
    $SoldDate = get_post_meta($listing->ID, "ffd_sale_date", true);
    $ListedDate = get_post_meta($listing->ID, "ffd_listed_date", true);
    $ListingStatus = get_post_meta($listing->ID, "ffd_status", true);
    $sale_price = get_post_meta($listing->ID, "ffd_sale_price", true);
    $list_price = get_post_meta($listing->ID, "ffd_listingprice_pb", true);
    $sqft= get_post_meta($listing->ID, "ffd_totalarea_pb", true);
    $address = get_post_meta($listing->ID, 'ffd_address_pb', true);

    if (is_array($media) && sizeof($media)>0) {
        $img="https://images1-focus-opensocial.googleusercontent.com/gadgets/proxy?url=" . $media[0] . "&container=focus&resize_w=287&refresh=2592000";
    }
    else
    {
        $img = ffdl_listing_card_default_img();
    }
    
    $imagesCount = !empty($media) ? count($media) : 0;
    $lazyLoadAfter = 1;
    //ffdl_debug$media);
?>
<div class="property-figure-wrap property-item-atts"
                            data-address="<?php echo $address; ?>"
                            data-listeddate="<?php echo $ListedDate; ?>"
                            data-solddate="<?php echo $SoldDate; ?>"
                            data-ListingId="<?php echo $ListingId; ?>"
                            data-pbid="<?php echo $pbid; ?>"
                            data-status="<?php echo $ListingStatus; ?>"
                            data-count="<?php echo $imagesCount; ?>"
                            data-lat="<?php echo get_post_meta($listing->ID, "ffd_latitude_pb", true) ?>" 
                            data-lng="<?php echo get_post_meta($listing->ID, "ffd_longitude_pb", true) ?>"
                            data-basedir="<?php echo get_stylesheet_directory_uri() ?>"
                            data-beds="<?php echo $beds; ?>"
                            data-baths="<?php echo $baths; ?>"
                            data-parking="<?php echo get_post_meta($listing->ID, "parkingspaces", true) ?>"
                            data-price="<?php echo number_format(get_post_meta($listing->ID, "ffd_listingprice_pb", true)) ?>"
                            data-soldprice="<?php echo number_format(get_post_meta($listing->ID, "ffd_sale_price", true)) ?>"
                            data-sqft="<?php echo $sqft ?>"
                            data-title="<?php echo $listing->post_title ?>"
                            data-thumbnail="<?php echo $img ?>"
                            data-id="<?php echo $listing->ID ?>"
                            data-url="<?php echo get_permalink($listing->ID) ?>"
                            data-mls="<?php echo $mls; ?>"
                            data-daysm="<?php echo $daysm; ?>"
>
 
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <?php  
                    if($imagesCount>0)
                    {
                        for ($i = 0; $i < $imagesCount; $i++) {
        ?>
                            <div class="swiper-slide">
                                <figure>
                                    <?php
                                    if ($i >= $lazyLoadAfter) { // Do lazy loading?>
                                        <a href="<?php echo get_permalink($listing->ID) ?>">
                                            <!-- Required swiper-lazy class and image source specified in data-src attribute -->
                                            <img data-src="<?php echo  $media[$i]; ?>" class="swiper-lazy"  onerror="app.common.imgError(this);">
                                        </a>
                                        <!-- Preloader image -->
                                        <!--<div class="swiper-lazy-preloader"></div>-->
                                        <div class="loader"><img src="<?php echo get_stylesheet_directory_uri() ?>/assets/images/loader.gif" /></div>
                                    <?php
                                    } else {  // Directly show images
                                        ?>
                                        <a href="<?php echo get_permalink($listing->ID) ?>">
                                            <img src="<?php echo $media[$i]; ?>" alt=""  onerror="app.common.imgError(this);">
                                        </a>
                                    <?php
                                    } ?>
                                </figure>
                            </div>
                                <?php  ?>
                        <?php
                        }  
                    }else{ ?>
                        <div class="swiper-slide">
                            <figure>
                            <a href="<?php echo get_permalink($listing->ID) ?>">
                                            <img src="<?php echo  ffdl_listing_card_default_img(); ?>" alt="">
                                        </a>
                            </figure>
                        </div>
                    <?php }?>
                </div>
                <div class="swiper-button-next swiper-button-white"></div>
                <div class="swiper-button-prev swiper-button-white"></div>
            </div>
    </div>
    <?php if(strtolower($ListingStatus)=='sold'){?><span class="soldbadge">SOLD</span><?php }?>
    <div class="property-content-wrap">
        <div class="property-item-type">
                <?php echo($propertyType == "Residential" ? "Home" : $propertyType); ?>
        </div>
        <div class="property-item-title">
        <a href="<?php echo get_permalink($listing->ID) ?>">
                <?php
                    $listing_title = ffdl_str_lreplace(',', '', $listing->post_title);
                    $title_pieces = explode(',', $listing_title); 
                    echo (isset($title_pieces[0]) ? rtrim(trim($title_pieces[0]), '#') : '');
                    //if(!empty($unit_number)) echo 'Unit # '.$unit_number;
                    echo (isset($title_pieces[1]) ? ', ' . $title_pieces[1] : '');
                    echo (isset($title_pieces[2]) ? ', ' . $title_pieces[2] : '');
                
                ?>
            </a>
        </div>
        <h5 class="property-item-price">
                $<?php
                    if( 'sold' == strtolower($ListingStatus))
                        echo !empty($sale_price) ? number_format($sale_price) :  '' . " " . $tenureAcronym;
                    else
                        echo !empty($list_price) ? number_format($list_price) : '' . " " . $tenureAcronym;
                    echo '<span class="extrawithprice">';
                  
                            $bedBathArray = array();
                            
                            if ($beds != '' ) {
                                $bedBathArray[] = $beds . '<i class="fa fa-bed" aria-hidden="true"></i>';
                            }
                            
                            if ($baths != '') {
                                $bedBathArray[] = $baths . '<i class="fa fa-bath" aria-hidden="true"></i>';
                            }

                            echo !empty($bedBathArray) ? " | " . implode(", ", $bedBathArray) : '';
                        

                    if ($propertyType == "Land") {
                        if (!empty($lotSize)) {
                            echo " | " . $lotSize . " AC";
                        }
                    }
                  
                    echo '</span>';
                ?>
        </h5>
        <div class="property-item-mls">
            <?php echo "MLS# " . $mls; ?>
        </div>
        <div class="propertylisting-main-fields">
            <?php 
            if(strtolower($propertyType) == 'residential')
            {
                
                $top_patts = array(
                    'Bed'=>$beds,'Baths'=>$bathrooms,
                    'SQ FT'=> !empty($sqft) ? number_format($sqft) : '',
                    'ACRES'=>$lotSize,
                    'Built'=>$yearBuilt
                );
            } else if(strtolower($propertyType) == 'condo') {
                $top_patts = array(
                    'Bed'=>$beds,'Baths'=>$bathrooms,
                    'SQ FT'=> !empty($sqft) ? number_format($sqft) : '',
                    'Built'=>$yearBuilt
                );
            } else if (strtolower($propertyType) == 'multi-family') {
                $top_patts = array(
                    'Sub-Type'=>$subtype
                );
            } else if (strtolower($propertyType) == 'land' ) {
                $top_patts = array(
                    'ACRES'=>$landArea
                );
            }
            else if (strtolower($propertyType) == 'commercial' || strtolower($propertyType) == 'business')
            {
                $top_patts = array(
                    'Sub-Type'=>$subtype
                );
            }?>
            <ul>
                
                <?php 
                    $details=array();
                foreach($top_patts as $key=>$value):
                    if(!empty($value) && $value!='$') :
                        array_push($details, $key . ": " . $value);
                    endif; 
                endforeach;?>
                <li><span class="title"><?php echo implode(" | ", $details);?></li>
            </ul>
            <div class="property-item-description">
                <?php echo wp_trim_words(get_post_field('post_content', $listing->ID), 40, '...'); ?>
            </div>
            
        </div>
        <div class="property-item-advactions">
            <a href="javascript:void(0);" class="showonmap" title="View On Map"><i class="fa fa-map-marker"></i></a> 
            <?php if(is_user_logged_in()) : 
                $likes = get_user_meta(get_current_user_id(), 'favorite_properties', false); ?>
                <a data-prop-id="<?php echo $ListingId ?>" href="javascript:void(0);"  class="favorite icon" title="<?php echo in_array($ListingId,$likes)?"Remove from":"Add to"; ?> Favorites"> <i class="fa fa-<?php echo in_array($ListingId,$likes)?"heartbeat":"heart"; ?>"></i> </a>
            <?php else: ?>
                <a href="/login?redirect=<?php echo urlencode(get_permalink()) ?>"  class="" title="Add to Favorites"> <i class="fa fa-heart"></i> </a>
            <?php endif; ?>
        </div>
    </div>
    
</div>