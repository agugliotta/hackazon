<?php

namespace App\Providers;

use App\Auth\Md5UserProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('hackazon.email', function () {
            return new class {
                public function send(string $to, string $from, string $subject, string $body): void
                {
                    Mail::raw($body, function ($message) use ($to, $from, $subject) {
                        $message->to($to)
                                ->from($from)
                                ->subject($subject);
                    });
                }
            };
        });
    }

    public function boot(): void
    {
        Auth::provider('md5', function ($app, array $config) {
            return new Md5UserProvider($app['hash'], $config['model']);
        });

        // Legacy PHPixie alias used by VulnModule IsAjax condition
        Request::macro('is_ajax', function () {
            /** @var Request $this */
            return $this->ajax();
        });
    }
}
