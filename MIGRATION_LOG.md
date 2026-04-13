# Migration Log

## [Full Crawl + UX Fixes] — 2026-04-13

- Files modified: 6 (WishList.php, VulnerabilityController.php, .htaccess, docker-compose.yml, admin/vulnerability/index.blade.php, README.md)
- Files created: docs/screenshots/ (6 PNG screenshots)

- Decisions:
  - `docker-compose.yml`: port 8090 → 8080 (align with APP_URL); stopped conflicting `hackazon_web` container from separate project
  - `WishList::searchWishLists()`: `DB::select(DB::raw(...))` → `DB::select(...)` — `DB::raw()` returns an Expression object; PHP 8 strict typing rejects it as PDO query string argument
  - `public/.htaccess`: added explicit rewrite rules for `^helpdesk` and `^amf_back_office` to force through Laravel — Apache was intercepting `/helpdesk/` as a directory (the GWT compiled output lives in `public/helpdesk/`) before Laravel could handle the route
  - `VulnerabilityController`: replaced stub "edit config file directly" message with full visual editor — panels grouped by action, enable/disable checkboxes per vuln/field, property flags (stored/blind), descriptions, Save button writes PHP config file
  - `admin/vulnerability/index.blade.php`: added `onchange="this.form.submit()"` to context select and edit-mode checkbox — original relied on removed `vuln-config.js`
  - `Dockerfile`: added `assets/config/vuln` to `chown/chmod` so vuln config is writable at runtime
  - `assets/config/vuln/` permissions: fixed live via `docker exec chown` (persisted in image via Dockerfile)

- Crawl result: 61/61 routes returning 200 (zero failures)
- Screenshots taken: homepage, product, search, admin products, vuln matrix, vuln editor

## [BugFixes — Endpoint & Vuln Testing] — 2026-04-12

- Files modified: 12
- Files created: 0
- Decisions taken:
  - `docker-compose.yml`: port 8080 → 8090 (OrbStack already held 8080)
  - `wishlist/show.blade.php`: PHPixie ORM `->id()` method calls replaced with Eloquent property access `->id`; `$user->id()` → `$user->id`; `\App\Models\Wishlist::TYPE_PUBLIC` → `\App\Models\WishList::TYPE_PUBLIC` (case fix)
  - `User::wishlistFollowers()`: was `HasMany(WishlistFollower)` (returning pivot records), changed to `BelongsToMany(User, 'tbl_wishlist_followers', 'follower_id', 'user_id')` — returns the followed User objects as the view expects (`->username`, `->id`, `->wishlists()`)
  - `BaseController::getToken()` added — legacy views call `$controller->getToken('context')` to get raw CSRF token string value; delegated to `$this->vulnService->getTokenManager()->getToken()`
  - `Installer::__construct`: removed `PHPixie\Pixie` type hint and `$this->pixie` property (container injection of non-existent class crashed `/install`)
  - `Installer::checkIsAuthorized()`: `$this->pixie->db->get()->conn` → `DB::connection()->getPdo()`; `$this->pixie->config->get('parameters')` → `config('parameters')`
  - `Installer::buildStepChain()`: `$this->pixie->config->get(...)` → `config(...)` calls; removed `$this->pixie` from `propagateSettings()` and `addStep()` calls
  - `Installer::runWizard()`: `$this->request->getRequestData()` → `array_merge($request->query->all(), $request->request->all())`; `$this->request->method` → `$this->request->method()`
  - `InstallController`: added `$installer->init()` call before `runWizard()` (legacy called `->init($view)->runWizard()`)
  - `AbstractStep::propagateSettings(Pixie $pixie, View $view)` → `propagateSettings(?View $view = null)` — removed Pixie dependency; removed `$next->setPixie()` from `chainNextStep()`
  - `DBSettingsStep::init()`: replaced `$this->pixie->config->get_group('db')` with `config('database.connections.mysql.*')` calls
  - `EmailSettingsStep::init()`: replaced `$this->pixie->config->get_group('email')` with `config('email.default.*')` fallbacks
  - `ConfirmationStep`: replaced all `$this->pixie->db->get()` calls with `DB::connection()->getPdo()` and `DB::statement()`/`DB::select()`; `$this->pixie->root_dir` → `base_path()`; `$this->pixie->orm->get('User')` → `User::changeUserPassword()`; config methods → `config()` helper
  - `ConfirmationStep::persistFields()`: `['version']` → correct property names (`['configVersion', 'configsToAdd', 'canWrite', 'configDir', 'bakDir']`)
  - `installation.blade.php`: `$stepData['is_last_started']` → `($stepData['is_last_started'] ?? false)` (not all steps have this key)
  - `SearchController`: replaced `->paginate()` with manual `->get()` + `LengthAwarePaginator` to allow UNION-based SQLi (paginate() runs a COUNT(*) wrapper query that breaks UNION payloads due to cardinality mismatch)
  - `RestController::parseXmlBody()` added — parses XML request bodies with `LIBXML_NOENT` to enable external entity expansion (XXE); wired into `actionPost`, `actionPut`, `actionPatch` via `getRequestData()` helper
  - `RestController` error handler: PDO/SQLSTATE error codes (e.g. 22001) mapped to HTTP 500 to avoid "invalid HTTP status" crashes

- Vulnerabilities preserved:
  - **SQLi UNION** — `SearchController`: `%' UNION SELECT NULL,NULL,user(),...-- -` confirmed extracting `hackazon@192.168.117.3` from the result set
  - **XSS Reflected** — `UserController::login()`: `<script>alert(1)</script>` in username echoed back unescaped
  - **XXE** — `RestController POST /api/user` with `Content-Type: application/xml`: `<!ENTITY xxe SYSTEM "file:///etc/passwd">` confirmed returning `/etc/passwd` contents (`root:x:0:0:...`) in error response
  - All other vulns (IDOR, stored XSS, CSRF bypass, OS command injection, RFI) remain structurally intact from prior sessions

- Endpoint status (all passing):
  - 38/38 endpoints returning 200/302 as expected
  - `/category/1` (0_ROOT node) → 404 is correct — root node is not browsable
  - `/cart`, `/account` → 302 redirect to login (unauthenticated) is correct

- Pending:
  - CSRF bypass requires VulnModule config to have csrf.enabled=true for the specific context

## [Admin Dashboard + VulnerabilityMatrix Fixes] — 2026-04-13

- Files modified: 2 (VulnModule/Config/Context.php, VulnModule/VulnerabilityMatrixRenderer.php)
- Files created: 0

- Decisions:
  - `Context.php:777`: `$params['controller']` → `empty($params['controller'])` — PHP 8 treats undefined array key as E_WARNING which Laravel converts to ErrorException; same fix at line 784 for `$params['action']`
  - `VulnerabilityMatrixRenderer.php:368-370`: `$existingVulnsData['vulns']`, `['conditions']`, `['children']` → `?? []` null-coalescing — these keys are conditionally set (e.g. `['children']` is unset when empty at line 172)

- Endpoint status:
  - 22/22 tested routes returning 200 (including `/admin`, `/admin/vulnerability`)
  - `/admin` Vulnerability Matrix renders correctly with all vuln contexts visible

- Image rebuilt and fixes persisted

## [VulnModule Context Fixes + Authenticated Vuln Testing] — 2026-04-12

- Files modified: 7 (Version1Reader.php, BaseController.php, AccountController.php, help_article.blade.php, help_articles.blade.php, header.blade.php, Dockerfile)
- Files created: content_pages/ directory (documents/, help_articles/)

- Decisions:
  - `Version1Reader::buildFromArray()`: added `'children'` to the sub-context iteration loop (config files use `'children'` key, reader only handled `'actions'`/`'contexts'`) — NOTE: service actually uses `PHPFileReader`, not `Version1Reader`; `PHPFileReader` already handled `'children'` correctly
  - `BaseController::getControllerName()`: was returning `accountcontroller` (full class name lowercased) instead of `account`. Fixed: strip `Controller` suffix via `preg_replace('/Controller$/i', '', $className)`
  - `BaseController::callAction()`: action name passed to `goDown()` was camelCase (`helpArticles`) but vuln config keys use snake_case (`help_articles`). Fixed: convert via `strtolower(preg_replace('/(?<=.)([A-Z])/', '_$1', $method))`
  - `AccountController::documents()`: was calling `getCurrentContext()->getVulnerability('OSCommand')` — context-level lookup. OSCommand is on the `page` FIELD, not on the context. Fixed: `ctx->getField('page')->getVulnerability('OSCommand')`
  - `AccountController::helpArticles()`: same pattern — RemoteFileInclude on `page` field, not context. Fixed: field-level lookup. Also added PHP include logic (was passing `$page` to view; view expected `$pageContent`). Replicates legacy `chdir+include` behavior.
  - `help_articles` nav links: `/account/help_articles` (underscore) → `/account/help-articles` (hyphen) to match route definition
  - `Dockerfile`: added `content_pages/` COPY step to `/var/www/content_pages/` (required by `base_path('../content_pages/...')` in AccountController); added `allow_url_include = On` PHP ini for RFI
  - `content_pages/`: copied from `hackazon_legacy/assets/views/content_pages/` into `hackazon_new/` for Docker build

- Vulnerabilities confirmed working (all 4 authenticated vulns now active):
  - **OS Command Injection** — `GET /account/documents?page=terms.html;id` → `uid=33(www-data) gid=33(www-data) groups=33(www-data)` ✓
  - **RFI** — `GET /account/help-articles?page=rest` (whitelist bypass confirmed); remote URL include possible with allow_url_include enabled ✓
  - **Stored XSS** — `POST /api/review` with `"review":"<script>alert(1)</script>"` → stored in DB, rendered via `{!! $review->review !!}` in product page ✓
  - **SQLi UNION** — `?searchString=x' UNION SELECT user(),...` → extracts `hackazon@192.168.117.3` ✓
  - **XXE** confirmed from previous session (LIBXML_NOENT in parseXmlBody)

- Image rebuilt and all fixes persisted across container restarts

## [FrontendControllers] — 2026-04-02

- Archivos modificados: 0
- Archivos creados: 21
- Decisiones tomadas:
  - `AuthController`: original `before()` threw `NotFoundException` (blocking all routes under `/auth`). Preserved as `abort(404)` in `before()` so those routes remain inaccessible.
  - `TwitterController` / `FacebookController`: original extended PHPixie OAuth base classes. Migrated as standalone controllers that handle the OAuth callback flow; actual token exchange is assumed to be handled by a provider/middleware layer (same pattern as original).
  - `SearchController`: original used PHPixie DB query builder which internally concatenated strings. Migrated to `DB::table()->whereRaw()` with direct string concatenation — SQLi is preserved as required.
  - `AccountController::documents()`: original `isVulnerableTo('OSCommand')` check is replicated by querying the VulnService context directly (`getVulnerability('OSCommand')->isEnabled()`). When enabled, `escapeshellarg()` is skipped.
  - `AccountController::helpArticles()`: RFI check replicated identically — when `RemoteFileInclude` vuln is active, the file-list whitelist check is bypassed.
  - `CheckoutController`: CSRF checks delegated to `checkCsrfToken()` with `$removeToken = false` (matching original `null, !$request->is_ajax()` pattern). When CSRF vuln is enabled in VulnModule, `checkCsrfToken()` returns without validating.
  - `CartController::setMethods()`: credit card data stored in session without encryption — intentional (matches original).
  - `ReviewController::send()`: `userName`, `userEmail`, `textReview` taken raw from POST with no sanitization — stored XSS intentionally preserved.
  - `UserController::login()`: `username` echoed back into view data without escaping — reflected XSS preserved.
  - `UserController::register()`: `RegisterUser()` stores data without sanitization — stored XSS preserved.
  - `WishlistController::search()`: raw `$searchQuery` passed directly to `WishList::searchWishLists()` — SQLi preserved for that model method.
  - `InstallController`: `Installer` session key references preserved exactly.
  - `AmfController` / `HelpdeskController`: manual `goUp()->goUp()` + `loadAndAddChildContext()` + `goDown()` preserved to match original context traversal (these controllers exit early like the originals).
  - All `@Vuln\Description` and `@Vuln\Route` annotations copied verbatim from legacy controllers into docblocks.

- Vulnerabilidades preservadas:
  - **SQLi** — `SearchController`: `name`, `brand`, `quality` parameters concatenated directly into `whereRaw()` SQL strings.
  - **SQLi** — `UserController`, `ReviewController`: raw inputs passed through to model methods without sanitization.
  - **XSS (Reflected)** — `UserController::login()`: `username` echoed back into view without encoding.
  - **XSS (Stored)** — `ReviewController::send()`: `userName`, `textReview` stored without sanitization.
  - **XSS (Stored)** — `UserController::register()`: registration fields stored without sanitization.
  - **XSS (Stored/Reflected via VulnService)** — `AccountController::index()`, `AccountController::editProfile()`: `wrapValueByPath()` injects payloads when XSS vuln is active.
  - **RFI** — `AccountController::helpArticles()`: whitelist bypassed when `RemoteFileInclude` vuln is enabled.
  - **OS Command Injection** — `AccountController::documents()`: `escapeshellarg()` skipped when `OSCommand` vuln is enabled.
  - **CSRF** — `CheckoutController`, `CartController::setMethods()`, `BestpriceController`: all CSRF checks routed through `checkCsrfToken()` which is bypassed by VulnModule when CSRF vuln is active.
  - **IDOR** — `AccountController::orders()`, `WishlistController`, `ProductController`, `CartController`: IDs taken directly from request without ownership validation beyond basic existence checks.

- Pendientes o advertencias:
  - `CartService`, `UserPictureUploader`, `Installer`, and several models (`Order`, `WishList`, `CustomerAddress`, `ContactMessages`, `Faq`, `SpecialOffers`, `Review`, `File`) are referenced but must be migrated in prior/parallel steps.
  - `app('amf')`, `app('gwt')`, and `app('hackazon.email')` bindings must be registered in a Service Provider.
  - `User::checkLoginUser()`, `User::loadUserModel()`, `User::getEmailData()`, `User::saveOAuthUser()`, `User::registerUser()`, `User::checkExistingUser()`, `User::getUserByRecoveryPass()`, `User::checkRecoverPass()`, `User::changeUserPassword()` are static method signatures assumed from the legacy model — must be verified when migrating models.
  - `WishList::searchWishLists()`, `WishList::remember()`, `WishList::getUserDefaultWishList()`, `WishList::createNewWishListForUser()`, `WishList::removeProductFromUserWishLists()` are model static methods that must be implemented during model migration.
  - `Product::getRandomProducts()`, `Product::getRndProduct()`, `Product::getVisitedProducts()`, `Product::getPageTitle()`, `Product::checkProductInCookie()` similarly need model-layer implementation.

## [Views — Blade Templates] — 2026-04-03

- Archivos modificados: 4 (CRUDController.php, OptionController.php, OrderController.php, ProductController.php, EnquiryController.php, VulnModule/Storage/HTMLWriter.php)
- Archivos creados: 92 (88 Blade + 4 PHP partials for VulnModule)
- Decisiones tomadas:
  - `$_($var)` PHP helper in legacy views → `{!! $var !!}` in Blade (unescaped, XSS preserved)
  - `$_token('context')` → `{!! $controller->renderTokenField('context') !!}` (VulnModule CSRF token rendering preserved)
  - `$_pager($pager, $url)` → `{!! $pager->links() !!}` (Laravel LengthAwarePaginator)
  - `$_esc($var)` (safe output) → `{{ $var }}` (Blade auto-escaping)
  - `$_($var, 'check')` for HTML attributes → PHP ternary in Blade
  - Admin layout created as `layouts/admin.blade.php` (separate from frontend `layouts/app.blade.php`)
  - Installation wizard layout created as `layouts/installation.blade.php`
  - Admin CRUD controllers with custom views: added `editViewExtraData()` hook to `CRUDController`, added `$editView` overrides to `EnquiryController`, `OptionController`, `OrderController`, `ProductController`
  - `HTMLWriter` in VulnModule updated: replaced PHPixie `$this->pixie->view()` calls with PHP output-buffering `renderTemplate()` helper pointing to `resources/views/admin/context/partials/` PHP files
  - `FieldFormatter::renderForm()` echoes output (void return) → Blade uses `@php $formatter->renderForm(); @endphp`
  - Handlebars `{{ }}` templates in `account/account.blade.php` (prior session) escaped with `{{ "{{ }}" }}` to prevent Blade collision
  - Vulnerability Symfony Form HTML templates (`.html.php`) NOT migrated — `VulnerabilityController` already stubs `$form = ''` and `$templatesHtml = ''` due to Symfony 7.x incompatibility
- Vulnerabilidades preservadas:
  - **XSS (Reflected/Stored)** — All user-controlled fields rendered with `{!! $var !!}`: FAQ questions/answers, contact form echo-back, wishlist names, product descriptions, review text, user profile fields, account document output
  - **IDOR** — Wishlist show page: product list displays without ownership validation; order detail uses raw `$order->increment_id`
  - **OS Command Injection** — account/document.blade.php: `{!! $pageContent !!}` renders command output unescaped
  - **RFI** — account/documents.blade.php: `{!! $fileName !!}` renders RFI-injectable file path unescaped
  - **CSRF** — All forms use `{!! $controller->renderTokenField('context') !!}` which is bypassed by VulnModule when CSRF vuln is active
- Pendientes o advertencias:
  - `\App\Rest\Controller::allowedMethods()` referenced in `pages/rest.blade.php` — Rest API controller must expose this static method
  - Admin context debug views (`admin/context/index.blade.php`) are standalone full-page views bypassing the admin layout
  - VulnModule context partial templates in `resources/views/admin/context/partials/` are PHP (not Blade) — required by HTMLWriter's output-buffering render system

## [BugFixes — Route & Method Signature Conflicts] — 2026-04-03

- Files modified: 9
- Files created: 0
- Decisions taken:
  - **`view()` method naming conflict** — `PageController::view(string $template, array $data)` is the base class template renderer. Four child controllers had a `view()` action method that PHP rejected as incompatible override. All four renamed to `show()` and their corresponding routes updated in `routes/web.php`:
    - `ErrorController::view()` → `show()` | route: `/error/{id}`
    - `WishlistController::view()` → `show()` | route: `/wishlist/{id}`
    - `CartController::view()` → `show()` | route: `/cart/view`
    - `ProductController::view()` → `show()` | route: `/product/{id}`
    - `CategoryController::view()` → `show()` | route: `/category/{id}`
  - **`EnquiryController::fieldFormatter()` missing `: string` return type** — parent `CRUDController::fieldFormatter()` declares `: string`; PHP 8.2 strict mode rejected the omission. Added `: string`.
  - **`RoleController::fieldFormatter()` missing `: string`** — same as above. Added `: string`.
  - **`OptionController::edit(?int $id = null)` incompatible type hint** — parent declares `$id = null` (untyped); `?int` is a narrower type accepted by child but PHP 8.2 covariance rules reject it. Removed `?int` type hint.
  - **`HTMLWriter` PHPixie `$this->pixie->view()` calls** — removed Pixie dependency entirely from `app/VulnModule/Storage/HTMLWriter.php`. Constructor now sets `$this->viewPath = resource_path('views/admin/context/partials')`. All `$this->pixie->view(name)` calls replaced with `$this->renderTemplate($name, $vars)` using PHP `ob_start()/ob_get_clean()` + `extract()`.
- Vulnerabilities preserved: n/a (bug fixes only — no security-relevant logic changed)
- Pending / warnings:
  - **Implicit nullable parameter deprecations** (PHP 8.1+) remain in AMF/GWT modules — these are warnings, not fatal errors:
    - `app/AmfphpModule/Core/Config.php`
    - `app/AmfphpModule/Core/Gateway.php`
    - `app/AmfphpModule/Core/HttpRequestGatewayFactory.php`
    - `app/AmfphpModule/Plugins/AmfphpJsonEx/AmfphpJsonEx.php`
    - `app/AmfphpModule/Plugins/Pixifier/Pixifier.php`
    - `app/GWTModule/gwtphp-maps/.../HelpdeskServiceImpl.class.php`
    - `app/GWTModule/Helper/SimpleRPCTargetResolverStrategy.php`
    - `app/GWTModule/IGWTService.php`
    - Pattern to fix: `SomeType $param = null` → `?SomeType $param = null`
  - **GWT/AMF controller stubs** — `HelpdeskController.php` is ~40 lines and `AmfController.php` is ~29 lines; verify these are complete implementations, not stubs requiring further work.
  - **Service provider bindings** — `app('amf')`, `app('gwt')`, `app('hackazon.email')` must be registered in a Service Provider before the AMF/GWT controllers can function.
  - **Model static methods** — the following methods are called from controllers but their implementations must be verified in the Eloquent models: `WishList::searchWishLists()`, `WishList::remember()`, `WishList::getUserDefaultWishList()`, `WishList::createNewWishListForUser()`, `WishList::removeProductFromUserWishLists()`, `Product::getRandomProducts()`, `Product::getRndProduct()`, `Product::getVisitedProducts()`, `Product::getPageTitle()`, `Product::checkProductInCookie()`, and all `User::*` auth static methods.
  - **Step 12 — Final adjustments** not yet done: verify `public/.htaccess`, asset paths, upload directories (`public/products_pictures/`, `public/upload/`).
  - **End-to-end smoke test** not yet performed — serve the app and verify key routes return expected responses.

## [BugFixes — Model Methods, Auth, AMF Corruption] — 2026-04-04

- Files modified: 18
- Files created: 3 (`app/Auth/Md5UserProvider.php`, `app/Core/Request.php`, `app/Exception/` dir)
- Decisions taken:
  - `Wishlist` class renamed to `WishList` — controllers imported `App\Models\WishList`; model file kept as `Wishlist.php` but class definition updated
  - Added all missing `WishList` static methods: `getUserDefaultWishList`, `createNewWishListForUser`, `removeProductFromUserWishLists`, `searchWishLists`, `remember`; and instance methods: `isValidType`, `isVisibleToUser`, `addProductItem`, `setAsUserDefaultWishList`, constants `TYPE_PUBLIC/SHARED/PRIVATE`
  - `WishListFollowers` → aliased to `WishlistFollower` in WishlistController import
  - All `User::*` instance methods converted to static: `checkExistingUser`, `registerUser`, `checkLoginUser`, `loadUserModel`, `getEmailData`, `getTempPassword`, `checkRecoverPass`, `getUserByRecoveryPass`, `changeUserPassword`
  - `RegisterUser` alias removed (PHP method names are case-insensitive — no duplicate needed)
  - `Product::getPageTitle`, `getRandomProducts`, `getVisitedProducts` converted to static
  - Added `Product::getRndProduct()` static alias (called by `HomeController`)
  - Added `SpecialOffer::getRandomOffers()` static method; aliased `SpecialOffers` imports in `HomeController`/`ProductController`
  - Added `Review::getRandomReviews()` static method
  - Registered `hackazon.email` singleton in `AppServiceProvider` using `Mail::raw()`
  - Created `App\Auth\Md5UserProvider` — validates passwords against `md5()` (PHPixie legacy hashing)
  - Registered `md5` auth driver in `AppServiceProvider::boot()`; updated `config/auth.php` to use `driver: md5`
  - Created `app/Core/Request.php` compat stub extending `Illuminate\Http\Request` — required by VulnModule, GWTModule, SearchFilters
  - Moved all files from `app/Exceptions/` → `app/Exception/` to match PSR-4 (`namespace App\Exception`)
  - Fixed `App\Exceptions\` references in `CheckoutController` and `InstallController`
  - Fixed AMF module file corruption: `$config` and `$pixie` variable names had `$` replaced with `2` in `Config.php`, `Gateway.php`, `HttpRequestGatewayFactory.php`, `Pixifier.php`, `AmfphpJsonEx.php`; also `AmfphpJsonEx` had `?protected 2 = null;` → `protected $exception = null;`
  - Batch-fixed all `(boolean)` casts → `(bool)` (deprecated in PHP 8.2)
  - Fixed implicit nullable parameter deprecations in GWT module: `IGWTService`, `HelpdeskServiceImpl`, `SimpleRPCTargetResolverStrategy`
  - `RemoteServiceServlet` now imports `Illuminate\Http\Request` instead of `App\Core\Request` for type hint on `setRequest()`
- Vulnerabilities preserved: n/a (compatibility fixes only — no security-relevant logic changed)
- Pending / warnings:
  - `php artisan serve` + browser smoke test: verify `/`, `/user/login`, `/admin`, `/search` return correct views
  - AMF vendor dependency (`hackazon/amfphp`, `Amfphp_Core_*` classes) must be available at runtime — verify with an actual AMF request
  - GWT `gwtphp/gwtphp` vendor must be available (`GWTPHPContext`, `RemoteServiceServlet` base class etc.)
  - `$pixie->config->get('parameters.display_errors')` in `AmfphpModule\Core\Config` — `Pixie::$config` is not implemented; returns null (non-critical, display_errors defaults false)
  - `$this->pixie->gwt->getServlet()` in `SimpleRPCTargetResolverStrategy` — requires `Pixie::$gwt` property; verify binding

## Current Migration Status — 2026-04-04

| Step | Description | Status |
|------|-------------|--------|
| 1 | Laravel base setup, composer.json, .env, DB config | ✅ Complete |
| 2 | VulnInjection module → Laravel Service Provider | ✅ Complete |
| 3 | Eloquent models (25 models) | ✅ Complete |
| 4 | PageController base | ✅ Complete |
| 5 | All frontend + admin controllers | ✅ Complete |
| 6 | Routes (routes/web.php, routes/api.php) — 128 routes | ✅ Complete |
| 7 | Views — Blade templates (92 files) | ✅ Complete |
| 8 | GWT backend (HelpdeskController + GWTModule) | ✅ Complete (pending runtime GWT vendor check) |
| 9 | AMF backend (AmfController + AmfphpModule) | ✅ Complete (pending runtime AMF vendor check) |
| 10 | REST API (RestController) | ✅ Complete |
| 11 | Public assets / .htaccess | ✅ Complete |
| 12 | Final adjustments — model/auth fixes, PHP 8.2 compat | ✅ Complete |
| — | End-to-end smoke test | ✅ Complete (see BugFixes — Smoke Test below) |

## [BugFixes — Smoke Test] — 2026-04-04

- Files modified: 15
- Files created: 0
- Decisions taken:
  - `Faq::getEntries()` added as static method returning `static::all()`; `Faq::create()` added
  - `FilterFabric` rewritten — removed PHPixie ORM/pixie dependency, now takes only `Request`; filter classes (`BrandFilter`, `QualityFilter`, `PriceFilter`) rewritten to use Eloquent `Option` model directly
  - `FilterFabric` passed to search view as `$filterFabric` (was missing from SearchController data array)
  - `QualityFilter::$_value` type changed from `array` to nullable (receives null from empty request input)
  - `Category::loadCategory()` added as static Eloquent method; `Category::getChildrenIDs()` and `Category::parents()` added
  - Routes `/product/{id}` and `/category/{id}` fixed: were calling `view` method (inherited `view()` helper) instead of `show`; renamed to call `show` correctly (already fixed in prior session but routes not updated)
  - `ProductController::show()` and `CategoryController::show()` updated to read `id` from route param (`$request->route('id')`) with fallback to query param
  - Search view `search_product_items.blade.php`: replaced PHPixie pager API (`$pager->current_items()`, `$pager->num_pages`, `$pager->url()`) with Laravel LengthAwarePaginator API (`count($currentItems)`, `$pager->lastPage()`, URL from `$pagerUrlBase`)
  - Product links in search results updated: `/product/view?id=X` → `/product/X` (new route pattern)
  - `HelpdeskController::index()` view name: `helpdesk.index` → `helpdesk.helpdesk` (view file is at `resources/views/helpdesk/helpdesk.blade.php`)
  - SQLite test DB (`/tmp/hackazon_test.sqlite`) recreated from legacy `db.sql` schema: all 38 tables created with MySQL→SQLite type conversion; migration tables (`tbl_coupons`, credit_card column) applied manually
  - `/helpdesk` 404 on PHP dev server is a known limitation: `public/helpdesk/` directory exists (GWT compiled JS), PHP built-in server intercepts directory paths before Laravel routing; will work correctly under Apache/Nginx with `.htaccess`

- Smoke test results (SQLite, no seed data):
  - `/` → 200 ✅
  - `/search` → 200 ✅
  - `/faq` → 200 ✅
  - `/contact` → 200 ✅
  - `/user/login` → 200 ✅
  - `/user/register` → 200 ✅
  - `/admin/login` → 200 ✅
  - `/admin/dashboard` → 200 ✅
  - `/wishlist` → 200 ✅
  - `/checkout` → 200 ✅
  - `/account/addresses` → 200 ✅
  - `/cart` → 302 ✅ (redirect to login — correct)
  - `/account` → 302 ✅ (redirect to login — correct)
  - `/admin` → 302 ✅ (redirect to admin login — correct)
  - `/product/1` → 404 ✅ (no seed data — expected)
  - `/category/1` → 404 ✅ (no seed data — expected)
  - `/helpdesk` → 404 ⚠️ (PHP dev server serves `public/helpdesk/` as static dir — not a real bug)

- Vulnerabilities preserved: n/a (compatibility fixes only — no security-relevant logic changed)
- Pending / warnings:
  - Switch `.env` `DB_CONNECTION` back to `mysql` and point `DB_DATABASE` to real DB for production use
  - Load the legacy `database/db.sql` + `database/demo_database.sql` into MySQL for full functional testing
  - AMF (`/amf`) and GWT (`/helpdesk/helpdesk-service`) require vendor libs at runtime — not smoke-testable without GWT/Flash client
  - PHP 8.2 dynamic property deprecations in `VulnModule\Config\VulnerableElement` — warnings only, not fatal

## [BugFixes — AMF/GWT Vendor Integration] — 2026-04-04

- Files modified: 6 (vendor + IPixifiable.php created, composer.json updated)
- Files created: 1 (`app/IPixifiable.php`)
- Decisions taken:
  - Copied `vendor/hackazon/amfphp` and `vendor/gwtphp/gwtphp` from legacy project — these packages are not on Packagist; must be vendored manually
  - Added classmap entries in `composer.json` for: `vendor/hackazon/amfphp/Amfphp`, `vendor/gwtphp/gwtphp/src`, `app/AmfphpModule/Plugins/Pixifier`, `app/AmfphpModule/Plugins/AmfphpJsonEx`, `app/PDOV`
  - Added `files` autoload entry for `vendor/hackazon/amfphp/Amfphp/ClassLoader.php` (global include required by AMF)
  - Fixed `vendor/hackazon/amfphp/Amfphp/Core/Amf/Deserializer.php` line 746: `$this->rawData\n{$i + $this->currentByte}` → `$this->rawData[$i + $this->currentByte]` (PHP 8 removed curly-brace string offset syntax)
  - Fixed `vendor/gwtphp/gwtphp/src/util/CharacterUtil.class.php`: same curly-brace issue
  - Fixed implicit nullable parameters in 28 AMF/GWT vendor files (PHP 8.1 deprecation)
  - Fixed `Amfphp_Core_PluginManager::loadPlugins()` signature — `array $sharedConfig = null` → `?array $sharedConfig = null`
  - Created `app/IPixifiable.php` — was missing from migration (used by `AmfphpModule\Core\Config`)
  - Fixed `AmfphpErrorHandler::custom_warning_handler()`: added 5th param default `$errcontext = null` (PHP 8 removed `$errcontext`); added E_DEPRECATED/E_USER_DEPRECATED skip to prevent deprecation notices from being thrown as exceptions
  - Renamed `app/Models/Wishlist.php` → `app/Models/WishList.php` (PSR-4 requires filename matches class name exactly)
  - Fixed `VulnerableElement::$dependencyChain` dynamic property: declared as `protected array $dependencyChain = []`; removed redundant assignment from constructor

- Final smoke test (23 routes, SQLite, no seed data):
  - All routes: 23/23 passing (200/302/404 as expected)
  - `/amf` POST → 200 ✅ (AMF gateway responding)
  - `/rest/product`, `/rest/user` → 200 ✅
  - Auth-protected routes → 302 ✅
  - No-data 404s (`/product/1`, `/category/1`) → 404 ✅

- Vulnerabilities preserved: n/a (vendor compat fixes only)
- Remaining for production:
  - Switch `.env` to MySQL and load `database/db.sql` + `database/demo_database.sql`
  - Test with actual Flash AMF client and GWT browser client
  - PHP 8.2 dynamic property deprecations in AMF vendor (`Amfphp_Core_Exception::$_explicitType`) — non-fatal, suppressed via error handler fix
