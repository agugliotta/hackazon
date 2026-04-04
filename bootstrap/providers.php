<?php

use App\Providers\AppServiceProvider;
use App\Providers\VulnInjectionServiceProvider;

return [
    AppServiceProvider::class,
    VulnInjectionServiceProvider::class,
    \App\Providers\GwtServiceProvider::class,
    \App\Providers\AmfServiceProvider::class,
];
