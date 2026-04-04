<?php
/**
 * Migrated from App\Rest\Controller (PHPixie) to Laravel 13.
 *
 * Handles /api, /api/my, and /api/<parent>/<id>/<child> routing patterns.
 * XXE vulnerability in XML endpoint is intentional — do NOT add xml entity protection.
 * IDOR is intentional — do NOT add ownership checks that weren't in the original.
 *
 * @Vuln\Route("rest")
 */

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VulnModule\Config\Context;

class RestController extends Controller
{
    const FORMAT_JSON = 'application/json';
    const FORMAT_XML  = 'application/xml';
    const FORMAT_HTML = 'text/html';

    protected string  $prefix          = '/api/';
    protected ?string $modelName       = null;
    protected ?Model  $modelInstance   = null;
    protected ?Model  $item            = null;
    protected ?User   $user            = null;
    protected bool    $isSubRequest    = false;
    protected bool    $isCollection    = false;
    protected string  $responseFormat  = self::FORMAT_JSON;
    protected int     $defaultPerPage  = 10;
    protected int     $maxPerPage      = 100;
    protected int     $perPage         = 10;
    protected array   $meta            = [];

    // ─── Laravel route entry points ───────────────────────────────────────────

    /** /api/my/<controller>/<id?>/<property?> */
    public function handleMy(Request $request, string $controller, $id = null, $property = null)
    {
        $this->isSubRequest = true;
        return $this->dispatch($request, $controller, $id, $property, 'my');
    }

    /** /api/<parent>/<parent_id>/<controller>/<id?>/<property?> */
    public function handleParented(Request $request, string $parent_controller, $parent_id, string $controller, $id = null, $property = null)
    {
        return $this->dispatch($request, $controller, $id, $property);
    }

    /** /api/<controller?>/<id?>/<property?> */
    public function handle(Request $request, $controller = null, $id = null, $property = null)
    {
        return $this->dispatch($request, $controller ?: 'Default', $id, $property);
    }

    // ─── Core dispatch ────────────────────────────────────────────────────────

    protected function dispatch(Request $request, string $controllerName, $id, $property, string $namespace = '')
    {
        $this->prepareContentType($request);
        $this->user = Auth::user();

        $this->modelName = $this->resolveModelName($controllerName);

        if ($this->modelName) {
            $modelClass = 'App\\Models\\' . $this->modelName;
            if (class_exists($modelClass)) {
                $this->modelInstance = new $modelClass();
            }
        }

        try {
            // Pre-load item if ID given (IDOR preserved — no ownership check here)
            if ($id && $this->modelInstance) {
                $this->item = $this->modelInstance->newQuery()->find($id);
                if (!$this->item) {
                    return $this->errorResponse('Not Found', 404);
                }
            }

            $action = $this->resolveAction($request, $id);

            $result = $this->runAction($action, $request, $id, $property, $controllerName);

            return $this->buildResponse($result, $request);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Not Found', 404);
        } catch (\Exception $e) {
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : ($e->getCode() ?: 500);
            return $this->errorResponse($e->getMessage(), (int)$code);
        }
    }

    protected function resolveModelName(string $controllerName): ?string
    {
        if (!$controllerName || $controllerName === 'Default') {
            return null;
        }
        // Map hyphenated or underscored names to CamelCase model names
        $map = [
            'cart-items'      => 'CartItem',
            'cartitems'       => 'CartItem',
            'cart_items'      => 'CartItem',
            'order-addresses' => 'OrderAddress',
            'orderaddresses'  => 'OrderAddress',
            'order_addresses' => 'OrderAddress',
            'contact-messages' => 'ContactMessage',
            'contactmessages'  => 'ContactMessage',
            'contact_messages' => 'ContactMessage',
            'customer-address' => 'CustomerAddress',
            'customeraddress'  => 'CustomerAddress',
            'customer_address' => 'CustomerAddress',
        ];
        $lower = strtolower($controllerName);
        if (isset($map[$lower])) {
            return $map[$lower];
        }
        return ucfirst($controllerName);
    }

    protected function resolveAction(Request $request, $id): string
    {
        $method = strtolower($request->method());
        if ($method === 'get' && !$id) {
            $this->isCollection = true;
            return 'getCollection';
        }
        $actionMap = [
            'get'    => 'get',
            'post'   => 'post',
            'put'    => 'put',
            'patch'  => 'patch',
            'delete' => 'delete',
            'options'=> 'options',
            'head'   => 'head',
        ];
        return $actionMap[$method] ?? 'get';
    }

    protected function runAction(string $action, Request $request, $id, $property, string $controllerName)
    {
        // Route to resource-specific handler if available
        $specificClass = 'App\\Http\\Controllers\\Api\\Resource\\' . ucfirst($controllerName) . 'Resource';

        switch ($action) {
            case 'get':
                return $this->actionGet($request);
            case 'head':
                return $this->actionGet($request);
            case 'getCollection':
                return $this->actionGetCollection($request);
            case 'post':
                return $this->actionPost($request);
            case 'put':
                return $this->actionPut($request);
            case 'patch':
                return $this->actionPatch($request);
            case 'delete':
                return $this->actionDelete($request);
            case 'options':
                return $this->actionOptions($request);
            default:
                abort(405);
        }
    }

    // ─── Default CRUD actions (IDOR preserved — no ownership checks) ───────────

    protected function actionGet(Request $request)
    {
        return $this->item ? $this->item->toArray() : null;
    }

    protected function actionGetCollection(Request $request)
    {
        if (!$this->modelInstance) {
            return [];
        }
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min($this->maxPerPage, max(1, (int) $request->query('per_page', $this->perPage)));

        $query  = $this->modelInstance->newQuery();
        $this->adjustOrder($request, $query);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $this->meta = [
            'page'        => $paginator->currentPage(),
            'total_items' => $paginator->total(),
            'pages'       => $paginator->lastPage(),
            'per_page'    => $paginator->perPage(),
        ];

        if ($paginator->previousPageUrl()) {
            $this->meta['prev_page'] = $page - 1;
        }
        if ($paginator->nextPageUrl()) {
            $this->meta['next_page'] = $page + 1;
        }

        return $paginator->items();
    }

    protected function actionPost(Request $request)
    {
        if (!$this->modelInstance) {
            abort(404);
        }
        $data = $request->all();
        unset($data[$this->modelInstance->getKeyName()]);

        $this->modelInstance->fill(array_intersect_key($data, array_flip($this->modelInstance->getFillable())));
        $this->modelInstance->save();
        $this->item = $this->modelInstance;

        return $this->item->toArray();
    }

    protected function actionPut(Request $request)
    {
        if (!$this->item) {
            abort(404);
        }
        $data = $request->all();
        unset($data[$this->item->getKeyName()]);
        $this->item->fill(array_intersect_key($data, array_flip($this->item->getFillable())));
        $this->item->save();
        return $this->item->toArray();
    }

    protected function actionPatch(Request $request)
    {
        if (!$this->item) {
            abort(404);
        }
        $data = $request->all();
        $this->item->fill(array_intersect_key($data, array_flip($this->item->getFillable())));
        $this->item->save();
        return $this->item->toArray();
    }

    protected function actionDelete(Request $request)
    {
        if (!$this->item) {
            abort(404);
        }
        $this->item->delete();
        return null;
    }

    protected function actionOptions(Request $request)
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD', 'TRACE'];
        return response('', 200)->header('Allow', implode(',', $methods));
    }

    // ─── Response building ────────────────────────────────────────────────────

    protected function buildResponse($data, Request $request)
    {
        if ($data instanceof \Illuminate\Http\Response) {
            return $data;
        }

        if ($this->isCollection && is_array($data)) {
            $data = array_merge(['data' => $data], $this->meta);
        }

        if ($this->responseFormat === self::FORMAT_XML) {
            // XXE vulnerability preserved — no external entity protection
            $xml = $this->toXML(is_array($data) ? $data : ($data !== null ? [$data] : []));
            return response($xml, 200)->header('Content-Type', self::FORMAT_XML . '; charset=utf-8');
        }

        $json = $data !== null ? json_encode($data) : '{}';
        return response($json, 200)->header('Content-Type', self::FORMAT_JSON . '; charset=utf-8');
    }

    protected function errorResponse(string $message, int $status)
    {
        if ($this->responseFormat === self::FORMAT_XML) {
            $xml = $this->toXML(['error' => $message, 'status' => $status]);
            return response($xml, $status)->header('Content-Type', self::FORMAT_XML . '; charset=utf-8');
        }
        return response()->json(['error' => $message, 'status' => $status], $status)
            ->header('Content-Type', self::FORMAT_JSON . '; charset=utf-8');
    }

    // ─── XML helpers (XXE intentionally unprotected) ─────────────────────────

    public function toXML(array $data, string $rootName = 'root'): string
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><{$rootName}></{$rootName}>");
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }

    protected function arrayToXml(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \stdClass) {
                $value = (array) $value;
            }
            if (is_array($value)) {
                $node = is_numeric($key) ? $xml->addChild("item{$key}") : $xml->addChild((string)$key);
                $this->arrayToXml($value, $node);
            } else {
                $xml->addChild((string)$key, htmlspecialchars((string)$value));
            }
        }
    }

    // ─── Content negotiation ─────────────────────────────────────────────────

    protected function prepareContentType(Request $request): void
    {
        $fmt = $request->query('_format');
        if ($fmt === 'xml') {
            $this->responseFormat = self::FORMAT_XML;
            return;
        }
        if ($fmt === 'json') {
            $this->responseFormat = self::FORMAT_JSON;
            return;
        }

        $accept = $request->header('Accept', '');
        if (str_contains($accept, 'application/xml') && !str_contains($accept, 'application/json')) {
            $this->responseFormat = self::FORMAT_XML;
        }
    }

    // ─── Order helper ─────────────────────────────────────────────────────────

    protected function adjustOrder(Request $request, $query): void
    {
        $order   = in_array(strtolower($request->query('order', 'asc')), ['asc', 'desc'])
            ? strtolower($request->query('order', 'asc'))
            : 'asc';
        $orderBy = $request->query('order_by', '');

        if ($orderBy && $this->modelInstance) {
            $fillable = $this->modelInstance->getFillable();
            if (in_array($orderBy, $fillable)) {
                $query->orderBy($orderBy, $order);
            }
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function underscorifyName(string $name): string
    {
        return strtolower(preg_replace('/(?<=.)([A-Z]+)/', '_$1', $name));
    }
}
