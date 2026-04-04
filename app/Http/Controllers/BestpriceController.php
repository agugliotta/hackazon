<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Class BestpriceController
 * @package App\Http\Controllers
 */
class BestpriceController extends PageController
{
    public function index(Request $request)
    {
        if ($request->isMethod('POST')) {
            // CSRF check is VulnModule-aware (CSRF vuln intentionally present when enabled)
            $this->checkCsrfToken('bestprice', null, !$request->ajax());

            if ($request->ajax()) {
                return response()->json([]);
            } else {
                return redirect('/bestprice');
            }
        }

        return $this->view('pages.bestprice');
    }
}
