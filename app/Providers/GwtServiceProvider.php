<?php
/**
 * Migrated from GWTModule/GWTModule.php (PHPixie module) to Laravel 13 Service Provider.
 *
 * Registers the 'gwt' binding used by HelpdeskController::helpdeskService().
 * The GWT compiled JavaScript is served as static files — only the PHP RPC backend is wired here.
 */

namespace App\Providers;

use App\Pixie;
use GWTModule\GWTModule;
use Illuminate\Support\ServiceProvider;

class GwtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gwt', function ($app) {
            return new GWTModule($app->make(Pixie::class));
        });
    }
}
