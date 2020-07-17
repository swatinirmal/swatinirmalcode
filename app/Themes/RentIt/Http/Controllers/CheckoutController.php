<?php

namespace Corp\Themes\RentIt\Http\Controllers;


use Cache;
use Corp\Coupon;
use Corp\Option;
use Corp\Plugins\eCommerce\Gateways\PaymentGateways;
use Corp\Plugins\eCommerce\Models\Product;
use Corp\Plugins\eCommerce\Repositories\OrderRepository;
use Corp\Themes\RentIt\RentItTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use DB;

class CheckoutController extends RentItTheme {


	/**
	 * Display a listing of the checkout page
	 * @return \Illuminate\Http\Response
	 */

	public function index( $cat_alias = FALSE ) {
	    
		$this->title = __( 'Checkout page' );

		$ecommerce_cart = Session::get( 'ecommerce_cart' );
		Session::forget('vat');
		$optionss = DB::table('option_translations')->where('option_id',15)->first();
        $currency = unserialize($optionss->translation_value);
        $currencybydefault = $currency['vat'];
        $totle = ($ecommerce_cart['total_price'] * $currencybydefault)/100;
		Session::put('vat', $totle);
		$vat_charge = Session::get( 'vat' );
        // print_r($ecommerce_cart);exit();
		$product = Product::where( 'id', $ecommerce_cart['product_id'] )->first();
		$PaymentGateways = PaymentGateways::instance();
		//dump($PaymentGateways);
		$gateway = null;
        // print_r($ecommerce_cart);exit();
		foreach ( $PaymentGateways->payment_gateways() as $item ) {
			if ( $item->id == $ecommerce_cart['payment'] ) {
				$gateway = $item;
			}

		}
		$content = $this->getTemplate( 'checkout.checkout',
			compact( 'ecommerce_cart', 'product', 'gateway', 'vat_charge' ) );


		$footer = $this->getTemplate( 'footer' );


		$this->vars = array_add( $this->vars, 'content', $content );
		$this->vars = array_add( $this->vars, 'footer', $footer );

		return $this->renderOutput();
	}

	/**
	 * Here we process payment gateway
	 * @param OrderRepository $order_rep
	 * @param Request $request
	 * @return mixed
	 */
	public function charge( OrderRepository $order_rep, Request $request, Coupon $coupon ) {


		$ecommerce_cart = Session::get( 'ecommerce_cart' );

		$PaymentGateways = PaymentGateways::instance();
		//dump($PaymentGateways);
		$gateway = null;


		if ( isset( $ecommerce_cart['coupon_code']{1} ) && !isset( $ecommerce_cart['coupon_code_used'] ) ) {
			$coupon = $coupon->where( 'code', $request->coupon_code )->first();

			if ( isset( $coupon->type ) ) {
				$total_price = $ecommerce_cart['total_price'];
				if ( $coupon->type == 'percent' ) {
					$total_price = $total_price - ( ( $coupon->value / 100 ) * $total_price );
				} else {
					$total_price = $total_price - $coupon->value;
				}
				$ecommerce_cart['total_price'] = $total_price;
				$ecommerce_cart['coupon_code_used'] = true;
				Session::put( 'ecommerce_cart', $ecommerce_cart );
			}
		}

		foreach ( $PaymentGateways->payment_gateways() as $item ) {
			if ( $item->id == $ecommerce_cart['payment'] ) {
				return $item->charge( $ecommerce_cart, $request );

				break;
			}

		}
	}


	/**
	 *  apply coupons to checkout
	 */
	public function coupon( Request $request, Coupon $coupon ) {

		$ecommerce_cart = Session::get( 'ecommerce_cart' );

		$coupon = $coupon->where( 'code', $request->coupon_code )->first();
		$total_price = $ecommerce_cart['total_price'];
		if ( isset( $coupon->type ) ) {

			$ecommerce_cart['coupon_code'] = $request->coupon_code;
			Session::put( 'ecommerce_cart', $ecommerce_cart );
			if ( $coupon->type == 'percent' ) {
				$total_price = $total_price - ( ( $coupon->value / 100 ) * $total_price );
			} else {
				$total_price = $total_price - $coupon->value;
			}

		}


		return $total_price;


	}


}
