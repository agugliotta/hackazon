<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class ErrorController
 * @package App\Http\Controllers
 */
class ErrorController extends PageController
{
    protected bool $checkSessionId = false;

    /**
     * @Vuln\Route(name = "error", params={"id": "<id>"})
     * @Vuln\Description("View: error/view.")
     */
    public function show(Request $request)
    {
        $exception = $request->attributes->get('exception', null);

        if (!$exception) {
            abort(404);
        }

        $status = method_exists($exception, 'getStatus')
            ? $exception->getStatus()
            : $exception->getCode() . ' ' . $exception->getMessage();

        header($request->server('SERVER_PROTOCOL') . ' ' . $status);
        header("Status: {$status}");

        return $this->view('error.view', [
            'exception' => $exception,
            'pageTitle'  => 'Error: ' . $exception->getCode() . ' ' . $exception->getMessage(),
        ]);
    }
}
