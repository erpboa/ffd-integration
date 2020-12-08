<?php 
global $post;
$ListingId = get_post_meta($listing->ID, "ffd_salesforce_id", true);
?>
<div class="property-item-advactions">
    <a href="javascript:void(0);" class="showonmap" title="View On Map"><i class="fa fa-map-marker"></i></a> 
    <?php if(is_user_logged_in()) : 
        $likes = get_user_meta(get_current_user_id(), 'favorite_properties', false); ?>
        <a data-prop-id="<?php echo $ListingId ?>" href="javascript:void(0);"  class="favorite icon" title="<?php echo in_array($ListingId,$likes)?"Remove from":"Add to"; ?> Favorites"> <i class="fa fa-<?php echo in_array($ListingId,$likes)?"heartbeat":"heart"; ?>"></i> </a>
    <?php else: ?>
        <a href="/login?redirect=<?php echo urlencode(get_permalink()) ?>"  class="" title="Add to Favorites"> <i class="fa fa-heart"></i> </a>
    <?php endif; ?>
</div>