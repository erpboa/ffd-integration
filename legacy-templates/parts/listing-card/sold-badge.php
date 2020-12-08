<?php 

global $post;
$listing = $post;
$ListingStatus = get_post_meta($listing->ID, "ffd_status", true);

?>
<?php if(strtolower($ListingStatus)=='sold'){?><span class="soldbadge">SOLD</span><?php } ?>