<?php
get_header();
global $post;
setup_postdata( $post );
$mls_id = get_post_meta($post->ID, "mls_id", true);
//debug($mls_id);
//$properties_all = get_agent_properties($mls_id,array('Active','Sold','Contingent'));
$properties_sold = get_agent_properties($mls_id,array('Sold'));
$properties_continactive = get_agent_properties($mls_id,array('Active','Contingent'));
?>
<div class="ffd-twbs">
<div class="container" style="margin-bottom: 20px;">
    <a class="btn-blue-transparent" href="about-us/"><< BACK TO AGENTS</a>
</div>
<div class="container agent-container">
    <div class="row">
        <div class="col-md-5 col-sm-5 agent-photo">
            <?php 
            $profileImage;
            $featured_img_url = get_the_post_thumbnail_url($post->ID,'full');
                        
            if( empty($featured_img_url) ){
                $profileImage = ffd_get_field("profile_image"); 
            //debugg($profileImage);
            } else {
                $profileImage['url'] = $featured_img_url;
            }
            ?>
            <img src="<?php echo $profileImage['url']; ?>" />
            <div class="profile-data">
                <span class="agent-name"><?php the_title(); ?></span>
                <span class="agent-serial"><?php the_field("broker_text"); echo " "; the_field("broker_id"); ?></span>
                <span class="agent-contact-container">
                    <span class="agent-office-phone">Office: <?php the_field("office_phone"); ?></span> |
                    <span class="agent-personal-phone">Cell: <?php the_field("personal_phone"); ?></span>
                </span>
            </div>
        </div>
        <div class="col-md-7 col-sm-5 agent-data">
            <?php
                $awardImage = ffd_get_field("award_image");
                if($awardImage && !empty($awardImage["url"])){
            ?>
                    <img src="<?php echo $awardImage['url']; ?>" />
            <?php } ?>
            <h1><?php the_title(); ?></h1>
            <p> <?php the_content(); ?></p>
            <?php
                $file=ffd_get_field("vcf_file");

                if($file):
            ?>
                    <a href="<?php echo $file["url"];?>">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/vcf-698.png"/>
                    </a>
                

                <?php endif; ?>
                <a href="mailto:<?php echo ffd_get_field("email",$post->ID);?>" class="btn-big btn-blue">CONTACT ME</a>
        </div>
    </div>
</div>

<?php
$active_total = $properties_continactive->found_posts;
$sold_total = $properties_sold->found_posts;

if( $mls_id && ( ($properties_continactive && $active_total > 0) || ($properties_sold && $sold_total > 0) ) ){
?>
<div class="container-fluid agent-properties-container">
    <div class="<?php echo (!($properties_continactive && $active_total > 0) || !($properties_sold && $sold_total > 0))?'container':'2';?>">
        <div class="row">
            <div class="col-md-12">
            <h2>MY LISTINGS</h2>
                <div class="row">
                    <?php if($properties_continactive && $active_total > 0){?>
                    <div class="col-md-<?php echo ($properties_sold && $sold_total > 0)?'6':'12';?>">
                        <h3 class="text-center lastrow">Active & Contingent (<?php echo $properties_continactive->found_posts?>)</h3>
                        <div class="swiper-container agentpropsslider">
                            <div class="swiper-wrapper">
                                <?php
                                foreach($properties_continactive->posts AS $property){
                                    $media = get_post_meta($property->ID,"media",true);
                                    $price = number_format(get_post_meta($property->ID,"pba__listingprice_pb__c",true));
                                    $ListingStatus = get_post_meta($property->ID,"pba__status__c",true);
                                ?>
                                    <div class="swiper-slide">
                                    <div class="property-item">
                                        <a href="<?php echo esc_url( get_post_permalink( $property->ID ) ); ?>" style="background-image:url(<?php echo $media[0]; ?>);">
                                            <div class="property-item-overlay">
                                                <?php if(get_post_meta($property->ID, "pba__propertytype__c", true)=="Condo"):?>
                                                    <span class="property-item-overlay-text"><?php echo explode(",",$property->post_title)[0]; ?></span>
                                                <?php else:?>
                                                    <span class="property-item-overlay-text"><?php echo get_post_meta($property->ID,"pba__address_pb__c",true); ?></span>
                                                <?php endif;?>
                                                <span class="property-item-overlay-text"><?php echo get_post_meta($property->ID,"pba__city_pb__c",true) . " " . get_post_meta($property->ID,"pba__postalcode_pb__c",true) ?></span>
                                                <span class="property-item-overlay-price">$<?php echo $price; ?></span>
                                            </div>
                                        </a>
                                    </div>
                                    </div>
                                <?php }?>
                                <!-- Add Arrows -->
                            </div>
                                <div class="swiper-button-next swiper-button-white"></div>
                                <div class="swiper-button-prev swiper-button-white"></div>
                        </div>
                    </div>
                    <?php } 
                    if($properties_sold && $sold_total > 0){?>
                    <div class="col-md-<?php echo ($properties_continactive && $active_total > 0)?'6':'12';?>">
                        <h3 class="text-center lastrow">Sold (<?php echo $properties_sold->found_posts?>)</h3>
                        <div class="swiper-container agentpropsslider">
                            <div class="swiper-wrapper">
                                <?php
                                foreach($properties_sold->posts AS $property){
                                    $media = get_post_meta($property->ID,"media",true);
                                    $price = number_format(get_post_meta($property->ID,"pba__listingprice_pb__c",true));
                                    $ListingStatus = get_post_meta($property->ID,"pba__status__c",true);
                                ?>
                                    <div class="swiper-slide">
                                    <div class="property-item">
                                        <a href="<?php echo esc_url( get_post_permalink( $property->ID ) ); ?>" style="background-image:url(<?php echo $media[0]; ?>);">
                                            <div class="property-item-overlay">
                                                <?php if(get_post_meta($property->ID, "pba__propertytype__c", true)=="Condo"):?>
                                                    <span class="property-item-overlay-text"><?php echo explode(",",$property->post_title)[0]; ?></span>
                                                <?php else:?>
                                                    <span class="property-item-overlay-text"><?php echo get_post_meta($property->ID,"pba__address_pb__c",true); ?></span>
                                                <?php endif;?>
                                                <span class="property-item-overlay-text"><?php echo get_post_meta($property->ID,"pba__city_pb__c",true) . " " . get_post_meta($property->ID,"pba__postalcode_pb__c",true) ?></span>
                                                <span class="property-item-overlay-price">$<?php echo $price; ?></span>
                                            </div>
                                        </a>
                                        <span class="soldbadge">SOLD</span>
                                    </div>
                                    </div>
                                <?php }?>
                                <!-- Add Arrows -->
                            </div>
                                <div class="swiper-button-next swiper-button-white"></div>
                                <div class="swiper-button-prev swiper-button-white"></div>
                        </div>
                    </div>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script type="text/javascript">
    jQuery(window).ready(function(){
        app.common.initScripts();
    });
    var agentpropsslider = new Swiper('.agentpropsslider', {
        slidesPerView:'1',
        centeredSlides: true,
        initialSlide:0,
        loop: true,
        loopedSlides: jQuery(".agentpropsslider .swiper-wrapper .swiper-slide").length,
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    });
</script>
<?php }?>
<?php get_footer(); ?>