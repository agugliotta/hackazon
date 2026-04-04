<?php
/**
 * Migrated from App\Admin\Controller\Error (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Throwable;

class ErrorController extends AdminController
{
    public function view(Request $request)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        /** @var Throwable|null $exception */
        $exception = $request->attributes->get('exception');

        $code    = $exception ? $exception->getCode()    : 500;
        $message = $exception ? $exception->getMessage() : 'Internal Server Error';

        return $this->adminView('admin.error.view', [
            'pageHeader' => 'Error',
            'exception'  => $exception,
            'pageTitle'  => 'Error: ' . $code . ' ' . $message,
        ])->withHeaders(['Content-Type' => 'text/html'])->setStatusCode((int)$code ?: 500);
    }
}
