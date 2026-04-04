<?php
/**
 * Migrated from App\Admin\Controller\OptionValue (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionValueController extends CRUDController
{
    public string $modelName = 'OptionValue';

    protected function getListFields(): array
    {
        return [
            'variantID' => ['type' => 'text'],
            'name'      => ['max_length' => '255', 'strip_tags' => true],
            'sort_order' => [],
            'edit' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="#" data-id="%variantID%" class="js-edit-variant">Edit</a>',
                'column_classes' => 'edit-action-column',
            ],
            'delete' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="#" data-id="%variantID%" class="js-delete-variant">Delete</a>',
                'column_classes' => 'delete-action-column',
            ],
        ];
    }

    protected function tuneModelForList(): void
    {
        $this->modelEagerLoad = ['parentOption'];
    }

    // POST /admin/option-value/save
    public function save(Request $request)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        $data      = $request->all();
        $variantId = $data['variantID'] ?? null;
        unset($data['variantID']);

        if ($variantId) {
            /** @var OptionValue|null $variant */
            $variant = OptionValue::find($variantId);
            if (!$variant) {
                abort(404);
            }
            unset($data['optionID']);
        } else {
            $variant = new OptionValue();
            if (empty($data['optionID'])) {
                throw new \LogicException('You must provide option to which this variant belongs.');
            }
            if (!Option::find($data['optionID'])) {
                abort(404, 'Option with ID=' . $data['optionID'] . ' does not exist.');
            }
        }

        $variant->fill(array_filter($data, fn($k) => in_array($k, $variant->getFillable()), ARRAY_FILTER_USE_KEY));
        $variant->save();

        return $this->jsonResponse(['success' => true, 'variant' => $variant->toArray()]);
    }

    // POST /admin/option-value/delete
    public function deleteVariant(Request $request)
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

        /** @var OptionValue|null $variant */
        $variant = OptionValue::find($id);
        if (!$variant) {
            abort(404);
        }

        $confirmed = (bool) $request->input('confirm');
        $count = DB::table('tbl_product_options_values')->where('variantID', $variant->getKey())->count();

        if ($count) {
            if (!$confirmed) {
                return $this->jsonResponse([
                    'error'        => 1,
                    'message'      => $count . ' products depend on this option variant. Confirm removal of product options.'
                        . "\nAll dependent products will lose this property. Be careful",
                    'productCount' => (int) $count,
                ]);
            }
            DB::table('tbl_product_options_values')->where('variantID', $variant->getKey())->delete();
        }

        $variant->delete();
        return $this->jsonResponse(['success' => 1]);
    }

    // GET /admin/option-value/get-option-values
    public function getOptionValues(Request $request)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        $optionId = $request->input('option_id');
        if (!$optionId) {
            abort(404);
        }

        /** @var Option|null $option */
        $option = Option::find($optionId);
        if (!$option) {
            abort(404);
        }

        $values = $option->getValuesForOption($optionId);
        return $this->jsonResponse(['optionVariants' => $values]);
    }
}
