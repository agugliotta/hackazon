<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class ReviewController
 * @package App\Http\Controllers
 */
class ReviewController extends PageController
{
    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @Vuln\Description("No view.")
     */
    public function send(Request $request)
    {
        if (!$request->isMethod('POST')) {
            abort(405, 'Method Not Allowed');
        }

        $this->checkCsrfToken('review');

        // $productID taken from POST — no sanitization (SQLi vector if vuln enabled)
        $productID = $request->input('productID');

        $product = Product::where('productID', $productID)->first();

        if (!$product) {
            abort(404, 'Product not found');
        }

        $user = Auth::user();

        // Username/email from user if logged in, else from POST — XSS preserved (stored XSS)
        $username = !is_null($user) ? $user->username : $request->input('userName');
        $email    = !is_null($user) ? $user->email    : $request->input('userEmail');
        $rating   = $request->input('starValue');
        $review   = $request->input('textReview');

        $reviewModel = new Review();
        $reviewModel->productID = $product->productID;
        // addReview stores data without sanitization — stored XSS intentionally preserved
        $reviewModel->addReview($username, $email, $review, $rating, $product->productID);

        return redirect('/product/view?id=' . $product->productID);
    }
}
