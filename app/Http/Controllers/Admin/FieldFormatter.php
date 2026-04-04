<?php
/**
 * Migrated from App\Admin\FieldFormatter (PHPixie) to Laravel 13.
 * Original author: Nikolay Chervyakov, 12.09.2014
 *
 * Renders HTML form fields for admin edit views.
 * PHPixie dependency removed; model is now an Eloquent Model.
 * $pixie reference removed — option_list callables now receive no $pixie arg
 * (callables should be updated to accept [$modelInstance, $options] or no args).
 */

namespace App\Http\Controllers\Admin;

use App\Helpers\ArraysHelper;
use Illuminate\Database\Eloquent\Model;

class FieldFormatter
{
    protected Model $item;
    protected array $formatOptions = [];
    protected array $renderedFields = [];
    protected array $options = [];
    protected string $controllerAlias = '';

    public function __construct(Model $item, array $formatOptions, array $options = [])
    {
        $this->item           = $item;
        $this->formatOptions  = $formatOptions;
        $this->options        = $options;
        $this->controllerAlias = $options['alias'] ?? class_basename(get_class($item));
    }

    public function renderForm(): void
    {
        $this->renderFormStart();
        $this->renderFields();
        $this->renderSubmitButtons();
        $this->renderFormEnd();
    }

    public function renderFields(?array $fields = null): void
    {
        if ($fields === null) {
            $fields = array_keys($this->formatOptions);
        }
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            $this->renderField($field, $this->formatOptions[$field] ?? []);
        }
    }

    public function renderField(string $field, ?array $options = null): void
    {
        if (in_array($field, $this->renderedFields)) {
            return;
        }

        if (!is_array($options)) {
            $options = $this->formatOptions[$field] ?? [];
        } else {
            $options = array_merge($this->formatOptions[$field] ?? [], $options);
        }

        if (isset($options['value'])) {
            $value = $options['value'];
        } elseif (($options['type'] ?? '') === 'extra') {
            $value = $options['title'] ?? '';
        } else {
            $value = $this->item->$field ?? ($options['default_value'] ?? '');
        }

        $type         = $options['type'] ?? 'text';
        $escapedValue = htmlspecialchars((string) $value);
        $fieldId      = 'field_' . $field;
        $commonAttrs  = ' name="' . $field . '" id="' . $fieldId . '" ';
        if (!empty($options['required'])) {
            $commonAttrs .= ' required ';
        }
        $label = '<label for="' . $fieldId . '">' . ($options['label'] ?? '') . '</label>';

        echo '<div class="form-group"> ';

        $method = 'render' . strtoupper($type . 'Field');
        if (method_exists($this, $method)) {
            $this->$method();

        } elseif (!empty($options['readonly']) && in_array($type, ['text', 'textarea', 'password', 'select'])) {
            echo $label . ': ' . $escapedValue;

        } elseif ($type === 'hidden') {
            echo '<input type="hidden" value="' . $escapedValue . '" ' . $commonAttrs . ' />';

        } elseif ($type === 'textarea') {
            echo $label . '<textarea cols="40" rows="4" ' . $commonAttrs . ' '
               . 'class="form-control ' . ($options['class_names'] ?? '') . '">' . $escapedValue . '</textarea>';

        } elseif ($type === 'select') {
            $optionList = $options['option_list'] ?? [];
            if (is_callable($optionList)) {
                // Callables: pass null in place of $pixie; child classes may override with specific args
                $optionList = call_user_func_array($optionList, [null, $options]);
            } elseif (!is_array($optionList)) {
                $optionList = ArraysHelper::arrayFillEqualPairs([$optionList]);
            }
            echo $label . '<br>' . $this->renderSelect($value, $optionList, array_merge([
                'name'  => $field,
                'id'    => $fieldId,
                'class' => 'form-control ' . ($options['class_names'] ?? ''),
            ], !empty($options['required']) ? ['required' => 'required'] : []));

        } elseif ($type === 'image') {
            echo $label;
            if ($value) {
                if (!empty($options['use_external_dir'])) {
                    $src = '/upload/download.php?image=' . $escapedValue;
                } else {
                    $src = htmlspecialchars(($options['dir_path'] ?? '') . $value);
                }
                echo '<br><img src="' . $src . '" alt="" '
                   . 'class="model-image model-' . htmlspecialchars(class_basename(get_class($this->item))) . '-image" /> <br>'
                   . '<label><input type="checkbox" name="remove_image_' . htmlspecialchars($field) . '" /> Remove image</label>';
            }
            echo '<br><input type="file" ' . $commonAttrs . ' class="file-input btn btn-default btn-primary btn-lg" '
               . 'title="Select image" value="' . $escapedValue . '">';

        } elseif ($type === 'boolean') {
            $checked = $value
                ? ' checked '
                : (!$this->item->getKey() && !empty($options['default_value']) ? ' checked ' : '');
            echo $label . ' <input type="checkbox" ' . $commonAttrs . $checked
               . ' class="form-horizontal ' . ($options['class_names'] ?? '') . '" value="1" />';

        } else {
            $dataType = ($options['data_type'] ?? '') === 'email' ? 'email' : 'text';
            echo $label . '<input type="' . $dataType . '" value="' . $escapedValue . '" '
               . $commonAttrs . ' class="form-control ' . ($options['class_names'] ?? '') . '"/>';
        }

        echo '</div>';
        $this->renderedFields[] = $field;
    }

    public function renderFormStart(): void
    {
        $enctype   = $this->hasFiles() ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
        $operation = $this->item->getKey() ? '/edit/' . $this->item->getKey() : '/new';
        echo '<form method="post" action="/admin/' . strtolower($this->controllerAlias) . $operation . '" '
           . 'enctype="' . $enctype . '" '
           . 'class="model-form model-' . class_basename(get_class($this->item)) . '-form">';
    }

    public function renderFormEnd(): void
    {
        echo '</form>';
    }

    public function hasFiles(): bool
    {
        foreach ($this->formatOptions as $options) {
            if (in_array($options['type'] ?? '', ['file', 'image'])) {
                return true;
            }
        }
        return false;
    }

    public function renderSelect($selectedValue = null, array $optionList = [], array $attributes = []): string
    {
        $result   = [];
        $result[] = '<select ' . $this->mergeAttributes($attributes) . '>';
        foreach ($optionList as $value => $label) {
            $result[] = '<option value="' . htmlspecialchars((string) $value) . '" '
                      . ($value == $selectedValue ? ' selected' : '') . '>' . htmlspecialchars((string) $label) . '</option>';
        }
        $result[] = '</select>';
        return implode("\n", $result);
    }

    public function renderSubmitButtons(): void
    {
        if ($this->item->getKey()) {
            echo '<a class="btn btn-primary" '
               . 'href="/admin/' . $this->controllerAlias . '/new/">Add new</a> ';
            echo '<a class="btn btn-danger js-delete-item" '
               . 'href="/admin/' . $this->controllerAlias . '/delete/' . $this->item->getKey() . '">Delete</a> ';
        }

        $name = $this->item->getKey() ? 'Save' : 'Add';
        echo '<button class="btn btn-primary" type="submit">' . $name . '</button> ';
    }

    public function mergeAttributes(array $attributes): string
    {
        $attrs = [];
        array_walk($attributes, function ($value, $attr) use (&$attrs) {
            $attrs[] = htmlspecialchars((string) $attr) . '="' . htmlspecialchars((string) $value) . '"';
        });
        return implode(' ', $attrs);
    }
}
