<div id="contact-popup-container" class="mfp-hide contact-popup-container ffd-twbs" data-teammember="<?php echo isset($team_member->ID) ? $team_member->ID : '0'; ?>">
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <h2>REQUEST INFO ABOUT</h2>
            <h3><?php the_title(); ?></h3>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 contact-container">
            <?php 
                 $phone;

                 if(is_user_logged_in())
                 $phone=get_user_meta(get_current_user_id(), "phone", true);
             
            $email="";

            //$email="fisher.jarrett@gmail.com";
          if(isset($team_member) && is_object($team_member))
                $email=ffd_get_field("email", $team_member->ID);

            if(!isset($email) || $email=="")
                $email=get_bloginfo('admin_email');


                 if(isset($phone) && $phone!="")
                     echo ffd_render_contact_form_7('[contact-form-7 id="2711" title="Request Info" send-to="' . $email . '" your-phone="' . $phone . '" listing-id="' . ffd_get_field("ListingId") . '" listing-url="' . get_permalink() . '" mlsid="' . ffd_get_field("mls_id__c") .'"]');
                 else
                     echo ffd_render_contact_form_7('[contact-form-7 id="2711" title="Request Info" send-to="' . $email . '" listing-id="' . ffd_get_field("ListingId") . '" listing-url="' . get_permalink() . '" mlsid="' . ffd_get_field("mls_id__c") .'"]');
            ?>
        </div>
    </div>
</div>

