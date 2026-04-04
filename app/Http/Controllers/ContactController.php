<?php

namespace App\Http\Controllers;

use App\Models\ContactMessages;
use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;
use VulnModule\Config\FieldDescriptor;

/**
 * Class ContactController
 * @package App\Http\Controllers
 */
class ContactController extends PageController
{
    /**
     * @throws \App\Exception\HttpException
     * @Vuln\Description("View: pages/contact.")
     */
    public function index(Request $request)
    {
        if ($request->isMethod('POST')) {
            $this->checkCsrfToken('contact');

            $postData = $request->all();
            $post = json_decode($postData['data']);

            // Create contact message — no sanitization (preserves stored XSS vulnerability)
            ContactMessages::create((array) $post);

            if ($request->ajax()) {
                return response('', 204);
            }
        }

        return $this->view('pages.contact', ['pageTitle' => 'Contact Us']);
    }
}
