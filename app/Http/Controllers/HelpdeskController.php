<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class HelpdeskController
 * GWT entry point.
 * Replaces App\Controller\Helpdesk.
 *
 * @Vuln\Description("GWT entry point.")
 */
class HelpdeskController extends PageController
{
    public function index(Request $request)
    {
        return $this->view('helpdesk.helpdesk', [
            'headScripts' => '<script type="text/javascript" src="helpdesk.nocache.js"></script>',
        ]);
    }

    /**
     * GWT RPC servlet endpoint.
     * Matches original action_HelpdeskService().
     */
    public function helpdeskService(Request $request)
    {
        // Load GWT child vuln context (matches original loadAndAddChildContext('gwt'))
        $this->vulnService->goUp()->goUp();
        $this->vulnService->loadAndAddChildContext('gwt');
        $this->vulnService->goDown('gwt');

        $servlet = app('gwt')->getServlet();
        $servlet->setRequest($request);
        $servlet->start();
        exit;
    }
}
