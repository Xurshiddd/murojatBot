<?php

if (! function_exists('is_admin')) {
    /**
    * Berilgan Telegram ID adminlar ro‘yxatida bormi?
    */
    function is_admin(int $telegramId): bool
    {
        return in_array($telegramId, config('telegram.admins'), true);
    }
}
