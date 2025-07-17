<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReplyState extends Model
{
    protected $fillable = ['admin_id', 'user_id', 'appeal_id'];
}
