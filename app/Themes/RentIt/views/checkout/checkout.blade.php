<div class="col-md-12 col-xs-12">
    <div class="white-box"><br>
        <h3 class="block-title alt"><b>{{__('Order items')}}</b></h3>
        <form action="{{route('FrontendCheckoutCharge')}}" method="POST" class="table-responsive">

            @csrf
<!--view check out page and place order page-->
            @if($product->alias ?? false && $ecommerce_cart)
                <table class="table table-bordered shop_table shop_table_responsive cart">

                    <thead>
                    <tr>
                        <th>{{__('item')}}</th>
                        <th></th>
                        <th>{{__('Cost')}}</th>
                        <th>{{__('Quantity')}}</th>
                        <th>{{__('Total')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="col-md-1">
                            @if($product->img > 0)
                                <img class="" style="margin-right: 10px;"
                                     src="{{ the_image_url($product->img,'thumbnail-260x260') }}"
                                     alt="{{$product->title}}" width="80">
                            @endif

                        </td>
                        <td>
                            <div class="form-group">
                                <a href="{{ route('fleet.show',['alias' => $product->alias]) }}">
                                    {{$product->title}}
                                </a>
                            </div>
                            <div class="clearfix"></div>


                            <table class="table table-bordered shop_table shop_table_responsive cart">
<!-- check checkin and checkout date for booking and calculate number of days then multiple single dy price with that-->	
	<?php
								$datert = str_replace('/', '-', $ecommerce_cart['PickingUpDate'] );
                                $uejr = date('m/d/Y h:i a', strtotime($datert));
								$star_date = ( $uejr );
								if ( isset( $ecommerce_cart['DroppingOffDate'] ) ) {
                                    $datoi = str_replace('/', '-', $ecommerce_cart['DroppingOffDate'] );
                                    $lopk = date('m/d/Y h:i a', strtotime($datoi));    
									$end_date = $lopk;
								} else {
									$end_date = 0;
								}

								$days = rentit_DateDiff( 'd', strtotime( $star_date ), strtotime( $end_date ) );
								$hour = rentit_DateDiff( 'h', strtotime( $star_date ), strtotime( $end_date ) );


								?>
                                <tbody>
                                <tr>
                                        <td> {{__('Day(s)')}} : {{$days}}</td>
                                </tr>
<!-- include extra features that client selected at booking time-->
                                @if($ecommerce_cart['names']['extras'] ?? false)
                                    <tr>
                                        <td><b>{{__('Extras & Frees')}}</b></td>
                                    </tr>
                                    @foreach($ecommerce_cart['names']['extras'] as $item)
                                    <?php $sub_month1 = ($days / 30) ;
                                        $sub_month1 = floor($sub_month1); 
                                        $sub_days1 = ($days % 30); // the rest of days
                                        if($days >= 30)
                                        {
                                            $discounts = $item["monthly_price"];
                                            if($discounts!='')
                                            {
                                                $main_price = $item["price"];
                                                $month_price = $discounts * $sub_month1;
                                                $day_price = $main_price * $sub_days1;
                                                $extra_price = $month_price + $day_price;
                                            }
                                            else
                                            {
                                                $extra_price = ( $item["price"] * (int) $days );
                                            }
                                        }
                                        else
                                        {
                                            $extra_price = ( $item["price"] * (int) $days );
                                        } ?>
                                        <tr>
                                            <td> &nbsp;&nbsp;{{$item['name']}}: AED {{$extra_price}}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                
                                

                                <tr>
                                    <td><b> {{__('Rent data')}}</b></td>
                                </tr>

                                <tr>

                                    <td><b>{{__('Start')}}</b> {{$star_date}}
                                        <b>&nbsp;:&nbsp;</b><b>{{__('End')}} </b>{{$end_date}}</td>

									<?php
									$date = DateTime::createFromFormat( 'm/d/y H:i  A', '09/30/18 10:00 AM' );
									?>

                                </tr>
                                <tr>
                                    <td>
                                        {{$ecommerce_cart['name']}}<br>
                                        {{$ecommerce_cart['street_address']}}<br>
                                        {{$ecommerce_cart['email']}}<br>
                                        {{$ecommerce_cart['phone']}}<br>
                                    </td>
                                    {{--<td><b>Location charge </b> : Free</td>--}}
                                </tr>
								<?php

								$product = \Corp\Plugins\eCommerce\Models\Product::where( 'id', $ecommerce_cart['product_id'] )->first();

								$product_meta = getProductMetas( $product );
								if(isset( $product_meta['rentit_deposit_percent'] )){

								?>
                                @if($ecommerce_cart['names']['addons_extras'] ?? false)
                                    @foreach($ecommerce_cart['names']['addons_extras'] as $item)
                                    <?php $sub_month2 = ($days / 30) ;
                                        $sub_month2 = floor($sub_month2); 
                                        $sub_days2 = ($days % 30); // the rest of days
                                        if($days >= 30)
                                        {
                                            $discounts1 = $item["monthly_price"];
                                            if($discounts1!='')
                                            {
                                                $main_price1 = $item["daily_price"];
                                                $month_price1 = $discounts1 * $sub_month2;
                                                $day_price1 = $main_price1 * $sub_days2;
                                                $extra_price1 = $month_price1 + $day_price1;
                                            }
                                            else
                                            {
                                                $extra_price1 = ( $item["daily_price"] * (int) $days );
                                            }
                                        }
                                        else
                                        {
                                            $extra_price1 = ( $item["daily_price"] * (int) $days );
                                        } ?>
                                        <tr>
                                            <td> <b>{{__('Full Protection')}}</b>  AED {{$extra_price1}}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                <tr>
                                    <td>
                                        <b> Deposit</b>
                                        AED {{$product_meta['rentit_deposit_percent']}} 

                                    </td>
                                    {{--<td><b>Location charge </b> : Free</td>--}}
                                </tr>
                                <?php }  ?>
                                        <?php if($ecommerce_cart['payment']=='cheque'){ ?>
                                        <?php }
                                        else
                                        { ?>
                                        <?php } ?>
                                </tbody>
                            </table>
                            <div class="row">

                                <div class="col-md-9">
                                    <input type="text" name="coupon_code" class="input-text pull-left
                               form-control placeholder" id="coupon_code" value="" placeholder="Coupon code">
                                </div>
                                <div class="col-md-3">
                                    <button style="height: 50px;" type="button"
                                            class="btn btn-theme pull-right btn-apply-coupon">
                                        Apply Coupon
                                    </button>
                                </div>

                            </div>

                        </td>
                        <td>AED {{ $ecommerce_cart['total_price'] }}</td>
                        <td>1</td>
                        <td class="font-500">AED {{ $ecommerce_cart['total_price'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="font-size: 120%;" class="font-500" align="right"><b>{{ __('VAT')  }}</b>
                        </td>
                        <td class="font-500 cart-total" style="font-size: 120%;">
                            AED {{$vat_charge}}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" style="font-size: 120%;" class="font-500" align="right"><b>{{__('Total')}}</b>
                        </td>
                        <td class="font-500 cart-total" style="font-size: 120%;">
                            <?php if($ecommerce_cart['payment']=='cheque'){ ?>
                                AED {{ $ecommerce_cart['total_price'] + $vat_charge }}
                            <?php } 
                            else
                            { ?><span style="font-size:14px">(5% off)</span><br>
                                AED {{ $ecommerce_cart['payment_now_price'] }}  ($ {{convertusd($ecommerce_cart['payment_now_price'])}})
                           <?php } ?>
                        </td>
                    </tr>

                    </tbody>
                </table>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-theme pull-right btn-reservation-now"
                            href="#">{{__('Place order')}}
                    </button>
                </div><br><br><br>
            @else
				<!--if not selelcted this else part of checkout page-->
                <h4>{{__('Checkout is empty!')}}</h4>
            @endif

        </form>
        @if(is_object($gateway) && method_exists($gateway,'afterCheckoutForm'))
            {!! $gateway->afterCheckoutForm($product, $ecommerce_cart) !!}
        @endif
        
    </div>
</div>