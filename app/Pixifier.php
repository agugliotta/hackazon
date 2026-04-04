<?php

namespace App;

/**
 * Singleton that holds the Pixie instance.
 * Provides compatibility with PHPixie's Pixifier pattern used in VulnModule.
 */
class Pixifier
{
    private static ?Pixifier $instance = null;
    private ?Pixie $pixie = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPixie(): ?Pixie
    {
        return $this->pixie;
    }

    public function setPixie(Pixie $pixie): void
    {
        $this->pixie = $pixie;
    }
}
