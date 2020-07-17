<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14.08.2019
 * Time: 13:13
 */

namespace Corp\Plugins\eCommerce\Repositories;


use App;
use Auth;
use Corp\Coupon;
use Corp\Plugins\eCommerce\Emails\OrdersEmail;
use Corp\Plugins\eCommerce\Emails\AdminOrdersEmail;
use Corp\Plugins\eCommerce\Models\Booking;
use Corp\Plugins\eCommerce\Models\Order;
use Corp\Plugins\eCommerce\Models\OrderItem;
use Corp\Plugins\eCommerce\Models\OrderItemMeta;
use Corp\Plugins\eCommerce\Models\Product;
use Corp\Repositories\Repository;
use Gate;
use Mail;
use Session;

/**
 * Class OrderRepository
 * @package Corp\Plugins\eCommerce\Repositories
 */
class OrderRepository extends Repository {
	/**
	 * OrderRepository constructor.
	 * @param Order $order
	 */
	public function __construct( Order $order ) {
		$this->model = $order;
	}

	/**
	 * @param $request
	 * @return array
	 */
	public function AddToCart( $request ) 
	{
		if ( !( $request->product_id ) ) {
			return [ 'error' => __( 'admin.some error occurred.' ) ];
		}
		$data = $request->except( '_token' );
		
		Session::forget( 'ecommerce_cart' );
        // print_r($request);
		list( $data['total_price'], $data['names'], $data['full_price'] ) = $this->totalPrice( $request );
        
		Session::put( 'ecommerce_cart', $data );
	}


	/**
	 * @param $request
	 * @param string $status
	 * @return array
	 */
	public function AddOrder( $request, $status = 'pending' ) {


		if ( !( $request->product_id ) ) {
			return [ 'error' => __( 'admin.some error occurred.' ) ];
		}


		$user_id = null;

		/**
		 * Price
		 */

		$price_all = $this->totalPrice( $request );
		$price = $price_all[0];
        

		$ecommerce_cart = Session::get( 'ecommerce_cart' );
        $vat = Session::get('vat');    
		$coupon = New Coupon();
		if ( isset( $ecommerce_cart['coupon_code']{1} ) ) {


			$coupon = $coupon->where( 'code', $request->coupon_code )->first();

			if ( isset( $coupon->type ) ) {
				$total_price = $ecommerce_cart['total_price'];
				if ( $coupon->type == 'percent' ) {
					$price = $price - ( ( $coupon->value / 100 ) * $price );
				} else {
					$price = $price - $coupon->value;
				}

			}
		}

		/***
		 *
		 */
		if ( Auth::check() ) {
			$user_id = Auth::id();
		}

		$data = [

// 			'gender' => $request->gender,
			'total_price' => $request->total_price,
			'name' => $request->name,
			'email' => $request->email,
			'phone' => $request->phone,
			'street_address' => $request->street_address,
			'payment' => $request->payment,
			'message' => $request->message ?? '',
			'status' => $status,
			'user_id' => $user_id,
			'vat' => $vat,
 			'ip' => request()->ip()

		];

        // print_r($data);exit();
		$this->model->fill( $data );

		$order_items = new OrderItem();
		$OrderItemMeta = new OrderItemMeta();
		$order_items->fill( [
			'quantity' => $request->quantity ?? 1,
			'price' => $price,
			'sku' => $request->sku ?? '',
			'product_id' => $request->product_id,


		] );

		if ( $order = $this->model->save() ) {


			$item = $this->model->items()->save( $order_items );
			$product = Product::where( 'id', $request->product_id )->first();

// 			$star_date = ( $request->PickingUpDate . ' ' . $request->PickingUpHour );
            $datert = str_replace('/', '-', $request->PickingUpDate );
            $uejr = date('m/d/Y h:i a', strtotime($datert));
            $star_date = ( $uejr );
            // $star_date = ( $request->PickingUpDate);
			if ( isset( $request->DroppingOffDate ) ) {
				// $end_date = ( $request->DroppingOffDate . ' ' . $request->DroppingOffHour );
				// $end_date = ( $request->DroppingOffDate );
				$dateolp = str_replace('/', '-', $request->DroppingOffDate );
                $yeht = date('m/d/Y h:i a', strtotime($dateolp));
                $end_date = ( $yeht );
			} else {
				$end_date = 0;
			}


			$days = rentit_DateDiff( 'd', strtotime( $star_date ), strtotime( $end_date ) );
			$hour = rentit_DateDiff( 'h', strtotime( $star_date ), strtotime( $end_date ) );
			if ( $days < 1 ) {
				$days = 1;
			}
			
			Session::put('days', $days);

			list( $prices, $names ) = $this->calculateExtras( $request, $product, $days, $hour );

			$extras = [];

			foreach ( $names as $name ) {
			    $sub_month1 = ($days / 30) ;
                $sub_month1 = floor($sub_month1); 
                $sub_days1 = ($days % 30); // the rest of days
                if($days >= 30)
                {
                    $discounts = $name["monthly_price"];
                    if($discounts!='')
                    {
                        $main_price = $name["price"];
                        $month_price = $discounts * $sub_month1;
                        $day_price = $main_price * $sub_days1;
                        $extra_price = $month_price + $day_price;
                    }
                    else
                    {
                        $extra_price = ( $name["price"] * (int) $days );
                    }
                }
                else
                {
                    $extra_price = ( $name["price"] * (int) $days );
                }
				$extras[] = $name['name'] . ' : ' . formatted_price( $extra_price );
			}
			
			list( $prices1, $names1 ) = $this->calculateAddonsExtras( $request, $product, $days, $hour );

			$addonextras = 0;

			foreach ( $names1 as $name ) {
			    $sub_month1 = ($days / 30) ;
                $sub_month1 = floor($sub_month1); 
                $sub_days1 = ($days % 30); // the rest of days
                if($days >= 30)
                {
                    $discounts = $name["monthly_price"];
                    if($discounts!='')
                    {
                        $main_price = $name["daily_price"];
                        $month_price = $discounts * $sub_month1;
                        $day_price = $main_price * $sub_days1;
                        $extra_price = $month_price + $day_price;
                    }
                    else
                    {
                        $extra_price = ( $name["daily_price"] * (int) $days );
                    }
                }
                else
                {
                    $extra_price = ( $name["daily_price"] * (int) $days );
                }
				$addonextras = $extra_price;
			}


			$arrayInsert = [
				[
					'title' => __( 'Extras' ),
					'key' => 'extras',
					'value' => serialize( $extras ),
					'product_id' => $request->product_id,
					'order_item_id' => $item->id
				],
				[
					'title' => __( 'Full Protection' ),
					'key' => 'addon_extras',
					'value' => $addonextras,
					'product_id' => $request->product_id,
					'order_item_id' => $item->id
				],
				]
				[
					'title' => __( 'Picking Up Date' ),
					'key' => 'PickingUpDate',
		
				    'value' => ( $request->PickingUpDate),
					'product_id' => $request->product_id,
					'order_item_id' => $item->id
				],
				

			];
			if ( $request->DroppingOffDate ?? false ) {
				$arrayInsert[] = [
					'title' => __( 'Dropping Off Date' ),
					'key' => 'DroppingOffDate',
			
				    'value' => ( $request->DroppingOffDate ?? ''),
					'product_id' => $request->product_id,
					'order_item_id' => $item->id


				];
			}


			$product_meta = getProductMetas( $product );
			if ( isset( $product_meta['rentit_deposit_percent'] ) && $product_meta['rentit_deposit_percent'] > 0 ) {
				$arrayInsert[] = [
					'title' => __( 'Deposit' ),
					'key' => 'rentit_deposit_percent',
					'value' => $product_meta['rentit_deposit_percent'],
					'product_id' => $request->product_id,
					'order_item_id' => $item->id


				];
				$arrayInsert[] = [
					'title' => __( 'Full price' ),
					'key' => 'rentit_deposit_full_price',
					'value' => $price_all[2],
					'product_id' => $request->product_id,
					'order_item_id' => $item->id


				];
			}

			if ( isset( $ecommerce_cart['coupon_code']{1} ) ) {
				$arrayInsert[] = [
					'title' => __( 'Coupon code' ),
					'key' => 'Coupon_code',
					'value' => ( $ecommerce_cart['coupon_code'] ?? '' ),
					'product_id' => $request->product_id,
					'order_item_id' => $item->id


				];

			}
			$OrderItemMeta->insert( $arrayInsert );

			//

			Booking::insert( [
				'order_id' => $this->model->id,
				'product_id' => $request->product_id,
				'PickingUpDate' => strtotime( $request->PickingUpDate ),
				'DroppingOffDate' => isset( $request->DroppingOffDate ) ? strtotime( $request->DroppingOffDate) : '',
				'user_id' => $user_id
			] );


			// send email


			$to = $request->email;

			try
			{
			 //   $rr = $this->model;echo $rr->id;exit();print_r($rr);exit();
			    if($request->payment=='cheque')
			    {
    				Mail::to( $to )->send( new OrdersEmail( $request, $this->model ) );
    				
    				Mail::to('Reservation@quickdrive.ae')->send( new AdminOrdersEmail( $request, $this->model ) );
			    }
			}
			catch ( \Exception $e)
			{
			    
			}
// 			exit();
			return [
				'status' => __( 'Thank you for renting a car with Quick Drive. An email confirmation is sent your email address.' ),
				'id' => $this->model->id

			];

		} else {
			return [ 'error' => __( 'admin.some error occurred.' ) ];
		}
	}


	public function updateProduct( $request, $product ) {


		$data = $request->only( 'title', 'alias', 'price', 'desc', 'text', 'img', 'meta_desc', 'keywords', 'status' );;
		if ( empty( $data ) ) {
			return array( 'error' => __( 'admin.not-have-dates' ) );
		}

		if ( empty( $data['alias'] ) ) {
			$data['alias'] = $this->transliterate( $data['title'] );
		}
		if ( empty( $data['img'] ) ) {
			$data['img'] = '';
		}
		if ( empty( $data['keywords'] ) ) {
			$data['keywords'] = '';
		}

		$result = $this->one( $data['alias'], FALSE );

		if ( isset( $result->id ) && ( $result->id != $product->id ) ) {
			$request->merge( array( 'alias' => $data['alias'] ) );
			$request->flash();

			return [ 'error' => __( 'admin.alias-used' ) ];
		}


		$data_translate = [
			'code' => App::getLocale(),
			App::getLocale() => $data,
			'alias' => $data['alias'],
			'img' => $data['img'],
			'status' => $data['status'] ?? 'published',
			'price' => $request->price ?? 0,


		];
		if ( isset( $request->published_at ) ) {
			$unix_date = strtotime( $request->published_at );
			$published_at = date( 'Y-m-d H:i:s', $unix_date );
			$data_translate['published_at'] = $published_at;
		}


		$product->fill( $data_translate );

		// set terms
		$product->terms()->sync( $request->category_id );


		if ( $product->update() ) {


			$this->setMeta( $request, $product );

			return [ 'status' => __( 'admin.Product updated' ) ];
		}
	}


	/**
	 * @param $request
	 * @param $product
	 */
	public function setMeta( $request, $product ) {
		// set gallery media

		if ( $request->gallery_media ) {

			$item = $product->meta()->updateOrCreate( [
				'name' => 'gallery_media',
			], [ 'value' => $request->gallery_media ] );

			//$item->save();

		}
		// set gallery

		if ( $request->attributes ) {


			$product->meta()->updateOrCreate( [
				'name' => 'attributes',
			],
				[
					'code' => App::getLocale(),
					'value' => '',
					App::getLocale() =>
						[ 'translation_value' => json_encode( $request->get( 'attributes' ) ) ],
				]
			);


		

		}




		if ( $request->__picking_up_location ) {

			$item = $product->meta()->updateOrCreate( [
				'name' => '__picking_up_location',
			], [ 'value' => json_encode( $request->__picking_up_location ) ] );
			$item->save();

		}
		if ( $request->__dropping_off_location ) {

			$item = $product->meta()->updateOrCreate( [
				'name' => '__dropping_off_location',
			], [ 'value' => json_encode( $request->__dropping_off_location ) ] );
			$item->save();

		}


	}


	/**
	 * @param $request
	 * @return array
	 */
	public function totalPrice( $request ) {


		$price = 0;
		$names = [];

		$product = Product::where( 'id', $request->product_id )->first();


// 		$star_date = ( $request->PickingUpDate . ' ' . $request->PickingUpHour );

        $datert = str_replace('/', '-', $request->PickingUpDate );
        $uejr = date('m/d/Y H:i p', strtotime($datert));
        $star_date = ( $uejr );
        // $star_date = ($request->PickingUpDate);
		if ( isset( $request->DroppingOffDate ) ) {
// 			$end_date = ( $request->DroppingOffDate . ' ' . $request->DroppingOffHour );
            $dateolp = str_replace('/', '-', $request->DroppingOffDate );
            $yeht = date('m/d/Y H:i p', strtotime($dateolp));
            $end_date = ( $yeht );
            // $end_date = ( $request->DroppingOffDate );
		} else {
			$end_date = 0;
		}

        // echo strtotime( $star_date ).'<br>';
        // echo strtotime( $end_date ).'<br>';
        $days = rentit_DateDiff( 'd', strtotime( $star_date ), strtotime( $end_date ) );
		$hour = rentit_DateDiff( 'h', strtotime( $star_date ), strtotime( $end_date ) );
// 		echo $hour;
        // echo $days;
		if ( $days < 1 ) {
			$days = 1;
		}
		
		$result = array(144);

        $sub_struct_month = ($days / 30) ;
        $sub_struct_month = floor($sub_struct_month); 
        $sub_struct_days = ($days % 30); // the rest of days
        // $sub_struct = $sub_struct_month."m ".$sub_struct_days."d";
        
        
      
        if($days >= 30)
        {
            $discounts = $product->monthly;
            if($discounts!='')
            {
                // $price = $product->getPriceWithSeason( $star_date, $end_date ) * $days + $discounts;
                $main_price = $product->getPriceWithSeason( $star_date, $end_date );
                $month_price = $discounts * $sub_struct_month;
                $day_price = $main_price * $sub_struct_days;
                $price = $month_price + $day_price;    
                // $calculateprice = $product->getPriceWithSeason( $star_date, $end_date ) * $discounts / 100;    
                // $minusprice = $product->getPriceWithSeason( $star_date, $end_date ) - $calculateprice;
                // $price = $minusprice * $days;
            }
            else
            {
                $price = $product->getPriceWithSeason( $star_date, $end_date ) * $days;
            }
        }
    
        else
        {
            $price = $product->getPriceWithSeason( $star_date, $end_date ) * $days;
        }
		
		$extras = $this->calculateExtras( $request, $product, $days, $hour );
		$price += $extras[0];
        
        $addonextras = $this->calculateAddonsExtras( $request, $product, $days, $hour );
		$price += $addonextras[0];

		$product_meta = getProductMetas( $product );
		$full_price = $price;
		

		}

		$names['extras'] = $extras[1];
		
        $names['addons_extras'] = $addonextras[1];

		return [ $price, $names, $full_price ];


	}

	/**
	 * @param $request
	 * @param $product
	 * @param $days
	 * @param $hour
	 * @return array
	 */
	function calculateExtras( $request, $product, $days, $hour ) {
		$price = 0;
		$names = [];
		if ( $request->checkbox_extras ?? false ) {
			$product_meta = getProductMetas( $product );
			$arr = json_decode( $product_meta['_rental_resource'] );


			$arr_resources = [];
			foreach ( $arr->item_name as $k => $v ) {

				$arr_resources[] = [
					'item_name' => $v,
					'quantity' => $arr->quantity[$k],
					'cost' => $arr->cost[$k],
					'monthly_cost' => $arr->monthly_cost[$k],
					'duration_type' => $arr->duration_type[$k],
				];
			}


			$arr_extras = array();
			foreach ( $arr_resources as $item ) {
				//$item["cost"] =  $item["cost"] ?? 0;
				if ( !isset( $item["cost"] ) && empty( $item["cost"] ) ) {
					$item["cost"] = 0;
				}
				$val = $item["cost"] . " " . ' / ' . $item["duration_type"];
				$checked = false;
				if (
					$item["cost"] == '0'
					||

					empty( $item["cost"] ) ) {


					$val = __( 'Free' );

				}

				if ( $item["duration_type"] == 'total' ) {
					$val = $item["cost"] . " " . ' / ' . __( 'Total' );

				}
				if ( $item["duration_type"] == 'Included' ) {
					$val = __( 'Included' );

				}

				if ( $item["duration_type"] == 'fixed_change' ) {
					$val = $item["cost"] . " ";
				}

				$arr_extras[] = array(
					'value' => $val,
					'name' => ( $item["item_name"] ),
					'price' => ( $item["cost"] ),
					'monthly_price' => ( $item["monthly_cost"] ),
					"duration_type" => ( $item["duration_type"] )

				);


			}
            
            $sub_struct_month = ($days / 30) ;
            $sub_struct_month = floor($sub_struct_month); 
            $sub_struct_days = ($days % 30); // the rest of days

			foreach ( $request->checkbox_extras as $k => $v ) {
				$array['extras'][] = $arr_extras[$k];

				//if the service on days when multiplied by the days
				if ( $arr_extras[$k]["duration_type"] == "days" ) {
				    // monthly calculation
				    if($days >= 30)
                    {
                        $discounts = $arr_extras[$k]["monthly_price"];
                        if($discounts!='')
                        {
                            $main_price = $arr_extras[$k]["price"];
                            $month_price = $discounts * $sub_struct_month;
                            $day_price = $main_price * $sub_struct_days;
                            $price += $month_price + $day_price;    
                        }
                        else
                        {
                            $price += ( $arr_extras[$k]["price"] * (int) $days );
                        }
                    }
                    else
                    {
                        $price += ( $arr_extras[$k]["price"] * (int) $days );
                    }
                    // end monthly calculation
					$names[$k] = $arr_extras[$k];
				}
				if ( $arr_extras[$k]["duration_type"] == "hours" ) {
				    // monthly calculation
				    if($days >= 30)
                    {
                        $discounts = $arr_extras[$k]["monthly_price"];
                        if($discounts!='')
                        {
                            $main_price = $arr_extras[$k]["price"];
                            $month_price = $discounts * $sub_struct_month;
                            $day_price = $main_price * $sub_struct_days;
                            $price += $month_price + $day_price;
                        }
                        else
                        {
                            $price += ( $arr_extras[$k]["price"] * (int) $days );
                        }
                    }
                    else
                    {
                        $price += ( $arr_extras[$k]["price"] * (int) $days );
                    }
                    // end monthly calculation
				// 	$price += ( (float) $arr_extras[$k]["price"] * $hour );
					$names[$k] = $arr_extras[$k];
				}
				if ( $arr_extras[$k]["duration_type"] == "total" ) {
				    // monthly calculation
				    if($days >= 30)
                    {
                        $discounts = $arr_extras[$k]["monthly_price"];
                        if($discounts!='')
                        {
                            $main_price = $arr_extras[$k]["price"];
                            $month_price = $discounts * $sub_struct_month;
                            $day_price = $main_price * $sub_struct_days;
                            $price += $month_price + $day_price;
                        }
                        else
                        {
                            $price += ( $arr_extras[$k]["price"] * (int) $days );
                        }
                    }
                    else
                    {
                        $price += ( $arr_extras[$k]["price"] * (int) $days );
                    }
                    // end monthly calculation
				// 	$price += $arr_extras[$k]["price"];
					$names[$k] = $arr_extras[$k];
				}
				if ( $arr_extras[$k]["duration_type"] == "fixed_change" ) {
				    // monthly calculation
				    if($days >= 30)
                    {
                        $discounts = $arr_extras[$k]["monthly_price"];
                        if($discounts!='')
                        {
                            $main_price = $arr_extras[$k]["price"];
                            $month_price = $discounts * $sub_struct_month;
                            $day_price = $main_price * $sub_struct_days;
                            $price += $month_price + $day_price;
                        }
                        else
                        {
                            $price += ( $arr_extras[$k]["price"] * (int) $days );
                        }
                    }
                    else
                    {
                        $price += ( $arr_extras[$k]["price"] * (int) $days );
                    }
                    // end monthly calculation
				// 	$price += $arr_extras[$k]["price"];
					$names[$k] = $arr_extras[$k];
				}


			}
		}
		return [ $price, $names ];

	}
	
	function calculateAddonsExtras( $request, $product, $days, $hour ) {
		$price = 0;
		$names = [];
		if ( $request->addons_extras ?? false ) {
		    $product_meta = getProductMetas( $product );
            $arr = json_decode( $product_meta['addon_extras'] );


            $arr_resources = [];
            foreach ( $arr->id as $k => $v ) {

                $arr_resources[] = [
                    'id' => $v,
                    'daily_price' => $arr->daily_price[$k],
                    'monthly_price' => $arr->monthly_price[$k],
                ];
            }


            $arr_extras = array();
            foreach ( $arr_resources as $item ) {
                //$item["cost"] =  $item["cost"] ?? 0;
                if ( !isset( $item["daily_price"] ) && empty( $item["daily_price"] ) ) {
                    $item["daily_price"] = 0;
                }
                
                $arr_extras[] = array(
                    'id' => $item['id'],
                    'daily_price' => $item['daily_price'],
                    'monthly_price' => $item['monthly_price'],
                );
            }
            
            $sub_struct_month = ($days / 30) ;
            $sub_struct_month = floor($sub_struct_month); 
            $sub_struct_days = ($days % 30); // the rest of days

            foreach ( $request->addons_extras as $k => $v ) {
                $array['addons_extras'][] = $arr_extras[$k];

                //if the service on days when multiplied by the days
                    // monthly calculation
                    if($days >= 30)
                    {
                        $discounts = $arr_extras[$k]["monthly_price"];
                        if($discounts!='')
                        {
                            $main_price = $arr_extras[$k]["daily_price"];
                            $month_price = $discounts * $sub_struct_month;
                            $day_price = $main_price * $sub_struct_days;
                            $price += $month_price + $day_price;    
                        }
                        else
                        {
                            $price += ( $arr_extras[$k]["daily_price"] * (int) $days );
                        }
                    }
                    else
                    {
                        $price += ( $arr_extras[$k]["daily_price"] * (int) $days );
                    }
                    // end monthly calculation
                    $names[$k] = $arr_extras[$k];
                
            }
        }
        
		return [ $price, $names ];

	}

	/**
	 * @param $product_id
	 * @param $star_date
	 * @param $end_date
	 * @return bool|float|int|null
	 */
	function rentit_get_season_price_by_between_two_days( $product_id, $star_date, $end_date ) {
		$season_date = '';


		$days = rentit_DateDiff( 'd', strtotime( $star_date ), strtotime( $end_date ) );
		$hour = rentit_DateDiff( 'h', strtotime( $star_date ), strtotime( $end_date ) );


		if ( $days < 1 ) {
			$days = 1;
		}

		$star_date = strtotime( $star_date );
		if ( $season_date ) {
			foreach ( $season_date as $key => $date ) {

				$contractDateBegin = strtotime( $date['start_date'] );
				$contractDateEnd = strtotime( $date['end_date'] );

				if ( ( $star_date > $contractDateBegin && $star_date < $contractDateEnd ) && $end_date < $contractDateEnd ) {


					$rental_season_discount = $date['rental_season_discount'];


					$arr_day = array();
					$arr_hour = array();
					if ( $rental_season_discount ) {

						for ( $i = 0; $i < count( $rental_season_discount['cost'] ); $i ++ ) {


							if ( isset( $rental_season_discount['duration_type'][$i] ) && $rental_season_discount['duration_type'][$i] == 'days' ) {
								if ( !empty( $rental_season_discount['cost'][$i] ) ) {

									$arr_day[$rental_season_discount['duration_val'][$i]] = array(
										'cost' => $rental_season_discount['cost'][$i],

									);
								}
							}

							if ( isset( $rental_season_discount['duration_type'][$i] ) && $rental_season_discount['duration_type'][$i] == 'hours' ) {
								if ( !empty( $rental_season_discount['cost'][$i] ) ) {

									$arr_hour[$rental_season_discount['duration_val'][$i]] = array(
										'cost' => $rental_season_discount['cost'][$i],

									);
								}
							}


						}


						krsort( $arr_day );
						krsort( $arr_hour );
						$price = null;
						//determine the largest number to the specified


						foreach ( $arr_day as $k => $price_disc ) {

							if ( $days >= $k ) {
								$price = $price_disc['cost'] * $days;

								break;
							}

						}

						if ( $arr_hour && $hour < 24 ) {

							///determine the largest number to the specified
							foreach ( $arr_hour as $k => $price_disc ) {
								if ( $hour >= $k ) {
									$price = $price_disc['cost'] * $hour;
									break;
								}

							}
						}

					}


					if ( isset( $price ) && !empty( $price ) ) {
						return $price;
					}
					return $date['price'] * $days;
				}


			}
		}
		return false;

	}


	/**
	 * @param $request
	 * @param $order
	 * @return array
	 */
	public function updateOrder( $request, $order ) {
		$data = $request->except( '_token', '_method' );

		if ( empty( $data ) ) {
			return array( 'error' => __( 'admin.not-have-dates' ) );
		}


		$order->fill( $data );

		if ( $order->update() ) {

			return [ 'status' => __( 'admin.Order-updated' ), 'id' => $order->id ];
		}

	}


	public function canceledOrder( $order_id ) {
		$order = $this->model->where( 'id', $order_id )->first();
		$order->status = 'canceled';
		$order->update();
		return [ 'status' => __( 'Order canceled' ) ];


	}


	/**
	 * @param $order
	 * @return array
	 */
	public function deleteOrder( $order ) {
		if ( Gate::denies( 'destroy', $order ) ) {
			abort( 403 );
		}
		$order->booking()->delete();
		$order->items()->delete();
		if ( $order->delete() ) {
			return [ 'status' => __( 'admin.page-deleted' ) ];
		}


	}


}