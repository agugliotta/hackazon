<?php
/**
 * Migrated from App\Admin\Controller\Coupon (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

class CouponController extends CRUDController
{
    public string $modelNamePlural = 'Coupons';
    public string $modelName       = 'Coupon';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'id' => [
                    'title'          => 'Id',
                    'column_classes' => 'dt-id-column',
                ],
                'coupon' => [
                    'type'       => 'link',
                    'max_length' => '50',
                    'strip_tags' => true,
                ],
                'discount' => [],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        return [
            'id'       => [],
            'coupon'   => ['required' => true],
            'discount' => ['required' => true, 'default_value' => 0],
        ];
    }
}
