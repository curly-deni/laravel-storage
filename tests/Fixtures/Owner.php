<?php

namespace Aesis\Storage\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    public $timestamps = false;

    protected $table = 'owners';

    protected $guarded = [];
}
