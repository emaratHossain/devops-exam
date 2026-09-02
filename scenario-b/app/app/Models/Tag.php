<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Only used for create, update and delete.
 * All reads go through the query builder (DB::table).
 */
class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['note_id', 'name'];
}
