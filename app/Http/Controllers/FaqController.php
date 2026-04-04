<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class FaqController
 * @package App\Http\Controllers
 */
class FaqController extends PageController
{
    /**
     * @throws \App\Exception\HttpException
     * @Vuln\Description("View: pages/faq. Or AJAX action.")
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // CSRF check is VulnModule-aware
            $this->checkCsrfToken('faq', null, false);

            $item = Faq::create($request->all());
            session()->flash('success', 'Thank you for your question. We will contact you as soon.');

            return response()->json([$item->toArray()]);
        }

        // Wrap FAQ entries through VulnService for XSS vulnerability injection
        // wrapValueByPath preserves intentional XSS when vuln is enabled
        $service = $this->vulnService;
        $entries = Faq::getEntries();

        foreach ($entries as $key => $entry) {
            $entry->question = $service->wrapValueByPath(
                $entry->question,
                'default->faq->index|userQuestion:any|0',
                true
            );
            $entries[$key] = $entry;
        }

        return $this->view('pages.faq', [
            'pageTitle' => 'Frequently Asked Questions',
            'entries'   => $entries,
        ]);
    }
}
