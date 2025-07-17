<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    protected $fillable = ['user_id', 'body', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }
}
