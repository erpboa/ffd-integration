<?php 
global $post;
$listing = $post;
$mls = get_post_meta($listing->ID, "ffd_mls_id", true);

?><div class="property-item-mls">
    <?php echo "MLS# " . $mls; ?>
</div>