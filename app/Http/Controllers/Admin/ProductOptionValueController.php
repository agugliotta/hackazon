<?php
/**
 * Migrated from App\Admin\Controller\ProductOptionValue (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductOptionValue;
use Illuminate\Http\Request;

class ProductOptionValueController extends CRUDController
{
    public string $modelName = 'ProductOptionValue';

    protected function getListFields(): array
    {
        return [
            'optionVariant.parentOption.name' => ['type' => 'text'],
            'optionVariant.name'              => ['max_length' => '255', 'strip_tags' => true],
            'variantID'                       => [],
            'ID'                              => [],
            'optionVariant.optionID'          => ['title' => 'optionID'],
            'price_surplus'                   => [],
            'edit' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="#" data-id="%ID%" class="js-edit-variant">Edit</a>',
                'column_classes' => 'edit-action-column',
            ],
            'delete' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="#" data-id="%ID%" class="js-delete-variant">Delete</a>',
                'column_classes' => 'delete-action-column',
            ],
        ];
    }

    protected function tuneModelForList(): void
    {
        $this->modelEagerLoad = ['optionVariant.parentOption'];
    }

    // POST /admin/product-option-value/save
    public function save(Request $request)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        $data        = $request->all();
        $prodOptionId = $data['ID'] ?? null;
        unset($data['ID']);

        if ($prodOptionId) {
            /** @var ProductOptionValue|null $prodOpt */
            $prodOpt = ProductOptionValue::find($prodOptionId);
            if (!$prodOpt) {
                abort(404);
            }
            unset($data['productID']);
        } else {
            $prodOpt = new ProductOptionValue();
            if (empty($data['variantID']) || empty($data['productID'])) {
                throw new \LogicException('You must provide option variant and product id for product option.');
            }
            if (!OptionValue::find($data['variantID'])) {
                abort(404, 'Option variant with ID=' . $data['variantID'] . ' does not exist.');
            }
            if (!Product::find($data['productID'])) {
                abort(404, 'Product with ID=' . $data['productID'] . ' does not exist.');
            }
        }

        $prodOpt->fill(array_filter($data, fn($k) => in_array($k, $prodOpt->getFillable()), ARRAY_FILTER_USE_KEY));

        if (method_exists($prodOpt, 'checkCanSaveProductOption') && !$prodOpt->checkCanSaveProductOption()) {
            return $this->jsonResponse([
                'error'   => 1,
                'message' => 'Such option already exists for this product. Please edit it instead.',
            ]);
        }

        $prodOpt->save();
        return $this->jsonResponse(['success' => true, 'productOption' => $prodOpt->toArray()]);
    }

    // POST /admin/product-option-value/delete
    public function deleteOption(Request $request)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        $id = $request->input('id');
        if (!$id) {
            abort(404);
        }

        /** @var ProductOptionValue|null $prodOption */
        $prodOption = ProductOptionValue::find($id);
        if (!$prodOption) {
            abort(404);
        }

        $prodOption->delete();
        return $this->jsonResponse(['success' => 1]);
    }
}
