<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for all frontend controllers.
 * Provides sidebar, search categories, and common layout data.
 * Replaces App\Page from PHPixie.
 */
class PageController extends BaseController
{
    /** @var array Shared view data passed to all views */
    protected array $viewData = [];

    protected bool $useRest = false;

    protected function before(): mixed
    {
        parent::before();

        $className = $this->getControllerName();

        $category = new Category();
        $this->viewData['sidebar'] = $category->getCategoriesSidebar();
        $this->viewData['search_category'] = $this->getSearchCategory(ucfirst($className));
        $this->viewData['search_subcategories'] = $this->getAllCategories($this->viewData['sidebar']);

        if (ucfirst($className) !== 'Home') {
            $this->viewData['categories'] = $category->getRootCategories();
        }

        $this->viewData['controller'] = $this;
        $this->viewData['currentUser'] = Auth::user();

        return null;
    }

    protected function view(string $template, array $data = [])
    {
        return view($template, array_merge($this->viewData, $data));
    }

    protected function getSearchCategory(string $className): array
    {
        switch ($className) {
            case 'Category':
                $id = request()->route('id', request()->query('id'));
                $category = new Category();
                $label = $category->getPageTitle((int)$id);
                return ['value' => $id, 'label' => $label];
            case 'Search':
                $id = request()->query('id');
                $category = new Category();
                $label = $category->getPageTitle((int)$id) ?: 'All';
                return ['value' => $id, 'label' => $label];
            default:
                return ['value' => '', 'label' => 'All'];
        }
    }

    protected function getAllCategories(array $categories): array
    {
        $all = [];
        foreach ($categories as $category) {
            $all[$category->categoryID] = $category->name;
            foreach (($category->childs ?? []) as $subcategory) {
                $all[$subcategory->categoryID] = $subcategory->name;
            }
        }
        return $all;
    }
}
