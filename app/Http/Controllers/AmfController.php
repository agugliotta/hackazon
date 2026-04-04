<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Class AmfController
 * AMF/Flash backend endpoint.
 * Replaces App\Controller\Amf.
 *
 * The VulnModule context traversal (goUp/goDown) is handled by BaseController::callAction().
 * The amf child context is loaded here matching the original.
 */
class AmfController extends PageController
{
    public function index(Request $request)
    {
        // Load AMF child vuln context (matches original loadAndAddChildContext('amf'))
        $this->vulnService->goUp()->goUp();
        $this->vulnService->loadAndAddChildContext('amf');
        $this->vulnService->goDown('amf');

        // header("Access-Control-Allow-Origin: *");
        // Run the AMF service — bound in the service container as 'amf'
        app('amf')->run();
        exit;
    }
}
