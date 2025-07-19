<?php
namespace App\Services;

use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class TelegramForwardService
{
    public static function forwardAll($adminMessage)
    {
        try {
            $chatId = self::getTargetChatId($adminMessage);
            $adminChatId = $adminMessage['message']['chat']['id'];
            $messageId = $adminMessage['message']['message_id'];

            Telegram::forwardMessage([
                'chat_id' => $chatId,
                'from_chat_id' => $adminChatId,
                'message_id' => $messageId,
            ]);
        } catch (\Exception $e) {
            Log::error("Murojaat forward qilishda xatolik: " . $e->getMessage());
        }
    }

    private static function getTargetChatId($adminMessage)
    {
        return cache('responding_user_' . $adminMessage['callback_query']['from']['id']);
    }
}
