<?php

namespace App\Http\Controllers;

use App\Models\Cart as CartModel;
use App\Services\CartService;
use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class CartController
 * @package App\Http\Controllers
 * @Vuln\Description("Cart operations.")
 */
class CartController extends PageController
{
    /**
     * Show overview page — redirect to view.
     */
    public function index()
    {
        return redirect('/cart/view');
    }

    /**
     * Add product to cart.
     * @Vuln\Description("No views used. It's AJAX method.")
     */
    public function add(Request $request)
    {
        $ids = [];
        if ($request->ajax()) {
            $ids = $this->getProductsInCartIds();
        }

        // qty validation mirrors original — no sanitization beyond numeric check
        $qty = $request->input('qty', 1);
        if (is_numeric($qty)) {
            if ($qty > 1000) {
                $qty = 1000;
            } elseif ($qty <= 0) {
                $qty = 1;
            }
        }

        $productId = $request->input('product_id');

        /** @var CartService $cartService */
        $cartService = app(CartService::class);
        $result = $cartService->addProductWithResult($productId, $qty);

        if ($request->ajax()) {
            return response()->json([
                'success'    => 1,
                'productId'  => $productId,
                'newProduct' => !in_array($productId, $ids),
                'product'    => [
                    'productID' => $result['product']->productID,
                    'name'      => $result['product']->name,
                    'Price'     => $result['product']->Price,
                ],
                'item'       => [
                    'id'    => $result['item']->id,
                    'qty'   => $result['item']->qty,
                    'price' => $result['item']->price,
                ],
            ]);
        }

        return redirect('/cart/view');
    }

    /**
     * Show cart overview.
     * @Vuln\Description("View: cart/view")
     */
    public function show(Request $request)
    {
        /** @var CartService $cartService */
        $cartService = app(CartService::class);
        $cart  = $cartService->getCart();
        $items = $cartService->getItems();

        return $this->view('cart.view', [
            'items'            => $items,
            'creditCardNumber' => $cartService->getParam('credit_card_number', ''),
            'creditCardYear'   => $cartService->getParam('credit_card_year', ''),
            'creditCardMonth'  => $cartService->getParam('credit_card_month', ''),
            'creditCardCVV'    => $cartService->getParam('credit_card_cvv', ''),
            'paymentMethod'    => $cart->payment_method,
            'shippingMethod'   => $cart->shipping_method,
            'itemQty'          => $cartService->count(),
            'totalPrice'       => $cartService->getTotalPrice(),
            'tab'              => 'overview',
            'coupon'           => $cartService->getCoupon(),
            'step'             => $cartService->getStepLabel(),
        ]);
    }

    /**
     * Update cart item quantity.
     * @Vuln\Description("No view. It's AJAX method.")
     */
    public function update(Request $request)
    {
        $quantity  = $request->input('qty');
        $productId = $request->input('productId');

        /** @var CartService $cartService */
        $cartService = app(CartService::class);
        $cartService->setProductCount($productId, $quantity);

        return response()->json([
            'items_qty'   => $cartService->count(),
            'total_price' => $cartService->getTotalPrice(),
        ]);
    }

    /**
     * Clear the cart.
     * @Vuln\Description("No view. It's AJAX method.")
     */
    public function empty(Request $request)
    {
        app(CartService::class)->clear();
        return response('', 204);
    }

    /**
     * Set shipping and payment methods.
     * @Vuln\Description("No view.")
     */
    public function setMethods(Request $request)
    {
        // CSRF check is VulnModule-aware; CSRF vuln intentionally preserved when enabled
        $this->checkCsrfToken('checkout_step_1', null, !$request->ajax());

        /** @var CartService $cartService */
        $cartService = app(CartService::class);
        $cart = $cartService->getCart();

        $cart->shipping_method = $request->input('shipping_method');
        $cart->payment_method  = $request->input('payment_method');

        if ($cart->payment_method == 'creditcard') {
            // Credit card data stored in session without encryption — intentional
            $cartService->setParam('credit_card_number', $request->input('credit_card_number'));
            $cartService->setParam('credit_card_year',   $request->input('credit_card_year'));
            $cartService->setParam('credit_card_month',  $request->input('credit_card_month'));
            $cartService->setParam('credit_card_cvv',    $request->input('credit_card_cvv'));
        }

        $cartService->updateLastStep(CartModel::STEP_SHIPPING);

        return response('', 204);
    }

    /**
     * Returns current product IDs in the cart session.
     */
    protected function getProductsInCartIds(): array
    {
        return app(CartService::class)->getProductIds();
    }
}
