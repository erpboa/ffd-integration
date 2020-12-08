<?php
$likes =array();
$loggedIn=false;

if (is_user_logged_in()) {
    $likes = get_user_meta(get_current_user_id(), 'favorite_properties', false);
    $loggedIn=true;
}   
?>
<div class="row">
    <div class="col-sm-12">
        <ul class="realtor_actions">
            <li class="save">
                <?php if($loggedIn): ?>
                    <a class="favorite" data-prop-id="<?php echo ffd_get_field("ListingId") ?>" href="javascript:void(0);"  class="pda_favorities"><?php echo in_array(ffd_get_field("ListingId"),$likes)?"Remove as Favorite":"Save Property"; ?></a>
                <?php else: ?>
                    <a href="/login?redirect=<?php echo urlencode(get_permalink()) ?>"  class="pda_favorities"><?php echo in_array(ffd_get_field("ListingId"),$likes)?"Remove as Favorite":"Save Property"; ?></a>
                <?php endif; ?>
            </li>
            <?php if(!$hiderequest): ?>
                <li class="request">
                    <a href="#contact-popup-container" class="show-contact-form-mini pda_request_info">Request Info</a>
                    <?php get_template_part('template-parts/request-info-popup');?>
                </li>
            <?php endif;?>      
            <li  class="download">
                <a href="javascript:download();" class="pda_download">Download</a>
            </li>
        </ul>
    </div>
</div>