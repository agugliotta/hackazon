<?php
/**
 * Hackazon REST API routes — migrated from assets/config/routes.php (PHPixie) to Laravel 13.
 *
 * Original PHPixie routes (preserved exactly):
 *   /api/my/<controller>(/<id>(/<property>))         → namespace App\Rest\My
 *   /api/<parent_controller>/<parent_id>/<controller>(/<id>(/<property>)) → App\Rest (parented)
 *   /api(/<controller>(/<id>(/<property>)))           → App\Rest\Controller\Default
 *
 * Laravel maps these to App\Http\Controllers\Api\*.
 * XXE vulnerability in XML endpoints is intentional — do not add xml parsing protections.
 */

use App\Http\Controllers\Api\RestController;
use Illuminate\Support\Facades\Route;

// ─── /api/my/<controller> — authenticated user's own resources ────────────────

Route::any('/my/{controller}/{id?}/{property?}', [RestController::class, 'handleMy']);

// ─── /api/<parent>/<parent_id>/<controller> — parented resources ──────────────

Route::any('/{parent_controller}/{parent_id}/{controller}/{id?}/{property?}', [RestController::class, 'handleParented'])
    ->where('parent_id', '\d+');

// ─── /api/<controller> — top-level resources ─────────────────────────────────

Route::any('/{controller?}/{id?}/{property?}', [RestController::class, 'handle']);
