<?php
/*
Template Name: Listing Download
*/
global $post;
setup_postdata( $post );
$postId=$_GET["postid"];

// $community = ffd_get_field("community_name",$postId);
// $price = ffd_get_field("ffd_listingprice_pb",$postId);
// $bedrooms = ffd_get_field("ffd_bedrooms_pb",$postId);
// $bathrooms = ffd_get_field("ffd_fullbathrooms_pb",$postId);
// $mlsId = ffd_get_field("mls_id",$postId);
// $status = ffd_get_field("ffd_status",$postId);
// $mls = "(" . $status . ") " . $mlsId;
// $interiorArea = ffd_get_field("ffd_totalarea_pb");
// if(!empty($interiorArea))
//     $interiorArea=number_format($interiorArea);

// $priceSqFt = ffd_get_field("price_per_square_foot",$postId);
// $yearBuilt = ffd_get_field("ffd_yearbuilt_pb",$postId);
// $lotSize = ffd_get_field("ffd_lotsize_pb",$postId);
// $landTenure = ffd_get_field("land_tenure",$postId);
// $propertyType = ffd_get_field("ffd_propertytype",$postId);
// $onMarket = ffd_get_field("days_on_market",$postId);
// $updated = "";
// $media = ffd_get_field("media",$postId);
// $team_member = get_team_member_by_broker_id(ffd_get_field("listing_agent_id",$postId));

// $formattedPrice = number_format($price);

global $post;
$post = get_post($postId);
setup_postdata( $post );

$community = ffd_get_field("community_name");
$price = ffd_get_field("ffd_listingprice_pb");
$bedrooms = ffd_get_field("ffd_bedrooms_pb");
$dom = ffd_get_field("days_on_market");
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
$status = ffd_get_field("ffd_status");
$mls = "(" . $status . ") " . $mlsId;

$interiorArea = ffd_get_field("ffd_totalarea_pb");

if(!empty($interiorArea))
    $interiorArea=number_format($interiorArea);

$landArea = ffd_get_field("land_area_calc");
$annualtax = ffd_get_field("taxes");
$taxkey = ffd_get_field("taxkey");
$view = ffd_get_field("view");
$zoning = ffd_get_field("zoning");
$pool = ffd_get_field("pool");
$assessedvalue = ffd_get_field("assessed_value");
$associationfee = ffd_get_field("monthly_fees");
$subtype = ffd_get_field("listing_subtype");
$agdedicated = ffd_get_field("ag_dedicated");
$roads = ffd_get_field("roads");


$virtualtour = ffd_get_field("ffd_listing_website");//'https://hawaiihomephotography.smugmug.com/frame/slideshow?key=RRXpJB&autoStart=1&captions=0&navigation=0&playButton=0&ra';
$previousprice = ffd_get_field("previous_price");
$priceSqFt = ffd_get_field("price_per_square_foot");
$yearBuilt = ffd_get_field("ffd_yearbuilt_pb");
$lotSize = ffd_get_field("ffd_lotsize_pb");
$landTenure = ffd_get_field("land_tenure");
$propertyType = ffd_get_field("ffd_propertytype");
$onMarket = ffd_get_field("days_on_market");
$updated = ffd_get_field("changed");
$media = ffd_get_field("ffd_media");
$team_member = get_team_member_by_broker_id(ffd_get_field("listing_agent_id"));
$coOfficeId=ffd_get_field("co_listor_office_id");
$offId=ffd_get_field("listing_office_id");
$coTeamMember;

if(isset($coOfficeId) && $coOfficeId==$offId && null!=ffd_get_field("co_listing_agent"))
    $coTeamMember=ffd_get_field("co_listing_agent");


$disclaimer = ffd_get_field("ffd_disclaimer");
$listing_office_name = ffd_get_field("listing_office_name");

wp_enqueue_script( 'jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js', false );
wp_enqueue_script( 'html2Canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js', false );

$pricetag='Price';
if(strtolower($status)=='sold')
{
    $price = ffd_get_field("sale_price");
    $solddate = ffd_get_field("sale_date");
    
    $pricetag='Sold Price';
}   
$formattedPrice = number_format($price) . " " . (ffd_get_field("land_tenure")=="Fee Simple"?"FS":"LH");

if (strtolower($propertyType) == 'single-family homes') {
    
    $top_patts = array(
        'Type'=>'Home','Bed'=>$bedrooms,'Baths'=>$bathrooms,
        'SQ FT'=>str_replace(' sq ft', '', strtolower($interiorArea)),'ACRES'=>$landArea,'Built'=>$yearBuilt
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'DOM'=>$dom, 'Interior Area'=>$interiorArea . " SQ Ft", 'Land Tenure'=>$landTenure,
        'MLS'=>$mls,'LAND AREA'=>$landArea,'Annual Tax'=>'$'.number_format($annualtax),
        'Year Built'=>$yearBuilt,'Setting'=>'','Tax Key'=>$taxkey,
        'Days On Market'=>$onMarket,'View'=>str_replace(";", ", ", $view),'Zone'=>$zoning,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'Pool'=>$pool,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice),
    );
} else if (strtolower($propertyType) == 'condominium' || strtolower($propertyType) == 'loft condominium') {
    $top_patts = array(
        'Type'=>$propertyType,'Bed'=>$bedrooms,'Baths'=>$bathrooms,
        'SQ FT'=>str_replace(' sq ft', '', strtolower($landArea)),'Built'=>$yearBuilt
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'DOM'=>$dom, 'Interior Area'=>$interiorArea, 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,'Setting'=>'','Tax Key'=>$taxkey,
        'Year Built'=>$yearBuilt,'View'=>str_replace(";", ", ", $view),'Zone'=>$zoning,
        'Days On Market'=>$onMarket,'Pool'=>$pool,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'ASSOCIATION  Fee'=>'$'.number_format($associationfee),'Previous Price'=>'$'.number_format($previousprice),
    );
} else if ( strpos(strtolower($propertyType), 'units') !== false ) {
    $top_patts = array(
        'Type'=>$propertyType,'Sub-Type'=>$subtype
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Bedrooms'=>$bedrooms,'Bathrooms'=>$bathrooms,
        'Property Type'=>$propertyType,'DOM'=>$dom, 'Sub-Type'=>$subtype,'Annual Tax'=>'$'.number_format($annualtax),        
        'MLS'=>$mls,'Interior Area'=>$interiorArea, 'Tax Key'=>$taxkey,
        'Year Built'=>$yearBuilt,'LAND AREA'=>$landArea,'Zone'=>$zoning,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2), 'Setting'=>'','Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice), 'View'=>str_replace(";", ", ", $view),
    );
} else if (strtolower($propertyType) == 'land') {
    $top_patts = array(
        'Type'=>$propertyType,'ACRES'=>$landArea
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'LAND AREA'=>$landArea, 'Land Tenure'=>$landTenure,
        'Property Type'=>$propertyType,'DOM'=>$dom,  'Setting'=>'', 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,'View'=>str_replace(";", ", ", $view),'Tax Key'=>$taxkey,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'AG Dedicated'=>$agdedicated,'Zone'=>$zoning,
        'Previous Price'=>'$'.number_format($previousprice),'Roads'=>$roads, 'Assessed Value'=>'$'.number_format($assessedvalue),
        
    );
} else if (strtolower($propertyType) == 'commercial' || strtolower($propertyType) == 'business') {
    $top_patts = array(
        'Type'=>$propertyType,'Sub-Type'=>$subtype
    );
    $patts = array(
        $pricetag=>'$'.$formattedPrice,'Sub-Type'=>$subtype, 'Land Tenure'=>$landTenure,
        'Property Type'=>$propertyType,'DOM'=>$dom,  'LAND AREA'=>$landArea, 'Annual Tax'=>'$'.number_format($annualtax),
        'MLS'=>$mls,  'Setting'=>'', 'Tax Key'=>$taxkey,
        'Price sq/ft'=>'$'.number_format($priceSqFt, 2),'Zone'=>$zoning,'Assessed Value'=>'$'.number_format($assessedvalue),
        'Previous Price'=>'$'.number_format($previousprice)
    );
}

//debugg(getGoogleMapsAPIKey());
?>
<html class="ffd-twbs">
    <head>
<link rel='stylesheet' id='ffdl-styles'  href='<?php echo ffdl_get_assets_url(); ?>/style.css?ver=1.0' type='text/css' media='all' />
<link rel='stylesheet' id='Bootstrap-css'  href='<?php echo ffdl_get_assets_url(); ?>/css/bootstrap.min.css?ver=1.0' type='text/css' media='all' />

<script type='text/javascript' src='<?php echo home_url(); ?>/wp-includes/js/jquery/jquery.js?ver=1.12.4'></script>
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js'></script>
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js'></script>
</head>
<body class="property-template property-template-PropertyDetails property-template-PropertyDetails-php single single-property property-print-page">
<div class="container-fluid property-single-top">
<div class="row">
        <div class="col-md-2 col-sm-2 col-xs-12">
            <div class="logo">
                <a href="<?php echo get_site_url(); ?>">
                    <img src="<?php echo ffdl_get_site_logo(); ?>">
                </a>
            </div>
        </div>
    </div>
    <div style="padding:30px;">
        <div class="row">
            <div class="property-container">
                <div class="row">
                    <div class="col-md-12">
                    </div>
                    <div class="col-md-9">
                        <h1><?php echo get_the_title($postId); ?></h1>
                    </div>
                    <div class="col-md-3">
                        <span class="property-price">$<?php echo $formattedPrice; ?></span>
                    </div>
                    <?php
                    if(!empty($community)){
                    ?>
                    <div class="col-md-12">
                        <span class="community-name"><?php echo $community; ?></span>
                    </div>
                    <?php } ?>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <img style="width:100%" src="<?php echo $media[0]; ?>"/>
                        </div>
                    </div>
                </div>
                
                <div class="row"><div class="container"><div class="row property-data-container">

                  <?php if(strtolower($status) == 'contingent'){?><div class="text-center contingent show-contact-form-header"><p>This property is currently in escrow with a contingent offer.  Backup offers may be accepted.  Please <a href="#contact-popup-container">contact us</a> for further information</p></div><?php }?>
                    
                    <?php foreach($patts as $key=>$value):
                        if(!empty($value) && $value!='$'):?>
                            <div class="col-md-4 col-sm-6 col-xs-6 property-field">
                                <span class="property-field-title"><?php echo $key?></span>
                                <span class="property-field-value"><?php echo $value; ?></span>
                            </div>
                    <?php endif; 
                    endforeach;?>

                </div></div></div>
            </div>
        </div>
        <div class="property-details-container">
            <div class="">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-7">
                                <h2 class="content-headings">PROPERTY DETAILS</h2>
                                <?php 
                                    $content_post = get_post($postId);
                                    echo $content_post->post_content;
                                ?>
                            </div>
                            <div class="col-md-5">
                                <?php
                                    $columns = ""; 
                                    
                                    if($team_member):
                                        ffdl_get_template_part('work-with-us-teammember', array(
                                            "team_member" => $team_member,
                                            "isprint" => true
                                        ));
                                    else:
                                        hr_get_template_part('work-with-us-box', array(
                                            "request" => true,
                                            "isprint" => true
                                        ));
                                    endif;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(window).ready(function(){
        html2canvas([document.getElementsByTagName('body')[0]], {
            "proxy":"<?php echo ffdl_plugin_url(); ?>/includes/legacy-includes/html2canvas-proxy.php",
        onrendered: function (canvas) {
           var imageData = canvas.toDataURL('image/jpeg',1.0); 
           var doc = new jsPDF('portrait');
           doc.addImage(imageData,'JPEG',5,0,200,250);
           doc.save('<?php echo get_the_title($postId); ?>.pdf');
            }
            });
    })
    //function(){
        // jQuery("#printArea").scrollLeft(0);
		// jQuery("#printArea").scrollTop(0);
        // jQuery("#printArea").css("overflow","visible");
        // var doc = new jsPDF("1","pt");//'p','pt',[jQuery("#printArea").width(),jQuery("#printArea").height()]);

        // var elementHandler = {
        //     '#ignorePDF': function (element, renderer) {
        //     return true;
        // }
        // };
        // var source = window.document.getElementById("printArea");

        // doc.addHTML(
        //     source,
        //     function (){
        //         doc.save("<?php echo the_title(); ?>.pdf");
        //     }
        // );

       
        
    //}
</script>
</body>
</html>