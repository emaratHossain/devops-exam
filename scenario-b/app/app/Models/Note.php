<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Only used for create, update and delete.
 * All reads go through the query builder (DB::table).
 */
class Note extends Model
{
    // The table has created_at but no updated_at.
    const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'title', 'body'];
}
