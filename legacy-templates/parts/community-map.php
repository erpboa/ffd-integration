<?php
if ( $current_community ) {
	$current_community->post_title = html_entity_decode($current_community->post_title);
	
	$current_listings = ffdl_get_community_listings(
		$current_community,
		array(
			'posts_per_page' => $max_listings
			//'orderby'      => 'price;DESC',
		)
	);
}
?>
<link rel="stylesheet" type="text/css" href="<?php echo bloginfo('template_directory');?>/assets/css/custom-dd.css" />
<div class="container-fluid">
	<h3>Offerings in <?php echo $current_community->post_title; ?></h3>
	<div class="row">
		<div class="map-locations col-md-12 col-sm-12 col-xs-12">
			<div class="map-locations-wrapper">
				<!--<ul class="locations">-->
					
					<?php
					$mobile_dropdown = '';
					foreach ( $communities as $community ) :
						//$kml_file = ffd_get_field("kml_file", $communityObj->ID);
						
						// $community = array();
						// $community['Id'] = ffd_get_field('ListingId', $communityObj->ID);
						// $community['kml'] = $kml_file['url'];
						// $community['Name'] = $communityObj->post_title;

     					if ( !isset( $community->kml ) ) {
						 	
							$community->kml = '';
						}
						
						$status = '';
						if($community->ListingId == $current_community->ListingId) {
							$status = 'active';
						}
						$mobile_dropdown .= '<li><a class="'.$status.'" data-id="'.esc_attr( $community->ListingId ).'" href="javascript:void(0);" data-kml="'.esc_attr( $community->kml ).'">'.$community->post_title.'</a></li>';
						
						?>
						<!--<li>
							<a class="<?php echo $community->ListingId == $current_community->ListingId ? 'active' : ''; ?>" data-id="<?php echo esc_attr( $community->ListingId ); ?>" href="javascript:void(0);" data-kml="<?php echo esc_attr( $community->kml ); ?>"><?php echo $community->post_title; ?></a>
						</li>-->
					<?php
					endforeach; ?>
					<!--<li>
						<a href="<?php echo ffdl_get_permalink_by_template( 'page-templates/advanced-search.php' ); ?>"> All Areas </a>
					</li>-->
				<!--</ul>-->
				
				<div id="resorts_dd" class="dropdown-wrapper mobile pull-left" tabindex="1">
					<span><?php echo $current_community->post_title; ?></span>
					<ul class="dropdown locations">
						<?php echo $mobile_dropdown; ?>
						<!--<li>
							<a href="<?php echo ffdl_get_permalink_by_template( 'page-templates/advanced-search.php' ); ?>"> All Areas </a>
						</li>-->
					</ul>
				</div>
				<a class="tkffdl-all-area gray-button pull-right" href="<?php echo ffdl_get_permalink_by_template( 'page-templates/advanced-search.php' ); ?>">All Areas</a>
				
				<!--<div class="dropdown">
				  <button class="dropbtn">Resorts</button>
				  <div class="dropdown-content">
					<ul class="locations">
						<?php echo $mobile_dropdown; ?>
						<li>
							<a href="<?php echo ffdl_get_permalink_by_template( 'page-templates/advanced-search.php' ); ?>"> All Areas </a>
						</li>
					</ul>	
				  </div>
				</div>-->
				
				
				<div class="map-container pull-left">
					<div class="toggle-map-wrapper">
                        <button id="ihf-map-toggle-button" class="btn gray-button hidden-md hidden-lg hidden-xlg" type="button" data-toggletarget="#community_map" data-toggleheight="530px;">
							<i class="glyphicon glyphicon-map-marker"></i> Map Search
						</button>
                    </div>
					<div id="community_map" style="height:530px;" class=""></div>
				</div>
			</div>
		</div>
		
		<div id="comm_prop_listing" class="resort-listing col-md-12 col-sm-12 col-xs-12">
			<?php
   	ffdl_get_template_part(
				'community-listings',
				array(
					'community'    => $current_community,
					'listings'     => $current_listings,
					'max_listings' => $max_listings,
				)
			); 
	?>
		</div><!-- /#comm_prop_listing -->
	</div>
</div>



<script>
	jQuery(function(){
		
	});
</script>
<script src="<?php echo bloginfo('template_directory');?>/assets/js/custom-dd.js"></script>