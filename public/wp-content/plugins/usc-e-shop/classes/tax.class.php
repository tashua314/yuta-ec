<?php
/**
 * Welcart_Tax class.
 *
 * @package Welcart e-Commerce
 */

if( !defined( 'ABSPATH' ) ) {
	exit;
}

class Welcart_Tax {

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	public $subtotal_standard;
	public $subtotal_reduced;
	public $item_total_price;
	public $tax_standard;
	public $tax_reduced;
	public $tax;
	public $reduced_tax_rate_mark;
	public $cart_standard;
	public $cart_reduced;

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		$this->reduced_tax_rate_mark = apply_filters( 'usces_filter_reduced_tax_rate_mark', __( '(*)', 'usces' ) );
		$this->initialize_data();
	}

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Initialize
	 */
	public function initialize_data() {
		$this->subtotal_standard = 0;
		$this->subtotal_reduced = 0;
		$this->tax_standard = 0;
		$this->tax_reduced = 0;
		$this->item_total_price = 0;
		$this->tax = 0;
		$this->cart_standard = array();
		$this->cart_reduced = array();
	}

	public function get_sku_applicable_tax_rate( $post_id, $sku ) {
		global $usces;

		$skus = $usces->get_skus( $post_id, 'code' );
		if( isset( $skus[$sku]['taxrate'] ) ) {
			$applicable_tax_rate = $skus[$sku]['taxrate'];
		} else {
			$applicable_tax_rate = 'standard';
		}
		return $applicable_tax_rate;
	}

	public function set_ordercart_applicable_tax_rate( $ordercart_id, $sku ) {
		global $usces, $wpdb;

		if( isset( $sku['taxrate'] ) && 'reduced' == $sku['taxrate'] ) {
			$tkey = 'reduced';
			$tvalue = $usces->options['tax_rate_reduced'];
		} else {
			$tkey = 'standard';
			$tvalue = $usces->options['tax_rate'];
		}
		$query = $wpdb->prepare( "INSERT INTO {$wpdb->usces_ordercart_meta} 
			( cart_id, meta_type, meta_key, meta_value ) VALUES ( %d, 'tax', %s, %s )", 
			$ordercart_id, $tkey, $tvalue
		);
		$res = $wpdb->query( $query );
	}

	public function get_ordercart_applicable_tax_rate( $ordercart_id ) {
		$tax = usces_get_ordercart_meta( 'tax', $ordercart_id );
		if( $tax && isset( $tax[0]['meta_key'] ) ) {
			$applicable_tax_rate = $tax[0]['meta_key'];
		} else {
			$applicable_tax_rate = 'standard';
		}
		return $applicable_tax_rate;
	}

	public function is_tax_rates_mixed( $cart = array() ) {
		global $usces;

		if( ! $usces->is_reduced_tax_rate() ) {
			return false;
		}

		$res = false;
		$subtotal_standard = 0;
		$subtotal_reduced = 0;

		if( empty( $cart ) ) {
			$cart = $usces->cart->get_cart();
		}

		foreach( (array)$cart as $cart_row ) {
			$items_price = $cart_row['price'] * $cart_row['quantity'];
			if( isset( $cart_row['cart_id'] ) ) {//Order Cart data.
				$tax = usces_get_ordercart_meta( 'tax', $cart_row['cart_id'] );
				if( $tax && isset( $tax[0]['meta_key'] ) && 'reduced' == $tax[0]['meta_key'] ) {
					$subtotal_reduced += (float)$items_price;
				} else {
					$subtotal_standard += (float)$items_price;
				}
			} else {//Entry Cart data.
				$sku = urldecode( $cart_row['sku'] );
				$skus = $usces->get_skus( $cart_row['post_id'], 'code' );
				if( isset( $skus[$sku]['taxrate'] ) && 'reduced' == $skus[$sku]['taxrate'] ) {
					$subtotal_reduced += (float)$items_price;
				} else {
					$subtotal_standard += (float)$items_price;
				}
			}
		}

		if( 0 < $subtotal_standard || 0 < $subtotal_reduced ) {
			//if( 0 == $subtotal_standard || 0 == $subtotal_reduced ) {
			//} else {
			//	$res = true;
			//}
			if( 0 < $subtotal_reduced ) {
				$res = true;
			}
		}

		return $res;
	}

	public function is_includes_reduced_tax_rate( $cart = array() ) {
		global $usces;

		$res = false;

		if( empty( $cart ) ) {
			$cart = $usces->cart->get_cart();
		}

		foreach( (array)$cart as $cart_row ) {
			$items_price = $cart_row['price'] * $cart_row['quantity'];
			if( isset( $cart_row['cart_id'] ) ) {//Order Cart data.
				$tax = usces_get_ordercart_meta( 'tax', $cart_row['cart_id'] );
				if( $tax && isset( $tax[0]['meta_key'] ) && 'reduced' == $tax[0]['meta_key'] ) {
					$res = true;
					break;
				}
			} else {//Entry Cart data.
				$sku = urldecode( $cart_row['sku'] );
				$skus = $usces->get_skus( $cart_row['post_id'], 'code' );
				if( isset( $skus[$sku]['taxrate'] ) && 'reduced' == $skus[$sku]['taxrate'] ) {
					$res = true;
					break;
				}
			}
		}

		return $res;
	}

	/**
	 * From Entry Cart data.
	 */
	public function get_tax_entry_cart( $cart = array() ) {
		global $usces;

		$this->initialize_data();
		if( empty( $carts ) ) {
			$cart = $usces->cart->get_cart();
		}

		foreach( (array)$cart as $cart_row ) {
			$items_price = $cart_row['price'] * $cart_row['quantity'];
			$sku = urldecode( $cart_row['sku'] );
			$skus = $usces->get_skus( $cart_row['post_id'], 'code' );
			if( isset( $skus[$sku]['taxrate'] ) && 'reduced' == $skus[$sku]['taxrate'] ) {
				$this->subtotal_reduced += (float)$items_price;
				$this->cart_reduced[] = $cart_row;
			} else {
				$this->subtotal_standard += (float)$items_price;
				$this->cart_standard[] = $cart_row;
			}
		}
		$this->item_total_price = $this->subtotal_standard + $this->subtotal_reduced;
		$this->tax_rounding_off();
		$this->tax = $this->tax_standard + $this->tax_reduced;
	}

	/**
	 * From Order Cart data.
	 */
	public function get_tax_order_cart( $cart ) {

		$this->initialize_data();

		foreach( (array)$cart as $cart_row ) {
			$items_price = $cart_row['price'] * $cart_row['quantity'];
			$tax = usces_get_ordercart_meta( 'tax', $cart_row['cart_id'] );
			if( $tax && isset( $tax[0]['meta_key'] ) && 'reduced' == $tax[0]['meta_key'] ) {
				$this->subtotal_reduced += (float)$items_price;
				$this->cart_reduced[] = $cart_row;
			} else {
				$this->subtotal_standard += (float)$items_price;
				$this->cart_standard[] = $cart_row;
			}
		}
		$this->item_total_price = $this->subtotal_standard + $this->subtotal_reduced;
		$this->tax_rounding_off();
		$this->tax = $this->tax_standard + $this->tax_reduced;
	}

	private function tax_rounding_off() {
		global $usces, $usces_settings;

		if( 'include' == $usces->options['tax_mode'] ) {
			if( 0 < $this->subtotal_standard ) {
				$this->tax_standard = (float)sprintf( '%.3f', (float)$this->subtotal_standard * (float)$usces->options['tax_rate'] / ( 100 + (float)$usces->options['tax_rate'] ) );
			}
			if( 0 < $this->subtotal_reduced ) {
				$this->tax_reduced = (float)sprintf( '%.3f', (float)$this->subtotal_reduced * (float)$usces->options['tax_rate_reduced'] / ( 100 + (float)$usces->options['tax_rate_reduced'] ) );
			}
		} else {
			if( 0 < $this->subtotal_standard ) {
				$this->tax_standard = (float)sprintf( '%.3f', (float)$this->subtotal_standard * (float)$usces->options['tax_rate'] / 100 );
			}
			if( 0 < $this->subtotal_reduced ) {
				$this->tax_reduced = (float)sprintf( '%.3f', (float)$this->subtotal_reduced * (float)$usces->options['tax_rate_reduced'] / 100 );
			}
		}

		$cr = $usces->options['system']['currency'];
		$decimal = (int)$usces_settings['currency'][$cr][1];
		$decipad = (int)str_pad( '1', $decimal + 1, '0', STR_PAD_RIGHT );
		switch( $usces->options['tax_method'] ) {
		case 'cutting':
			if( 0 < $this->tax_standard ) {
				$this->tax_standard = floor( (float)$this->tax_standard * $decipad ) / $decipad;
			}
			if( 0 < $this->tax_reduced ) {
				$this->tax_reduced = floor( (float)$this->tax_reduced * $decipad ) / $decipad;
			}
			break;
		case 'bring':
			if( 0 < $this->tax_standard ) {
				$this->tax_standard = ceil( (float)$this->tax_standard * $decipad ) / $decipad;
			}
			if( 0 < $this->tax_reduced ) {
				$this->tax_reduced = ceil( (float)$this->tax_reduced * $decipad ) / $decipad;
			}
			break;
		case 'rounding':
			if( 0 < $decimal ) {
				if( 0 < $this->tax_standard ) {
					$this->tax_standard = round( (float)$this->tax_standard, $decimal );
				}
				if( 0 < $this->tax_reduced ) {
					$this->tax_reduced = round( (float)$this->tax_reduced, $decimal );
				}
			} else {
				if( 0 < $this->tax_standard ) {
					$this->tax_standard = round( (float)$this->tax_standard );
				}
				if( 0 < $this->tax_reduced ) {
					$this->tax_reduced = round( (float)$this->tax_reduced );
				}
			}
			break;
		}
	}
}

new Welcart_Tax();
