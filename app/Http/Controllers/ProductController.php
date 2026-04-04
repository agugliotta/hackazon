<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SpecialOffer as SpecialOffers;
use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class ProductController
 * @package App\Http\Controllers
 */
class ProductController extends PageController
{
    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @Vuln\Description("View: product/product.")
     */
    public function show(Request $request)
    {
        // $productID taken directly from query/route — no sanitization (IDOR/SQLi vector preserved)
        $productID = $request->route('id') ?? $request->query('id');

        if (!$productID) {
            abort(404, 'Missing product id.');
        }

        $product = Product::where('productID', '=', $productID)->first();

        if (!$product) {
            abort(404, 'Invalid product id');
        }

        $options       = $product->options()->get()->all();
        $pageTitle     = Product::getPageTitle($productID);
        $breadcrumbs   = $this->getBreadcrumbs($product);
        $specialOffers = SpecialOffers::getRandomOffers(4);
        $related       = Product::getRandomProducts(4);

        // Track visited product in cookie (same as original checkProductInCookie)
        $product->checkProductInCookie($productID, $request);

        return $this->view('product.product', [
            'product'       => $product,
            'options'       => $options,
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'special_offers' => $specialOffers,
            'related'       => $related,
        ]);
    }

    private function getBreadcrumbs(Product $product): array
    {
        $categories  = $product->categories()->get();
        $breadcrumbs = [];

        foreach ($categories as $cat) {
            $parents           = $cat->parents();
            $breadcrumbsParts  = [];

            foreach ($parents as $p) {
                $breadcrumbsParts['/category/view?id=' . $p->categoryID] = $p->name;
            }

            $breadcrumbsParts['/category/view?id=' . $cat->categoryID]   = $cat->name;
            $breadcrumbsParts['/product/view?id=' . $product->productID]  = $product->name;
            $breadcrumbs[] = array_merge(['/' => 'Home'], $breadcrumbsParts);
        }

        return $breadcrumbs;
    }
}
