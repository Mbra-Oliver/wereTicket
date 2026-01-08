<?php

use App\Http\Controllers\Api\AdminScannerController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\OrganizerController;
use App\Http\Controllers\Api\OrganizerScannerController;
use App\Http\Controllers\Api\ProductOrderController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//guest customer routes
Route::get('/', [HomeController::class, 'index'])->name('api.index');
Route::get('/get-lang/{code}', [LanguageController::class, 'getLang']);
Route::get('/get-basic', [HomeController::class, 'getBasic'])->name('getBasic');
Route::post('/push-notification-store-endpoint', [HomeController::class, 'pushNotificationStore']);
Route::post('/save-fcm-token', [FcmTokenController::class, 'store']);
Route::get('/get-notifications', [FcmTokenController::class, 'getNotifications']);

Route::prefix('events')->group(function () {
  Route::get('/', [EventController::class, 'index'])->name('api.events');
  Route::get('/details', [EventController::class, 'details'])->name('api.event.details');
  Route::get('/slot/seat-details', [EventController::class, 'slotMapping'])->name('api.event.slot_mapping_seat');
  Route::get('/categories', [EventController::class, 'categories'])->name('api.event.categories');
});

Route::post('/event/apply-coupon', [EventController::class, 'applyCoupon'])->name('api.event.apply_coupon');
Route::post('/event/checkout-verify', [EventController::class, 'checkoutVerify'])->name('api.event.checkout_verify');
Route::post('/event/verify-payment', [EventController::class, 'verifyPayment'])->name('api.event.payment_verify');
Route::post('/event-booking', [EventController::class, 'store_booking'])->name('api.event.booking.store');
Route::get('shop/', [ShopController::class, 'index'])->name('api.shop');
Route::get('product/details', [ShopController::class, 'details'])->name('api.product.details');

Route::post('product/review/store', [ShopController::class, 'store_review'])->name('api.product.review.store');

Route::prefix('organizers')->group(function () {
  Route::get('/', [OrganizerController::class, 'index'])->name('api.organizers.index');
  Route::get('/details/{id}', [OrganizerController::class, 'details'])->name('api.organizers.details');
  Route::post('/contact-mail', [OrganizerController::class, 'contactMail'])->name('api.organizers.contact');
});

Route::prefix('customer')->group(function () {
  Route::get('/signup', [CustomerController::class, 'signup'])->name('api.customer.signup');
  Route::post('/signup/submit', [CustomerController::class, 'signupSubmit'])->name('api.customer.signup_submit');

  //facebook
  Route::get('login/facebook/callback', [CustomerController::class, 'handleFacebookCallback']);
  Route::get('auth/facebook', [CustomerController::class, 'facebookRedirect']);

  //google
  Route::get('login/google/callback', [CustomerController::class, 'handleGoogleCallback']);
  Route::get('auth/google', [CustomerController::class, 'googleRedirect']);

  Route::get('/login', [CustomerController::class, 'login'])->name('api.customer.login');
  Route::get('/authentication-fail', [CustomerController::class, 'authentication_fail'])->name('api.customer.authentication.fail');
  Route::post('/login/submit', [CustomerController::class, 'loginSubmit'])->name('api.customer.login_submit');
  //forget password

  Route::post('/forget-password', [CustomerController::class, 'forget_mail'])->name('api.customer.forget_password');
  Route::post('/reset-password-update', [CustomerController::class, 'reset_password_submit'])->name('api.customer.update_reset_password');
});

/* ************************************
 * Customer dashboard routes are goes here
 * ************************************/
Route::prefix('/customers')->middleware('auth:sanctum')->group(function () {
  Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('api.customers.dashboard');

  /* ************************************
   * Event Bookings routes are goes here
   * ************************************/
  Route::get('/bookings', [CustomerController::class, 'bookings'])->name('api.customers.bookings');
  Route::get('/booking/details', [CustomerController::class, 'booking_details'])->name('api.customers.booking.details');

  /* ************************************
   * Event Bookings routes are goes here
   * ************************************/

  Route::prefix('wishlists')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('api.customers.wishlists.index');
    Route::post('/store', [WishlistController::class, 'store'])->name('api.customers.wishlists.store');
    Route::post('/delete', [WishlistController::class, 'delete'])->name('api.customers.wishlists.delete');
  });

  /* ************************************
   * Product order routes are goes here
   * ************************************/
  Route::get('/product-orders', [ProductOrderController::class, 'product_order'])->name('api.customers.product_orders');
  Route::get('/product-order/details', [ProductOrderController::class, 'product_order_details'])->name('api.customers.product_order.details');

  /* ************************************
   * Support ticket routes are goes here
   * ************************************/
  Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('api.customers.support_tickets');
  Route::get('/support-ticket/details', [SupportTicketController::class, 'details'])->name('api.customers.support_tickets.details');
  Route::post('/support-ticket/store', [SupportTicketController::class, 'store'])->name('api.customers.support_tickets.store');
  Route::post('/support-ticket/reply', [SupportTicketController::class, 'reply'])->name('api.customers.support_tickets.reply');

  //edit profile
  Route::get('/edit-profile', [CustomerController::class, 'edit_profile'])->name('api.customers.edit_profile');
  //update profile info
  Route::post('/update/profile', [CustomerController::class, 'update_profile'])->name('api.customers.update_profile');

  //update password
  Route::post('/update/password', [CustomerController::class, 'updated_password'])->name('api.customers.updated_password');

  Route::post('/logout', [CustomerController::class, 'logoutSubmit'])->name('api.customers.logout');
});

Route::prefix('/organizer')->group(function () {
  Route::post('/login/submit', [OrganizerScannerController::class, 'loginSubmit'])->name('api.organizer.login_submit');
  Route::get('/authentication-fail', [OrganizerScannerController::class, 'authentication_fail'])->name('api.organizer.authentication.fail');
  Route::middleware('auth:organizer_sanctum')->group(function () {
    Route::post('/check-qrcode', [OrganizerScannerController::class, 'check_qrcode'])->name('api.organizer.check-qrcode');
    Route::post('/logout', [OrganizerScannerController::class, 'logoutSubmit'])->name('api.organizer.logout');
  });
});

Route::prefix('/admin')->group(function () {
  Route::post('/login/submit', [AdminScannerController::class, 'loginSubmit'])->name('api.admin.login_submit');
  Route::get('/authentication-fail', [AdminScannerController::class, 'authentication_fail'])->name('api.admin.authentication.fail');
  Route::group(['middleware' => 'auth:admin_sanctum'], function ($e) {
    Route::post('/check-qrcode', [AdminScannerController::class, 'check_qrcode'])->name('api.admin.check-qrcode');
    Route::post('/logout', [AdminScannerController::class, 'logoutSubmit'])->name('api.admin.logout');
  });
});
