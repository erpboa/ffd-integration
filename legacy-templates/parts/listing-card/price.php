<?php 

global $post;
$listing = $post;

$ListingStatus = get_post_meta($listing->ID, "ffd_status", true);
$sale_price = get_post_meta($listing->ID, "ffd_sale_price", true);
$list_price = get_post_meta($listing->ID, "ffd_listingprice_pb", true);
$tenure = get_post_meta($listing->ID, "land_tenure", true);
$landArea = get_post_meta($listing->ID, "land_area_calc", true);
$yearBuilt = get_post_meta($listing->ID, "ffd_yearbuilt_pb", true);
$tenureAcronym = getStringAcronym($tenure);

$beds = get_post_meta($listing->ID, "ffd_bedrooms_pb", true);
$baths = get_post_meta($listing->ID, "ffd_fullbathrooms_pb", true);
$halfbathrooms = get_post_meta($listing->ID, "ffd_halfbathrooms_pb", true);

$lotSize = get_post_meta($listing->ID, "ffd_lotsize_pb", true);
$propertyType = get_post_meta($listing->ID, "ffd_propertytype", true);

?><h5 class="property-item-price">
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
           

        if (strtolower($propertyType) == "land") {
            if (!empty($lotSize)) {
                echo " | " . $lotSize . " AC";
            }
        }

        echo '</span>';
    ?>
</h5>