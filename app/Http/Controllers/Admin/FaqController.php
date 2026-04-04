<?php
/**
 * Migrated from App\Admin\Controller\Faq (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

class FaqController extends CRUDController
{
    public string $modelNamePlural = 'FAQs';
    public string $modelName       = 'Faq';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'faqID' => [
                    'title'          => 'Id',
                    'column_classes' => 'dt-id-column',
                ],
                'question' => [
                    'type'       => 'link',
                    'max_length' => '80',
                    'strip_tags' => true,
                ],
                'answer' => [
                    'max_length' => '80',
                    'strip_tags' => true,
                ],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        return [
            'faqID'    => [],
            'question' => ['required' => true],
            'answer'   => ['type' => 'textarea', 'required' => true],
            'email',
        ];
    }
}
