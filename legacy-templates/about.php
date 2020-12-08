<?php
/*
Template Name: About
*/
get_header();

?>
<div class="ffd-twbs">
<?php
ffdl_get_template_part('slider');

global $post;
setup_postdata( $post );
$featured_image = ffdl_get_featured_image($post->ID, 'full', false);
?>

<?php if( !empty($featured_image) ): ?>
<div class="container about-team-photo">
    <div class="row">
        <div class="col-md-12">
            <h1><?php the_title(); ?></h1>
           
                    <figure class="align-center">
                        <img src="<?php echo $featured_image; ?>" class="img-responsive " />
                    </figure>
                
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$args = array('post_type' => 'teammember', "orderby" => 'menu_order', "order" => "ASC");
$agents = new WP_Query($args);
if($agents->have_posts()):

?>
<div class="container about-team-members topPad-mini">
    <?php 
    while ($agents->have_posts()) : $agents->the_post();
        $designation = ffd_get_field("designation");
        $brokerId = ffd_get_field("broker_id");

        $featured_img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');

        if (empty($featured_img_url)) {
            $profileImage = ffd_get_field("profile_image"); 
                            //debugg($profileImage);
        } else {
            $profileImage['url'] = $featured_img_url;
        }
        $profile_image_url = (isset($profileImage['url']) && !empty($profileImage['url']) ) ? $profileImage['url'] : '';

        if( empty($profile_image_url) )
            $profile_image_url = 'http://via.placeholder.com/300?text=Photo';
    ?>
    <div class="row topPad-mini bottomPad-mini about-team-member-content agent-container">
       <div class="row">
            <div class="col-md-3 text-center">
                <a href="<?php the_permalink(); ?>">
                <img src="<?php echo $profile_image_url; ?>" class="img-responsive about-profile-img"/>
                <div class="profile-data">
                    <span class="agent-name"><?php the_title(); ?></span>
                    <span class="agent-serial"><?php the_field("broker_text");
                                                echo " ";
                                                the_field("broker_id"); ?></span>
                    <div class="agent-contact-container">
                        <div class="agent-office-phone">Office: <?php the_field("office_phone"); ?></div> 
                        <div class="agent-personal-phone">Cell: <?php the_field("personal_phone"); ?></div>
                    </div>
                </div>
                </a>
            </div>
        <div class="col-md-9">
            <div class="tm-content agent-data bottomPad-mini">
                <h1 class="first-row"> <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                <?php the_content(); ?>
            </div>
            <div class="tm-contact agent-data">
            <?php
            $awardImage = ffd_get_field("award_image");
            if ($awardImage && !empty($awardImage["url"])) {
                ?>
                        <img src="<?php echo $awardImage['url']; ?>" />
                <?php 
            } ?>
                
                <?php
                $file = ffd_get_field("vcf_file");
                if ($file) :
                ?>
                <a href="<?php echo $file["url"]; ?>">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/vcf-698.png"/>
                </a>
                <?php endif; ?>
                <?php if ($mail_to = ffd_get_field("email", $post->ID)) : ?>
                    <a href="mailto:<?php echo $mail_to; ?>" class="btn-big btn-blue">CONTACT ME</a>
                <?php endif; ?>
            </div>
        </div>
       </div>
    </div>
    <?php endwhile; wp_reset_postdata(); ?>

</div>
<?php endif; ?>

<div class="container about-team-members topPad-mini bottomPad-mini">
    
        <div class="row">
            <div class="col-md-12">
                <h1>About The Team</h1>
                <?php the_content() ?>
            </div>
        </div>
</div>

<script type="text/javascript">
    jQuery(window).on('load resize', function(){
        console.log("test");
        // Fixes the variable height issue.
        var maxHeight = Math.max.apply(null, jQuery(".agent-thumb img").map(function (){
            console.log(jQuery(this).height());
            return jQuery(this).height();
        }).get());
        console.log(maxHeight);
        jQuery(".agent-thumb a").css({"height": maxHeight, "overflow": "hidden"});
    });
</script>

</div>
<?php get_footer(); ?>