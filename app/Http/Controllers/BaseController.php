<?php

namespace App\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use VulnModule\Config\FieldDescriptor;
use VulnModule\Csrf\CsrfToken;
use VulnModule\VulnInjection\Service as VulnService;

/**
 * Base Laravel controller replacing PHPixie's BaseController.
 * Sets up VulnModule context per controller/action.
 */
class BaseController extends Controller
{
    use AuthorizesRequests;

    const TOKEN_PREFIX = '_csrf_';

    /** @var VulnService */
    protected VulnService $vulnService;

    /** @var Request */
    protected Request $request;

    /** @var UserModel|null */
    protected ?UserModel $currentUser = null;

    protected bool $installationProcess = false;

    public function __construct()
    {
        $this->vulnService = app(VulnService::class);
    }

    /**
     * Override callAction to mimic PHPixie's before()/run()/after() lifecycle.
     */
    public function callAction($method, $parameters)
    {
        $this->request = request();

        // Set up controller-level vulnerability context
        $controllerName = $this->getControllerName();
        $this->vulnService->loadAndAddChildContext($controllerName);
        $this->vulnService->goDown($controllerName);
        $this->vulnService->getConfig()->getCurrentContext()->setRequest($this->request);
        $this->vulnService->setRequest($this->request);

        // Set up action-level context — convert camelCase to snake_case to match vuln config keys
        $actionName = strtolower(preg_replace('/(?<=.)([A-Z])/', '_$1', $method));
        $this->vulnService->goDown($actionName);
        $this->vulnService->getConfig()->getCurrentContext()->setRequest($this->request);

        // Check referrer via VulnModule
        $this->vulnService->checkReferrer();

        // Run before hook
        $beforeResponse = $this->before();
        if ($beforeResponse !== null) {
            // Clean up contexts before returning
            $this->vulnService->goUp(); // action
            $this->vulnService->goUp(); // controller
            return $beforeResponse;
        }

        // Execute action
        $response = parent::callAction($method, $parameters);

        // Run after hook
        $afterResponse = $this->after($response);

        // Clean up contexts
        $this->vulnService->goUp(); // action
        $this->vulnService->goUp(); // controller

        return $afterResponse ?? $response;
    }

    /**
     * Override in subclasses for pre-action logic. Return a response to abort.
     */
    protected function before(): mixed
    {
        return null;
    }

    /**
     * Override in subclasses for post-action logic.
     */
    protected function after(mixed $response): mixed
    {
        return null;
    }

    protected function getControllerName(): string
    {
        $className = class_basename(static::class);
        // Strip "Controller" suffix to match vuln config filenames (e.g. AccountController → account)
        return strtolower(preg_replace('/Controller$/i', '', $className));
    }

    // ─── CSRF helpers (VulnModule-aware) ──────────────────────────────────────

    public function isTokenValid(string $tokenId, ?string $value = null): bool
    {
        $context = $this->vulnService->getConfig()->getCurrentContext();
        if ($context->getVulnerability('CSRF') && $context->getVulnerability('CSRF')->isEnabled()) {
            return true;
        }

        $fullTokenId = self::TOKEN_PREFIX . $tokenId;

        if ($value === null) {
            $source = in_array($this->request->method(), ['POST', 'PATCH', 'PUT'])
                ? FieldDescriptor::SOURCE_BODY
                : FieldDescriptor::SOURCE_QUERY;
            $value = $source === FieldDescriptor::SOURCE_BODY
                ? $this->request->input($fullTokenId)
                : $this->request->query($fullTokenId);
        }

        if (!$value) {
            return false;
        }

        return $this->vulnService->getTokenManager()->isTokenValid(new CsrfToken($fullTokenId, $value));
    }

    public function removeToken(string $tokenId): void
    {
        $fullTokenId = self::TOKEN_PREFIX . $tokenId;
        $this->vulnService->getTokenManager()->removeToken($fullTokenId);
    }

    public function getToken(string $tokenId): string
    {
        $token = $this->vulnService->getTokenManager()->getToken(self::TOKEN_PREFIX . $tokenId);
        return $token->getValue();
    }

    public function renderTokenField(string $tokenId): string
    {
        return $this->vulnService->renderTokenField(self::TOKEN_PREFIX . $tokenId);
    }

    public function checkCsrfToken(string $tokenId, ?string $tokenValue = null, bool $removeToken = true): void
    {
        if (!$this->isTokenValid($tokenId, $tokenValue)) {
            abort(400, 'Invalid token!');
        }
        if ($removeToken) {
            $this->removeToken($tokenId);
        }
    }

    // ─── Auth helpers ──────────────────────────────────────────────────────────

    protected function getAuthUser(): ?UserModel
    {
        if ($this->currentUser === null) {
            $this->currentUser = Auth::user();
        }
        return $this->currentUser;
    }

    protected function redirect(string $url)
    {
        return redirect($url);
    }

    // ─── Utility ──────────────────────────────────────────────────────────────

    public function get_real_class(object $obj): string
    {
        return class_basename(get_class($obj));
    }
}
