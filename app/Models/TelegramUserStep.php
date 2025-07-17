<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUserStep extends Model
{
    protected $fillable = ['telegram_id', 'step'];
}
