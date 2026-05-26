<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base Controller for NSIA Fleet Management System.
 *
 * Extends Laravel's Routing Controller and pulls in:
 *   - AuthorizesRequests  → $this->authorize(), $this->authorizeResource()
 *   - DispatchesJobs      → $this->dispatch(), $this->dispatchNow()
 *   - ValidatesRequests   → $this->validate(), $this->validateWithBag()
 *
 * NOTE: In Laravel 11, this file is NOT generated automatically.
 * You must create it manually — which is what this file does.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
