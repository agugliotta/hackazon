<?php
/**
 * Migrated from App\Admin\Controller\Order (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends CRUDController
{
    public string $modelNamePlural = 'Orders';
    public string $modelName       = 'Order';
    public string $editView        = 'admin.order.edit';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'id' => ['column_classes' => 'dt-id-column'],
                'customer_firstname' => ['title' => 'First Name'],
                'customer_lastname'  => ['title' => 'Last Name'],
                'customer_email'     => ['title' => 'Email'],
                'customer.username'  => [
                    'title'    => 'Customer Username',
                    'is_link'  => true,
                    'template' => '/admin/user/edit/%customer.id%',
                ],
                'status'          => ['type' => 'status'],
                'payment_method'  => [],
                'shipping_method' => [],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        $statuses = Order::getOrderStatuses();
        return [
            'id'     => [],
            'status' => [
                'type'        => 'select',
                'option_list' => array_combine($statuses, $statuses),
                'required'    => true,
            ],
            'customer_id' => [
                'label'       => 'Customer',
                'type'        => 'select',
                'option_list' => 'App\Http\Controllers\Admin\UserController::getAvailableUsers',
                'required'    => true,
            ],
            'customer_firstname',
            'customer_lastname',
            'customer_email',
            'payment_method'  => ['required' => true],
            'shipping_method' => ['required' => true],
            'comment'         => ['type' => 'textarea'],
            'created_at'      => ['data_type' => 'date'],
            'updated_at'      => ['data_type' => 'date'],
        ];
    }

    protected function tuneModelForList(): void
    {
        $this->modelEagerLoad = ['customer'];
    }

    protected function editViewExtraData(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'order'      => $item,
            'orderItems' => $item->orderItems()->with('product')->get(),
        ];
    }
}
