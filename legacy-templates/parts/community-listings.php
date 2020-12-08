<?php

$intCommPropCount = $listings->found_posts; ?>
<div class="community-listings active" data-community-id="<?php echo esc_attr( $community->ID ); ?>" data-total="<?php echo $intCommPropCount; ?>">
  <h3 style="font-size: 17px;">
      
          <?php echo $community->post_title; ?>: <?php echo $intCommPropCount; ?> Listings
	 
	</h3>



	<?php if ( ! empty( $listings ) ) : ?>

	 <div class="property-item-wrap" >
		<div class="row properties-container">
			<?php
			if(count($listings->posts)>0)
			{
				foreach ($listings->posts as $listing) {
					hr_get_template_part(
						"listing-card-part",
								array(
									"listing" => $listing,
									//"search_params" => $search_params,
								)
					);
				}
			}
		?>
		</div>
		</div>
		<ul class="listing hidden">
			<?php
			$intDispCount = 0;
			foreach ( $listings->posts as $listing ) :
				//debug($i);
				//debug($listing);
				//die("OK");
				//$property_type = strtolower( jacksonfuller_HR_Helper::get_prop_type($listing->propertytype) );
				$property_type = FFDL_Helper::get_property_type($listing->ID);

				//$arrPropId = $listing->pbid;
				$media = get_post_meta($listing->ID,"media",true);
				
				//$strMainImage = $listing->image;
				$strMainImage = $media[0];
				//$strPropertyPrice = jacksonfuller_HR_Helper::format_price($listing->price);
				$strPropertyPrice = number_format(get_post_meta($listing->ID,"pba__listingprice_pb__c",true));
				//$intMid = $listing->mlsid;
				$intMid = get_post_meta($listing->ID,"mls_id__c",true);
				$comproptype = get_post_meta($listing->ID,"pba__propertytype__c",true);
				$comlistdets='';
				if(strtolower($comproptype) == 'land')
				{
					$lotsize = get_post_meta($listing->ID,"pba__lotsize_pb__c",true);
					$lotsize2 = get_post_meta($listing->ID,"land_area_calc__c",true);
					if(!empty($lotsize))
						$comlistdets = $lotsize;//.'AC';
					else if(!empty($lotsize2))
						$comlistdets = $lotsize2;//.'AC';
				}
				else
				{
					$beds = get_post_meta($listing->ID,"pba__bedrooms_pb__c",true);
					if ( $beds ) {
						$arrPropDetials['BD'] = $beds;
					} else {
						$arrPropDetials['BD'] = "--";
					}

					$baths = get_post_meta($listing->ID,"pba__fullbathrooms_pb__c",true);
					if ( $baths ) {
						$arrPropDetials['BT'] = $baths;
					} else {
						$arrPropDetials['BT'] = "--";
					}

					$comlistdets =  $arrPropDetials['BD'] . 'BD/' . $arrPropDetials['BT'] . 'BA';
				}
				$strPCode = $listing->zipCode; 
				
      	//$url="/" . jacksonfuller_HR_Helper::get_prop_type($listing->propertytype) . "/" . $listing->url;
		  $url = esc_url( get_post_permalink( $listing->ID ) );
      ?>
				<li data-pid="<?php echo $listing->ID?>" data-lat="<?php echo get_post_meta($listing->ID,"pba__latitude_pb__c",true) ?>" data-thumbnail="<?php echo $strMainImage; ?>" data-mls="<?php echo $intMid; ?>" data-lng="<?php echo get_post_meta($listing->ID,"pba__longitude_pb__c",true); ?>" data-district="<?php echo get_post_meta($listing->ID,"district__c",true) ?>" data-price="<?php echo $strPropertyPrice; ?>" data-address="<?php echo get_post_meta($listing->ID,"pba__address_pb__c",true) ?>" data-id="<?php echo get_post_meta($listing->ID,"ListingId",true) ?>" data-url="<?php echo esc_url($url) ?>">
          <?php
          	
          ?>
					<a href="<?php echo esc_url($url); ?>" style="background-image: url('<?php echo esc_url( $strMainImage ); ?>');">
						<span class="price">$<?php echo $strPropertyPrice; ?></span>
					</a>
					<address>
						<a href="<?php echo esc_url( $url ); ?>" class="address"><?php echo get_post_meta($listing->ID,"pba__address_pb__c",true) ?></a> 
						<p>
							<?php echo $comlistdets;?>, MLS# <?php echo $intMid; ?>
						</p>
					</address>
				</li>
				<?php
				
				// if ( ( $i + 1 ) >= $max_listings ) {
				// 	break;
				// }
			endforeach;
			
			if ( $intCommPropCount > $max_listings ) {
				$viewall_url = jacksonfuller_get_permalink_by_template( 'page-templates/advanced-search.php' ) . '?community=' . urlencode($community->post_title);
				?>
				<li>
					<a href="<?php echo esc_url( $viewall_url ); ?>" class="view-all">
						<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/view-all.jpg' ) ); ?>" alt="View All" />
					</a>
				</li>
				<?php
			} ?>
		</ul>
	<?php endif; ?>
</div>