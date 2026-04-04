<?php

namespace App\Http\Controllers;

use App\Models\Product as ProductModel;
use App\Models\User as UserModel;
use App\Models\WishList;
use App\Models\WishlistFollower as WishListFollowers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class WishlistController
 * @package App\Http\Controllers
 */
class WishlistController extends PageController
{
    /** @var UserModel|null */
    private ?UserModel $user = null;

    /**
     * Either shows empty page if user has no lists, or shows the default list.
     * @Vuln\Description("View: wishlist/no_list.")
     */
    public function index(Request $request)
    {
        $this->prepare();

        if ($this->user == null) {
            return $this->view('wishlist.no_list', ['pageTitle' => 'Wish List']);
        }

        $wishList = WishList::getUserDefaultWishList($this->user);

        if ($wishList) {
            return $this->showDefaultWishList($wishList);
        }

        return $this->view('wishlist.no_list', ['pageTitle' => 'Wish List', 'user' => $this->user]);
    }

    /**
     * View wish list.
     * @Vuln\Route(params={"id": "_id_"})
     * @Vuln\Description("View: wishlist/show.")
     */
    public function show(Request $request)
    {
        $this->prepare();

        // $id taken directly — IDOR: visibility check is only via isVisibleToUser()
        $id = $request->route('id') ?? $request->query('id');

        $wishList = WishList::find($id);

        if (!$wishList) {
            abort(404);
        }

        if (!$wishList->isVisibleToUser($this->user)) {
            abort(404);
        }

        return $this->showWishList($request, $wishList);
    }

    /**
     * Create new wish list.
     * @Vuln\Route(name="wishlist_new")
     * @Vuln\Description("No view.")
     */
    public function newList(Request $request)
    {
        $this->prepare();

        if (!$this->user) {
            abort(403);
        }

        $name = $request->input('name', 'New Wish List');
        $type = $request->input('type', WishList::TYPE_PRIVATE);

        if (!$name || !$type) {
            if ($request->ajax()) {
                return response()->json(['error' => 1]);
            }
            abort(400, 'Invalid request');
        }

        // Check CSRF only if user already has wishlists (same logic as original)
        if ($this->user->wishlists()->count()) {
            $this->checkCsrfToken('wishlist_add', null, !$request->ajax());
        }

        $wishList = WishList::createNewWishListForUser($this->user, $name, $type);

        if ($request->ajax()) {
            return response()->json(['success' => 1, 'id' => $wishList->id]);
        }

        return redirect('/wishlist');
    }

    /**
     * Edit wish list.
     * @Vuln\Route(params={"id": "_id_"})
     * @Vuln\Description("No view.")
     */
    public function edit(Request $request)
    {
        $this->prepare();

        if (!$this->user) {
            abort(403);
        }

        if ($request->method() !== 'POST') {
            return redirect('/wishlist');
        }

        $id   = $request->route('id') ?? $request->query('id');
        $name = $request->input('name', 'New Wish List');
        $type = $request->input('type', WishList::TYPE_PRIVATE);

        $wishList = $this->getWishList($id);

        if (!$wishList) {
            abort(404);
        }

        if (!$wishList->isValidType($type)) {
            if ($request->ajax()) {
                return response()->json(['error' => 1, 'message' => 'Invalid "type" parameter.']);
            }
            abort(400, 'Invalid wishlist type');
        }

        $wishList->name = $name ?: $wishList->name;
        $wishList->type = $type;
        $wishList->save();

        if ($request->ajax()) {
            return response()->json(['success' => 1, 'id' => $wishList->id]);
        }

        return redirect('/wishlist');
    }

    /**
     * Set given wish list as default.
     * @Vuln\Description("No view.")
     */
    public function setDefault(Request $request)
    {
        $this->prepare();

        if ($request->method() !== 'POST') {
            abort(400);
        }

        if (!$this->user) {
            abort(403);
        }

        $id = $request->input('id');

        if (!$id) {
            abort(404, 'Missing wishlist id.');
        }

        $wishList = $this->getWishList($id);

        // IDOR: only checks owner id, but $id comes raw from POST
        if ($wishList->user_id != $this->user->id) {
            abort(404, 'Missing wishlist');
        }

        $wishList->setAsUserDefaultWishList($this->user);

        if ($request->ajax()) {
            return response()->json(['success' => 1]);
        }

        return redirect('/wishlist/view/' . $wishList->id);
    }

    /**
     * Add product to wish list.
     * @Vuln\Route(name = "wishlist_add_product", params={"id" : "_id_"})
     * @Vuln\Description("No view.")
     */
    public function addProduct(Request $request)
    {
        $this->prepare();

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        if (!$this->user) {
            abort(403);
        }

        // $productId raw from route — IDOR preserved
        $productId  = $request->route('id') ?? $request->query('id');
        $wishlistId = $request->input('wishlist_id');

        $product = ProductModel::find($productId);

        if (!$product) {
            return response()->json(['error' => 1, "Product with id={$productId} doesn't exist."]);
        }

        if ($product->isInUserWishList($this->user)) {
            return response()->json(['success' => 1, "Product with id={$productId} is in your wish list already."]);
        }

        if ($wishlistId) {
            $wishList = WishList::find($wishlistId);
        } else {
            $wishList = WishList::getUserDefaultWishList($this->user);
            if (!$wishList) {
                $wishList = WishList::createNewWishListForUser($this->user);
            }
        }

        if (!$wishList || $wishList->user_id != $this->user->id) {
            return response()->json(['error' => 1, 'You can add products only to your own wish lists.']);
        }

        $item = $wishList->addProductItem($product->id);

        return response()->json([
            'success' => 1,
            'id'      => $item->id,
            'message' => 'You have successfully added product into your wish list.',
        ]);
    }

    /**
     * Remove product from wish list.
     * @Vuln\Route(name = "wishlist_delete_product", params={"id": "_id_"})
     * @Vuln\Description("No view.")
     */
    public function deleteProduct(Request $request)
    {
        $this->prepare();

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        if (!$this->user) {
            abort(403);
        }

        $productId = $request->route('id') ?? $request->query('id');

        $product = ProductModel::find($productId);

        if (!$product) {
            return response()->json(['error' => 1, "Product with id={$productId} doesn't exist."]);
        }

        WishList::removeProductFromUserWishLists($this->user, $productId);

        return response()->json(['success' => 1]);
    }

    /**
     * Delete wish list.
     * @Vuln\Route(params={"id": "_id_"})
     * @Vuln\Description("No view.")
     */
    public function delete(Request $request)
    {
        $this->prepare();

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        if (!$this->user) {
            abort(403);
        }

        $id = $request->route('id') ?? $request->query('id');

        if (!$id) {
            abort(400, 'Missing wishlist id.');
        }

        $wishList = $this->getWishList($id);

        // IDOR: only ownership check — $id comes raw from POST
        if ($wishList->user_id != $this->user->id) {
            abort(404);
        }

        $this->checkCsrfToken('wishlist', $request->input('token'), !$request->ajax());

        $wishList->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 1]);
        }

        return redirect('/wishlist');
    }

    /**
     * Search users and wishlists by username or email.
     * @Vuln\Description("No view.")
     */
    public function search(Request $request)
    {
        // $searchQuery is raw — no sanitization (SQLi when vuln enabled)
        $searchQuery = $request->input('search');
        $result      = WishList::searchWishLists($searchQuery);

        return response()->json($result);
    }

    /**
     * @Vuln\Description("No view.")
     */
    public function remember(Request $request)
    {
        // $userId raw from POST — IDOR preserved
        $userId = $request->input('user_id');
        $result = WishList::remember($userId);

        if ($result) {
            return response()->json(['success' => 1]);
        }

        return response()->json([]);
    }

    /**
     * @Vuln\Description("No view.")
     */
    public function removeFollower(Request $request)
    {
        $user = Auth::user();

        /** @var WishListFollowers|null $item */
        $item = WishListFollowers::where('user_id', $user->id)
            ->where('follower_id', $request->input('follower_id'))
            ->first();

        if ($item) {
            $item->delete();
            return response()->json(['success' => 1]);
        }

        return response()->json(['success' => 0]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function prepare(): void
    {
        $this->user = Auth::user();
    }

    protected function showDefaultWishList(WishList $wishList)
    {
        if (!$wishList) {
            return $this->view('wishlist.no_list', [
                'showDefaultPage' => true,
                'pageTitle'       => 'Wish List',
            ]);
        }

        return $this->showWishList(request(), $wishList);
    }

    protected function showWishList(Request $request, WishList $wishList)
    {
        $page    = (int) $request->query('page', 1);
        $perPage = 9;

        $products     = $wishList->products()
            ->offset($perPage * ($page - 1))
            ->limit($perPage)
            ->get()
            ->all();
        $productCount = $wishList->products()->count();

        $data = [
            'pageTitle'    => 'Wish List',
            'page'         => $page,
            'perPage'      => $perPage,
            'wishList'     => $wishList,
            'products'     => $products,
            'productCount' => $productCount,
        ];

        if ($this->user) {
            $data['user'] = $this->user;
        }

        return $this->view('wishlist.show', $data);
    }

    public function getWishList($id): ?WishList
    {
        return WishList::where('id', '=', $id)->first();
    }
}
