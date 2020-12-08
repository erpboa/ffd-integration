<?php 

global $post;
$listing = $post;
$propertyType = get_post_meta($listing->ID, "ffd_propertytype", true);
?>
<div class="property-item-type">
 	<?php echo($propertyType == "Residential" ? "Home" : $propertyType); ?>
</div>