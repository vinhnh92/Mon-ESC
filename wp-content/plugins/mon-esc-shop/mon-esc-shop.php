<?php
/**
 * Plugin Name: Mon ESC Shop
 * Description: Tuỳ biến WooCommerce cho shop linh kiện FPV: thông số ESC/firmware, checkout kiểu Việt Nam, nút gọi/Zalo nổi.
 * Version:     1.0.0
 * Author:      Mon ESC
 * Text Domain: mon-esc-shop
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

final class Mon_ESC_Shop {

	/** Các trường thông số kỹ thuật lưu trong post meta `_mon_esc_{key}`. */
	const SPECS = array(
		'firmware' => 'Firmware',
		'current'  => 'Dòng liên tục',
		'cells'    => 'Điện áp (cell)',
		'mcu'      => 'MCU',
		'size'     => 'Kích thước / lỗ bắt',
	);

	const FIRMWARES = array( '', 'BLHeli_S', 'Bluejay', 'BLHeli_32', 'AM32', 'Khác' );

	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		// Thông số sản phẩm.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'admin_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_fields' ) );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'product_tab' ) );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'loop_badge' ), 6 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'summary_specs' ), 25 );

		// Lọc theo firmware: /cua-hang/?firmware=Bluejay
		add_action( 'woocommerce_product_query', array( __CLASS__, 'filter_by_firmware' ) );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'firmware_filter_ui' ), 25 );

		// Checkout Việt Nam.
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'address_fields' ), 20 );
		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'billing_fields' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ), 20 );

		// Hotline / Zalo.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_footer', array( __CLASS__, 'floating_contact' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'styles' ) );
	}

	/* ------------------------------------------------------------ Thông số */

	public static function admin_fields() {
		echo '<div class="options_group"><p class="form-field"><strong>Thông số kỹ thuật (Mon ESC)</strong></p>';
		woocommerce_wp_select( array(
			'id'      => '_mon_esc_firmware',
			'label'   => self::SPECS['firmware'],
			'options' => array_combine( self::FIRMWARES, array_map( static function ( $f ) {
				return '' === $f ? '— Không áp dụng —' : $f;
			}, self::FIRMWARES ) ),
		) );
		foreach ( array( 'current', 'cells', 'mcu', 'size' ) as $key ) {
			woocommerce_wp_text_input( array(
				'id'    => "_mon_esc_{$key}",
				'label' => self::SPECS[ $key ],
			) );
		}
		echo '</div>';
	}

	public static function save_fields( $product ) {
		foreach ( array_keys( self::SPECS ) as $key ) {
			$field = "_mon_esc_{$key}";
			if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WC đã kiểm tra nonce.
				$product->update_meta_data( $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
	}

	public static function get_specs( $product ) {
		$specs = array();
		foreach ( self::SPECS as $key => $label ) {
			$value = $product->get_meta( "_mon_esc_{$key}" );
			if ( '' !== $value ) {
				$specs[ $label ] = $value;
			}
		}
		return $specs;
	}

	public static function product_tab( $tabs ) {
		global $product;
		if ( $product && self::get_specs( $product ) ) {
			$tabs['mon_esc_specs'] = array(
				'title'    => 'Thông số kỹ thuật',
				'priority' => 15,
				'callback' => array( __CLASS__, 'render_specs_table' ),
			);
		}
		return $tabs;
	}

	public static function render_specs_table() {
		global $product;
		echo '<table class="woocommerce-product-attributes shop_attributes mon-esc-specs"><tbody>';
		foreach ( self::get_specs( $product ) as $label => $value ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
		}
		echo '</tbody></table>';
	}

	public static function summary_specs() {
		global $product;
		$specs = self::get_specs( $product );
		if ( ! $specs ) {
			return;
		}
		echo '<ul class="mon-esc-chips">';
		foreach ( $specs as $label => $value ) {
			printf( '<li><span>%s</span> %s</li>', esc_html( $label ), esc_html( $value ) );
		}
		echo '</ul>';
	}

	public static function loop_badge() {
		global $product;
		$fw = $product ? $product->get_meta( '_mon_esc_firmware' ) : '';
		if ( $fw ) {
			printf( '<span class="mon-esc-fw mon-esc-fw--%s">%s</span>', esc_attr( sanitize_title( $fw ) ), esc_html( $fw ) );
		}
	}

	public static function filter_by_firmware( $q ) {
		$fw = isset( $_GET['firmware'] ) ? sanitize_text_field( wp_unslash( $_GET['firmware'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $fw && in_array( $fw, self::FIRMWARES, true ) ) {
			$meta   = (array) $q->get( 'meta_query' );
			$meta[] = array( 'key' => '_mon_esc_firmware', 'value' => $fw );
			$q->set( 'meta_query', $meta );
		}
	}

	public static function firmware_filter_ui() {
		$current = isset( $_GET['firmware'] ) ? sanitize_text_field( wp_unslash( $_GET['firmware'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<nav class="mon-esc-fw-filter" aria-label="Lọc theo firmware"><span>Firmware:</span>';
		foreach ( self::FIRMWARES as $fw ) {
			if ( 'Khác' === $fw ) {
				continue;
			}
			$url = $fw ? add_query_arg( 'firmware', rawurlencode( $fw ) ) : remove_query_arg( 'firmware' );
			$url = remove_query_arg( 'paged', $url );
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $url ),
				$fw === $current ? 'is-active' : '',
				esc_html( $fw ? $fw : 'Tất cả' )
			);
		}
		echo '</nav>';
	}

	/* ------------------------------------------------------------ Checkout */

	public static function address_fields( $fields ) {
		unset( $fields['company'], $fields['postcode'], $fields['address_2'] );
		$fields['address_1']['label']       = 'Địa chỉ';
		$fields['address_1']['placeholder'] = 'Số nhà, tên đường, phường/xã, quận/huyện';
		$fields['city']['label']            = 'Tỉnh / Thành phố';
		$fields['city']['priority']         = 70;
		if ( isset( $fields['state'] ) ) {
			$fields['state']['required'] = false;
		}
		// Gộp họ tên thành một ô cho người Việt.
		$fields['first_name']['label'] = 'Họ và tên';
		$fields['first_name']['class'] = array( 'form-row-wide' );
		unset( $fields['last_name'] );
		return $fields;
	}

	public static function billing_fields( $fields ) {
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone']['required'] = true;
			$fields['billing_phone']['priority'] = 25;
			$fields['billing_phone']['class']    = array( 'form-row-wide' );
		}
		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email']['required'] = false;
			$fields['billing_email']['priority'] = 26;
			$fields['billing_email']['class']    = array( 'form-row-wide' );
		}
		return $fields;
	}

	public static function checkout_fields( $fields ) {
		// Áp dụng lần cuối ở đây vì WooCommerce có thể ghi đè trường phone/email theo cài đặt riêng.
		if ( isset( $fields['billing'] ) ) {
			$fields['billing'] = self::billing_fields( $fields['billing'] );
		}
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['placeholder'] = 'Ví dụ: cần flash Bluejay 48kHz, giao giờ hành chính…';
		}
		return $fields;
	}

	/* ------------------------------------------------------------ Liên hệ nhanh */

	public static function register_settings() {
		foreach ( array( 'mon_esc_phone' => 'Hotline', 'mon_esc_zalo' => 'Số Zalo' ) as $opt => $label ) {
			register_setting( 'general', $opt, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_phone' ) ) );
			add_settings_field( $opt, $label . ' (Mon ESC)', static function () use ( $opt ) {
				printf( '<input type="text" class="regular-text" name="%1$s" value="%2$s" />', esc_attr( $opt ), esc_attr( get_option( $opt ) ) );
			}, 'general' );
		}
	}

	public static function sanitize_phone( $v ) {
		return preg_replace( '/[^0-9+]/', '', (string) $v );
	}

	public static function floating_contact() {
		$phone = get_option( 'mon_esc_phone' );
		$zalo  = get_option( 'mon_esc_zalo' );
		if ( ! $phone && ! $zalo ) {
			return;
		}
		echo '<div class="mon-esc-float" aria-label="Liên hệ nhanh">';
		if ( $zalo ) {
			printf( '<a class="mon-esc-float__zalo" href="%s" target="_blank" rel="noopener">Zalo</a>', esc_url( 'https://zalo.me/' . $zalo ) );
		}
		if ( $phone ) {
			printf( '<a class="mon-esc-float__call" href="%s">Gọi %s</a>', esc_url( 'tel:' . $phone ), esc_html( $phone ) );
		}
		echo '</div>';
	}

	public static function styles() {
		wp_enqueue_style( 'mon-esc-shop', plugins_url( 'mon-esc-shop.css', __FILE__ ), array(), '1.0.0' );
	}
}

add_action( 'plugins_loaded', array( 'Mon_ESC_Shop', 'init' ) );
