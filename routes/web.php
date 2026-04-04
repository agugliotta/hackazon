<?php
/**
 * Hackazon web routes — migrated from assets/config/routes.php (PHPixie) to Laravel 13.
 *
 * Route order intentionally mirrors PHPixie's specificity (most-specific first).
 * All vulnerabilities (SQLi, XSS, CSRF, IDOR, RFI, OS-Command) are preserved in
 * the controllers — no sanitisation is added here.
 */

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AmfController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BestpriceController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HelpdeskController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TwitterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\ErrorController as AdminErrorController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\OptionController;
use App\Http\Controllers\Admin\OptionValueController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductOptionValueController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VulnerabilityController;

use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════════════════════
// INSTALL
// ═══════════════════════════════════════════════════════════════════════════════

Route::match(['GET', 'POST'], '/install/login',  [InstallController::class, 'login']);
Route::match(['GET', 'POST'], '/install/finish', [InstallController::class, 'finish']);
Route::match(['GET', 'POST'], '/install/{id?}',  [InstallController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// FRONTEND ERROR
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/error/{id}', [ErrorController::class, 'show']);

// ═══════════════════════════════════════════════════════════════════════════════
// OAUTH / SOCIAL AUTH
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/twitter/callback',  [TwitterController::class,  'callback']);
Route::get('/facebook/callback', [FacebookController::class, 'callback']);

// Auth (routes kept under /auth but abort(404) preserved in controller — original behaviour)
Route::any('/auth/login',    [AuthController::class, 'login']);
Route::any('/auth/logout',   [AuthController::class, 'logout']);
Route::any('/auth/password', [AuthController::class, 'password']);
Route::any('/auth/register', [AuthController::class, 'register']);
Route::any('/auth/facebook', [AuthController::class, 'facebook']);

// ═══════════════════════════════════════════════════════════════════════════════
// USER
// ═══════════════════════════════════════════════════════════════════════════════

Route::match(['GET', 'POST'], '/user/login',    [UserController::class, 'login']);
Route::get('/user/logout',                      [UserController::class, 'logout']);
Route::match(['GET', 'POST'], '/user/register', [UserController::class, 'register']);
Route::match(['GET', 'POST'], '/user/password', [UserController::class, 'password']);
Route::match(['GET', 'POST'], '/user/recover',  [UserController::class, 'recover']);
Route::match(['GET', 'POST'], '/user/newpassw', [UserController::class, 'newpassw']);
Route::get('/user/terms',                       [UserController::class, 'terms']);

// ═══════════════════════════════════════════════════════════════════════════════
// ACCOUNT
// ═══════════════════════════════════════════════════════════════════════════════

Route::match(['GET', 'POST'], '/account/profile/edit', [AccountController::class, 'editProfile']);
Route::get('/account/orders',                          [AccountController::class, 'orders']);
Route::match(['GET', 'POST'], '/account/documents',    [AccountController::class, 'documents']);
Route::get('/account/help-articles',                   [AccountController::class, 'helpArticles']);
Route::post('/account/add-photo',                      [AccountController::class, 'addPhoto']);
Route::get('/account',                                 [AccountController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// WISHLIST (specific actions before generic)
// ═══════════════════════════════════════════════════════════════════════════════

Route::post('/wishlist/add-product/{id}',    [WishlistController::class, 'addProduct']);
Route::post('/wishlist/remove-product/{id}', [WishlistController::class, 'deleteProduct']);
Route::post('/wishlist/new',                 [WishlistController::class, 'newList']);
Route::match(['GET', 'POST'], '/wishlist/edit',         [WishlistController::class, 'edit']);
Route::post('/wishlist/set-default',                    [WishlistController::class, 'setDefault']);
Route::post('/wishlist/delete',                         [WishlistController::class, 'delete']);
Route::get('/wishlist/search',                          [WishlistController::class, 'search']);
Route::post('/wishlist/remember',                       [WishlistController::class, 'remember']);
Route::post('/wishlist/remove-follower',                [WishlistController::class, 'removeFollower']);
Route::get('/wishlist/{id}',                            [WishlistController::class, 'show']);
Route::get('/wishlist',                                 [WishlistController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// CART
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/cart',                                    [CartController::class, 'index']);
Route::post('/cart/add',                               [CartController::class, 'add']);
Route::get('/cart/view',                               [CartController::class, 'view']);
Route::post('/cart/update',                            [CartController::class, 'update']);
Route::post('/cart/empty',                             [CartController::class, 'empty']);
Route::match(['GET', 'POST'], '/cart/set-methods',     [CartController::class, 'setMethods']);

// ═══════════════════════════════════════════════════════════════════════════════
// CHECKOUT
// ═══════════════════════════════════════════════════════════════════════════════

Route::match(['GET', 'POST'], '/checkout/shipping',       [CheckoutController::class, 'shipping']);
Route::match(['GET', 'POST'], '/checkout/billing',        [CheckoutController::class, 'billing']);
Route::get('/checkout/get-address',                       [CheckoutController::class, 'getAddress']);
Route::post('/checkout/delete-address',                   [CheckoutController::class, 'deleteAddress']);
Route::match(['GET', 'POST'], '/checkout/confirmation',   [CheckoutController::class, 'confirmation']);
Route::post('/checkout/place-order',                      [CheckoutController::class, 'placeOrder']);
Route::get('/checkout/order',                             [CheckoutController::class, 'order']);

// ═══════════════════════════════════════════════════════════════════════════════
// PRODUCT & CATEGORY
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/product/{id}',  [ProductController::class, 'show']);
Route::get('/category/{id}', [CategoryController::class, 'show']);

// ═══════════════════════════════════════════════════════════════════════════════
// SEARCH
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/search', [SearchController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// REVIEW / CONTACT / FAQ / BLOG / BESTPRICE
// ═══════════════════════════════════════════════════════════════════════════════

Route::post('/review/send', [ReviewController::class, 'send']);
Route::match(['GET', 'POST'], '/contact', [ContactController::class, 'index']);
Route::get('/faq',           [FaqController::class,       'index']);
Route::get('/blog',          [BlogController::class,      'index']);
Route::get('/blog/post',     [BlogController::class,      'post']);
Route::any('/bestprice',     [BestpriceController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// HELPDESK (GWT backend)
// ═══════════════════════════════════════════════════════════════════════════════

Route::any('/helpdesk',                  [HelpdeskController::class, 'index']);
Route::any('/helpdesk/helpdesk-service', [HelpdeskController::class, 'helpdeskService']);

// ═══════════════════════════════════════════════════════════════════════════════
// AMF (Flash backend)
// ═══════════════════════════════════════════════════════════════════════════════

Route::any('/amf',             [AmfController::class, 'index']);
Route::any('/amf_back_office', [AmfController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// ADMIN — login / logout (no auth middleware, handled inside controller)
// ═══════════════════════════════════════════════════════════════════════════════

Route::match(['GET', 'POST'], '/admin/user/login',  [AdminUserController::class, 'login']);
Route::get('/admin/user/logout',                     [AdminUserController::class, 'logout']);

// ─── ADMIN — OptionValue (hyphenated path) ────────────────────────────────────

Route::get('/admin/option-value',                      [OptionValueController::class, 'index']);
Route::match(['GET', 'POST'], '/admin/option-value/edit/{id?}',   [OptionValueController::class, 'edit']);
Route::match(['GET', 'POST'], '/admin/option-value/new',          [OptionValueController::class, 'create']);
Route::post('/admin/option-value/delete/{id?}',                   [OptionValueController::class, 'destroy']);
Route::post('/admin/option-value/save',                            [OptionValueController::class, 'save']);
Route::post('/admin/option-value/delete-variant',                  [OptionValueController::class, 'deleteVariant']);
Route::get('/admin/option-value/get-option-values',                [OptionValueController::class, 'getOptionValues']);

// ─── ADMIN — ProductOptionValue (hyphenated path) ─────────────────────────────

Route::get('/admin/product-option-value',                              [ProductOptionValueController::class, 'index']);
Route::match(['GET', 'POST'], '/admin/product-option-value/edit/{id?}', [ProductOptionValueController::class, 'edit']);
Route::match(['GET', 'POST'], '/admin/product-option-value/new',        [ProductOptionValueController::class, 'create']);
Route::post('/admin/product-option-value/delete/{id?}',                 [ProductOptionValueController::class, 'destroy']);
Route::post('/admin/product-option-value/save',                          [ProductOptionValueController::class, 'save']);
Route::post('/admin/product-option-value/delete-option',                 [ProductOptionValueController::class, 'deleteOption']);

// ─── ADMIN — Vulnerability ────────────────────────────────────────────────────

Route::get('/admin/vulnerability',           [VulnerabilityController::class, 'index']);
Route::post('/admin/vulnerability',          [VulnerabilityController::class, 'index']);
Route::get('/admin/vulnerability/matrix',    [VulnerabilityController::class, 'matrix']);
Route::post('/admin/vulnerability/restore',  [VulnerabilityController::class, 'restore']);

// ─── ADMIN — Enquiry extra action ─────────────────────────────────────────────

Route::post('/admin/enquiry/{id}/add-message', [EnquiryController::class, 'addMessage']);

// ─── ADMIN — generic CRUD for each resource ───────────────────────────────────
// Order matters: specific paths listed first, then the catch-all edit/{id}.

$adminCrud = [
    'home'     => AdminHomeController::class,
    'category' => AdminCategoryController::class,
    'coupon'   => CouponController::class,
    'enquiry'  => EnquiryController::class,
    'faq'      => AdminFaqController::class,
    'option'   => OptionController::class,
    'order'    => OrderController::class,
    'product'  => AdminProductController::class,
    'role'     => RoleController::class,
    'user'     => AdminUserController::class,
];

foreach ($adminCrud as $alias => $controller) {
    Route::get("/admin/{$alias}",                            [$controller, 'index']);
    Route::match(['GET', 'POST'], "/admin/{$alias}/new",     [$controller, 'create']);
    Route::match(['GET', 'POST'], "/admin/{$alias}/edit/{id?}", [$controller, 'edit']);
    Route::post("/admin/{$alias}/delete/{id?}",              [$controller, 'destroy']);
}

// ─── ADMIN — Error ────────────────────────────────────────────────────────────

Route::get('/admin/error/{id}', [AdminErrorController::class, 'view']);

// ─── ADMIN — Dashboard (root) ─────────────────────────────────────────────────

Route::get('/admin', [AdminHomeController::class, 'index']);

// ═══════════════════════════════════════════════════════════════════════════════
// HOME (catch-all — must be last)
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/', [HomeController::class, 'index']);
Route::get('/{any}', [HomeController::class, 'notFound'])->where('any', '.*');
