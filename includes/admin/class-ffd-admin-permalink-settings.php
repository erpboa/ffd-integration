<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @class       FFD_Admin_Permalink_Settings
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'FFD_Admin_Permalink_Settings', false ) ) {
	return new FFD_Admin_Permalink_Settings();
}

/**
 * FFD_Admin_Permalink_Settings Class.
 */
class FFD_Admin_Permalink_Settings {

	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private $permalinks = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->settings_init();
		$this->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		add_settings_section( 'ffd-permalink', __( 'Listing permalinks', 'ffd' ), array( $this, 'settings' ), 'permalink' );

		add_settings_field(
			'ffd_listing_category_slug',
			__( 'Listing category base', 'ffd' ),
			array( $this, 'listing_category_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'ffd_listing_tag_slug',
			__( 'Listing tag base', 'ffd' ),
			array( $this, 'listing_tag_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'ffd_listing_attribute_slug',
			__( 'Listing attribute base', 'ffd' ),
			array( $this, 'listing_attribute_slug_input' ),
			'permalink',
			'optional'
		);

		$this->permalinks = ffd_get_permalink_structure();
	}

	/**
	 * Show a slug input box.
	 */
	public function listing_category_slug_input() {
		?>
		<input name="ffd_listing_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'listing-category', 'slug', 'ffd' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function listing_tag_slug_input() {
		?>
		<input name="ffd_listing_tag_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'listing-tag', 'slug', 'ffd' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function listing_attribute_slug_input() {
		?>
		<input name="ffd_listing_attribute_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
		<?php
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your listing URLs here. For example, using <code>shop</code> would make your listing links like <code>%sshop/sample-listing/</code>. This setting affects listing URLs only, not things such as listing categories.', 'ffd' ), esc_url( home_url( '/' ) ) ) ) );

		$shop_page_id = 0;
		$base_slug    = urldecode( ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'ffd' ) );
		$listing_base = _x( 'listing', 'default-slug', 'ffd' );

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%listing_cat%' ),
		);
		?>
		<table class="form-table ffd-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="listing_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="ffdtog" <?php checked( $structures[0], $this->permalinks['listing_base'] ); ?> /> <?php esc_html_e( 'Default', 'ffd' ); ?></label></th>
					<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?listing=sample-listing</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $listing_base ); ?>/sample-listing/</code></td>
				</tr>
				<tr>
					<th><label><input name="listing_permalink" id="ffd_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $this->permalinks['listing_base'], $structures, true ), false ); ?> />
						<?php esc_html_e( 'Custom base', 'ffd' ); ?></label></th>
					<td>
						<input name="listing_permalink_structure" id="ffd_permalink_structure" type="text" value="<?php echo esc_attr( $this->permalinks['listing_base'] ? trailingslashit( $this->permalinks['listing_base'] ) : '' ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'ffd' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'ffd-permalinks', 'ffd-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.ffdtog').change(function() {
					jQuery('#ffd_permalink_structure').val( jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').change(function() {
					jQuery('.ffd-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.ffd-permalink-structure code.non-default-example').show();
						jQuery('.ffd-permalink-structure input').removeAttr('disabled');
					} else {
						jQuery('.ffd-permalink-structure code.default-example').show();
						jQuery('.ffd-permalink-structure input:eq(0)').click();
						jQuery('.ffd-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').change();
				jQuery('#ffd_permalink_structure').focus( function(){
					jQuery('#ffd_custom_selection').click();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['ffd-permalinks-nonce'], $_POST['ffd_listing_category_slug'], $_POST['ffd_listing_tag_slug'], $_POST['ffd_listing_attribute_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['ffd-permalinks-nonce'] ), 'ffd-permalinks' ) ) { // WPCS: input var ok, sanitization ok.
			//ffd_switch_to_site_locale();

			$permalinks                   = (array) get_option( 'ffd_permalinks', array() );
			$permalinks['category_base']  = ffd_sanitize_permalink( wp_unslash( $_POST['ffd_listing_category_slug'] ) ); // WPCS: input var ok, sanitization ok.
			$permalinks['tag_base']       = ffd_sanitize_permalink( wp_unslash( $_POST['ffd_listing_tag_slug'] ) ); // WPCS: input var ok, sanitization ok.
			$permalinks['attribute_base'] = ffd_sanitize_permalink( wp_unslash( $_POST['ffd_listing_attribute_slug'] ) ); // WPCS: input var ok, sanitization ok.

			// Generate listing base.
			$listing_base = isset( $_POST['listing_permalink'] ) ? ffd_clean( wp_unslash( $_POST['listing_permalink'] ) ) : ''; // WPCS: input var ok, sanitization ok.

			if ( 'custom' === $listing_base ) {
				if ( isset( $_POST['listing_permalink_structure'] ) ) { // WPCS: input var ok.
					$listing_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['listing_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
				} else {
					$listing_base = '/';
				}

				// This is an invalid base structure and breaks pages.
				if ( '/%listing_cat%/' === trailingslashit( $listing_base ) ) {
					$listing_base = '/' . _x( 'listing', 'slug', 'ffd' ) . $listing_base;
				}
			} elseif ( empty( $listing_base ) ) {
				$listing_base = _x( 'listing', 'slug', 'ffd' );
			}

			$permalinks['listing_base'] = ffd_sanitize_permalink( $listing_base );

			

			update_option( 'ffd_permalinks', $permalinks );
			//ffd_restore_locale();
		}
	}
}

return new FFD_Admin_Permalink_Settings();
