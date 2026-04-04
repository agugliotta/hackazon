<?php

namespace App\Http\Controllers;

use App\Models\Cart as CartModel;
use App\Models\CustomerAddress;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class CheckoutController
 * @package App\Http\Controllers
 *
 * VULNERABILITY NOTE: CSRF tokens are checked via VulnModule — when CSRF vuln
 * is enabled, checkCsrfToken() is bypassed. No additional token enforcement added.
 */
class CheckoutController extends PageController
{
    /**
     * Validate step access restriction.
     * Redirects or aborts based on current cart step.
     */
    protected function restrictActions(Request $request, int $actionStep)
    {
        /** @var CartService $service */
        $service  = app(CartService::class);
        $lastStep = $service->getLastStep();

        if ($lastStep == CartModel::STEP_ORDER && $actionStep != CartModel::STEP_ORDER) {
            return redirect('/checkout/order');
        }

        if ($actionStep > $lastStep) {
            if ($request->ajax()) {
                abort(403);
            } else {
                return redirect('/cart/view');
            }
        }

        return null;
    }

    /**
     * Step 1 — Shipping address.
     * @Vuln\Description("View: cart/shipping. Or AJAX request.")
     */
    public function shipping(Request $request)
    {
        $redirect = $this->restrictActions($request, CartModel::STEP_SHIPPING);
        if ($redirect) {
            return $redirect;
        }

        /** @var CartService $service */
        $service           = app(CartService::class);
        $customerAddresses = $service->getAddresses();

        if ($request->ajax()) {
            // CSRF check is VulnModule-aware (CSRF vuln intentionally present)
            $this->checkCsrfToken('checkout_step2', null, false);

            $post      = $request->all();
            $addressId = !empty($post['address_id']) ? $post['address_id'] : 0;

            if (!empty($post['full_form'])) {
                $address = new CustomerAddress();
                $address->createFromArray($post);

                if ($addressId) {
                    $existingAddress = $this->getAddressForUid($addressId, $service);

                    if ($existingAddress && $existingAddress->isSimilarTo($address)) {
                        $service->setShippingAddressUid($addressId);
                        $service->setShippingAddress($existingAddress);
                    } else {
                        $service->setShippingAddressUid(null);
                        $service->setShippingAddress($address);
                    }
                } else {
                    $service->setShippingAddressUid(null);
                    $service->setShippingAddress($address);
                }
            } else {
                if ($addressId) {
                    $existingAddress = $this->getAddressForUid($addressId, $service);
                    if ($existingAddress) {
                        $service->setShippingAddressUid($addressId);
                        $service->setShippingAddress($existingAddress);
                    } else {
                        abort(404);
                    }
                } else {
                    abort(404);
                }
            }

            $service->updateLastStep(CartModel::STEP_BILLING);
            return response('', 204);

        } else {
            $this->prepareShippingAndBillingAddresses($service, $customerAddresses);
            $currentAddress = $service->getShippingAddress();

            return $this->view('cart.shipping', [
                'tab'              => 'shipping',
                'step'             => $service->getStepLabel(),
                'shippingAddress'  => !$currentAddress ? [] : $currentAddress->toArray(),
                'customerAddresses' => $customerAddresses,
            ]);
        }
    }

    /**
     * Step 2 — Billing address.
     * @Vuln\Description("View: cart/billing. Or AJAX request.")
     */
    public function billing(Request $request)
    {
        $redirect = $this->restrictActions($request, CartModel::STEP_BILLING);
        if ($redirect) {
            return $redirect;
        }

        /** @var CartService $service */
        $service           = app(CartService::class);
        $customerAddresses = $service->getAddresses();

        if ($request->ajax()) {
            // CSRF check is VulnModule-aware
            $this->checkCsrfToken('checkout_step3', null, false);

            $post      = $request->all();
            $addressId = !empty($post['address_id']) ? $post['address_id'] : 0;

            if (!empty($post['full_form'])) {
                $address = new CustomerAddress();
                $address->createFromArray($post);

                if ($addressId) {
                    $existingAddress = $this->getAddressForUid($addressId, $service);

                    if ($existingAddress && $existingAddress->isSimilarTo($address)) {
                        $service->setBillingAddressUid($addressId);
                        $service->setBillingAddress($existingAddress);
                    } else {
                        $service->setBillingAddressUid(null);
                        $service->setBillingAddress($address);
                    }
                } else {
                    $service->setBillingAddressUid(null);
                    $service->setBillingAddress($address);
                }
            } else {
                if ($addressId) {
                    $existingAddress = $this->getAddressForUid($addressId, $service);
                    if ($existingAddress) {
                        $service->setBillingAddressUid($addressId);
                        $service->setBillingAddress($existingAddress);
                    } else {
                        abort(404);
                    }
                } else {
                    abort(404);
                }
            }

            $service->updateLastStep(CartModel::STEP_CONFIRM);
            return response('', 204);

        } else {
            $this->prepareShippingAndBillingAddresses($service, $customerAddresses);
            $currentAddress = $service->getBillingAddress();

            return $this->view('cart.billing', [
                'tab'              => 'billing',
                'step'             => $service->getStepLabel(),
                'billingAddress'   => !$currentAddress ? [] : $currentAddress->toArray(),
                'customerAddresses' => $customerAddresses,
            ]);
        }
    }

    /**
     * AJAX — return CustomerAddress JSON by address id.
     * @Vuln\Description("No view. AJAX request for address.")
     */
    public function getAddress(Request $request)
    {
        /** @var CartService $service */
        $service   = app(CartService::class);
        $addressId = $request->input('address_id');
        $address   = $service->getAddress($addressId);

        return response()->json($address->toArray());
    }

    /**
     * Delete address by id.
     * @Vuln\Description("No view. AJAX request to delete address.")
     */
    public function deleteAddress(Request $request)
    {
        /** @var CartService $service */
        $service   = app(CartService::class);
        $addressId = $request->input('address_id');
        $service->removeAddress($addressId);

        return response('', 204);
    }

    /**
     * Step 3 — Confirmation.
     * @Vuln\Description("View: cart/confirmation.")
     */
    public function confirmation(Request $request)
    {
        $redirect = $this->restrictActions($request, CartModel::STEP_CONFIRM);
        if ($redirect) {
            return $redirect;
        }

        $this->checkCart();

        /** @var CartService $service */
        $service = app(CartService::class);

        return $this->view('cart.confirmation', [
            'tab'             => 'confirmation',
            'step'            => $service->getStepLabel(),
            'cart'            => $service->getCart(),
            'items'           => $service->getItems(),
            'shippingAddress' => $service->getShippingAddress(),
            'billingAddress'  => $service->getBillingAddress(),
            'totalPrice'      => $service->getTotalPrice(),
            'discount'        => $service->getDiscount(),
        ]);
    }

    /**
     * Place order (AJAX).
     * @Vuln\Description("No view.")
     *
     * VULNERABILITY NOTE: CSRF check is VulnModule-aware. When CSRF vuln is enabled
     * the token is not verified — CSRF intentionally preserved.
     */
    public function placeOrder(Request $request)
    {
        if (is_null(Auth::user())) {
            $location = '/user/login?return_url=' . rawurlencode('/checkout/confirmation');
            if ($request->ajax()) {
                return response()->json(['location' => $location]);
            }
            return redirect($location);
        }

        // CSRF check bypassed by VulnModule when CSRF vuln active
        $this->checkCsrfToken('checkout_step4', null, false);

        $redirect = $this->restrictActions($request, CartModel::STEP_CONFIRM);
        if ($redirect) {
            return $redirect;
        }

        $this->checkCart();

        /** @var CartService $service */
        $service = app(CartService::class);
        $service->placeOrder();

        if ($request->ajax()) {
            return response()->json(['success' => 1]);
        }

        return redirect('/checkout/order');
    }

    /**
     * Step 4 — Order success.
     * @Vuln\Description("View: cart/order")
     */
    public function order(Request $request)
    {
        $redirect = $this->restrictActions($request, CartModel::STEP_ORDER);
        if ($redirect) {
            return $redirect;
        }

        /** @var CartService $service */
        $service = app(CartService::class);
        $service->reset();

        return $this->view('cart.order', [
            'tab'  => 'order',
            'step' => $service->getStepLabel(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function prepareShippingAndBillingAddresses(CartService $service, array &$customerAddresses): void
    {
        if ($service->getShippingAddress() && !$service->getShippingAddress()->id) {
            $service->getShippingAddress()->setUid('_shipping_');
            array_unshift($customerAddresses, $service->getShippingAddress());
        }

        if ($service->getBillingAddress()
            && !$service->getBillingAddress()->id
            && !$service->getBillingAddress()->isSimilarTo($service->getShippingAddress())
        ) {
            $service->getBillingAddress()->setUid('_billing_');
            array_unshift($customerAddresses, $service->getBillingAddress());
        }
    }

    protected function getAddressForUid(string $addressId, CartService $service): ?CustomerAddress
    {
        if ($addressId === '_shipping_') {
            return $service->getShippingAddress();
        }

        if ($addressId === '_billing_') {
            return $service->getBillingAddress();
        }

        return $service->getAddress($addressId);
    }

    protected function checkCart(): void
    {
        /** @var CartService $service */
        $service = app(CartService::class);

        try {
            $service->checkCart();
        } catch (\App\Exception\RedirectException $e) {
            if ($e->getLocation()) {
                abort(redirect($e->getLocation())->getStatusCode());
            }
        }
    }
}
