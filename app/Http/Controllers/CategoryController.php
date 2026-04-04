<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class CategoryController
 * @package App\Http\Controllers
 * @Vuln\Description("Controller for category handling.")
 * @Vuln\Description("Controller for category handling 21.")
 */
class CategoryController extends PageController
{
    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @Vuln\Description("View: category/category.")
     */
    public function show(Request $request)
    {
        // $categoryID is taken directly from query/route — no sanitization (IDOR preserved)
        $categoryID = $request->route('id') ?? $request->query('id');

        if (!$categoryID) {
            abort(404);
        }

        $category = Category::loadCategory($categoryID);

        if ($category instanceof Category && $category->parent) {
            $children = $category->nested_children ?? [];

            // Build pager via Eloquent + paginate
            $products = $category->products()->paginate(12);

            return $this->view('category.category', [
                'pageTitle'     => $category->name,
                'subCategories' => $children,
                'products'      => $products->items(),
                'pager'         => $products,
                'breadcrumbs'   => $this->getBreadcrumbs($category),
                'categoryID'    => $categoryID,
            ]);

        } else {
            abort(404, 'No such category');
        }
    }

    private function getBreadcrumbs(Category $category): array
    {
        $breadcrumbs = [];
        $parents     = $category->parents();
        $breadcrumbs['/'] = 'Home';

        foreach ($parents as $p) {
            $breadcrumbs['/category/view?id=' . $p->categoryID] = $p->name;
        }

        $breadcrumbs[''] = $category->name;
        return $breadcrumbs;
    }
}
