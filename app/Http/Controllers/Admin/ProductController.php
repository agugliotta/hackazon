<?php
/**
 * Migrated from App\Admin\Controller\Product (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends CRUDController
{
    public string $modelNamePlural = 'Products';
    public string $modelName       = 'Product';
    public string $editView        = 'admin.product.edit';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'productID' => [
                    'title'          => 'Id',
                    'column_classes' => 'dt-id-column',
                    'data_type'      => 'integer',
                ],
                'name' => [
                    'max_length' => 64,
                    'type'       => 'link',
                ],
                'category.name' => [
                    'title'    => 'Category',
                    'type'     => 'link',
                    'template' => '/admin/category/%category.categoryID%',
                    'width'    => 150,
                ],
                'Price' => [
                    'value_prefix' => '$',
                    'data_type'    => 'integer',
                ],
                'picture' => [
                    'type'           => 'image',
                    'dir_path'       => '/products_pictures/',
                    'max_width'      => 40,
                    'max_height'     => 30,
                    'is_link'        => true,
                    'column_classes' => 'dt-picture-column',
                    'title'          => 'Pic',
                ],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function tuneModelForList(): void
    {
        $this->modelEagerLoad = ['category'];
    }

    protected function getEditFields(): array
    {
        return [
            'productID'         => ['label' => 'Id'],
            'name'              => ['type' => 'text', 'required' => true],
            'categoryID'        => [
                'label'       => 'Category',
                'type'        => 'select',
                'option_list' => 'App\Http\Controllers\Admin\CategoryController::getAvailableCategoryOptions',
                'required'    => true,
            ],
            'description'       => ['type' => 'textarea'],
            'brief_description' => ['type' => 'textarea'],
            'Price'             => ['label' => 'Price ($)'],
            'product_code'      => [],
            'picture'           => ['type' => 'image', 'dir_path' => '/products_pictures/'],
            'big_picture'       => ['type' => 'image', 'dir_path' => '/products_pictures/'],
            'meta_title'        => ['type' => 'textarea'],
            'meta_keywords'     => ['type' => 'textarea'],
            'meta_desc'         => ['type' => 'textarea', 'label' => 'Meta Description'],
            'in_stock'          => ['type' => 'boolean'],
            'enabled'           => ['type' => 'boolean'],
        ];
    }

    protected function editViewExtraData(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'allOptions'  => $this->getOptionsArray(),
        ];
    }

    /**
     * Builds the full list of options with their variants for use in the product edit form.
     */
    private function getAllProductOptionsWithValuesArray(): array
    {
        $result = [];
        /** @var Option[] $options */
        $options = Option::orderBy('sort_order', 'asc')->get();
        foreach ($options as $option) {
            $res = ['name' => $option->name, 'variants' => []];
            foreach ($option->variants()->orderBy('sort_order', 'asc')->get() as $variant) {
                $res['variants'][$variant->getKey()] = $variant->name;
            }
            $result[$option->getKey()] = $res;
        }
        return $result;
    }

    /**
     * Returns an [id => name] map for all options (used in product option add/edit form).
     */
    public function getOptionsArray(): array
    {
        $result = [];
        foreach (Option::orderBy('name', 'asc')->get() as $opt) {
            $result[$opt->getKey()] = $opt->name;
        }
        return $result;
    }
}
