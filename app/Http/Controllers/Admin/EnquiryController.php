<?php
/**
 * Migrated from App\Admin\Controller\Enquiry (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnquiryController extends CRUDController
{
    public string $modelNamePlural = 'Enquiries';
    public string $modelName       = 'Enquiry';
    public string $editView        = 'admin.enquiry.edit';

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'id' => [
                    'column_classes' => 'dt-id-column',
                ],
                'title' => [
                    'type' => 'link',
                ],
                'status',
                'creator.username' => [
                    'is_link'  => true,
                    'title'    => 'Created By',
                    'template' => '/admin/user/%creator.id%',
                ],
                'assignee.username' => [
                    'is_link'  => true,
                    'title'    => 'Assigned To',
                    'template' => '/admin/user/%assignee.id%',
                ],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function tuneModelForList(): void
    {
        // Eager load relations — sets are handled inside CRUDController via Eloquent
        $this->modelEagerLoad = ['creator', 'assignee'];
    }

    protected function getEditFields(): array
    {
        return [
            'id' => [],
            'created_by' => [
                'type'        => 'select',
                'option_list' => 'App\Http\Controllers\Admin\UserController::getAvailableUsers',
                'required'    => true,
            ],
            'assigned_to' => [
                'type'        => 'select',
                'option_list' => 'App\Http\Controllers\Admin\UserController::getAvailableUsers',
            ],
            'title' => [
                'required' => true,
            ],
            'description' => [
                'type'     => 'textarea',
                'required' => true,
            ],
            'status' => [
                'type'        => 'select',
                'option_list' => array_combine(
                    ['new', 'rejected', 'resolved'],
                    ['new', 'rejected', 'resolved']
                ),
            ],
            'created_on' => [
                'data_type' => 'date',
            ],
            'updated_on',
        ];
    }

    protected function editViewExtraData(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'enquiryMessages' => $item->messages()->orderBy('created_on', 'asc')->get(),
        ];
    }

    public function fieldFormatter($value, $item = null, array $format = []): string
    {
        $value2 = parent::fieldFormatter($value, $item, $format);
        if (($format['original_field_name'] ?? '') === 'status') {
            $value2 = '<span class="label label-' . strtolower((string)$value2) . '">' . $value . '</span>';
        }
        return $value2;
    }

    // POST /admin/enquiry/{id}/add-message
    public function addMessage(Request $request, int $id)
    {
        $redirect = $this->before();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($request->method() !== 'POST') {
            abort(405, 'Method Not Allowed');
        }

        /** @var Enquiry|null $enquiry */
        $enquiry = Enquiry::find($id);
        if (!$enquiry) {
            abort(404);
        }

        $message = $request->input('message');
        if (!$message) {
            return $this->jsonResponse(['error' => 1, 'message' => 'Please enter the message.']);
        }

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $enquiryMessage = $enquiry->createMessage($message, $authUser->id);

        if ($enquiryMessage && $enquiryMessage->exists) {
            return $this->jsonResponse([
                'success'        => 1,
                'enquiryMessage' => $enquiryMessage->toArray(),
            ]);
        }

        return $this->jsonResponse(['error' => 1, 'message' => 'Error while adding message.']);
    }
}
