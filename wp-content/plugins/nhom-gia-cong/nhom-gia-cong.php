<?php
/**
 * Plugin Name: Nhôm Gia Công
 * Description: Bán nhôm định hình cắt theo chiều dài (mm), khoan lỗ theo vị trí, taro ren hai đầu — tự tính giá, lưu thông số gia công vào đơn hàng. Kèm checkout kiểu Việt Nam và nút gọi/Zalo.
 * Version:     1.0.0
 * Author:      Nhôm Định Hình
 * Text Domain: nhom-gia-cong
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'NDH_VERSION', '1.0.0' );

final class NDH_Gia_Cong {

	/** Khoảng cách tối thiểu từ tâm lỗ khoan tới đầu thanh (mm). */
	const EDGE_MIN = 5;
	const MAX_HOLES = 30;
	const FACES = array( 1 => 'Mặt 1', 2 => 'Mặt 2', 3 => 'Mặt 3', 4 => 'Mặt 4' );

	/** Thông số kỹ thuật hiển thị (meta `_ndh_{key}`). */
	const SPECS = array(
		'series'      => 'Hệ',
		'slot'        => 'Rãnh',
		'center_hole' => 'Lỗ tâm',
		'weight'      => 'Trọng lượng (kg/m)',
		'finish'      => 'Bề mặt',
	);

	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		// Admin.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'admin_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'admin_save' ) );

		// Trang sản phẩm.
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_configurator' ) );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'price_html' ), 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'loop_button' ), 10, 2 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'product_tab' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'summary_specs' ), 25 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		// Giỏ hàng & đơn hàng.
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_prices' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_item_meta' ), 10, 3 );

		// Checkout Việt Nam.
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'address_fields' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ), 20 );

		// Liên hệ nhanh.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_footer', array( __CLASS__, 'floating_contact' ) );
	}

	/* =================================================================
	 * Cấu hình sản phẩm
	 * ================================================================= */

	public static function is_cut( $product ) {
		return $product && 'yes' === $product->get_meta( '_ndh_cut' );
	}

	/** Đọc cấu hình gia công của sản phẩm. */
	public static function config( $product ) {
		$num  = static function ( $key, $default ) use ( $product ) {
			$v = $product->get_meta( "_ndh_{$key}" );
			return '' === $v ? $default : (float) $v;
		};
		$list = static function ( $key ) use ( $product ) {
			return array_values( array_filter( array_map( 'trim', explode( ',', (string) $product->get_meta( "_ndh_{$key}" ) ) ), 'strlen' ) );
		};
		return array(
			'price_m'     => $num( 'price_m', 0 ),
			'cut_fee'     => $num( 'cut_fee', 0 ),
			'min_len'     => (int) $num( 'min_len', 20 ),
			'max_len'     => (int) $num( 'max_len', 6000 ),
			'drill_fee'   => $num( 'drill_fee', 0 ),
			'drill_sizes' => $list( 'drill_sizes' ),
			'tap_fee'     => $num( 'tap_fee', 0 ),
			'tap_sizes'   => $list( 'tap_sizes' ),
			'edge_min'    => self::EDGE_MIN,
			'max_holes'   => self::MAX_HOLES,
		);
	}

	/**
	 * Tính giá 1 thanh theo thông số. Làm tròn lên bội số 100đ.
	 * Công thức phải khớp với hàm calc() trong configurator.js.
	 */
	public static function calc( array $cfg, array $spec ) {
		$parts = array(
			'material' => $cfg['price_m'] * $spec['len'] / 1000,
			'cut'      => $cfg['cut_fee'],
			'drill'    => count( $spec['holes'] ) * $cfg['drill_fee'],
			'tap'      => ( ( '' !== $spec['tap_a'] ? 1 : 0 ) + ( '' !== $spec['tap_b'] ? 1 : 0 ) ) * $cfg['tap_fee'],
		);
		return (float) ( ceil( array_sum( $parts ) / 100 ) * 100 );
	}

	public static function admin_fields() {
		global $product_object;
		echo '<div class="options_group show_if_simple">';
		woocommerce_wp_checkbox( array(
			'id'          => '_ndh_cut',
			'label'       => 'Cắt theo chiều dài',
			'description' => 'Khách nhập chiều dài (mm), chọn khoan/taro; giá tự tính. Giá thường ở trên sẽ tự đặt bằng đơn giá/mét.',
		) );
		$fields = array(
			'_ndh_price_m'     => array( 'Đơn giá (₫/mét)', '85000' ),
			'_ndh_cut_fee'     => array( 'Phí cắt (₫/thanh)', '5000' ),
			'_ndh_min_len'     => array( 'Chiều dài tối thiểu (mm)', '20' ),
			'_ndh_max_len'     => array( 'Chiều dài tối đa (mm)', '6000' ),
			'_ndh_drill_fee'   => array( 'Phí khoan (₫/lỗ)', '3000' ),
			'_ndh_drill_sizes' => array( 'Đường kính khoan (mm, cách nhau dấu phẩy)', '5, 7' ),
			'_ndh_tap_fee'     => array( 'Phí taro (₫/đầu)', '5000' ),
			'_ndh_tap_sizes'   => array( 'Cỡ ren taro (cách nhau dấu phẩy, để trống nếu không taro được)', 'M5' ),
		);
		foreach ( $fields as $id => $f ) {
			woocommerce_wp_text_input( array( 'id' => $id, 'label' => $f[0], 'placeholder' => $f[1] ) );
		}
		echo '</div><div class="options_group"><p class="form-field"><strong>Thông số kỹ thuật</strong></p>';
		foreach ( self::SPECS as $key => $label ) {
			woocommerce_wp_text_input( array( 'id' => "_ndh_{$key}", 'label' => $label ) );
		}
		echo '</div>';
	}

	public static function admin_save( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce đã kiểm tra nonce khi lưu sản phẩm.
		$product->update_meta_data( '_ndh_cut', isset( $_POST['_ndh_cut'] ) ? 'yes' : 'no' );
		$keys = array_merge(
			array( 'price_m', 'cut_fee', 'min_len', 'max_len', 'drill_fee', 'drill_sizes', 'tap_fee', 'tap_sizes' ),
			array_keys( self::SPECS )
		);
		foreach ( $keys as $key ) {
			if ( isset( $_POST[ "_ndh_{$key}" ] ) ) {
				$product->update_meta_data( "_ndh_{$key}", sanitize_text_field( wp_unslash( $_POST[ "_ndh_{$key}" ] ) ) );
			}
		}
		// phpcs:enable
		if ( self::is_cut( $product ) && (float) $product->get_meta( '_ndh_price_m' ) > 0 ) {
			$product->set_regular_price( (string) (float) $product->get_meta( '_ndh_price_m' ) );
			$product->set_sale_price( '' );
		}
	}

	/* =================================================================
	 * Giao diện trang sản phẩm
	 * ================================================================= */

	public static function assets() {
		wp_enqueue_style( 'nhom-gia-cong', plugins_url( 'assets.css', __FILE__ ), array(), NDH_VERSION );
		if ( is_product() ) {
			wp_enqueue_script( 'nhom-gia-cong', plugins_url( 'configurator.js', __FILE__ ), array(), NDH_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}

	public static function price_html( $html, $product ) {
		if ( self::is_cut( $product ) ) {
			$cfg = self::config( $product );
			return wc_price( $cfg['price_m'] ) . '<span class="ndh-unit">/mét</span>';
		}
		return $html;
	}

	public static function loop_button( $html, $product ) {
		if ( self::is_cut( $product ) ) {
			return sprintf( '<a href="%s" class="button ndh-loop-btn">Chọn chiều dài</a>', esc_url( $product->get_permalink() ) );
		}
		return $html;
	}

	public static function render_configurator() {
		global $product;
		if ( ! self::is_cut( $product ) ) {
			return;
		}
		$cfg = self::config( $product );
		// Giữ lại giá trị khách đã nhập nếu thêm vào giỏ bị lỗi.
		$old = self::read_request();
		?>
		<div class="ndh-config" data-config="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
			<div class="ndh-field">
				<label for="ndh_len">Chiều dài cắt (mm) <abbr class="required" title="bắt buộc">*</abbr></label>
				<div class="ndh-input-unit">
					<input type="number" id="ndh_len" name="ndh_len" inputmode="decimal" step="0.5"
						min="<?php echo esc_attr( $cfg['min_len'] ); ?>" max="<?php echo esc_attr( $cfg['max_len'] ); ?>"
						value="<?php echo esc_attr( $old['len'] ? $old['len'] : '' ); ?>" placeholder="VD: 1250" required>
					<span>mm</span>
				</div>
				<small>Từ <?php echo esc_html( number_format_i18n( $cfg['min_len'] ) ); ?> đến <?php echo esc_html( number_format_i18n( $cfg['max_len'] ) ); ?> mm. Dung sai cắt ±0,5 mm.</small>
			</div>

			<div class="ndh-bar" aria-hidden="true"><svg class="ndh-bar__svg" viewBox="0 0 600 90" preserveAspectRatio="xMidYMid meet"></svg></div>

			<?php if ( $cfg['drill_sizes'] ) : ?>
				<fieldset class="ndh-field ndh-holes">
					<legend>Khoan lỗ <span class="ndh-fee">+<?php echo wp_kses_post( wc_price( $cfg['drill_fee'] ) ); ?>/lỗ</span></legend>
					<small>Vị trí tính từ <strong>đầu A</strong> tới tâm lỗ. Mặt 1–4 theo chiều kim đồng hồ khi nhìn từ đầu A.</small>
					<div class="ndh-holes__list">
						<?php foreach ( $old['holes'] as $i => $h ) : ?>
							<?php self::hole_row( $cfg, $i, $h ); ?>
						<?php endforeach; ?>
					</div>
					<template class="ndh-hole-tpl"><?php self::hole_row( $cfg, '__i__', array( 'pos' => '', 'dia' => $cfg['drill_sizes'][0], 'face' => 1 ) ); ?></template>
					<div class="ndh-holes__actions">
						<button type="button" class="ndh-add-hole">+ Thêm lỗ</button>
						<button type="button" class="ndh-add-even" title="Chia đều các lỗ trên chiều dài thanh">Chia đều…</button>
					</div>
				</fieldset>
			<?php endif; ?>

			<?php if ( $cfg['tap_sizes'] ) : ?>
				<fieldset class="ndh-field ndh-taps">
					<legend>Taro ren lỗ tâm <span class="ndh-fee">+<?php echo wp_kses_post( wc_price( $cfg['tap_fee'] ) ); ?>/đầu</span></legend>
					<div class="ndh-taps__grid">
						<?php foreach ( array( 'a' => 'Đầu A', 'b' => 'Đầu B' ) as $end => $label ) : ?>
							<label><?php echo esc_html( $label ); ?>
								<select name="ndh_tap_<?php echo esc_attr( $end ); ?>" class="ndh-tap">
									<option value="">Không taro</option>
									<?php foreach ( $cfg['tap_sizes'] as $size ) : ?>
										<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $old[ "tap_{$end}" ], $size ); ?>><?php echo esc_html( $size ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endif; ?>

			<div class="ndh-field">
				<label for="ndh_note">Ghi chú gia công</label>
				<textarea id="ndh_note" name="ndh_note" rows="2" maxlength="300" placeholder="VD: lỗ khoan xuyên 2 mặt, vát mép đầu thanh…"><?php echo esc_textarea( $old['note'] ); ?></textarea>
			</div>

			<div class="ndh-summary" aria-live="polite">
				<div class="ndh-summary__rows"></div>
				<div class="ndh-summary__total"><span>Giá 1 thanh</span><strong class="ndh-total">—</strong></div>
				<div class="ndh-grand"></div>
			</div>
		</div>
		<?php
	}

	private static function hole_row( $cfg, $i, $h ) {
		?>
		<div class="ndh-hole">
			<label><span>Vị trí (mm)</span>
				<input type="number" step="0.5" min="0" name="ndh_holes[<?php echo esc_attr( $i ); ?>][pos]" value="<?php echo esc_attr( $h['pos'] ); ?>" class="ndh-hole__pos" required>
			</label>
			<label><span>Đường kính</span>
				<select name="ndh_holes[<?php echo esc_attr( $i ); ?>][dia]">
					<?php foreach ( $cfg['drill_sizes'] as $d ) : ?>
						<option value="<?php echo esc_attr( $d ); ?>" <?php selected( (string) $h['dia'], (string) $d ); ?>>Ø<?php echo esc_html( $d ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><span>Mặt</span>
				<select name="ndh_holes[<?php echo esc_attr( $i ); ?>][face]" class="ndh-hole__face">
					<?php foreach ( self::FACES as $f => $fl ) : ?>
						<option value="<?php echo esc_attr( $f ); ?>" <?php selected( (int) $h['face'], $f ); ?>><?php echo esc_html( $fl ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="button" class="ndh-hole__remove" aria-label="Xoá lỗ">×</button>
		</div>
		<?php
	}

	/* =================================================================
	 * Giỏ hàng
	 * ================================================================= */

	/** Đọc dữ liệu khách gửi lên từ form sản phẩm (chưa kiểm tra hợp lệ). */
	private static function read_request() {
		// phpcs:disable WordPress.Security.NonceVerification -- form thêm vào giỏ của WooCommerce không dùng nonce.
		$holes = array();
		if ( isset( $_POST['ndh_holes'] ) && is_array( $_POST['ndh_holes'] ) ) {
			foreach ( wp_unslash( $_POST['ndh_holes'] ) as $h ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- làm sạch từng trường bên dưới.
				if ( ! is_array( $h ) || ! isset( $h['pos'] ) || '' === trim( (string) $h['pos'] ) ) {
					continue;
				}
				$holes[] = array(
					'pos'  => round( (float) str_replace( ',', '.', (string) $h['pos'] ), 1 ),
					'dia'  => isset( $h['dia'] ) ? sanitize_text_field( (string) $h['dia'] ) : '',
					'face' => isset( $h['face'] ) ? (int) $h['face'] : 1,
				);
			}
		}
		$field = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		};
		$spec = array(
			'len'   => round( (float) str_replace( ',', '.', $field( 'ndh_len' ) ), 1 ),
			'holes' => $holes,
			'tap_a' => $field( 'ndh_tap_a' ),
			'tap_b' => $field( 'ndh_tap_b' ),
			'note'  => isset( $_POST['ndh_note'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['ndh_note'] ) ), 0, 300 ) : '',
		);
		// phpcs:enable
		usort( $spec['holes'], static function ( $a, $b ) {
			return $a['pos'] <=> $b['pos'];
		} );
		return $spec;
	}

	/** Trả về danh sách lỗi (rỗng nếu hợp lệ). */
	public static function errors( array $cfg, array $spec ) {
		$err = array();
		if ( $spec['len'] < $cfg['min_len'] || $spec['len'] > $cfg['max_len'] ) {
			$err[] = sprintf( 'Chiều dài phải từ %s đến %s mm.', number_format_i18n( $cfg['min_len'] ), number_format_i18n( $cfg['max_len'] ) );
		}
		if ( count( $spec['holes'] ) > $cfg['max_holes'] ) {
			$err[] = sprintf( 'Tối đa %d lỗ khoan mỗi thanh.', $cfg['max_holes'] );
		}
		if ( $spec['holes'] && ! $cfg['drill_sizes'] ) {
			$err[] = 'Sản phẩm này không hỗ trợ khoan lỗ.';
		}
		foreach ( $spec['holes'] as $n => $h ) {
			if ( $h['pos'] < $cfg['edge_min'] || $h['pos'] > $spec['len'] - $cfg['edge_min'] ) {
				$err[] = sprintf( 'Lỗ thứ %d: vị trí %s mm phải cách hai đầu thanh ít nhất %d mm.', $n + 1, $h['pos'], $cfg['edge_min'] );
			}
			if ( ! in_array( $h['dia'], $cfg['drill_sizes'], true ) ) {
				$err[] = sprintf( 'Lỗ thứ %d: đường kính không hợp lệ.', $n + 1 );
			}
			if ( ! isset( self::FACES[ $h['face'] ] ) ) {
				$err[] = sprintf( 'Lỗ thứ %d: mặt khoan không hợp lệ.', $n + 1 );
			}
		}
		foreach ( array( 'tap_a' => 'A', 'tap_b' => 'B' ) as $k => $end ) {
			if ( '' !== $spec[ $k ] && ! in_array( $spec[ $k ], $cfg['tap_sizes'], true ) ) {
				$err[] = "Cỡ ren taro đầu {$end} không hợp lệ.";
			}
		}
		return $err;
	}

	public static function validate( $passed, $product_id, $qty ) {
		$product = wc_get_product( $product_id );
		if ( ! self::is_cut( $product ) ) {
			return $passed;
		}
		$errors = self::errors( self::config( $product ), self::read_request() );
		foreach ( $errors as $e ) {
			wc_add_notice( $e, 'error' );
		}
		return $passed && ! $errors;
	}

	public static function cart_item_data( $data, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( self::is_cut( $product ) ) {
			// Mỗi thông số khác nhau là một dòng riêng trong giỏ; cùng thông số thì cộng dồn số lượng.
			$data['ndh'] = self::read_request();
		}
		return $data;
	}

	public static function apply_prices( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		foreach ( $cart->get_cart() as $item ) {
			if ( isset( $item['ndh'] ) && self::is_cut( $item['data'] ) ) {
				$item['data']->set_price( self::calc( self::config( $item['data'] ), $item['ndh'] ) );
			}
		}
	}

	/** Mô tả thông số gia công thành các cặp nhãn => giá trị dễ đọc. */
	public static function describe( array $spec ) {
		$fmt = static function ( $n ) {
			return rtrim( rtrim( number_format( (float) $n, 1, ',', '.' ), '0' ), ',' );
		};
		$out = array( 'Chiều dài' => $fmt( $spec['len'] ) . ' mm' );
		if ( $spec['holes'] ) {
			$holes = array();
			foreach ( $spec['holes'] as $h ) {
				$holes[] = sprintf( '%s mm (Ø%s, mặt %d)', $fmt( $h['pos'] ), $h['dia'], $h['face'] );
			}
			$out['Khoan lỗ'] = count( $holes ) . ' lỗ: ' . implode( '; ', $holes );
		}
		if ( '' !== $spec['tap_a'] ) {
			$out['Taro đầu A'] = $spec['tap_a'];
		}
		if ( '' !== $spec['tap_b'] ) {
			$out['Taro đầu B'] = $spec['tap_b'];
		}
		if ( '' !== $spec['note'] ) {
			$out['Ghi chú'] = $spec['note'];
		}
		return $out;
	}

	public static function display_item_data( $data, $item ) {
		if ( isset( $item['ndh'] ) ) {
			foreach ( self::describe( $item['ndh'] ) as $k => $v ) {
				$data[] = array( 'key' => $k, 'value' => esc_html( $v ) );
			}
		}
		return $data;
	}

	public static function order_item_meta( $order_item, $key, $values ) {
		if ( isset( $values['ndh'] ) ) {
			foreach ( self::describe( $values['ndh'] ) as $k => $v ) {
				$order_item->add_meta_data( $k, $v );
			}
			// Bản máy đọc được, để xuất phiếu gia công / tích hợp sau này.
			$order_item->add_meta_data( '_ndh_spec', $values['ndh'] );
		}
	}

	/* =================================================================
	 * Thông số kỹ thuật
	 * ================================================================= */

	public static function get_specs( $product ) {
		$specs = array();
		foreach ( self::SPECS as $key => $label ) {
			$v = $product->get_meta( "_ndh_{$key}" );
			if ( '' !== $v ) {
				$specs[ $label ] = $v;
			}
		}
		return $specs;
	}

	public static function product_tab( $tabs ) {
		global $product;
		if ( $product && self::get_specs( $product ) ) {
			$tabs['ndh_specs'] = array(
				'title'    => 'Thông số kỹ thuật',
				'priority' => 15,
				'callback' => static function () {
					global $product;
					echo '<table class="woocommerce-product-attributes shop_attributes"><tbody>';
					foreach ( self::get_specs( $product ) as $l => $v ) {
						printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $l ), esc_html( $v ) );
					}
					echo '</tbody></table>';
				},
			);
		}
		return $tabs;
	}

	public static function summary_specs() {
		global $product;
		$specs = self::get_specs( $product );
		if ( ! $specs ) {
			return;
		}
		echo '<ul class="ndh-chips">';
		foreach ( $specs as $l => $v ) {
			printf( '<li><span>%s</span> %s</li>', esc_html( $l ), esc_html( $v ) );
		}
		echo '</ul>';
	}

	/* =================================================================
	 * Checkout Việt Nam
	 * ================================================================= */

	public static function address_fields( $fields ) {
		unset( $fields['postcode'], $fields['address_2'], $fields['last_name'] );
		$fields['first_name']['label'] = 'Họ và tên';
		$fields['first_name']['class'] = array( 'form-row-wide' );
		$fields['company']['label']    = 'Công ty / xưởng (nếu cần xuất hoá đơn)';
		$fields['company']['required'] = false;
		$fields['address_1']['label']       = 'Địa chỉ';
		$fields['address_1']['placeholder'] = 'Số nhà, tên đường, phường/xã, quận/huyện';
		$fields['city']['label']            = 'Tỉnh / Thành phố';
		if ( isset( $fields['state'] ) ) {
			$fields['state']['required'] = false;
		}
		return $fields;
	}

	public static function checkout_fields( $fields ) {
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required'] = true;
			$fields['billing']['billing_phone']['class']    = array( 'form-row-wide' );
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['required'] = false;
			$fields['billing']['billing_email']['class']    = array( 'form-row-wide' );
		}
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['placeholder'] = 'VD: giao giờ hành chính, cần xuất hoá đơn VAT (ghi mã số thuế)…';
		}
		return $fields;
	}

	/* =================================================================
	 * Hotline / Zalo
	 * ================================================================= */

	public static function register_settings() {
		foreach ( array( 'ndh_phone' => 'Hotline', 'ndh_zalo' => 'Số Zalo' ) as $opt => $label ) {
			register_setting( 'general', $opt, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_phone' ) ) );
			add_settings_field( $opt, $label . ' (cửa hàng)', static function () use ( $opt ) {
				printf( '<input type="text" class="regular-text" name="%1$s" value="%2$s" />', esc_attr( $opt ), esc_attr( get_option( $opt ) ) );
			}, 'general' );
		}
	}

	public static function sanitize_phone( $v ) {
		return preg_replace( '/[^0-9+]/', '', (string) $v );
	}

	public static function floating_contact() {
		$phone = get_option( 'ndh_phone' );
		$zalo  = get_option( 'ndh_zalo' );
		if ( ! $phone && ! $zalo ) {
			return;
		}
		echo '<div class="ndh-float" aria-label="Liên hệ nhanh">';
		if ( $zalo ) {
			printf( '<a class="ndh-float__zalo" href="%s" target="_blank" rel="noopener">Zalo</a>', esc_url( 'https://zalo.me/' . $zalo ) );
		}
		if ( $phone ) {
			printf( '<a class="ndh-float__call" href="%s">Gọi %s</a>', esc_url( 'tel:' . $phone ), esc_html( $phone ) );
		}
		echo '</div>';
	}
}

add_action( 'plugins_loaded', array( 'NDH_Gia_Cong', 'init' ) );
