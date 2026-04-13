<?php
/**
 * Migrated from App\Admin\CRUDController (PHPixie) to Laravel 13.
 * Original author: Nikolay Chervyakov, 28.08.2014
 *
 * Provides generic CRUD operations for Eloquent models.
 * All PHPixie ORM calls replaced with Eloquent equivalents.
 * Vulnerability-intentional behaviour (IDOR, no auth checks on IDs) preserved.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\FieldFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generic CRUD controller for admin area.
 * Derive from this and override getListFields()/getEditFields() for each model.
 *
 * @package App\Http\Controllers\Admin
 */
abstract class CRUDController extends AdminController
{
    /** @var string Plural label shown in UI */
    public string $modelNamePlural = '';

    /** @var string Singular label shown in UI */
    public string $modelNameSingle = '';

    /** @var string Model base name (e.g. 'Product') */
    public string $modelName = '';

    /** @var string URL path alias (e.g. 'product') */
    protected string $alias = '';

    protected array $modelFields = [];

    public string $listView  = 'admin.crud.list';
    public string $editView  = 'admin.crud.edit';

    /** @var array|null Cached prepared edit fields */
    protected ?array $preparedEditFields = null;

    /** @var Model|null Active Eloquent model instance (class, not record) */
    protected ?Model $modelInstance = null;

    // ─── Lifecycle ─────────────────────────────────────────────────────────────

    protected function before(): mixed
    {
        $parent = parent::before();
        if ($parent !== null) {
            return $parent;
        }

        if (!$this->modelName) {
            $this->modelName = $this->get_real_class($this);
        }

        if (!$this->alias) {
            $this->alias = strtolower($this->modelName);
        }

        if (!$this->modelNamePlural) {
            $this->modelNamePlural = $this->modelName . 's';
        }

        if (!$this->modelNameSingle) {
            $this->modelNameSingle = $this->modelName;
        }

        // Resolve the Eloquent model class
        $modelClass = 'App\\Models\\' . $this->modelName;
        if (class_exists($modelClass)) {
            $this->modelInstance = new $modelClass();
        }

        $this->viewData['modelNameSingle'] = $this->modelNameSingle;
        $this->viewData['pageTitle']       = $this->modelNamePlural;
        $this->viewData['pageHeader']      = $this->modelNamePlural;
        $this->viewData['alias']           = $this->alias;
        $this->viewData['adminRoot']       = $this->root;

        $this->prepareModelFields();

        return null;
    }

    // ─── Actions ───────────────────────────────────────────────────────────────

    /**
     * List items (DataTables-aware).
     */
    public function index(Request $request)
    {
        $listFields = $this->prepareListFields();

        if ($request->ajax()) {
            return $this->processDataTableRequest($request, $listFields);
        }

        return $this->adminView($this->listView, [
            'listFields' => $listFields,
            'modelName'  => $this->modelName,
            'alias'      => $this->alias,
        ]);
    }

    /**
     * Edit existing item (GET shows form, POST saves).
     * IDOR preserved: no ownership check — only ID lookup, same as original.
     */
    public function edit(Request $request, $id = null)
    {
        $modelClass = 'App\\Models\\' . $this->modelName;

        if ($request->isMethod('POST')) {
            if (!$id) {
                abort(404);
            }

            /** @var Model $item */
            $item = $modelClass::find($id);
            if (!$item) {
                abort(404);
            }

            $data = $request->all();
            $this->preProcessEdit($item, $data);

            // Ensure checkbox fields exist in dataset even if unchecked
            $data = array_merge(array_fill_keys(array_keys($this->getEditFields()), ''), $data);

            $this->processRequestFilesForItem($item, $data, $request);

            $item->fill(array_intersect_key($data, array_flip($item->getFillable())));
            $item->save();

            $this->onSuccessfulEdit($item);
            return redirect('/admin/' . strtolower($this->alias) . '/edit/' . $item->getKey());
        }

        if (!$id) {
            abort(404);
        }

        $item = $modelClass::find($id);
        if (!$item) {
            abort(404);
        }

        $editFields = $this->prepareEditFields();
        $formatter  = new FieldFormatter($item, $editFields, ['alias' => $this->alias]);

        return $this->adminView($this->editView, array_merge([
            'pageTitle'  => $this->modelNameSingle,
            'pageHeader' => $this->modelNameSingle,
            'modelName'  => $this->modelName,
            'item'       => $item,
            'editFields' => $editFields,
            'formatter'  => $formatter,
        ], $this->editViewExtraData($item)));
    }

    /**
     * Hook for subclasses to add extra view data to the edit view.
     */
    protected function editViewExtraData(Model $item): array
    {
        return [];
    }

    /**
     * Create new item (GET shows form, POST saves).
     */
    public function create(Request $request)
    {
        $modelClass = 'App\\Models\\' . $this->modelName;

        /** @var Model $item */
        $item = new $modelClass();

        if ($request->isMethod('POST')) {
            $data = $request->all();
            $this->processRequestFilesForItem($item, $data, $request);
            $item->fill(array_intersect_key($data, array_flip($item->getFillable())));
            $item->save();

            if ($item->getKey()) {
                return redirect('/admin/' . strtolower($this->alias) . '/edit/' . $item->getKey());
            }
        }

        $editFields = $this->prepareEditFields();
        $formatter  = new FieldFormatter($item, $editFields, ['alias' => $this->alias]);

        return $this->adminView($this->editView, [
            'pageTitle'  => 'Add new ' . $this->modelNameSingle,
            'pageHeader' => 'Add new ' . $this->modelNameSingle,
            'modelName'  => $this->modelName,
            'item'       => $item,
            'editFields' => $editFields,
            'formatter'  => $formatter,
        ]);
    }

    /**
     * Delete item (POST only).
     * IDOR preserved: no ownership check — only ID lookup.
     */
    public function destroy(Request $request, $id = null)
    {
        if (!$request->isMethod('POST')) {
            abort(404);
        }

        if (!$id) {
            abort(404);
        }

        $modelClass = 'App\\Models\\' . $this->modelName;
        /** @var Model $item */
        $item = $modelClass::find($id);

        if (!$item) {
            abort(404);
        }

        $item->delete();

        $location = '/admin/' . strtolower($this->modelName);

        if ($request->ajax()) {
            return $this->jsonResponse(['success' => 1, 'location' => $location]);
        }

        return redirect($location);
    }

    // ─── Field Metadata ────────────────────────────────────────────────────────

    protected function prepareModelFields(): void
    {
        if ($this->modelInstance) {
            $this->modelFields = $this->modelInstance->getFillable();
        }
    }

    /**
     * Override in child classes to describe list fields precisely.
     */
    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            array_combine($this->modelFields, array_fill(0, count($this->modelFields), [])),
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function prepareListFields(): array
    {
        $idField   = $this->modelInstance ? $this->modelInstance->getKeyName() : 'id';
        $listFields = $this->getListFields();

        $result = [];
        foreach ($listFields as $field => &$data) {
            if (is_numeric($field) && is_string($data)) {
                $field = $data;
                $data  = [];
            }

            $data['original_field_name'] = $field;

            if (empty($data['type']) && $field !== $idField) {
                $data['type'] = 'text';
            }

            if (!array_key_exists('title', $data) || $data['title'] === null) {
                $data['title'] = ucwords(implode(' ', preg_split('/_+/', $field, -1, PREG_SPLIT_NO_EMPTY)));
            }

            $this->checkSubProp($field, $data);

            if (($data['type'] ?? '') === 'link' || !empty($data['is_link'])) {
                $data['is_link'] = true;
                if (empty($data['template'])) {
                    $data['template'] = '/admin/' . $this->alias . '/edit/%' . $idField . '%';
                }
            }

            if (($data['type'] ?? '') === 'image') {
                $data['max_width']  = $data['max_width']  ?? 40;
                $data['max_height'] = $data['max_height'] ?? 30;
                $data['dir_path']   = $data['dir_path']   ?? '/images/';
                $data['orderable']  = $data['orderable']  ?? false;
                $data['searching']  = $data['searching']  ?? false;
            }

            if (!empty($data['extra'])) {
                $data['orderable'] = false;
                $data['searching'] = false;
            }

            $data['orderable'] = $data['orderable'] ?? true;
            $data['searching'] = $data['searching'] ?? true;

            $field         = $this->recursiveCreateRelativeFieldName($field, $data);
            $result[$field] = $data;
        }
        unset($data);

        $listFields = $result;

        if (array_key_exists($idField, $listFields)) {
            if (!array_key_exists('type', $listFields[$idField])) {
                $listFields[$idField]['type']     = 'link';
                $listFields[$idField]['template'] = '/admin/' . $this->alias . '/edit/%' . $idField . '%';
            }
            $listFields[$idField]['width'] = '60';
        }

        return $listFields;
    }

    protected function getEditFields(): array
    {
        return array_combine($this->modelFields, array_fill(0, count($this->modelFields), []));
    }

    protected function prepareEditFields(): array
    {
        if ($this->preparedEditFields !== null) {
            return $this->preparedEditFields;
        }

        $idField    = $this->modelInstance ? $this->modelInstance->getKeyName() : 'id';
        $editFields = $this->getEditFields();

        $result = [];
        foreach ($editFields as $field => &$data) {
            if (is_numeric($field) && is_string($data)) {
                $field = $data;
                $data  = [];
            }

            $data['original_field_name'] = $field;

            if (empty($data['type'])) {
                $data['type'] = 'text';
            }

            if (empty($data['label'])) {
                $data['label'] = ucwords(implode(' ', preg_split('/_+/', $field, -1, PREG_SPLIT_NO_EMPTY)));
            }

            if (!empty($data['select']) && !array_key_exists('multiple', $data)) {
                $data['multiple'] = false;
            }

            if (($data['type'] ?? '') === 'image') {
                $data['max_width']  = $data['max_width']  ?? 400;
                $data['max_height'] = $data['max_height'] ?? 300;
                $data['dir_path']   = $data['dir_path']   ?? '/images/';
            }

            if (in_array($data['type'] ?? '', ['image', 'file'])) {
                $data['dir_path'] = $data['dir_path'] ?? '/upload/';
                $data['abs_path'] = $data['abs_path'] ?? false;
            }

            $result[$field] = $data;
        }
        unset($data);

        $editFields = $result;
        $editFields[$idField]['type'] = 'hidden';

        $this->preparedEditFields = $editFields;
        return $this->preparedEditFields;
    }

    // ─── DataTables ────────────────────────────────────────────────────────────

    protected function processDataTableRequest(Request $request, array $listFields)
    {
        $perPage = (int) $request->input('length', 10);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 10;
        }

        $start = (int) $request->input('start', 0);
        if ($start < 0) {
            $start = 0;
        }

        $page = (int) floor($start / $perPage) + 1;

        $modelClass = 'App\\Models\\' . $this->modelName;
        $query      = $modelClass::query();
        $this->tuneQueryForList($query);

        $totalCount = (clone $query)->count();

        // Ordering
        $columns     = $request->input('columns', []);
        $orderData   = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $orderData   = $orderData[0];
        $orderColIdx = $orderData['column'] ?? 0;
        $orderColDef = $columns[$orderColIdx] ?? [];
        $orderColumn = $orderColDef['data'] ?? array_key_first($listFields);

        if (!empty($listFields[$orderColumn]['extra'])) {
            foreach ($listFields as $lKey => $lValue) {
                if (empty($lValue['extra']) && !empty($lValue['orderable'])) {
                    $orderColumn = $lKey;
                    break;
                }
            }
        }

        $orderDir = in_array(strtolower($orderData['dir'] ?? ''), ['asc', 'desc']) ? $orderData['dir'] : 'asc';
        $dbColumn = array_key_exists($orderColumn, $listFields)
            ? str_replace('___', '.', $orderColumn)
            : str_replace('___', '.', (string) array_key_first($listFields));
        $query->orderByRaw("`$dbColumn` $orderDir");

        // Filtering (global search)
        $search       = $request->input('search', ['value' => '']);
        $searchValue  = $search['value'] ?? '';
        $searchValues = preg_split('/\s+/', $searchValue, -1, PREG_SPLIT_NO_EMPTY);

        if ($searchValues) {
            $query->where(function ($q) use ($listFields, $searchValues) {
                foreach ($listFields as $lf => $lfData) {
                    if (empty($lfData['searching'])) {
                        continue;
                    }
                    $dbCol = str_replace('___', '.', $lf);
                    $q->orWhere(function ($inner) use ($dbCol, $searchValues, $lfData) {
                        foreach ($searchValues as $sVal) {
                            if (!is_numeric($sVal) && ($lfData['data_type'] ?? '') === 'integer') {
                                continue;
                            }
                            $inner->orWhere($dbCol, 'LIKE', "%$sVal%");
                        }
                    });
                }
            });
        }

        $filteredCount = (clone $query)->count();
        $items         = $query->skip($start)->take($perPage)->get();

        $data = [];
        foreach ($items as $item) {
            $row = [];
            foreach ($listFields as $field => $info) {
                $row[$field] = $this->recursiveFormatField($item, $field, $info);
            }
            $data[] = $row;
        }

        return $this->jsonResponse([
            'data'            => $data,
            'recordsTotal'    => $totalCount,
            'recordsFiltered' => $filteredCount,
        ]);
    }

    /**
     * Override to apply eager loads or additional constraints on the list query.
     * Receives an Eloquent Builder. PHPixie's tuneModelForList() is replaced by this.
     */
    protected function tuneQueryForList($query): void
    {
    }

    // ─── Field Rendering ───────────────────────────────────────────────────────

    /**
     * Format a single field value for display.
     * Intentional XSS preserved: htmlspecialchars is only applied to values,
     * html-type templates are rendered raw (as in the original).
     */
    public function fieldFormatter($value, $item = null, array $format = []): string
    {
        if (!empty($format['max_length'])) {
            if (strlen((string) $value) > $format['max_length']) {
                $value = substr((string) $value, 0, $format['max_length']) . '...';
            }
        }

        if (!empty($format['strip_tags'])) {
            $value = strip_tags((string) $value);
        }

        $value = ($format['value_prefix'] ?? '') . $value;
        $value = htmlspecialchars((string) $value);

        $type = $format['type'] ?? '';

        if ($type === 'image') {
            if ($value) {
                $dirPath   = $format['dir_path']   ?? '/images/';
                $maxWidth  = $format['max_width']  ?? 40;
                $maxHeight = $format['max_height'] ?? 30;
                $value = '<img src="' . $dirPath . $value . '" style="max-width: ' . $maxWidth . 'px; '
                       . 'max-height: ' . $maxHeight . 'px;" />';
            } else {
                $value = '';
            }
        }

        if ($type === 'boolean') {
            $value = '<span class="fa-boolean fa fa-circle' . ($value ? '' : '-o') . '"></span>';
        }

        if ($type === 'html' && !empty($format['template'])) {
            $controller = $this;
            $value = preg_replace_callback('/%(.+?)%/', function ($match) use ($item, $controller, $value) {
                $prop = $match[1];
                $controller->checkSubProp($prop, $matches);
                if (!empty($matches['model'])) {
                    $model     = $matches['model'];
                    $modelProp = $matches['model_prop'];
                    return $item->$model->$modelProp ?? '';
                }
                return $item->$prop ?? '';
            }, $format['template']);
        }

        $isLink = $type === 'link' || !empty($format['is_link']);

        if ($isLink && !empty($format['template'])) {
            preg_match('/%(.+?)%/', $format['template'], $matches);
            $linkProp = $matches[1] ?? null;
            if ($linkProp) {
                $this->checkSubProp($linkProp, $lpMatches);
                if (!empty($lpMatches['model'])) {
                    $lpModel     = $lpMatches['model'];
                    $lpModelProp = $lpMatches['model_prop'];
                    $linkPropValue = $item->$lpModel->$lpModelProp ?? '';
                } else {
                    $linkPropValue = $item->$linkProp ?? '';
                }
                $text  = !empty($format['text']) ? $format['text'] : $value;
                $value = '<a href="' . str_replace('%' . $linkProp . '%', $linkPropValue, $format['template']) . '">'
                       . $text . '</a>';
            }
        }

        return $value;
    }

    public function checkSubProp(string $field, &$data): void
    {
        if (strpos($field, '.') !== false) {
            if (!is_array($data)) {
                $data = [];
            }
            preg_match('/^(?<model>[^\.]*)\.(?<prop>.*)/', $field, $matches);
            $data['model']      = $matches['model'];
            $data['model_prop'] = $matches['prop'];
            $this->checkSubProp($matches['prop'], $data['model_prop']);
        }
    }

    // ─── File handling ─────────────────────────────────────────────────────────

    protected function processRequestFilesForItem(Model $item, array &$data, Request $request): void
    {
        $editFields = $this->prepareEditFields();

        foreach ($editFields as $field => $options) {
            if (!in_array($options['type'] ?? '', ['image', 'file'])) {
                continue;
            }

            $removeFieldName = 'remove_image_' . $field;
            $removeOld       = array_key_exists($removeFieldName, $data);

            if ($removeOld) {
                $this->removeExistingFile($item, $field, $options);
            }

            if (!$request->hasFile($field)) {
                continue;
            }

            $file    = $request->file($field);
            $dirPath = !empty($options['abs_path'])
                ? $options['dir_path']
                : public_path(ltrim($options['dir_path'] ?? '/upload/', '/'));

            $this->removeExistingFile($item, $field, $options);

            $fileName = time() . '_' . ($this->user ? $this->user->getKey() : 'anon') . '_' . $file->getClientOriginalName();
            $file->move($dirPath, $fileName);
            $item->$field = $fileName;
        }
    }

    protected function removeExistingFile(Model $item, string $field, array $options): void
    {
        if (!$item->getKey()) {
            return;
        }

        $existingFile = $item->$field;
        if (!$existingFile) {
            return;
        }

        $absPath = !empty($options['abs_path'])
            ? $options['dir_path'] . $existingFile
            : public_path(ltrim($options['dir_path'] ?? '/upload/', '/') . $existingFile);

        if (file_exists($absPath) && is_file($absPath) && is_writable($absPath)) {
            unlink($absPath);
        }

        $item->$field = '';
    }

    // ─── Link/Checkbox column helpers ──────────────────────────────────────────

    protected function getEditLinkProp(): array
    {
        $idField = $this->modelInstance ? $this->modelInstance->getKeyName() : 'id';
        return [
            'edit' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="/admin/' . strtolower($this->alias) . '/edit/%' . $idField . '%" class="js-edit-item">Edit</a>',
                'column_classes' => 'edit-action-column',
            ],
        ];
    }

    protected function getDeleteLinkProp(): array
    {
        $idField = $this->modelInstance ? $this->modelInstance->getKeyName() : 'id';
        return [
            'delete' => [
                'extra'          => true,
                'type'           => 'html',
                'template'       => '<a href="/admin/' . strtolower($this->alias) . '/delete/%' . $idField . '%" class="js-delete-item">Delete</a>',
                'column_classes' => 'delete-action-column',
            ],
        ];
    }

    protected function getIdCheckboxProp(): array
    {
        $idField = $this->modelInstance ? $this->modelInstance->getKeyName() : 'id';
        return [
            'cb' => [
                'extra'          => true,
                'template'       => '<input type="checkbox" name="ids[]" value="%' . $idField . '%" />',
                'title'          => '',
                'type'           => 'html',
                'column_classes' => 'cb-column',
            ],
        ];
    }

    // ─── Internal helpers ──────────────────────────────────────────────────────

    protected function recursiveCreateRelativeFieldName(string $field, $data): string
    {
        if (strpos($field, '.') !== false) {
            $parts = preg_split('/\./', $field, 2);
            return ($data['model'] ?? $parts[0]) . '___' . $this->recursiveCreateRelativeFieldName($parts[1], $data['model_prop'] ?? []);
        }
        return $field;
    }

    protected function recursiveFormatField($item, string $field, array $info): string
    {
        if (!empty($info['model'])) {
            $modelName = $info['model'];
            $modelProp = $info['model_prop'];
            if (is_array($modelProp)) {
                return $this->recursiveFormatField($item->$modelName, '', $modelProp);
            }
            return $this->fieldFormatter($item->$modelName->$modelProp ?? '', $item, $info);
        }

        if (isset($item->$field)) {
            return $this->fieldFormatter($item->$field, $item, $info);
        }

        if (!empty($info['extra'])) {
            return $this->fieldFormatter($item->getKey(), $item, $info);
        }

        return '';
    }

    /** Override in child to run logic after a successful edit save. */
    protected function onSuccessfulEdit(Model $model): void
    {
    }

    /** Override in child to mutate $item or $data before save on edit. */
    protected function preProcessEdit($item, $data): void
    {
    }
}
