<?php

namespace App\Services;

/**
 * Wraps the cart array from session, persisting changes on property set.
 */
class CartProxy
{
    private array $data;
    private \Closure $persist;

    public function __construct(array $data, \Closure $persist)
    {
        $this->data    = $data;
        $this->persist = $persist;
    }

    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->data[$key] = $value;
        ($this->persist)($this->data);
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }
}
