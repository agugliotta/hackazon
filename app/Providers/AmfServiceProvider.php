<?php
/**
 * Migrated from AmfphpModule/AmfphpModule.php (PHPixie module) to Laravel 13 Service Provider.
 *
 * Registers the 'amf' binding used by AmfController::index().
 * Endpoints /amf and /amf_back_office are served by the Flash client — the PHP AMF gateway is bound here.
 */

namespace App\Providers;

use App\Pixie;
use AmfphpModule\AmfphpModule;
use Illuminate\Support\ServiceProvider;

class AmfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('amf', function ($app) {
            return new AmfphpModule($app->make(Pixie::class));
        });
    }
}
