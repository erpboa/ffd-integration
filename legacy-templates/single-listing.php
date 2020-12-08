<?php

global $post;
setup_postdata( $post );

if( isset($_GET['debug_post_meta']) ){
    ffd_debug(get_post_meta($post->ID), true);
}

$media = get_post_meta($post->ID,"ffd_media",true);

add_action('wp_head',function() use($media){hook_meta($media);});
function hook_meta($media) {
 
    if(count($media)>0 && isset($media[0]))
        $output='<meta property="og:image" content="'. $media[0] . '" />';
 
        echo $output;
}

get_header();
?>


<?php do_action('ffdl_listing_template_before'); ?>

<?php

$status = ffd_get_field("ffd_status");
$community = ffd_get_field("ffd_community_name");
if( 'sold' == strtolower($status) )
    $price = ffd_get_field("ffd_sale_price");
else
    $price = ffd_get_field("ffd_listingprice_pb");

    $price = (float) $price;

$listprice = ffd_get_field("ffd_listingprice_pb");

$bedrooms = ffd_get_field("ffd_bedrooms_pb");
$dom = ffd_get_field("ffd_days_on_market");
$fullbathrooms = ffd_get_field("ffd_fullbathrooms_pb");
$halfbathrooms = ffd_get_field("ffd_halfbathrooms_pb");
$bathrooms='';
if(!empty($halfbathrooms) && !empty($fullbathrooms))
    $bathrooms = $fullbathrooms.' Full, '.$halfbathrooms.' Half';
else if(!empty($fullbathrooms))
    $bathrooms = $fullbathrooms.' Full';
else if(!empty($halfbathrooms))
    $bathrooms = $halfbathrooms.' Half';

$mlsId = ffd_get_field("ffd_mls_id");

$mls = "(" . $status . ") " . $mlsId;

$interiorArea = ffd_get_field("ffd_totalarea_pb");

if(!empty($interiorArea))
    $interiorArea=number_format($interiorArea);

$landArea = ffd_get_field("ffd_land_area_calc");
$annualtax = ffd_get_field("ffd_taxes");
$annualtax = (float) $annualtax;

$taxkey = ffd_get_field("ffd_taxkey");
$view = ffd_get_field("ffd_view");
$zoning = ffd_get_field("ffd_zoning");
$pool = ffd_get_field("ffd_pool");
$assessedvalue = ffd_get_field("ffd_assessed_value");
$assessedvalue = (float) $assessedvalue;

$associationfee = ffd_get_field("ffd_monthly_fees");
$subtype = ffd_get_field("ffd_listing_subtype");
$agdedicated = ffd_get_field("ffd_ag_dedicated");
$roads = ffd_get_field("ffd_roads");
$virtualtour = ffd_get_field("ffd_listing_website");//'https://hawaiihomephotography.smugmug.com/frame/slideshow?key=RRXpJB&autoStart=1&captions=0&navigation=0&playButton=0&ra';
$previousprice = ffd_get_field("ffd_previous_price");
$previousprice = (float) $previousprice;

$priceSqFt = ffd_get_field("ffd_price_per_square_foot");
$priceSqFt = (float) $priceSqFt;

$yearBuilt = ffd_get_field("ffd_yearbuilt_pb");
$lotSize = ffd_get_field("ffd_lotsize_pb");
$landTenure = ffd_get_field("ffd_land_tenure");
$propertyType = ffd_get_field("ffd_propertytype");
$listing_type = ffd_get_field("ffd_listingtype");
$number_of_units =  ffd_get_field("ffd_of_units");
$unit_number =  ffd_get_field("ffd_unit_number");
$area =  ffd_get_field("ffd_area_pb");
$zip_postcode = ffd_get_field('ffd_postalcode_pb');

$onMarket = ffd_get_field("ffd_days_on_market");
$updated = ffd_get_field("ffd_changed");

$team_member = get_team_member_by_broker_id(ffd_get_field("ffd_listing_agent_id"));
$coOfficeId=ffd_get_field("ffd_co_listor_office_id");
$offId=ffd_get_field("ffd_listing_office_id");
$coTeamMember;

if(isset($coOfficeId) && $coOfficeId==$offId && null!=ffd_get_field("ffd_co_listing_agent"))
    $coTeamMember=ffd_get_field("ffd_co_listing_agent");


$disclaimer = ffd_get_field("ffd_disclaimer");
$listing_office_name = ffd_get_field("ffd_listing_office_name");
$listing_agent_name = ffd_get_field('ffd_listing_agent_firstname') . ( ffd_get_field('ffd_listing_agent_lastname') ? ' ' . ffd_get_field('ffd_listing_agent_lastname') : '');
$listing_co_agent_name = ffd_get_field('ffd_listing_co_agent_name');

$listing_co_office_name = ffd_get_field("ffd_listing_co_office_name");

$selling_agent_name = ffd_get_field("ffd_selling_agent_name");
$selling_office_name = ffd_get_field("ffd_selling_office_name");

$selling_co_agent_name = ffd_get_field("ffd_selling_co_agent_name");
$selling_co_office_name = ffd_get_field("ffd_selling_co_office_name");


$parking = ffd_get_field('ffd_parkingspaces');
$hoa_dues = ffd_get_field('ffd_hoa_dues');

wp_enqueue_script( 'jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js', false );
wp_enqueue_script( 'html2Canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js', false );

$pricetag='Price';
if(strtolower($status)=='sold')
{
    $price = ffd_get_field("ffd_sale_price");
    $listprice = ffd_get_field('ffd_original_price');
   
    $soldpercent = (round( $price / $listprice, 2) ) * 100;

    $solddate = ffd_get_field("ffd_sale_date");
    
    $pricetag='Sold Price';

    $listprice_formatedtext = ' of the list price $' . number_format($listprice);
    $soldpercent_text = ' for ' . $soldpercent . '%';
}   
$formattedPrice = number_format($price);

if (strtolower($propertyType) == 'single-family home' || strtolower($propertyType) == 'single-family homes') {
    
    $top_patts = array(
        'Type'=>'Home','Bed'=>$bedrooms,'Baths'=>$bathrooms,
        'SQ FT'=>str_replace(' sq ft', '', strtolower($interiorArea)),'ACRES'=>$landArea,'Built'=>$yearBuilt
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'Days on Market'=>$dom, 'Interior Area'=>$interiorArea . " SQ FT", 'Land Tenure'=>$landTenure,
        'MLS'=>$mls,'LAND AREA'=>$landArea,'Annual Tax'=>'$'.number_format($annualtax),
        'Year Built'=>$yearBuilt,'Setting'=>'','Tax Key'=>$taxkey,
        'View'=>str_replace(',', ", ", $view),'Zone'=>$zoning,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'Pool'=>$pool,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice),
    );
} else if (strtolower($propertyType) == 'condominium' || strtolower($propertyType) == 'loft condominium') {
    $top_patts = array(
        'Type'=>$propertyType,'Bed'=>$bedrooms,'Baths'=>$bathrooms,
        'SQ FT'=>str_replace(' sq ft', '', strtolower($landArea)),
        '# of Units' => $number_of_units,
        'Interior Area'=>$interiorArea
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'Days on Market'=>$dom, 'Interior Area'=>$interiorArea, 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,'Setting'=>'','Tax Key'=>$taxkey,
        'Year Built'=>$yearBuilt,'View'=>str_replace(',', ", ", $view),'Zone'=>$zoning,
        'Pool'=>$pool,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'ASSOCIATION  Fee'=>'$'.number_format($associationfee),'Previous Price'=>'$'.number_format($previousprice),
    );
} else if (strpos(strtolower($propertyType), 'unit') !== false ) {
    $top_patts = array(
        'Type'=>$propertyType, '# of Units' => $number_of_units, 'Interior Area'=>$interiorArea
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'Days on Market'=>$dom, 'Sub-Type'=>$subtype,'Annual Tax'=>'$'.number_format($annualtax),        
        'MLS'=>$mls,'Interior Area'=>$interiorArea, 'Tax Key'=>$taxkey,
        'Year Built'=>$yearBuilt,'LAND AREA'=>$landArea,'Zone'=>$zoning,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2), 'Setting'=>'','Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice), 'View'=>str_replace(',', ", ", $view),
    );
} else if (strtolower($propertyType) == 'land') {
    $top_patts = array(
        'Type'=>$propertyType,'ACRES'=>$landArea
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'LAND AREA'=>$landArea, 'Land Tenure'=>$landTenure,
        'Property Type'=>$propertyType,'Days on Market'=>$dom,  'Setting'=>'', 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,'View'=>str_replace(',', ", ", $view),'Tax Key'=>$taxkey,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'AG Dedicated'=>$agdedicated,'Zone'=>$zoning,
        'Previous Price'=>'$'.number_format($previousprice),'Roads'=>$roads, 'Assessed Value'=>'$'.number_format($assessedvalue),
        
    );
} else if (strtolower($propertyType) == 'commercial' || strtolower($propertyType) == 'business' ) {
    $top_patts = array(
        'Type'=>$propertyType,'Sub-Type'=>$subtype
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Sub-Type'=>$subtype, 'Land Tenure'=>$landTenure,
        'Property Type'=>$propertyType,'Days on Market'=>$dom,  'LAND AREA'=>$landArea, 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,  'Setting'=>'', 'Tax Key'=>$taxkey,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'Zone'=>$zoning,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice)
    );
} else {
   
    $top_patts = array(
        'Type'=>'Home','Bed'=>$bedrooms,'Baths'=>$bathrooms,
        'SQ FT'=>str_replace(' sq ft', '', strtolower($interiorArea)),'ACRES'=>$landArea,'Built'=>$yearBuilt
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'Days on Market'=>$dom, 'Interior Area'=>$interiorArea . " SQ FT", 'Land Tenure'=>$landTenure,
        'MLS'=>$mls,'LAND AREA'=>$landArea,'Annual Tax'=>'$'.number_format($annualtax),
        'Year Built'=>$yearBuilt,'Setting'=>'','Tax Key'=>$taxkey,
        'View'=>str_replace(',', ", ", $view),'Zone'=>$zoning,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'Pool'=>$pool,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice),
    );

}


if( !empty($propertyType) ){
    if( strtolower($propertyType) == 'single-family home') 
        $top_patts['Type'] = 'HOME';
    else  if( strtolower($propertyType) == 'condominium') 
        $top_patts['Type'] = 'CONDO';
    else  if( strtolower($propertyType) == 'loft condominium') 
        $top_patts['Type'] = 'LOFT';
    else 
        $top_patts['Type'] = $propertyType;
}

$landArea_sqft = str_replace(' sq ft', '', strtolower($landArea));
if( !isset($top_patts['SQ FT']) && !empty($landArea_sqft)){
    $top_patts['SQ FT'] = $landArea_sqft;
}

if( $parking != '' ){
    $top_patts['Parking'] = $parking;
    $patts['Parking'] = $parking;

}

if( $hoa_dues != '' ){
    $top_patts['HOA Dues'] = '$' . number_format($hoa_dues, 2);
    $patts['HOA Dues'] = '$' . number_format($hoa_dues, 2);
}


$subdist_c = ffd_get_field("ffd_subdist");
$community_title = $subdist_c;
if( strpos($subdist_c, '-') ){
    $subdist_pieces = explode('-', $subdist_c);
    $community_title = trim($subdist_pieces[1]);
}

$community_obj = get_page_by_title($community_title, 'OBJECT', 'community');
if( !$community_obj )
    $community_obj = get_page_by_title($subdist_c, 'OBJECT', 'community');

if( $community_obj ){
    $community_link = '<a href="'.get_permalink($community_obj->ID).'"><b>'.$subdist_c.'</b></a>';
} else {
    $community_link = $subdist_c;
}

if( !empty($unit_number) ){
    $patts['Unit #'] = $unit_number;
}

if( !empty($formattedPrice) ){
    $patts[$pricetag] = '$'.$formattedPrice;
}
    

$nonzeros_values = array('SQ FT', 'Interior Area');
foreach($nonzeros_values as $nonzero_value ){
    if( isset($top_patts[$nonzero_value]) && $top_patts[$nonzero_value] == 0 ) unset($top_patts[$nonzero_value]);
    if( isset($patts[$nonzero_value]) && $patts[$nonzero_value] == 0 ) unset($patts[$nonzero_value]);
}


    
//debugg(ffdl_get_gmap_api_key());

?>
<div class="ffd-twbs">
<div class="container-fluid light-gray-bg property-single-top">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <span class="breadcrumb">California  <?php if( $subdist_c ){ ?> >  <?php echo $community_link; } ?>  >  <?php echo the_title() ?></span>
            </div>
        </div>
        <div class="row property-container">
            <div class="col-md-12 ">
                <?php  
                if( !empty($_SESSION) && isset($_SESSION['ffdl_user_last_search']) ){
                $back_to_search_url = add_query_arg(wp_parse_args('?search='.$_SESSION['ffdl_user_last_search'], array()), get_permalink($_SESSION['ffdl_search_page_id']));?>
                <a href="<?php echo $back_to_search_url; ?>"><< BACK TO SEARCH RESULTS</a>
                <?php } ?>
            </div>
            <div class="col-md-9">
                <h1 class="h3 last-row"><?php 
                $title_pieces = explode(',', get_the_title()); 
                 /* if(!empty($unit_number)) { 
                    $title_pieces[0] = str_replace(array('#'.$unit_number, '#'), '', $title_pieces[0]); 
                    $title_pieces[0] = trim($title_pieces[0]) . ', Unit #'.$unit_number;
                } */
                echo rtrim(trim($title_pieces[0]), '#');
                 ?></h1>
                <h4 class="h4 first-row"><?php echo isset($title_pieces[1]) ? $title_pieces[1] : ''; ?> <?php echo isset($title_pieces[2]) ? ', ' . $title_pieces[2] : ''; //if($zip_postcode){ echo ', ' . $zip_postcode; } ?></h4>
                <span class="community-name">
                <?php if(!empty($community)) {?>
                <a href="<?php echo get_permalink(FFDL_Helper::get_community_id($community)) ?>"><?php echo $community; ?></a>
                <?php } else{ 
                    echo (null!=ffd_get_field("ffd_subdist")?ffd_get_field("ffd_subdist"):"") . (null!=ffd_get_field("ffd_subdivision")?" | " . ffd_get_field("ffd_subdivision"):"");
                }?>
                </span>
            </div>
            <div class="col-md-3">
                <span class="property-price text-center" <?php if(strtolower($status)=='sold'){ echo 'style="margin-top: -10px;"';}?>><?php if(strtolower($status)=='sold'){ echo '<small>SOLD FOR</small>';}?>$<?php echo $formattedPrice; ?><?php if(strtolower($status)=='sold'){ echo '<small>ON '.date('m/d/Y',strtotime($solddate)).' '.$soldpercent_text.' '.$listprice_formatedtext.'</small>';}?></span>
            </div>
            <div class="col-md-12 property-slider-container">
                <div class="swiper-container gallery-top">
                    <div class="swiper-wrapper">
                    <?php 
                    if(count($media)>0 && !empty($media) )
                    {
                        foreach( $media as $img ){ ?>    
                            <div class="swiper-slide">
                                <img src="<?php echo "https://images1-focus-opensocial.googleusercontent.com/gadgets/proxy?url=" . $img . "&container=focus&no_expand=1&resize_w=945&refresh=2592000"; ?>" class="img-responsive" />
                            </div>
                        <?php 
                        }
                    }else{?>
                   
                    <div class="swiper-slide">
                        <img src="<?php echo ffdl_get_assets_url() . "/images/default-preview.jpg"; ?>" class="img-responsive" />
                    <div>    
                    <?php } ?>
                    </div>
                    
                    <!-- Add Arrows -->
                    <div class="swiper-button-next swiper-button-white"></div>
                    <div class="swiper-button-prev swiper-button-white"></div>
                </div>
                <div class="swiper-container gallery-thumbs">
                    <div class="swiper-wrapper">
                    <?php 
                    if(count($media)>0 && !empty($media))
                    {
                        foreach( $media as $img ){ ?>    
                            <div class="swiper-slide" style="background-image:url(<?php echo "https://images1-focus-opensocial.googleusercontent.com/gadgets/proxy?url=" . $img . "&container=focus&resize_w=287&refresh=2592000"; ?>)"></div>
                        <?php }
                    }else{?>
                        <div class="swiper-slide" style="background-image:url(<?php echo get_template_directory_uri()."/assets/images/default-preview.jpg"; ?>)"></div>                                
                    <?php }?>
                    </div>
                    <!-- Add Arrows -->
                    <div class="swiper-button-next swiper-button-white"></div>
                    <div class="swiper-button-prev swiper-button-white"></div>
                </div>
            </div>
            <?php if(!empty($top_patts)): ?>
            <div class="col-md-12">
                <div class="property-shortdata-container">
                    <ul>
                        <?php foreach($top_patts as $key=>$value):
                            $value = trim($value);
                            if($value != '' && $value!='$'):?>
                            <li><?php echo $value; ?><span class="title"><?php echo $key?></span></li>
                            <?php endif; 
                        endforeach;?>
                    </ul>
                </div>
            </div> 
            <?php endif; ?>
            <?php if(strtolower($status)!='sold'){?>
            <div class="col-md-12"> <div class="property-actions-container">
                <div class="<?php if (!empty(trim($virtualtour))) : ?> col-md-9 <?php else : ?> col-md-10 <?php endif; ?>">
                    <ul>
                        <li  class="download">
                            <a href="javascript:download();" class="pda_download"><i class="fa fa-print"></i>Print</a>
                        </li>
                        <li class="request">
                            <a href="#contact-popup-container" class="show-contact-form-mini pda_request_info"><i class="fa fa-window-restore"></i>Request Info</a>
                            <?php 
                            
                                ffdl_get_template_part('request-info-popup', array(
                                    "team_member" => $team_member
                                ));
                            ?>
                        </li>
                         <li class="request">
                             
                            <a href="#email-popup-container" class="show-contact-form-mini pda_request_info"><i class="fa fa-share-square-o"></i>Email to Friend</a>           
                        </li>
                         <li class="text-center">
                            <a href="#mortgage-calculator-popup-container" class="mortgage-cal-btn"><i class="fa fa-calculator"></i> Mortgage Calculator</a>
                        </li>
                    </ul>
                     <!-- Email Friend -->
                    <div id="email-popup-container" class="mfp-hide contact-popup-container">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <h2>Email To Friend</h2>
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 contact-container">
                                <?php
                                    echo ffd_render_contact_form_7('[contact-form-7 title="Email Friend" listing-id="' . ffd_get_field("ffd_salesforce_id", $post->ID) . '" listing-url="' . get_permalink($post->ID) .'"]');
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- mortgage calculator -->              
                    <div id="mortgage-calculator-popup-container" class="mfp-hide topPad-mini">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="property-data-container">
                                    <?php ffdl_get_template_part('mortgage-calculator'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="<?php if (!empty(trim($virtualtour))) : ?> col-md-3 <?php else : ?> col-md-2 <?php endif; ?>">
                    <ul>
                        <li  class="save">
                            <?php if(is_user_logged_in()): $likes = get_user_meta(get_current_user_id(), 'favorite_properties', false);?>
                                <a class="favorite icon" data-prop-id="<?php echo ffd_get_field("ffd_salesforce_id") ?>" href="javascript:void(0);"  class="pda_favorities" title="<?php echo in_array(ffd_get_field("ffd_salesforce_id"),$likes)?"Remove from ":"Add to"; ?> favorite"><i class="fa fa-<?php echo in_array(ffd_get_field("ffd_salesforce_id"),$likes)?"heartbeat":"heart"; ?>"></i></a>
                            <?php else: ?>
                                <a href="/login?redirect=<?php echo urlencode(get_permalink()) ?>"  class="pda_favorities" title="Add to favorite"><i class="fa fa-heart"></i></a>
                            <?php endif; ?>
                        </li>
                        <?php if(!empty(trim($virtualtour))){?><li class="virtualtour"><a href="<?php echo $virtualtour?>" class="btn-big-wide btn-blue virtualtourpopup">Virtual Tour</a></li><?php }?>
                    </ul>
                </div>
            </div></div>
            <?php }?>
            <div class="col-md-12"><div class="property-summary-container">
                <div class="col-md-12 col-xs-12">
                    <h2 class="content-headings">PROPERTY DESCRIPTION</h2>
                    <?php the_content(); ?>
                </div></div>
            </div>
            <div class="col-md-12">
                <div class="property-data-container">                    
                    <?php if(strtolower($status) == 'contingent'){?><div class="text-center contingent show-contact-form-header"><p>This property is currently in escrow with a contingent offer.  Backup offers may be accepted.  Please <a href="#contact-popup-container">contact us</a> for further information</p></div><?php }?>
                    
                    <?php if(!empty($patts)): foreach($patts as $key=>$value):
                        if(!empty($value) && $value!='$' && ltrim('$', $value)):?>
                            <div class="col-md-4 col-sm-6 col-xs-6 property-field">
                                <span class="property-field-title"><?php echo $key?></span>
                                <span class="property-field-value"><?php echo $value; ?></span>
                            </div>
                    <?php endif; 
                    endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container-fluid light-gray-bg property-details-container">
    <div class="container">
        <div class="row topPad-mini">
            <div class="col-md-12">
                <h2 class="content-headings">PROPERTY LOCATION</h2>
                <div id="map" style="width: 100%; height:300px;"></div>
            </div>
        </div>
        <div class="row topPad">
            <div class="col-md-12">
                <?php if( isset($_GET['debug'])): ?><pre><?php print_r(get_post_meta($post->ID)); ?></pre><?php endif; ?>
                <?php 
                if(!empty($listing_office_name) || !empty($listing_co_office_name)):
                
                    $str = $listing_office_name . ( !empty($listing_agent_name) ? ', '. $listing_agent_name : '');

                    if( strtolower($listing_office_name) != strtolower($listing_co_office_name) )
                        $str .= (!empty($listing_co_office_name) ? ', '.$listing_co_office_name : '') . ( !empty($listing_co_agent_name) ? ', '. $listing_co_agent_name : '');
                ?>
                    <!-- Listing Courtesy of [brokerage 1], [agent 1], [brokerage 2], [agent 2] -->
                    <p>Listing Courtesy of: <?php echo ltrim($str, ',');  ?></p>
                <?php endif; ?>
              
                <?php 
                if( strtolower($status)=='sold' && ( !empty($selling_office_name) || !empty($selling_co_office_name) ) ):
                
                    $str = ( !empty($selling_agent_name) ? $selling_agent_name . ' at ': '' ) . ( !empty($selling_office_name) ? $selling_office_name : '');
                    $str = trim($str, ' at ');
                    if( strtolower($selling_agent_name) != strtolower($selling_co_agent_name) )
                        $str .= (!empty($selling_co_agent_name) ? ' and '.$selling_co_agent_name : '') . ( !empty($selling_co_office_name) ? ' at '. $selling_co_office_name : '');
                ?>

                    <p>Buyer represented by <?php $str = trim($str); $str = trim($str, 'at'); $str = trim($str, 'and'); echo $str; ?></p>
                <?php endif; ?>
                <h2 class="content-headings">Disclaimer</h2>
                <p><?php echo (!empty($disclaimer))?$disclaimer:'MLS listing information is deemed reliable but is not guaranteed by the service.';?></p>
            </div>
        </div>
        <?php if(!empty($updated)){?>
            <div class="row topPad">
                <div class="col-md-12">
                    <h2 class="content-headings">Updated</h2>
                    <p><?php echo date('m/d/Y',strtotime($updated));?></p>
                </div>
            </div>
        <?php }?>


    </div>
</div>
</div>
<script type="text/javascript">
    jQuery(window).ready(function(){
        app.common.initScripts();
    });
    var galleryTop = new Swiper('.gallery-top', {
        slidesPerView:'1',
        centeredSlides: true,
        initialSlide:0,
        loop: true,
        loopedSlides: jQuery(".gallery-top .swiper-wrapper .swiper-slide").length,
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    });
    var galleryThumbs = new Swiper('.gallery-thumbs', {
        spaceBetween: 10,
        slideToClickedSlide: true,
        visibilityFullFit: true,
	    slidesPerView:'5',
        centeredSlides: true,
        initialSlide:0,
        loop: true,
        loopedSlides: jQuery(".gallery-thumbs .swiper-wrapper .swiper-slide").length,
        
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    });
    galleryTop.controller.control = galleryThumbs;
    galleryThumbs.controller.control = galleryTop;
    

    function showMap(){
        var map;
        var lat = '<?php the_field('ffd_latitude_pb'); ?>';
        var lng = '<?php the_field('ffd_longitude_pb'); ?>';

        var myLatLng = {lat: parseFloat(lat), lng:parseFloat(lng)};
        map = new google.maps.Map(document.getElementById('map'), {
            center: myLatLng,
            zoom: 18
        });

        var marker = new google.maps.Marker({
            position: myLatLng,
            map: map,
            title: "<?php the_title(); ?>" 
        }); 
    }
    function download(){
        window.open("<?php echo get_permalink(get_page_by_title("Print Listing")) . '?postid=' . get_the_ID(); ?>");
    }
</script>

<?php do_action('ffdl_listing_template_after'); ?>

<?php get_footer(); ?>