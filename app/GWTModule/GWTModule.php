<?php
/**
 * Migrated from GWTModule/GWTModule (PHPixie) to Laravel 13.
 * PHPixie config/logger calls replaced with Laravel equivalents.
 * GWT compiled JavaScript is untouched — only the PHP RPC backend is wired here.
 */

namespace GWTModule;

use App\Pixie;

/**
 * GWT Module which sets up a GWTPHP gateway for the application.
 */
class GWTModule
{
    /** @var Pixie */
    public Pixie $pixie;

    protected bool $initialized = false;

    /** @var RemoteServiceServlet|null */
    protected ?RemoteServiceServlet $servlet = null;

    public function __construct(Pixie $pixie)
    {
        $this->pixie = $pixie;
    }

    protected function init(): void
    {
        if ($this->initialized) {
            return;
        }

        // Logger configuration (log4php) — replaced with Laravel logging
        // \Logger::configure() removed; log4php not available in Laravel 13.
        // Log output goes through Laravel's logging channels instead.

        $mapsDir = realpath(__DIR__ . '/../../../gwtphp-maps')
            ?: base_path('app/GWTModule/gwtphp-maps');

        if (class_exists(\GWTPHPContext::class)) {
            \GWTPHPContext::getInstance()->setServicesRootDir($mapsDir);

            if (defined('GWTPHP_DIR')) {
                \GWTPHPContext::getInstance()->setGWTPHPRootDir(GWTPHP_DIR);
            }
        }

        $this->initialized = true;
    }

    public function getServlet(): RemoteServiceServlet
    {
        if ($this->servlet !== null) {
            return $this->servlet;
        }

        $this->init();

        $this->servlet = new RemoteServiceServlet($this->pixie);

        if (class_exists(\FolderMappedClassLoader::class)) {
            $mappedClassLoader = new \FolderMappedClassLoader();
            $this->servlet->setMappedClassLoader($mappedClassLoader);
        }

        return $this->servlet;
    }
}
