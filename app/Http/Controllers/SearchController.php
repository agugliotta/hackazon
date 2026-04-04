<?php

namespace App\Http\Controllers;

use App\SearchFilters\FilterFabric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class SearchController
 * @package App\Http\Controllers
 *
 * VULNERABILITY NOTE: This controller intentionally preserves SQLi vectors.
 * The search query uses direct string concatenation / raw DB expressions
 * exactly as the original PHPixie code did. DO NOT add prepared statement
 * bindings for the name/category/brand/quality/price parameters.
 */
class SearchController extends PageController
{
    /**
     * @Vuln\Route(name="search")
     * @Vuln\Description("View: search/main.")
     */
    public function index(Request $request)
    {
        // Pull all search params directly from query — no sanitization (SQLi preserved)
        $catId        = $request->query('id', '');
        $name         = $request->query('searchString', '');
        $brand        = $request->query('brands', '');
        $price        = $request->query('price', '');
        $quality      = $request->query('quality', '');
        $currentPage  = (int) $request->query('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        // ── Build raw query with concatenation (SQLi intentionally preserved) ──
        // Original used PHPixie DB query builder with ->where() chaining, which
        // internally concatenated values. We replicate using DB::table() + raw
        // wheres where the value is not bound but concatenated.

        $query = DB::table('tbl_products');

        if ($catId !== '') {
            // Load category and get child IDs — IDOR: no ownership check
            $category = \App\Models\Category::find((int) $catId);
            $subCategoryIds = $category ? $category->getChildrenIDs() : [];

            if (count($subCategoryIds) > 0) {
                $idList = implode(',', array_map('intval', $subCategoryIds));
                $query->leftJoin('tbl_category_product', 'tbl_category_product.productID', '=', 'tbl_products.productID')
                      ->whereRaw("tbl_category_product.categoryID IN ($idList)");
            } else {
                $query->leftJoin('tbl_category_product', 'tbl_category_product.productID', '=', 'tbl_products.productID')
                      ->where('tbl_category_product.categoryID', $catId);
            }
        }

        if ($name !== '') {
            // INTENTIONAL SQLi: name is concatenated directly into LIKE pattern without escaping
            $query->whereRaw("name LIKE '%" . $name . "%'");
        }

        if ($price !== '') {
            // Price filter uses variant ranges — values are numeric from predefined list
            $pricesVariants = $this->getPriceVariants();
            if (isset($pricesVariants[$price])) {
                $query->where('Price', '>=', $pricesVariants[$price][0])
                      ->where('Price', '<=', $pricesVariants[$price][1]);
            }
        }

        if ($brand !== '' && $quality !== '') {
            $query->leftJoin('tbl_product_options_values', 'tbl_product_options_values.productID', '=', 'tbl_products.productID')
                  // INTENTIONAL SQLi: brand and quality concatenated directly
                  ->whereRaw("tbl_product_options_values.variantID = '" . $brand . "' OR tbl_product_options_values.variantID = '" . $quality . "'");

        } elseif ($brand !== '') {
            $query->leftJoin('tbl_product_options_values', 'tbl_product_options_values.productID', '=', 'tbl_products.productID')
                  // INTENTIONAL SQLi: brand concatenated directly
                  ->whereRaw("tbl_product_options_values.variantID = '" . $brand . "'");

        } elseif ($quality !== '') {
            $query->leftJoin('tbl_product_options_values', 'tbl_product_options_values.productID', '=', 'tbl_products.productID')
                  // INTENTIONAL SQLi: quality concatenated directly
                  ->whereRaw("tbl_product_options_values.variantID = '" . $quality . "'");
        }

        $perPage = 12;
        $products = $query->select('tbl_products.*')
                          ->distinct()
                          ->paginate($perPage, ['*'], 'page', $currentPage);

        // Build URL callback for pager links — XSS-escaped values in URLs (same as original escapeXSS())
        $pagerUrlBase = "/search/page/?id=" . htmlspecialchars($catId, ENT_QUOTES, 'UTF-8')
            . "&searchString=" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            . "&brands=" . htmlspecialchars($brand, ENT_QUOTES, 'UTF-8')
            . "&price=" . htmlspecialchars($price, ENT_QUOTES, 'UTF-8')
            . "&quality=" . htmlspecialchars($quality, ENT_QUOTES, 'UTF-8');

        // Page title uses raw $name (reflected XSS preserved — no escaping in template)
        $pageTitle = 'Search by &laquo;' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '&raquo;';

        $filterFabric = new FilterFabric($request);

        $data = [
            'searchString' => $name,
            'categoryId'   => $catId,
            'price'        => $price,
            'brand'        => $brand,
            'quality'      => $quality,
            'pageTitle'    => $pageTitle,
            'pager'        => $products,
            'currentItems' => $products->items(),
            'pagerUrlBase' => $pagerUrlBase,
            'filterFabric' => $filterFabric,
        ];

        if ($request->ajax()) {
            return view('search.main', $data);
        }

        return $this->view('search.main', $data);
    }

    /**
     * Price filter variants (same as original FilterFabric Price filter).
     */
    private function getPriceVariants(): array
    {
        return [
            '0-50'      => [0,    50],
            '50-100'    => [50,   100],
            '100-200'   => [100,  200],
            '200-500'   => [200,  500],
            '500-1000'  => [500,  1000],
            '1000+'     => [1000, PHP_INT_MAX],
        ];
    }
}
