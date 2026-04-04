<?php

namespace App\Providers;

use App\Pixie;
use Illuminate\Support\ServiceProvider;
use VulnModule\VulnInjection;
use VulnModule\VulnInjection\Service;

class VulnInjectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Pixie::class, function () {
            return new Pixie();
        });

        $this->app->singleton(VulnInjection::class, function ($app) {
            $pixie = $app->make(Pixie::class);
            $module = new VulnInjection($pixie);
            $pixie->vulninjection = $module;
            return $module;
        });

        $this->app->singleton(Service::class, function ($app) {
            $pixie = $app->make(Pixie::class);
            // Ensure VulnInjection module is initialized
            $app->make(VulnInjection::class);

            $service = new Service($pixie, 'default');
            $pixie->addInstance('vulnService', $service);
            $pixie->vulnService = $service;
            return $service;
        });

        // Alias for convenient access via app('vulnService')
        $this->app->alias(Service::class, 'vulnService');
        $this->app->alias(Pixie::class, 'pixie');
    }

    public function boot(): void
    {
        // Eagerly boot the service so Pixifier singleton is populated
        $this->app->make(Service::class);
    }
}
