<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// if(!lr_is_installed()){

// 	//Route::group( [ 'middleware' => 'web' ], function () {

// 		Route::get( '/lr-install',
// 			[
// 				'uses' => 'Admin\InstallController@showInstall',
// 			] );
// 		Route::post( '/lr-install',
// 			[
// 				'uses' => 'Admin\InstallController@installCms',
// 			] );
// 	//});




// }





	if(!lr_is_installed()) return;
// Localization
Route::get( '/js/lang.js', function () {
	$strings = Cache::rememberForever( 'lang.js', function () {
		$lang = config( 'app.locale' );

		$files = glob( resource_path( 'lang/' . $lang . '/*.php' ) );
		$strings = [];

		foreach ( $files as $file ) {
			$name = basename( $file, '.php' );
			$strings[$name] = require $file;
		}

		return $strings;
	} );

	header( 'Content-Type: text/javascript' );
	echo( 'window.i18n = ' . json_encode( $strings ) . ';' );
	exit();
} )->name( 'assets.lang' );


// we regenerate thumbails
Route::get( '/uploads/{img}/{h}/{w}', function ( $filePath, $h = 200, $w = 200 ) {

	$filePath = public_path( "/uploads/$filePath" );

	$img = Image::cache( function ( $image ) use ( $filePath, $h, $w ) {
		$image->make( $filePath )->fit( $h, $w ); // This line works fine without being wrapped in `cache`
	}, 1, true );


	return $img->response();


	// return Image::make(public_path("/uploads/$filePath"))->fit($h, $w)->response();
} );

// we regenerate thumbails
Route::get( 'storage/uploads/{img}/{h}/{w}', function ( $filePath, $h = 200, $w = 200 ) {

	$filePath = storage_path( "app/public/uploads/$filePath" );


	$img = Image::cache( function ( $image ) use ( $filePath, $h, $w ) {
		$image->make( $filePath )->fit( $h, $w ); // This line works fine without being wrapped in `cache`
	}, 10, true );


//	dd($filePath);
	return $img->response();


	// return Image::make(public_path("/uploads/$filePath"))->fit($h, $w)->response();
} );


Route::group( [ 'prefix' => Corp\Http\Middleware\LocaleMiddleware::getLocale() ], function () {


//admin
	Route::group( [ 'prefix' => 'admin', 'middleware' => ['auth'] ], function () {

		//admin
		Route::get( '/',
			[
				'uses' => 'Admin\IndexController@index',
				'as' => 'adminIndex'
			] );

		// posts


		Route::resource( 'posts', 'Admin\PostsController', [
			'as' => 'admin'
		] );

		Route::post( 'posts/clone', 'Admin\PostsController@Clone' )->name( 'admin.posts.clone' );

		Route::resource( 'comments', 'Admin\CommentsController', [
			'as' => 'admin'
		] );

		// comments
//		Route::resource( 'comments', 'Admin\CommentsController', [
//			'as' => 'comments'
//		] );


		// pages
		Route::resource( 'pages', 'Admin\PageController', [
			'as' => 'admin'
		] );

		Route::resource( 'post_tag', 'Admin\PostTagController', [
			'as' => 'admin'
		] );
		Route::resource( 'por_categories', 'Admin\PortfolioCategoriesController', [
			'as' => 'admin'
		] );


		// categories
		Route::resource( 'categories', 'Admin\CategoriesController', [
			'as' => 'admin'
		] );

		// portfolio
		Route::resource( 'portfolio', 'Admin\PortfolioController', [
			'as' => 'admin'
		] );
		Route::resource( 'portfolio_categories', 'Admin\PortfolioCategoriesController', [
			'as' => 'admin'
		] );
		Route::post( 'portfolio/clone', 'Admin\PortfolioController@Clone' )->name( 'admin.portfolio.clone' );


		// menus
		Route::resource( 'menus', 'Admin\MenusController', [
			'as' => 'admin'
		] );

		// widgets
		Route::resource( 'widgets', 'Admin\WidgetsController', [
			'as' => 'admin'
		] );
		// media library
		Route::resource( 'media', 'Admin\MediaController', [
			'as' => 'admin'
		] );
		//media regenerate thumbnails
		Route::resource( 'regenerateThumbnails', 'Admin\RegenerateThumbnailsController', [
			'as' => 'admin'
		] );

		Route::post( '/media_popup', 'Admin\MediaController@mediaPopup' )->name( 'media_media_popup' );
        
        Route::resource( 'promotions', 'Admin\PromotionsController', [
			'as' => 'admin'
		] );
		
		Route::get( 'promotions/delete/{id}', 'Admin\PromotionsController@destroy')->name( 'admin.promotions.delete' );
		
		Route::resource( 'features', 'Admin\FeaturesController', [
			'as' => 'admin'
		] );
		
		Route::get( 'features/delete/{id}', 'Admin\FeaturesController@destroy')->name( 'admin.features.delete' );
		
		Route::resource( 'price_related_features', 'Admin\PriceFeaturesController', [
			'as' => 'admin'
		] );
		
		Route::get( 'price_related_features/delete/{id}', 'Admin\PriceFeaturesController@destroy')->name( 'admin.price_related_features.delete' );
		
		Route::resource( 'enquiries', 'Admin\EnquiryController', [
			'as' => 'admin'
		] );

		Route::resource( '/permissions', 'Admin\PermissionsController', [
			'as' => 'admin'
		] );


		Route::resource( '/users', 'Admin\UsersController', [
			'as' => 'admin'
		] );

		// themes
		Route::resource( '/themes', 'Admin\ThemesController', [
			'as' => 'admin'
		] );
		// plugins
		Route::resource( '/plugins', 'Admin\PluginController', [
			'as' => 'admin'
		] );
		// Options
		Route::resource( '/options', 'Admin\OptionsController', [
			'as' => 'admin'
		] );

		Route::resource( '/customize', 'Admin\CustomizeController', [
			'as' => 'admin'
		] );


	} );


	Auth::routes();
	Route::group( [ 'middleware' => 'web' ], function () {

		Route::resource( 'comment', 'Frontend\CommentController', [ 'only' => [ 'store' ] ] );
	} );


	Route::get( 'setlocale/{lang}', function ( $lang ) {

		$referer = Redirect::back()->getTargetUrl(); //Previous page URL

		$parse_url = parse_url( $referer, PHP_URL_PATH ); //URI previous page


		// break into array by delimiter
		$segments = explode( '/', $parse_url );
        // echo count($segments);exit();
		// If the URL (where the language switch was clicked) contained the correct language label
		if(count($segments) > 1)
		{
    		if ( in_array( $segments[1], getlangsCodes() ) ) {
    		    
    			unset( $segments[1] ); //remove label
    		}
		}	

		// Add a language label to the URL (if you choose a non-default language)
		if ( $lang != getOption('LANG', 'en') ) {
			array_splice( $segments, 1, 0, $lang );
		}

		//we form full URL
		$url = Request::root() . implode( "/", $segments );

		//if there were still GET parameters - add them
		if ( parse_url( $referer, PHP_URL_QUERY ) ) {
			$url = $url . '?' . parse_url( $referer, PHP_URL_QUERY );
		}
		return redirect( $url ); //Redirect back to the same page.

	} )->name( 'setlocale' );

	Route::get('logout', 'Auth\LoginController@logout');

	Route::get('/lr-clear-cache',function (){
		Artisan::call('cache:clear');
		Artisan::call('view:clear');
		return back();
	})->name('lr-clear-cache');
} );


// install script

//Route::get( '/lr-install',
//	[
//		'uses' => 'Admin\InstallController',
//		'as' => 'lr-install'
//	] );