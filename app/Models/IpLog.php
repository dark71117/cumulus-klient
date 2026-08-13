<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpLog extends Model
{
    protected $table = 'ip_logs';

    public $timestamps = false;

    protected $guarded = [];
}
