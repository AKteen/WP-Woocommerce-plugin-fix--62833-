<?php
/**
 * Plugin Name: WooCommerce REST Variable Restore Fix
 * Description: Restores trashed variations when a variable product is restored via REST API.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore trashed variations when a variable product
 * is restored via REST API update.
 */
add_action(
	'woocommerce_rest_insert_product_object',
	function ( $product, $request, $creating ) {

		// Safety: must be WC_Product
		if ( ! $product instanceof WC_Product ) {
			error_log( '[WC REST FIX] Invalid product object.' );
			return;
		}

		// Ignore product creation
		if ( $creating ) {
			return;
		}

		// Only variable products
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		// Status must be explicitly part of request
		if ( ! isset( $request['status'] ) ) {
			return;
		}

		$parent_id     = $product->get_id();
		$parent_status = $product->get_status();

		// We only act on restore (not trashing)
		if ( 'trash' === $parent_status ) {
			return;
		}

		// Fetch children DIRECTLY from DB (important)
		$children = get_posts(
			array(
				'post_type'      => 'product_variation',
				'post_parent'    => $parent_id,
				'post_status'    => 'trash',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		// No children? Nothing to do (this is OK)
		if ( empty( $children ) ) {
			error_log(
				sprintf(
					'[WC REST FIX] No trashed variations found for parent %d.',
					$parent_id
				)
			);
			return;
		}

		foreach ( $children as $variation_id ) {
			$result = wp_untrash_post( $variation_id );

			if ( false === $result ) {
				error_log(
					sprintf(
						'[WC REST FIX] Failed to restore variation %d (parent %d).',
						$variation_id,
						$parent_id
					)
				);
			} else {
				error_log(
					sprintf(
						'[WC REST FIX] Restored variation %d for parent %d.',
						$variation_id,
						$parent_id
					)
				);
			}
		}
	},
	10,
	3
);
