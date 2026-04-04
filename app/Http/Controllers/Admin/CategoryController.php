<?php
/**
 * Migrated from App\Admin\Controller\Category (PHPixie) to Laravel 13.
 * Original author: Nikolay Chervyakov, 28.08.2014
 */

namespace App\Http\Controllers\Admin;

use App\Models\Category;

class CategoryController extends CRUDController
{
    public string $modelNamePlural = 'Categories';
    public string $modelName       = 'Category';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'categoryID' => [
                    'title'          => 'Id',
                    'column_classes' => 'dt-id-column',
                ],
                'name' => [
                    'max_length' => 64,
                    'type'       => 'link',
                ],
                'parentCategory.name' => [
                    'is_link'  => true,
                    'template' => '/admin/category/%parentCategory.categoryID%',
                    'title'    => 'Parent',
                ],
                'enabled' => [
                    'type'           => 'boolean',
                    'column_classes' => 'dt-flag-column',
                    'title'          => '+',
                ],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    /**
     * Eagerly load parentCategory for list view.
     * Mirrors tuneModelForList() from the original.
     * Excludes the root category (categoryID=1) — preserved from original.
     */
    protected function tuneQueryForList($query): void
    {
        $query->with('parentCategory')->where('categoryID', '<>', 1);
    }

    public function fieldFormatter($value, $item = null, array $format = []): string
    {
        // Original: hide '0_ROOT' for the root category parent name
        if (($format['original_field_name'] ?? '') === 'parentCategory.name' && $value === '0_ROOT') {
            $value = '';
        }
        return parent::fieldFormatter($value, $item, $format);
    }

    protected function getEditFields(): array
    {
        return [
            'categoryID' => [
                'label' => 'Id',
            ],
            'name' => [
                'type'     => 'text',
                'required' => true,
            ],
            'parent' => [
                'label'       => 'Category',
                'type'        => 'select',
                'option_list' => [self::class, 'getAvailableCategoryOptions'],
            ],
            'description' => [
                'type' => 'textarea',
            ],
            'enabled' => [
                'type' => 'boolean',
            ],
            'hidden' => [
                'type' => 'boolean',
            ],
            'picture' => [
                'type'     => 'image',
                'dir_path' => '/products_pictures/',
                'abs_path' => false,
            ],
            'meta_title' => [
                'type' => 'textarea',
            ],
            'meta_keywords' => [
                'type' => 'textarea',
            ],
            'meta_desc' => [
                'type'  => 'textarea',
                'label' => 'Meta Description',
            ],
        ];
    }

    /**
     * Returns available parent categories for the select field.
     * Migrated from PHPixie ORM to Eloquent.
     * $pixie arg is kept for signature compatibility but unused.
     */
    public static function getAvailableCategoryOptions($pixie = null, $options = []): array
    {
        $results = [];
        $items = Category::with('parentCategory')
            ->where('depth', 2)
            ->orderBy('name', 'asc')
            ->get();

        foreach ($items as $item) {
            $results[$item->categoryID] = ($item->parentCategory->name ?? '') . ' / ' . $item->name;
        }

        return $results;
    }
}
