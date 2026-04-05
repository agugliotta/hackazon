<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;

/**
 * Session-based cart service (migrated from SessionCartStorage + CartService).
 * All cart state is stored in Laravel session under 'cart_service'.
 */
class CartService
{
    private function &data(): array
    {
        if (!session()->has('cart_service')) {
            $this->reset();
        }
        $data = session('cart_service');
        return $data;
    }

    private function save(array $data): void
    {
        session(['cart_service' => $data]);
    }

    public function reset(): void
    {
        session(['cart_service' => [
            'cart'     => $this->makeCartArray(),
            'items'    => [],
            'params'   => [],
            'last_step' => Cart::STEP_OVERVIEW,
            'shipping_address' => null,
            'billing_address'  => null,
            'addresses'        => [],
            'removed_addresses' => [],
        ]]);
    }

    private function makeCartArray(): array
    {
        return [
            'uid'              => session()->getId(),
            'items_count'      => 0,
            'items_qty'        => 0,
            'payment_method'   => null,
            'shipping_method'  => null,
            'shipping_address_id' => 0,
            'billing_address_id'  => 0,
            'last_step'        => Cart::STEP_OVERVIEW,
        ];
    }

    // ── Cart object (acts as a fluent object via proxy) ────────────────────

    public function getCart(): CartProxy
    {
        $data = session('cart_service', []);
        if (empty($data)) { $this->reset(); $data = session('cart_service'); }
        return new CartProxy($data['cart'], function(array $cart) use ($data) {
            $data['cart'] = $cart;
            $this->save($data);
        });
    }

    // ── Items ──────────────────────────────────────────────────────────────

    public function getItems(): array
    {
        return session('cart_service.items', []);
    }

    public function count(): int
    {
        $qty = 0;
        foreach ($this->getItems() as $item) {
            $qty += (int)($item['qty'] ?? 1);
        }
        return $qty;
    }

    public function getProductIds(): array
    {
        return array_column($this->getItems(), 'product_id');
    }

    public function addProductWithResult($productId, $qty): array
    {
        $result = ['product' => null, 'item' => null];

        if (!is_numeric((string)$qty)) {
            return $result;
        }

        $product = Product::where('productID', $productId)->first();
        if (!$product) {
            return $result;
        }

        $data  = session('cart_service', []);
        if (empty($data)) { $this->reset(); $data = session('cart_service'); }
        $items = $data['items'];
        $found = false;

        foreach ($items as &$item) {
            if ((string)$item['product_id'] === (string)$productId) {
                $item['qty'] += (int)$qty;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $items[] = [
                'id'         => $productId,   // stable id (product_id) for JSON response
                'product_id' => $productId,
                'name'       => $product->name,
                'qty'        => (int)$qty,
                'price'      => $product->Price,
            ];
        }

        $data['items'] = $items;
        $data['cart']['items_qty'] = array_sum(array_column($items, 'qty'));
        $data['cart']['items_count'] = count($items);
        $this->save($data);

        // Find the stored item
        $storedItem = null;
        foreach ($items as $i) {
            if ((string)$i['product_id'] === (string)$productId) {
                $storedItem = (object)$i;
                break;
            }
        }

        $result['product'] = $product;
        $result['item']    = $storedItem;
        return $result;
    }

    public function setProductCount($productId, int $qty): void
    {
        $data  = session('cart_service', []);
        if (empty($data)) { $this->reset(); $data = session('cart_service'); }
        $items = $data['items'];

        if ($qty <= 0) {
            $items = array_values(array_filter($items, fn($i) => (string)$i['product_id'] !== (string)$productId));
        } else {
            $found = false;
            foreach ($items as &$item) {
                if ((string)$item['product_id'] === (string)$productId) {
                    $item['qty'] = $qty;
                    $found = true;
                    break;
                }
            }
            unset($item);
            if (!$found) {
                $product = Product::where('productID', $productId)->first();
                if ($product) {
                    $items[] = ['id' => $productId, 'product_id' => $productId, 'name' => $product->name, 'qty' => $qty, 'price' => $product->Price];
                }
            }
        }

        $data['items'] = $items;
        $data['cart']['items_qty']   = array_sum(array_column($items, 'qty'));
        $data['cart']['items_count'] = count($items);
        $this->save($data);
    }

    public function clear(): void
    {
        $data = session('cart_service', []);
        $data['items'] = [];
        $data['cart']['items_qty']   = 0;
        $data['cart']['items_count'] = 0;
        $this->save($data);
    }

    // ── Params ─────────────────────────────────────────────────────────────

    public function setParam(string $param, $value): void
    {
        $data = session('cart_service', []);
        $data['params'][$param] = $value;
        $this->save($data);
    }

    public function getParam(string $param, $default = null)
    {
        return session("cart_service.params.$param", $default);
    }

    public function hasParam(string $param): bool
    {
        return session()->has("cart_service.params.$param");
    }

    // ── Step ───────────────────────────────────────────────────────────────

    public function getLastStep(): int
    {
        return (int) session('cart_service.last_step', Cart::STEP_OVERVIEW);
    }

    public function setLastStep(int $step): void
    {
        $data = session('cart_service', []);
        $data['last_step'] = $step;
        $this->save($data);
    }

    public function updateLastStep(int $step): void
    {
        if ($step > $this->getLastStep()) {
            $this->setLastStep($step);
        }
    }

    public function getStepLabel(): string
    {
        return match ($this->getLastStep()) {
            Cart::STEP_SHIPPING    => 'shipping',
            Cart::STEP_BILLING     => 'billing',
            Cart::STEP_CONFIRM     => 'confirmation',
            Cart::STEP_ORDER       => 'order',
            default                => 'overview',
        };
    }

    // ── Coupon ─────────────────────────────────────────────────────────────

    public function getCoupon(): ?Coupon
    {
        $couponId = session('cart_service.params._coupon');
        if (!$couponId) return null;
        $coupon = Coupon::find($couponId);
        return $coupon ?: null;
    }

    // ── Total ──────────────────────────────────────────────────────────────

    public function getTotalPrice(): float
    {
        $total = 0.0;
        foreach ($this->getItems() as $item) {
            $qty = (int)($item['qty'] ?? 1);
            $total += (float)($item['price'] ?? 0) * $qty;
        }
        $coupon = $this->getCoupon();
        if ($coupon) {
            $total *= 1.0 - ($coupon->discount / 100);
        }
        return $total;
    }

    // ── Addresses ──────────────────────────────────────────────────────────

    public function getAddresses(): array
    {
        return session('cart_service.addresses', []);
    }

    public function getShippingAddress()
    {
        return session('cart_service.shipping_address');
    }

    public function getBillingAddress()
    {
        return session('cart_service.billing_address');
    }
}
