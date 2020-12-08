<?php

/**
 * Retrieve page ids 
 *
 * @param string $page Page slug.
 * @return int
 */
function ffd_get_page_id( $page ) {
	

	$page = apply_filters( 'ffd_get_' . $page . '_page_id', get_option( 'ffd_' . $page . '_page_id' ) );

	return $page ? absint( $page ) : -1;
}