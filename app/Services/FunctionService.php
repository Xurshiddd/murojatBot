<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\AdminReplyState;
use App\Models\AdminNotification;
use App\Models\Appeal;
use App\Models\User;

class FunctionService
{
    public function startFunc($chatId)
    {
        Telegram::sendMessage([
            "chat_id" => $chatId,
            "text" => "Iltimos tilni tanlang",
            "reply_markup" => json_encode([
                "keyboard" => [
                    [["text" => "O'zbekcha"], ["text" => "Русский"]],
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true,
                "selective" => true,
            ]),
        ]);
    }
    public function registerFunc($chatId, $message, $step = null)
    {
        $step = TelegramStepService::getStep($chatId);
        TelegramStepService::processStep($chatId, $message, $step);
    }
    public function adminFunc(
        int $adminId,
        \Telegram\Bot\Objects\Message $msg = null,
        $update
        ): void {
            $callback = $update->getCallbackQuery();
            
            
            // 1) Javob yozish tugmasi bosildi
            if ($callback) {
                $data = $callback->getData();
                
                if (preg_match('/^reply_(\d+)_(\d+)$/', $data, $m)) {
                    [$_, $appealId, $userTgId] = $m;
                    
                    $adminId = $callback->getFrom()->getId();
                    
                    // 1. Holatni saqlash
                    AdminReplyState::updateOrCreate(
                        ['admin_id' => $adminId],
                        ['user_id' => $userTgId, 'appeal_id' => $appealId]
                    );
                    
                    // 2. Tugmalarni o‘chirish
                    $notifications = AdminNotification::where('appeal_id', $appealId)->get();
                    foreach ($notifications as $note) {
                        if ($note->admin_id != $adminId) {
                            try {
                                Telegram::editMessageReplyMarkup([
                                    'chat_id' => $note->admin_id,
                                    'message_id' => $note->message_id,
                                    'reply_markup' => json_encode(['inline_keyboard' => []]),
                                ]);
                            } catch (\Exception $e) {
                                \Log::error("Failed to remove buttons: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // 3. Adminga so‘rov yuborish
                    TelegramStepService::sendMessage(
                        $adminId,
                        "✍️ Foydalanuvchiga javob matnini yozing yoki fayl yuboring.\n/cancel bilan bekor qilishingiz mumkin."
                    );
                    
                    // 4. Spinnerni yopish
                    Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/answerCallbackQuery", [
                        'callback_query_id' => $callback->getId(),
                    ]);
                    
                    return;
                }
            }
            
            // Qolgan holatlar: /admin, kutayotganlar va boshqalar
            if ($msg && $msg->getText()) {
                $text = $msg->getText();
                if ($text === '/admin') {
                    TelegramStepService::sendMessage($adminId, 'Admin menyu:', self::adminMainMenu());
                    return;
                }
                if ($text === "/cancel") {
                    AdminReplyState::where('admin_id', $adminId)->delete();
                    TelegramStepService::sendMessage($adminId, "❌ Bekor qilindi.");
                    return;
                }
                
                if ($text === "🕓 Kutayotgan murojaatlar") {
                    $pending = Appeal::where("status", "pending")
                    ->latest()
                    ->take(10)
                    ->get();
                    
                    if ($pending->isEmpty()) {
                        TelegramStepService::sendMessage(
                            $adminId,
                            "Hozircha javobsiz murojaat yo‘q."
                        );
                        return;
                    }
                    
                    foreach ($pending as $app) {
                        $response = TelegramStepService::sendMessageWithResponse(
                            $adminId,
                            "#{$app->id} – <i>{$app->body}</i>",
                            replyMarkup: [
                                "inline_keyboard" => [
                                    [
                                        [
                                            "text" => "✉️ Javob yozish",
                                            "callback_data" => "reply_{$app->id}_{$app->user->telegram_id}",
                                        ],
                                    ],
                                ],
                            ],
                            parseMode: "HTML"
                        );
                        
                        $messageId = data_get($response, "result.message_id");
                        
                        if ($messageId) {
                            AdminNotification::create([
                                "appeal_id" => $app->id,
                                "admin_id" => $adminId,
                                "message_id" => $messageId,
                            ]);
                        }
                    }
                }
                
                // Javob yozish holati
                $state = AdminReplyState::where("admin_id", $adminId)->first();
                if ($state) {
                    $this->forwardToUser($msg, $state->user_id, $callback, $msg->getCaption());
                    $this->finalizeReply($state->appeal_id);
                    $state->delete();
                    TelegramStepService::sendMessage($adminId, "✅ Yuborildi.");
                    return;
                }
            }
        }
        
        
        private function forwardToUser(
            \Telegram\Bot\Objects\Message $msg,
            int $toUserId,
            $callback,
            ?string $caption = null
            ): void {
                try {
                    $fromChat = $msg->getChat()->getId();
                    $messageId = $msg->getMessageId();
                    
                    if (!$msg->getText() && !$msg->getCaption()) {
                        \Log::info("asdasdas",["asdas"=>"sdas"]);
                        
                        return;
                    }
                     \Log::info("asd",["asdas"=>"sdas"]);
                    $customTextOrCaption = $msg->getText() ?? $caption;
                    
                    $data = [
                        "chat_id" => $toUserId,
                        "from_chat_id" => $fromChat,
                        "message_id" => $messageId,
                        "parse_mode" => "HTML",
                    ];
                    
                    if ($customTextOrCaption) {
                        $data['caption'] = $customTextOrCaption;
                    }
                    
                    Http::post("https://api.telegram.org/bot" . env("TELEGRAM_BOT_TOKEN") . "/copyMessage", $data);
                    
                } catch (\Throwable $e) {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => "Xabarni yuborishda xatolik yuz berdi",
                        'show_alert' => true,
                    ]);
                    \Log::error("Telegram xatolik: " . $e->getMessage());
                }
            }
            
            
            private function finalizeReply(int $appealId): void
            {
                $appeal = Appeal::find($appealId);
                
                // 1) Statusni yangilaymiz
                $appeal->update([
                    "status" => "answered",
                    "answered_at" => now(),
                ]);
                $notifications = AdminNotification::where(
                    "appeal_id",
                    $appealId
                    )->get();
                    // 2) Admin notifications
                    foreach ($notifications as $n) {
                        Http::post(
                            "https://api.telegram.org/bot" .
                            env("TELEGRAM_BOT_TOKEN") .
                            "/editMessageReplyMarkup",
                            [
                                "chat_id" => $n->admin_id,
                                "message_id" => $n->message_id,
                                "reply_markup" => json_encode(["inline_keyboard" => []]),
                                ]
                            );
                            
                            // --- (ixtiyoriy) Matnni yangilash ---
                            // Http::post("https://api.telegram.org/bot".env('TELEGRAM_BOT_TOKEN')."/editMessageText", [
                            //     'chat_id'    => $n->admin_id,
                            //     'message_id' => $n->message_id,
                            //     'text'       => "✅ Javob berildi (#{$appeal->id})",
                            // ]);
                        }
                        
                        // 3) Istasangiz, AdminNotification yozuvlarini o‘chirib tashlashingiz mumkin:
                            // AdminNotification::where('appeal_id', $appealId)->delete();
                        }
                        
                        protected static function adminMainMenu(): array
                        {
                            return [
                                "keyboard" => [[["text" => "🕓 Kutayotgan murojaatlar"]]],
                                "resize_keyboard" => true,
                            ];
                        }
                        public static function notifyAdminsOfAppeal(
                            User $user,
                            Appeal $appeal
                            ): void {
                                foreach (config("telegram.admins") as $adminId) {
                                    $response = Http::post(
                                        "https://api.telegram.org/bot" .
                                        env("TELEGRAM_BOT_TOKEN") .
                                        "/sendMessage",
                                        [
                                            "chat_id" => $adminId,
                                            "text" => "🆕 Yangi murojaat #{$appeal->id}\n{$user->full_name} (@{$user->telegram_id})\n\n<i>{$appeal->body}</i>",
                                            "parse_mode" => "HTML",
                                            "reply_markup" => json_encode(
                                                [
                                                    "inline_keyboard" => [
                                                        [
                                                            [
                                                                "text" => "✉️ Javob yozish",
                                                                "callback_data" => "reply_{$appeal->id}_{$user->telegram_id}",
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                JSON_UNESCAPED_UNICODE
                                            ),
                                            ]
                                        );
                                        // dd($response);
                                        // 3) Javobdan message_id ni aniqlaymiz (200 OK bo‘lsa)
                                        $messageId = data_get($response->json(), "result.message_id");
                                        
                                        if ($messageId) {
                                            AdminNotification::create([
                                                "appeal_id" => $appeal->id,
                                                "admin_id" => $adminId,
                                                "message_id" => $messageId,
                                            ]);
                                        }
                                    }
                                }
                            }
                            