<?php
/**
 * FFD_Integration Uninstall
 *
 * Uninstalling FFD_Integration 
 *
 * @package FFD_Integration\Uninstaller
 * @version 1.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb, $wp_version;

wp_clear_scheduled_hook( 'ffd_timeout_sync' );
wp_clear_scheduled_hook( 'ffd_main_sync' );

update_option('fdd_logger_data', '');
update_option('ffd_synced_listings_total', '');
update_option('ffd_prune_listings', true);

update_option('ffd_sync_last_id', '');
update_option('ffd_sync_last_run', '');
update_option('ffd_sync_current_run', '');

update_option('ffd_propertybase_sync_ids', '');
update_option('ffd_propertybase_sync_status', 'idle');
update_option('ffd_propertybase_sync_index', '0');