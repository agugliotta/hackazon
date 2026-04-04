<?php
/**
 * Migrated from App\Admin\Controller\Option (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends CRUDController
{
    public string $modelNamePlural = 'Product Options';
    public string $modelNameSingle = 'Product Option';
    public string $modelName       = 'Option';
    public string $editView        = 'admin.option.edit';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'optionID' => [
                    'title'          => 'Id',
                    'column_classes' => 'dt-id-column',
                ],
                'name' => [
                    'type'       => 'link',
                    'max_length' => '50',
                    'strip_tags' => true,
                ],
                'sort_order' => [],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        return [
            'optionID'   => [],
            'name'       => ['required' => true],
            'sort_order' => [],
        ];
    }

    public function edit(Request $request, $id = null)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($id) {
            /** @var Option|null $option */
            $option = Option::find($id);
            if ($option) {
                $this->viewData['pageHeader'] = "Product Option &laquo;" . $option->name . "&raquo;";
            }
        }

        return parent::edit($request, $id);
    }
}
