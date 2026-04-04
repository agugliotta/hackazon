<?php

namespace App\Http\Controllers;

use App\Installation\Installer;
use Illuminate\Http\Request;

/**
 * Class InstallController
 * @package App\Http\Controllers
 */
class InstallController extends PageController
{
    public function index(Request $request)
    {
        try {
            /** @var Installer $installer */
            $installer = app(Installer::class);
            $installer->setForceFreshInstall(!!$request->query('force', false));
            $result = $installer->runWizard($request);

        } catch (\App\Exception\RedirectException $e) {
            return redirect($e->getLocation());

        } catch (\App\Exception\ForbiddenException $e) {
            return redirect('/install/login');
        }

        if ($result->isCompleted()) {
            app(Installer::class)->finish();
            session(['isInstalled' => true]);
            return redirect('/');
        }

        $step = $result->getStep();

        if (!$step) {
            $step = $result->getLastStartedStep();
            return redirect('/install/' . $step->getName());
        }

        if ($step->getCompleted()) {
            return redirect('/install/' . $step->getNextStep()->getName());
        }

        if ($result->needRedirect()) {
            return redirect('/install/' . $step->getName());
        }

        $data = $result->getViewData();
        $data['errorMessage'] = implode('<br>', $step->getErrors());
        $data['step']         = $step;
        $data['bodyClass']    = 'installation-page';

        return $this->view($step->getTemplate(), $data);
    }

    public function login(Request $request)
    {
        $params         = config('parameters') ?: [];
        $storedPassword = trim($params['installer_password'] ?? '');

        if (!$storedPassword) {
            return redirect('/install');
        }

        $errors = '';

        if ($request->isMethod('POST')) {
            $password = $request->input('password');

            if ($password && $password == $storedPassword) {
                session([Installer::SESSION_KEY => ['authorized' => true]]);
                return redirect('/install');
            } else {
                $errors = "Incorrect password.";
            }
        }

        return $this->view('installation.login', ['errors' => $errors]);
    }

    public function finish(Request $request)
    {
        session()->forget(Installer::SESSION_KEY);
        return redirect('/install');
    }
}
