<?php

namespace Corp\Plugins\eCommerce\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Order extends Model {
	public $table = 'ec_orders';

	protected $fillable = [
		'name',
		'gender',
		'email',
		'phone',
		'street_address',
		'payment',
		'meta',
		'message',
		'status',
		'total_price',
		'user_id',
		'ip',
		'coupon_id',
		'discount',
		'vat'

	];


	public function items() {
		return $this->hasMany( OrderItem::class );
	}

	public function booking() {
		return $this->hasMany( Booking::class );
	}

	/**
	 * @return mixed
	 */
	public function getTotalSalesPerMoth() {
		$sql = DB::select( "SELECT YEAR(created_at) as SalesYear,
         MONTH(created_at) as SalesMonth,
         SUM(total_price) AS TotalSales
    FROM ec_orders
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY YEAR(created_at), MONTH(created_at)" );

		return $sql;
	}


	/**
	 * @return mixed
	 */
	public function getTotalSalesSum(){
		$sql = DB::select( "SELECT 
         SUM(total_price) AS TotalSales
    FROM ec_orders;");

		return $sql;
	}

	/**
	 * @return mixed
	 */
	public function getOrdersFromCurrentMoth(){
		$sql = DB::select( "SELECT * FROM ec_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");

		return $sql;
	}

	/**
	 * @return mixed
	 */
	public function getSalesDaysInCurrentMonth(){
		$sql = DB::select( "SELECT DAY(created_at) as `day`,sum(total_price) as price FROM ec_orders 
WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
GROUP BY DAY(created_at)
");

		return $sql;
	}

	public function getSalesDaysInCurrentYear(){
		$sql = DB::select( "SELECT DAY(created_at) as `day`,sum(total_price) as price FROM ec_orders 
WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
GROUP BY DAY(created_at)
");

		return $sql;
	}



}
