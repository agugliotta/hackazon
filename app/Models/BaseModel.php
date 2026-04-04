<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base Eloquent model for Hackazon.
 * Disables Laravel's created_at/updated_at management where tables don't have them.
 */
class BaseModel extends Model
{
    // Subclasses override these as needed
}
