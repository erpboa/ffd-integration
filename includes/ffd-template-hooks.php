<?php
/**
 * FFD_Integration Template Hooks
 *
 * Action/filter hooks used for FFD_Integration functions/templates.
 *
 * @package FFD_Integration/Templates
 * @version 2.1.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', 'ffd_body_class' );
add_filter( 'post_class', 'ffd_listing_post_class', 20, 3 );

/**
 * WP Header.
 *
 * @see ffd_generator_tag()
 */
add_filter( 'get_the_generator_html', 'ffd_generator_tag', 10, 2 );
add_filter( 'get_the_generator_xhtml', 'ffd_generator_tag', 10, 2 );

/**
 * Content Wrappers.
 *
 * @see ffd_output_content_wrapper()
 * @see ffd_output_content_wrapper_end()
 */
add_action( 'ffd_before_main_content', 'ffd_output_content_wrapper', 10 );
add_action( 'ffd_after_main_content', 'ffd_output_content_wrapper_end', 10 );

/**
 * Sale flashes.
 *
 * @see ffd_show_listing_loop_sale_flash()
 * @see ffd_show_listing_sale_flash()
 */
add_action( 'ffd_before_listings_loop_item_title', 'ffd_show_listing_loop_sale_flash', 10 );
add_action( 'ffd_before_single_listing_summary', 'ffd_show_listing_sale_flash', 10 );

/**
 * Breadcrumbs.
 *
 * @see ffd_breadcrumb()
 */
add_action( 'ffd_before_main_content', 'ffd_breadcrumb', 20, 0 );

/**
 * Sidebar.
 *
 * @see ffd_get_sidebar()
 */
add_action( 'ffd_sidebar', 'ffd_get_sidebar', 10 );

/**
 * Archive descriptions.
 *
 * @see ffd_taxonomy_archive_description()
 * @see ffd_listing_archive_description()
 */
add_action( 'ffd_archive_description', 'ffd_taxonomy_archive_description', 10 );
add_action( 'ffd_archive_description', 'ffd_listing_archive_description', 10 );

/**
 * Product loop start.
 */
add_filter( 'ffd_listing_loop_start', 'ffd_maybe_show_listing_subcategories' );

/**
 * Products Loop.
 *
 * @see ffd_result_count()
 * @see ffd_catalog_ordering()
 */
add_action( 'ffd_before_listings_loop', 'ffd_result_count', 20 );
add_action( 'ffd_before_listings_loop', 'ffd_catalog_ordering', 30 );
add_action( 'ffd_no_products_found', 'ffd_no_products_found' );

/**
 * Product Loop Items.
 *
 * @see ffd_template_loop_listing_link_open()
 * @see ffd_template_loop_listing_link_close()
 * @see ffd_template_loop_add_to_cart()
 * @see ffd_template_loop_listing_thumbnail()
 * @see ffd_template_loop_listing_title()
 * @see ffd_template_loop_category_link_open()
 * @see ffd_template_loop_category_title()
 * @see ffd_template_loop_category_link_close()
 * @see ffd_template_loop_price()
 * @see ffd_template_loop_rating()
 */
add_action( 'ffd_before_listings_loop_item', 'ffd_template_loop_listing_link_open', 10 );
add_action( 'ffd_after_listings_loop_item', 'ffd_template_loop_listing_link_close', 5 );
add_action( 'ffd_after_listings_loop_item', 'ffd_template_loop_add_to_cart', 10 );
add_action( 'ffd_before_listings_loop_item_title', 'ffd_template_loop_listing_thumbnail', 10 );
add_action( 'ffd_listings_loop_item_title', 'ffd_template_loop_listing_title', 10 );

add_action( 'ffd_before_subcategory', 'ffd_template_loop_category_link_open', 10 );
add_action( 'ffd_listings_loop_subcategory_title', 'ffd_template_loop_category_title', 10 );
add_action( 'ffd_after_subcategory', 'ffd_template_loop_category_link_close', 10 );

add_action( 'ffd_after_listings_loop_item_title', 'ffd_template_loop_price', 10 );
add_action( 'ffd_after_listings_loop_item_title', 'ffd_template_loop_rating', 5 );

/**
 * Subcategories.
 *
 * @see ffd_subcategory_thumbnail()
 */
add_action( 'ffd_before_subcategory_title', 'ffd_subcategory_thumbnail', 10 );

/**
 * Before Single Products Summary Div.
 *
 * @see ffd_show_listing_images()
 * @see ffd_show_listing_thumbnails()
 */
add_action( 'ffd_before_single_listing_summary', 'ffd_show_listing_images', 20 );
add_action( 'ffd_listing_thumbnails', 'ffd_show_listing_thumbnails', 20 );

/**
 * After Single Products Summary Div.
 *
 * @see ffd_output_listing_data_tabs()
 * @see ffd_upsell_display()
 * @see ffd_output_related_products()
 */
add_action( 'ffd_after_single_listing_summary', 'ffd_output_listing_data_tabs', 10 );
add_action( 'ffd_after_single_listing_summary', 'ffd_upsell_display', 15 );
add_action( 'ffd_after_single_listing_summary', 'ffd_output_related_products', 20 );

/**
 * Product Summary Box.
 *
 * @see ffd_template_single_title()
 * @see ffd_template_single_rating()
 * @see ffd_template_single_price()
 * @see ffd_template_single_excerpt()
 * @see ffd_template_single_meta()
 * @see ffd_template_single_sharing()
 */
add_action( 'ffd_single_listing_summary', 'ffd_template_single_title', 5 );
add_action( 'ffd_single_listing_summary', 'ffd_template_single_rating', 10 );
add_action( 'ffd_single_listing_summary', 'ffd_template_single_price', 10 );
add_action( 'ffd_single_listing_summary', 'ffd_template_single_excerpt', 20 );
add_action( 'ffd_single_listing_summary', 'ffd_template_single_meta', 40 );
add_action( 'ffd_single_listing_summary', 'ffd_template_single_sharing', 50 );

/**
 * Reviews
 *
 * @see ffd_review_display_gravatar()
 * @see ffd_review_display_rating()
 * @see ffd_review_display_meta()
 * @see ffd_review_display_comment_text()
 */
add_action( 'ffd_review_before', 'ffd_review_display_gravatar', 10 );
add_action( 'ffd_review_before_comment_meta', 'ffd_review_display_rating', 10 );
add_action( 'ffd_review_meta', 'ffd_review_display_meta', 10 );
add_action( 'ffd_review_comment_text', 'ffd_review_display_comment_text', 10 );

/**
 * Product Add to cart.
 *
 * @see ffd_template_single_add_to_cart()
 * @see ffd_simple_add_to_cart()
 * @see ffd_grouped_add_to_cart()
 * @see ffd_variable_add_to_cart()
 * @see ffd_external_add_to_cart()
 * @see ffd_single_variation()
 * @see ffd_single_variation_add_to_cart_button()
 */
add_action( 'ffd_single_listing_summary', 'ffd_template_single_add_to_cart', 30 );
add_action( 'ffd_simple_add_to_cart', 'ffd_simple_add_to_cart', 30 );
add_action( 'ffd_grouped_add_to_cart', 'ffd_grouped_add_to_cart', 30 );
add_action( 'ffd_variable_add_to_cart', 'ffd_variable_add_to_cart', 30 );
add_action( 'ffd_external_add_to_cart', 'ffd_external_add_to_cart', 30 );
add_action( 'ffd_single_variation', 'ffd_single_variation', 10 );
add_action( 'ffd_single_variation', 'ffd_single_variation_add_to_cart_button', 20 );

/**
 * Pagination after shop loops.
 *
 * @see ffd_pagination()
 */
add_action( 'ffd_after_listings_loop', 'ffd_pagination', 10 );

/**
 * Product page tabs.
 */
add_filter( 'ffd_listing_tabs', 'ffd_default_listing_tabs' );
add_filter( 'ffd_listing_tabs', 'ffd_sort_listing_tabs', 99 );

/**
 * Additional Information tab.
 *
 * @see ffd_display_listing_attributes()
 */
add_action( 'ffd_listing_additional_information', 'ffd_display_listing_attributes', 10 );

/**
 * Checkout.
 *
 * @see ffd_checkout_login_form()
 * @see ffd_checkout_coupon_form()
 * @see ffd_order_review()
 * @see ffd_checkout_payment()
 */
add_action( 'ffd_before_checkout_form', 'ffd_checkout_login_form', 10 );
add_action( 'ffd_before_checkout_form', 'ffd_checkout_coupon_form', 10 );
add_action( 'ffd_checkout_order_review', 'ffd_order_review', 10 );
add_action( 'ffd_checkout_order_review', 'ffd_checkout_payment', 20 );
add_action( 'ffd_checkout_terms_and_conditions', 'ffd_checkout_privacy_policy_text', 20 );
add_action( 'ffd_checkout_terms_and_conditions', 'ffd_terms_and_conditions_page_content', 30 );

/**
 * Cart widget
 */
add_action( 'ffd_widget_shopping_cart_buttons', 'ffd_widget_shopping_cart_button_view_cart', 10 );
add_action( 'ffd_widget_shopping_cart_buttons', 'ffd_widget_shopping_cart_proceed_to_checkout', 20 );

/**
 * Cart.
 *
 * @see ffd_cross_sell_display()
 * @see ffd_cart_totals()
 * @see ffd_button_proceed_to_checkout()
 */
add_action( 'ffd_cart_collaterals', 'ffd_cross_sell_display' );
add_action( 'ffd_cart_collaterals', 'ffd_cart_totals', 10 );
add_action( 'ffd_proceed_to_checkout', 'ffd_button_proceed_to_checkout', 20 );
add_action( 'ffd_cart_is_empty', 'ffd_empty_cart_message', 10 );

/**
 * Footer.
 *
 * @see  ffd_print_js()
 * @see ffd_demo_store()
 */
add_action( 'wp_footer', 'ffd_print_js', 25 );
add_action( 'wp_footer', 'ffd_demo_store' );

/**
 * Order details.
 *
 * @see ffd_order_details_table()
 * @see ffd_order_again_button()
 */
add_action( 'ffd_view_order', 'ffd_order_details_table', 10 );
add_action( 'ffd_thankyou', 'ffd_order_details_table', 10 );
add_action( 'ffd_order_details_after_order_table', 'ffd_order_again_button' );

/**
 * Order downloads.
 *
 * @see ffd_order_downloads_table()
 */
add_action( 'ffd_available_downloads', 'ffd_order_downloads_table', 10 );

/**
 * Auth.
 *
 * @see ffd_output_auth_header()
 * @see ffd_output_auth_footer()
 */
add_action( 'ffd_auth_page_header', 'ffd_output_auth_header', 10 );
add_action( 'ffd_auth_page_footer', 'ffd_output_auth_footer', 10 );

/**
 * Comments.
 *
 * Disable Jetpack comments.
 */
add_filter( 'jetpack_comment_form_enabled_for_product', '__return_false' );

/**
 * My Account.
 */
add_action( 'ffd_account_navigation', 'ffd_account_navigation' );
add_action( 'ffd_account_content', 'ffd_account_content' );
add_action( 'ffd_account_orders_endpoint', 'ffd_account_orders' );
add_action( 'ffd_account_view-order_endpoint', 'ffd_account_view_order' );
add_action( 'ffd_account_downloads_endpoint', 'ffd_account_downloads' );
add_action( 'ffd_account_edit-address_endpoint', 'ffd_account_edit_address' );
add_action( 'ffd_account_payment-methods_endpoint', 'ffd_account_payment_methods' );
add_action( 'ffd_account_add-payment-method_endpoint', 'ffd_account_add_payment_method' );
add_action( 'ffd_account_edit-account_endpoint', 'ffd_account_edit_account' );
add_action( 'ffd_register_form', 'ffd_registration_privacy_policy_text', 20 );

/**
 * Notices.
 */
add_action( 'ffd_cart_is_empty', 'ffd_output_all_notices', 5 );
add_action( 'ffd_shortcode_before_listing_cat_loop', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_listings_loop', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_single_product', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_cart', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_checkout_form', 'ffd_output_all_notices', 10 );
add_action( 'ffd_account_content', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_customer_login_form', 'ffd_output_all_notices', 10 );
add_action( 'ffd_before_lost_password_form', 'ffd_output_all_notices', 10 );
add_action( 'before_ffd_pay', 'ffd_output_all_notices', 10 );
