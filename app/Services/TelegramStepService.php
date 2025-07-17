<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\TelegramUserStep;
use App\Models\User;
use App\Keyboards;
use App\Models\Appeal;
use App\Services\FunctionService;
class TelegramStepService
{
    use Keyboards;
    
    public static function getStep($telegramId)
    {
        return TelegramUserStep::firstOrCreate([
            "telegram_id" => $telegramId,
            ])->step;
        }
        
        public static function setStep($telegramId, $step)
        {
            TelegramUserStep::updateOrCreate(
                ["telegram_id" => $telegramId],
                ["step" => $step]
            );
        }
        
        public static function processStep($telegramId, $text, $step)
        {
            $user = User::firstOrCreate(["telegram_id" => $telegramId]);
            
            switch ($step) {
                case "first":
                    self::setStep($telegramId, "full_name");
                    self::sendMessage(
                        $telegramId,
                        "Ism familyangizni to'liq yozing:"
                    );
                    break;
                    case "full_name":
                        $user->full_name = $text;
                        $user->save();
                        
                        self::setStep($telegramId, "region");
                        
                        // Klaviaturani jo‘natamiz
                        self::sendMessage($telegramId, "Viloyatingizni tanlang:", [
                            "keyboard" => [
                                [
                                    ["text" => "Toshkent shahar"],
                                    ["text" => "Toshkent viloyati"],
                                ],
                                [["text" => "Samarqand"], ["text" => "Buxoro"]],
                                [["text" => "Farg‘ona"], ["text" => "Andijon"]],
                                [["text" => "Namangan"], ["text" => "Qashqadaryo"]],
                                [["text" => "Surxondaryo"], ["text" => "Xorazm"]],
                                [["text" => "Navoiy"], ["text" => "Jizzax"]],
                                [
                                    ["text" => "Sirdaryo"],
                                    ["text" => "Qoraqalpog‘iston"],
                                ],
                            ],
                            "resize_keyboard" => true,
                            "one_time_keyboard" => true,
                        ]);
                        break;
                        
                        case "region":
                            $user->region = $text;
                            $user->save();
                            self::setStep($telegramId, "address");
                            self::sendMessage($telegramId, "Manzilingizni kiriting:");
                            break;
                            
                            case "address":
                                $user->address = $text;
                                $user->save();
                                self::setStep($telegramId, "birth_date");
                                self::sendMessage(
                                    $telegramId,
                                    "Tug‘ilgan yilingizni kiriting (YYYY-MM-DD):"
                                );
                                break;
                                
                                case "birth_date":
                                    // Sana formatini tekshirish: YYYY-MM-DD
                                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
                                        self::sendMessage(
                                            $telegramId,
                                            "❗️ Tug‘ilgan sanani to‘g‘ri kiriting. Masalan: 2000-01-25"
                                        );
                                        return;
                                    }
                                    
                                    $user->birth_date = $text;
                                    $user->save();
                                    self::setStep($telegramId, "phone");
                                    self::sendContactRequest($telegramId);
                                    break;
                                    
                                    case "phone":
                                        $user->phone = $text;
                                        $user->save();
                                        self::setStep($telegramId, "done");
                                        self::sendMessage(
                                            $telegramId,
                                            "✅ Ro'yxatdan o'tdingiz!",
                                            self::mainMenu()
                                        );
                                        self::setStep($telegramId, "main_menu");
                                        break;
                                        
                                        case "done": // ro‘yxat tugadi → asosiy menyu
                                            self::sendMessage(
                                                $telegramId,
                                                "📋 Asosiy menyu:",
                                                self::mainMenu()
                                            );
                                            self::setStep($telegramId, "main_menu");
                                            break;
                                            
                                            /* ---------- ASOSIY MENYU ﻿---------- */
                                            case "main_menu":
                                                if ($text === "📤 Yangi murojaat yuborish") {
                                                    self::setStep($telegramId, "new_appeal");
                                                    self::sendMessage($telegramId, "Murojaatingizni yuboring:");
                                                } elseif ($text === "📋 Mening murojaatlarim") {
                                                    self::listAppeals($telegramId, $user);
                                                } elseif ($text === "⚙️ Sozlamalar") {
                                                    self::setStep($telegramId, "settings");
                                                    self::sendMessage(
                                                        $telegramId,
                                                        "⚙️ Sozlamalar:",
                                                        self::settingsMenu()
                                                    );
                                                } else {
                                                    self::sendMessage(
                                                        $telegramId,
                                                        "Iltimos menyudan tanlang:",
                                                        self::mainMenu()
                                                    );
                                                }
                                                break;
                                                
                                                /* ---------- YANGI MUROJAAT ﻿---------- */
                                                case "new_appeal":
                                                    // matnni bazaga saqlash
                                                    $appeal = Appeal::create([
                                                        "user_id" => $user->id,
                                                        "body" => $text,
                                                    ]);
                                                    FunctionService::notifyAdminsOfAppeal($user, $appeal);
                                                    self::sendMessage(
                                                        $telegramId,
                                                        "✅ Murojaatingiz qabul qilindi!",
                                                        self::mainMenu()
                                                    );
                                                    
                                                    
                                                    self::setStep($telegramId, "main_menu");
                                                    break;
                                                    
                                                    /* ---------- SOZLAMALAR ﻿---------- */
                                                    case "settings":
                                                        if ($text === "🌐 Tilni o‘zgartirish") {
                                                            self::sendMessage($telegramId, "Tilni tanlang:", [
                                                                "keyboard" => [
                                                                    [["text" => "O'zbekcha"], ["text" => "Русский"]],
                                                                    [["text" => "◀️ Orqaga"]],
                                                                ],
                                                                "resize_keyboard" => true,
                                                            ]);
                                                            self::setStep($telegramId, "lang_change");
                                                        } elseif ($text === "◀️ Orqaga") {
                                                            self::sendMessage(
                                                                $telegramId,
                                                                "Menyuga qaytdingiz:",
                                                                self::mainMenu()
                                                            );
                                                            self::setStep($telegramId, "main_menu");
                                                        } else {
                                                            self::sendMessage(
                                                                $telegramId,
                                                                "Iltimos variantni tanlang:",
                                                                self::settingsMenu()
                                                            );
                                                        }
                                                        break;
                                                        
                                                        case "lang_change":
                                                            if (in_array($text, ["O'zbekcha", "Русский"])) {
                                                                $user->language = $text;
                                                                $user->save();
                                                                self::sendMessage(
                                                                    $telegramId,
                                                                    "Til yangilandi ✅",
                                                                    self::settingsMenu()
                                                                );
                                                                self::setStep($telegramId, "settings");
                                                            } else {
                                                                self::sendMessage(
                                                                    $telegramId,
                                                                    "Iltimos tilni menyudan tanlang."
                                                                );
                                                            }
                                                            break;
                                                        }
                                                    }
                                                    
                                                    public static function sendMessage(
                                                        $chatId,
                                                        string $text,
                                                        $replyMarkup = null,
                                                        $parseMode = null,
                                                        $disablePreview = false
                                                        ) {
                                                            $payload = [
                                                                "chat_id" => $chatId,
                                                                "text" => $text,
                                                            ];
                                                            
                                                            if ($replyMarkup) {
                                                                $payload["reply_markup"] = json_encode(
                                                                    $replyMarkup,
                                                                    JSON_UNESCAPED_UNICODE
                                                                );
                                                            }
                                                            
                                                            if ($parseMode) {
                                                                $payload["parse_mode"] = $parseMode;
                                                            }
                                                            
                                                            if ($disablePreview) {
                                                                $payload["disable_web_page_preview"] = true;
                                                            }
                                                            
                                                            Http::post(
                                                                "https://api.telegram.org/bot" .
                                                                env("TELEGRAM_BOT_TOKEN") .
                                                                "/sendMessage",
                                                                $payload
                                                            );
                                                        }
                                                        
                                                        public static function sendContactRequest($chatId)
                                                        {
                                                            Http::post(
                                                                "https://api.telegram.org/bot" .
                                                                env("TELEGRAM_BOT_TOKEN") .
                                                                "/sendMessage",
                                                                [
                                                                    "chat_id" => $chatId,
                                                                    "text" =>
                                                                    "📞 Iltimos telefon raqamingizni yuboring yoki tugma orqali jo‘nating:",
                                                                    "reply_markup" => json_encode([
                                                                        "keyboard" => [
                                                                            [
                                                                                [
                                                                                    "text" => "📱 Telefon raqamni yuborish",
                                                                                    "request_contact" => true,
                                                                                ],
                                                                            ],
                                                                        ],
                                                                        "resize_keyboard" => true,
                                                                        "one_time_keyboard" => true,
                                                                    ]),
                                                                    ]
                                                                );
                                                            }
                                                            protected static function listAppeals($chatId, User $user): void
                                                            {
                                                                $appeals = $user
                                                                ->appeals()
                                                                ->latest()
                                                                ->take(5)
                                                                ->get();
                                                                if ($appeals->isEmpty()) {
                                                                    self::sendMessage(
                                                                        $chatId,
                                                                        "Sizda hali murojaatlar yo‘q.",
                                                                        self::mainMenu()
                                                                    );
                                                                    return;
                                                                }
                                                                
                                                                $text = "🗂 Mening murojaatlarim (so‘nggi 5):\n\n";
                                                                foreach ($appeals as $i => $app) {
                                                                    $text .=
                                                                    $i +
                                                                    1 .
                                                                    ". " .
                                                                    $app->body .
                                                                    "\n— " .
                                                                    $app->created_at->format("d.m.Y H:i") .
                                                                    "\n\n";
                                                                }
                                                                self::sendMessage($chatId, $text, self::mainMenu());
                                                            }
                                                            public static function sendMessageWithResponse($chatId, $text, $replyMarkup = null, $parseMode = null)
                                                            {
                                                                $data = [
                                                                    'chat_id' => $chatId,
                                                                    'text' => $text,
                                                                ];
                                                                
                                                                if ($replyMarkup) {
                                                                    $data['reply_markup'] = json_encode($replyMarkup);
                                                                }
                                                                
                                                                if ($parseMode) {
                                                                    $data['parse_mode'] = $parseMode;
                                                                }
                                                                
                                                                $url = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage";
                                                                
                                                                $response = Http::post($url, $data);
                                                                
                                                                return $response->json(); // bu yerda message_id bor
                                                            }
                                                            
                                                            public static function deleteMessage(int $chatId, int $messageId): ?array
                                                            {
                                                                return self::request('deleteMessage', [
                                                                    'chat_id' => $chatId,
                                                                    'message_id' => $messageId,
                                                                ]);
                                                            }
                                                            public static function request(string $method, array $params = []): ?array
                                                            {
                                                                $token = config('services.telegram.bot_token'); // .env fayldan TOKEN oladi
                                                                $url = "https://api.telegram.org/bot{$token}/{$method}";
                                                                
                                                                $response = Http::post($url, $params);
                                                                
                                                                if ($response->successful()) {
                                                                    return $response->json();
                                                                }
                                                                
                                                                \Log::error("Telegram API error: " . $response->body());
                                                                return null;
                                                            }
                                                            
                                                        }
                                                        