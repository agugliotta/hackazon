<?php

namespace App\Http\Controllers;

use App\Models\File as FileModel;
use App\Models\Order;
use App\Models\User;
use App\Services\UserPictureUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class AccountController
 * @package App\Http\Controllers
 *
 * VULNERABILITY NOTES:
 * - action_help_articles: RFI — page parameter used in file include without validation
 *   when RemoteFileInclude vuln is enabled (bypasses the file-list whitelist check).
 * - action_documents: OS Command Injection — path passed to exec() without
 *   escapeshellarg() when OSCommand vuln is enabled.
 * - action_edit_profile: XSS — stored user fields wrapped via wrapValueByPath()
 *   which outputs raw HTML when XSS vuln is active.
 */
class AccountController extends PageController
{
    protected bool $useRest = false;

    /**
     * Require authentication before any account action.
     */
    protected function before(): mixed
    {
        if (is_null(Auth::user())) {
            return redirect('/user/login?return_url=' . rawurlencode(request()->server('REQUEST_URI')));
        }

        $this->useRest = config('parameters.rest_in_profile', false);

        return parent::before();
    }

    /**
     * @Vuln\Description("Used view: account/account")
     */
    public function index(Request $request)
    {
        $myOrders = [];

        if (!$this->useRest) {
            $myOrders = (new Order)->getMyOrdersPager(1, 5);
        }

        $service  = $this->vulnService;
        $user     = Auth::user();
        $userData = $user->toArray();

        // wrapValueByPath injects XSS payload when XSS vuln is enabled (intentional)
        foreach ($userData as $key => $value) {
            $userData[$key] = $service->wrapValueByPath(
                $value,
                'default->account->edit_profile|' . $key . ':body|0',
                true
            );
        }

        return $this->view('account.account', [
            'myOrders' => $myOrders,
            'user'     => $user,
            'userData' => $userData,
            'useRest'  => $this->useRest,
        ]);
    }

    /**
     * @Vuln\Route(params={"id" : "[id]"})
     * @Vuln\Description("Used views: account/orders, account/order, depending on the presence of the parameter 'id'.")
     */
    public function orders(Request $request)
    {
        // $orderId taken directly from route — IDOR: no ownership check beyond loaded()
        $orderId = $request->route('id') ?? $request->query('id');

        if ($orderId) {
            $order = Order::getByIncrement($orderId);

            if (!$order) {
                abort(404);
            }

            return $this->view('account.order', [
                'id'         => $orderId,
                'order'      => $order,
                'orderItems' => $order->orderItems()->get()->all(),
            ]);

        } else {
            $page = (int) $request->query('page', 1);
            $myOrders = Order::orderBy('created_at', 'DESC')
                ->paginate(10, ['*'], 'page', $page);

            return $this->view('account.orders', [
                'pager'    => $myOrders,
                'myOrders' => $myOrders->items(),
            ]);
        }
    }

    /**
     * Documents — OS Command Injection preserved when OSCommand vuln is enabled.
     * @Vuln\Description("Views: account/document, account/documents")
     */
    public function documents(Request $request)
    {
        if ($request->query('page')) {
            $page     = $request->query('page');
            $basePath = realpath(base_path('../content_pages/documents'));
            $path     = $basePath . DIRECTORY_SEPARATOR . $page;

            // OSCommand vuln: check field-level vulnerability on the 'page' query param
            $ctx = $this->vulnService->getConfig()->getCurrentContext();
            $pageField = $ctx->getField('page');
            $isOsCommandVuln = $pageField ? $pageField->getVulnerability('OSCommand') : null;

            if (!$isOsCommandVuln || !$isOsCommandVuln->isEnabled()) {
                $path = escapeshellarg($path);
            }

            $content = [];
            if (stristr(php_uname('s'), 'Windows NT')) {
                exec('type ' . $path, $content);
            } else {
                exec('cat ' . $path, $content);
            }

            // XSS: pageTitle uses ucwords on raw $page — no sanitization
            $pageTitle = ucwords(preg_replace('/\.html$/i', '', $page));

            return $this->view('account.document', [
                'pageTitle'   => $pageTitle,
                'pageContent' => implode("\n", $content),
            ]);

        } else {
            $files    = [];
            $basePath = base_path('../content_pages/documents');

            if (is_dir($basePath)) {
                $dirIterator = new \DirectoryIterator($basePath);
                foreach ($dirIterator as $fileInfo) {
                    if ($fileInfo->isFile()
                        && preg_match('/html/i', $fileInfo->getExtension())
                        && $fileInfo->isReadable()
                    ) {
                        $pathinfo = pathinfo($fileInfo->getRealPath());
                        $files[$pathinfo['filename']] = $pathinfo['basename'];
                    }
                }
            }

            return $this->view('account.documents', [
                'pageTitle' => 'Documents',
                'files'     => $files,
            ]);
        }
    }

    /**
     * Help Articles — RFI vulnerability preserved when RemoteFileInclude vuln is enabled.
     *
     * When RemoteFileInclude is active, the whitelist check is bypassed and the $page
     * parameter can point to any file (including remote URLs), matching the original
     * PHPixie behaviour exactly.
     *
     * @Vuln\Description("Views: account/help_article, account/help_articles")
     */
    public function helpArticles(Request $request)
    {
        if ($request->query('page')) {
            $page = $request->query('page');

            // RFI: check field-level vulnerability on the 'page' query param
            $ctx = $this->vulnService->getConfig()->getCurrentContext();
            $pageField = $ctx->getField('page');
            $isRfiVuln = $pageField ? $pageField->getVulnerability('RemoteFileInclude') : null;

            if (!$isRfiVuln || !$isRfiVuln->isEnabled()) {
                $files = $this->getHelpArticlesFiles();
                if (!in_array($page, $files)) {
                    abort(404);
                }
            }

            // XSS: pageTitle built from raw $page — no sanitization
            $pageTitle = ucwords(str_replace('_', ' ', $page));

            // RFI: include the page file (local or remote when RFI enabled)
            // Replicates PHPixie view behavior: chdir to help_articles, then include($page.'.php')
            $basePath = base_path('../content_pages/help_articles');
            $pageContent = '';
            $prevCwd = getcwd();
            chdir($basePath);
            try {
                // Strip null bytes like original (preg_split on \n\r\0)
                $includePath = preg_split("/[\n\r\0]/", $page . '.php')[0];
                ob_start();
                include trim($includePath);
                $pageContent = ob_get_clean();
            } catch (\Exception $e) {
                ob_end_clean();
            }
            chdir($prevCwd);

            return $this->view('account.help_article', [
                'pageTitle'   => $pageTitle,
                'pageContent' => $pageContent,
            ]);

        } else {
            return $this->view('account.help_articles', [
                'pageTitle' => 'Help Articles',
                'files'     => $this->getHelpArticlesFiles(),
            ]);
        }
    }

    protected function getHelpArticlesFiles(): array
    {
        $files    = [];
        $basePath = base_path('../content_pages/help_articles');

        if (is_dir($basePath)) {
            $dirIterator = new \DirectoryIterator($basePath);
            foreach ($dirIterator as $fileInfo) {
                if ($fileInfo->isFile()
                    && preg_match('/php/i', $fileInfo->getExtension())
                    && $fileInfo->isReadable()
                ) {
                    $pathinfo = pathinfo($fileInfo->getRealPath());
                    $files[str_replace('_', ' ', $pathinfo['filename'])] = $pathinfo['filename'];
                }
            }
        }

        return $files;
    }

    /**
     * @Vuln\Route(name="profile_edit")
     * @Vuln\Description("View: account/edit_profile")
     */
    public function editProfile(Request $request)
    {
        if ($this->useRest) {
            abort(404);
        }

        $user   = $this->getUser();
        $fields = ['first_name', 'last_name', 'user_phone'];
        $errors = [];

        if ($request->isMethod('POST')) {
            $this->checkCsrfToken('profile');

            // Validate photo upload (type/extension check)
            $photo = $request->file('photo');
            if ($photo) {
                $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png'];
                $allowedMimeTypes  = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
                if (!in_array(strtolower($photo->getClientOriginalExtension()), $allowedExtensions)
                    || !in_array($photo->getMimeType(), $allowedMimeTypes)
                ) {
                    $errors[] = 'Incorrect avatar file';
                }
            }

            $data = [];
            foreach ($fields as $field) {
                // Raw input — no sanitization (XSS stored via profile fields preserved)
                $data[$field] = $request->input($field);
            }

            if (!count($errors)) {
                $uploader = new UserPictureUploader($user, $photo, $request->input('remove_photo'));
                $uploader->execute();

                foreach ($data as $field => $value) {
                    $user->$field = $value;
                }
                $user->save();

                session()->flash('success', 'You have successfully updated your profile.');

                if ($request->input('_submit_save_and_exit')) {
                    return redirect('/account#profile');
                }

                return redirect('/account/profile/edit');
            }

            $data['photo'] = $user->photo;

        } else {
            $service = $this->vulnService;
            $data    = [];

            foreach (array_merge($fields, ['photo']) as $field) {
                // wrapValueByPath injects XSS payload when XSS vuln is enabled
                $data[$field] = $service->wrapValueByPath(
                    $user->$field,
                    'default->account->edit_profile|' . $field . ':body|0',
                    true
                );
            }
        }

        $photoUrl = null;
        if (!empty($data['photo'])) {
            $photoUrl = $data['photo'];
        }

        if (isset($data['photo']) && is_numeric($data['photo'])) {
            $photoObj = FileModel::find($data['photo']);
            if ($photoObj && $photoObj->user_id == $user->id) {
                $photoUrl = preg_replace(
                    '#.*?([^\\\\/]{2}[\\\\/][^\\\\/]+)$#',
                    '$1',
                    $photoObj->path
                );
            }
        }

        $viewData = $data;
        $viewData['photoUrl']     = $photoUrl;
        $viewData['success']      = session()->pull('success', '');
        $viewData['errorMessage'] = implode('<br>', $errors);
        $viewData['user']         = $user;

        return $this->view('account.edit_profile', $viewData);
    }

    /**
     * @Vuln\Description("No views are used. Only processing action.")
     */
    public function addPhoto(Request $request)
    {
        if (!$request->isMethod('POST')) {
            abort(405, 'Method Not Allowed');
        }

        $user   = $this->getUser();
        $errors = [];

        $photo = $request->file('photo');
        if ($photo) {
            $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png'];
            $allowedMimeTypes  = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
            if (!in_array(strtolower($photo->getClientOriginalExtension()), $allowedExtensions)
                || !in_array($photo->getMimeType(), $allowedMimeTypes)
            ) {
                $errors[] = 'Incorrect avatar file';
            }
        }

        if (!count($errors)) {
            $uploader = new UserPictureUploader($user, $photo, $request->input('remove_photo'));
            $uploader->setModifyUser(false);
            $uploader->execute();

            $result   = $uploader->getResult();
            $photoUrl = null;

            if ($result && is_numeric($result)) {
                $photoObj = FileModel::find($result);
                if ($photoObj && $photoObj->user_id == $user->id) {
                    $photoUrl = preg_replace(
                        '#.*?([^\\\\/]{2}[\\\\/][^\\\\/]+)$#',
                        '$1',
                        $photoObj->path
                    );
                }
            }

            return response()->json(['photo' => $result, 'photoUrl' => $photoUrl]);
        }

        return response()->json(['errors' => $errors]);
    }

    public function getUser(): User
    {
        return Auth::user();
    }
}
