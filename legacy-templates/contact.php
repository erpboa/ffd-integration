<?php
/*
Template Name: Contact
*/

get_header();

?>
<div class="ffd-twbs">
<?php ffdl_get_template_part('slider'); ?>
<div class="container-fluid">

<div class="container menu-contact-popup-container">
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <h2>Contact</h2>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 contact-container">

            <?php 
                $phone;

                if(is_user_logged_in())
                    $phone=get_user_meta(get_current_user_id(),"phone",true);
                
                if(isset($phone) && $phone!="")
                    echo do_shortcode('[contact-form-7 id="101" your-phone="' . $phone .'" title="Contact"]');
                else
                    echo do_shortcode('[contact-form-7 id="101" title="Contact"]');
            ?>
        </div>
    </div>
</div>
</div>
</div>

<?php get_footer(); ?>