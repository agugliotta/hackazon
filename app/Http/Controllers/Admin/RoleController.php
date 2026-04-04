<?php
/**
 * Migrated from App\Admin\Controller\Role (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

class RoleController extends CRUDController
{
    public string $modelNamePlural = 'Roles';
    public string $modelName       = 'Role';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'id'   => ['column_classes' => 'dt-id-column'],
                'name' => ['title' => 'Role', 'type' => 'link'],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        return [
            'id',
            'name' => ['required' => true],
        ];
    }

    public function fieldFormatter($value, $item = null, array $format = []): string
    {
        // Non-removable roles cannot be deleted
        if (($format['extra'] ?? false)
            && in_array($format['original_field_name'] ?? '', ['delete'])
            && $item
            && isset($item->removable)
            && !$item->removable
        ) {
            return '';
        }
        return parent::fieldFormatter($value, $item, $format);
    }
}
