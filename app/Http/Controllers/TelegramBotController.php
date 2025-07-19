<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FunctionService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\TelegramUserStep;

class TelegramBotController extends Controller
{
    protected $telegram;
    public function __construct(private FunctionService $functionService) {
        $this->telegram = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
    }
    
    public function handleWebhook(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        // 1) Callback bormi?
        $callback = $update->getCallbackQuery();
        
        // 2) Message obyektini topamiz (text xabar yoki callback ichidagi message)
        $message  = $update->getMessage() ?? $callback?->getMessage();
        if (!$message) {
            return;    // media/foto only
        }
        // --- TO‘G‘RI ID LAR ---
        $fromId = $callback?->getFrom()->getId()           // callback bo‘lsa admin foydalanuvchi
        ?? $message->getFrom()->getId();           // oddiy xabar bo‘lsa
        $chatId = $message->getChat()->getId();            // xabarni qaerga yuboramiz?
        
        // Matn: text | contact | callback_data
        $text = $message->getText()
        ?? $message->getContact()?->getPhoneNumber()
        ?? $callback?->getData();
        if (is_admin($fromId)) {                   // ← endi to‘g‘ri tekshiradi
            $this->functionService->adminFunc($chatId, $message, $update); // $chatId ≈ $fromId (private chat)
            
            // Spinnerni yopish
            if ($callback) {
                Http::post("https://api.telegram.org/bot".env('TELEGRAM_BOT_TOKEN')."/answerCallbackQuery", [
                    'callback_query_id' => $callback->getId(),
                ]);
            }
            return;
        }
        
        if ($text === '/start') {
            return $this->functionService->startFunc($chatId);
        }
        if (in_array($text, ["O'zbekcha", 'Русский'])) {
            if(User::where('telegram_id', $chatId)->exists()){
                User::updateOrCreate(['telegram_id' => $chatId], ['language' => $text]);
                TelegramUserStep::updateOrCreate(
                ["telegram_id" => $fromId],
                ["step" => 'main_menu']
            );
            }else {
                User::updateOrCreate(['telegram_id' => $chatId], ['language' => $text]);
                TelegramUserStep::updateOrCreate(
                ["telegram_id" => $fromId],
                ["step" => 'first']
            );
            }
        }
        $this->functionService->registerFunc($chatId, $text);
    }
    
}
