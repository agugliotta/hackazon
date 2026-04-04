<?php

namespace App;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use VulnModule\AnnotationReader as VulnAnnotationReader;
use VulnModule\Config\ContextMetadataFactory;
use VulnModule\Config\Context;

/**
 * Compatibility adapter replacing PHPixie's Pixie DI container.
 * Provides the interface expected by VulnModule classes.
 */
class Pixie
{
    /** @var PixieContainer */
    public $container;

    /** @var \VulnModule\VulnInjection\Service */
    public $vulnService;

    /** @var \VulnModule\VulnInjection */
    public $vulninjection;

    /** @var PixieRouter */
    public $router;

    /** @var PixieOrm */
    public $orm;

    /** @var AnnotationReader */
    public $annotationReader;

    /** @var array */
    public $assets_dirs = [];

    /** @var array */
    protected $instances = [];

    public function __construct()
    {
        $this->container = new PixieContainer($this);
        $this->router = new PixieRouter();
        $this->orm = new PixieOrm();
        $this->annotationReader = new VulnAnnotationReader($this);

        Pixifier::getInstance()->setPixie($this);
    }

    public function addInstance(string $name, $object): void
    {
        $this->instances[$name] = $object;
        if ($name === 'vulnService') {
            $this->vulnService = $object;
        }
    }

    /**
     * PHPixie compat — returns the current Laravel Request (replaces PHPixie http_request()).
     */
    public function http_request(): \Illuminate\Http\Request
    {
        return request();
    }

    public function __get(string $name)
    {
        if ($name === 'config') {
            static $config;
            return $config ??= new PixieConfig();
        }
        return $this->instances[$name] ?? null;
    }
}

/**
 * ArrayAccess container providing annotation.reader and context_metadata_factory.
 */
class PixieContainer implements \ArrayAccess
{
    protected Pixie $pixie;
    protected array $services = [];

    public function __construct(Pixie $pixie)
    {
        $this->pixie = $pixie;
    }

    public function offsetGet($offset): mixed
    {
        if (!isset($this->services[$offset])) {
            $this->services[$offset] = $this->build($offset);
        }
        return $this->services[$offset];
    }

    protected function build(string $key): mixed
    {
        if ($key === 'annotation.reader') {
            try {
                return new DoctrineAnnotationReader();
            } catch (\Exception $e) {
                return null;
            }
        }

        if ($key === 'vulnerability.context_metadata_factory') {
            $factory = new ContextMetadataFactory($this->pixie->annotationReader);
            $factory->addNamespace('App\\Http\\Controllers\\', Context::TECH_GENERIC);
            $factory->addNamespace('App\\Http\\Controllers\\', Context::TECH_WEB);
            $factory->addNamespace('App\\AmfphpModule\\Services\\', Context::TECH_AMF);
            $factory->addNamespace('App\\Http\\Controllers\\Api\\', Context::TECH_REST);
            $factory->addNamespace('', Context::TECH_GWT);
            return $factory;
        }

        return null;
    }

    public function offsetExists($offset): bool { return true; }
    public function offsetSet($offset, $value): void { $this->services[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->services[$offset]); }
}

/**
 * Stub for PHPixie router — provides generateUrl() for admin vulnerability UI.
 */
class PixieRouter
{
    public function generateUrl(string $name, array $params = [], bool $absolute = false, string $protocol = 'http', bool $prepend = true): string
    {
        return route($name, $params);
    }
}

/**
 * Stub for PHPixie ORM — provides get() for ModelInfoRepository.
 */
class PixieOrm
{
    public function get(string $modelName): ?\Illuminate\Database\Eloquent\Model
    {
        $class = 'App\\Models\\' . ucfirst($modelName);
        if (class_exists($class)) {
            return new $class();
        }
        return null;
    }
}

/**
 * Stub for PHPixie config accessor used by AmfphpModule\Core\Config.
 */
class PixieConfig
{
    public function get(string $key, $default = null)
    {
        // Map PHPixie config keys to Laravel config
        $map = [
            'parameters.display_errors' => config('app.debug', false),
        ];
        return $map[$key] ?? $default;
    }
}
