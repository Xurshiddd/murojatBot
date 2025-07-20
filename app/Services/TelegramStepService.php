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
            if($user->language == "O'zbekcha"){
                $lang = [
                    "full_name" => "Ism familyangizni to'liq yozing:",
                    "region" => "Viloyatingizni tanlang:",
                    "address" => "Manzilingizni kiriting:",
                    "birth" => "Tug‘ilgan yilingizni kiriting (YYYY-MM-DD):",
                    "birth_date" => "❗️ Tug‘ilgan sanani to‘g‘ri kiriting. Masalan: 2000-01-25",
                    "phone" => "✅ Ro'yxatdan o'tdingiz!",
                    "man" => "Asosiy menyu",
                    "main_menu" => [
                        "yangi" => "📤 Yangi murojaat yuborish",
                        "new_appeal" => "Murojaatingizni yuboring:",
                        "myMr" => "📋 Mening murojaatlarim",
                        "settings" => "⚙️ Sozlamalar",
                        "no" => "Iltimos menyudan tanlang:",
                        "yes" => "✅ Murojaatingiz qabul qilindi!",
                        "null" => "Sizda hali murojaatlar yo‘q.",
                        "lang_ch" => "🌐 Tilni o‘zgartirish",
                        "til" => "Tilni tanlang:",
                        "back" => "◀️ Orqaga",
                        "back_menu" => "Menyuga qaytdingiz:",
                        "new_lang" => "Til yangilandi ✅",
                    ],
                    "please" => "Iltimos menyudan tini tanlang",
                    "mymr" => "🗂 Mening murojaatlarim (so‘nggi 5):\n\n",
                    "snd_phone" => "📞 Iltimos telefon raqamingizni yuboring yoki tugma orqali jo‘nating:",
                    "txt_phone" => "📱 Telefon raqamni yuborish"
                ];
            }else{
                $lang = [
                    "full_name" => "Введите ваше полное имя:",
                    "region" => "Выберите вашу область:",
                    "address" => "Введите ваш адрес:",
                    "birth" => "Введите вашу дату рождения (ГГГГ-ММ-ДД):",
                    "birth_date" => "❗️ Пожалуйста, введите правильную дату рождения. Например: 2000-01-25",
                    "phone" => "✅ Вы успешно зарегистрировались!",
                    "man" => "Главное меню",
                    "main_menu" => [
                        "yangi" => "📤 Отправить новое обращение",
                        "new_appeal" => "Отправьте ваше обращение:",
                        "myMr" => "📋 Мои обращения",
                        "settings" => "⚙️ Настройки",
                        "no" => "Пожалуйста, выберите из меню:",
                        "yes" => "✅ Ваше обращение принято!",
                        "null" => "У вас пока нет обращений.",
                        "lang_ch" => "🌐 Изменить язык",
                        "til" => "Выберите язык:",
                        "back" => "◀️ Назад",
                        "back_menu" => "Вы вернулись в меню:",
                        "new_lang" => "Язык успешно изменён ✅",
                    ],
                    "please" => "Пожалуйста, выберите язык из меню.",
                    "mymr" => "🗂 Мои обращения (последние 5):\n\n",
                    "snd_phone" => "📞 Пожалуйста, отправьте свой номер телефона или отправьте через кнопку:",
                    "txt_phone" => "📱 Отправить номер телефона"
                ];
            }
            switch ($step) {
                case "first":
                    self::setStep($telegramId, "full_name");
                    self::sendMessage(
                        $telegramId,
                        $lang['full_name']
                    );
                    break;
                    case "full_name":
                        $user->full_name = $text;
                        $user->save();
                        
                        self::setStep($telegramId, "region");
                        
                        // Klaviaturani jo‘natamiz
                        self::sendMessage($telegramId, $lang['region'], [
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
                            self::sendMessage($telegramId, $lang['address']);
                            break;
                            
                            case "address":
                                $user->address = $text;
                                $user->save();
                                self::setStep($telegramId, "birth_date");
                                self::sendMessage(
                                    $telegramId,
                                    $lang['birth']
                                );
                                break;
                                
                                case "birth_date":
                                    // Sana formatini tekshirish: YYYY-MM-DD
                                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
                                        self::sendMessage(
                                            $telegramId,
                                           $lang['birth_date']
                                        );
                                        return;
                                    }
                                    
                                    $user->birth_date = $text;
                                    $user->save();
                                    self::setStep($telegramId, "phone");
                                    self::sendContactRequest($telegramId, $lang);
                                    break;
                                    
                                    case "phone":
                                        $user->phone = $text;
                                        $user->save();
                                        self::setStep($telegramId, "done");
                                        self::sendMessage(
                                            $telegramId,
                                            $lang['phone'],
                                            self::mainMenu($user->language)
                                        );
                                        self::setStep($telegramId, "main_menu");
                                        break;
                                        
                                        case "done": // ro‘yxat tugadi → asosiy menyu
                                            self::sendMessage(
                                                $telegramId,
                                               $lang['man'],
                                                self::mainMenu($user->language)
                                            );
                                            self::setStep($telegramId, "main_menu");
                                            break;
                                            
                                            /* ---------- ASOSIY MENYU ﻿---------- */
                                            case "main_menu":
                                                if ($text === $lang["main_menu"]['yangi']) {
                                                    self::setStep($telegramId, "new_appeal");
                                                    self::sendMessage($telegramId, $lang['main_menu']['new_appeal']);
                                                } elseif ($text === $lang["main_menu"]["myMr"]) {
                                                    self::listAppeals($telegramId, $user, $lang);
                                                } elseif ($text === $lang['main_menu']['settings']) {
                                                    self::setStep($telegramId, "settings");
                                                    self::sendMessage(
                                                        $telegramId,
                                                        $lang['main_menu']['settings'],
                                                        self::settingsMenu($user->language)
                                                    );
                                                } else {
                                                    self::sendMessage(
                                                        $telegramId,
                                                        $lang['main_menu']['no'],
                                                        self::mainMenu($user->language)
                                                    );
                                                }
                                                break;
                                                
                                                /* ---------- YANGI MUROJAAT ﻿---------- */
                                                case "new_appeal":
                                                    // matnni bazaga saqlash
                                                    $arr = [$lang['main_menu']['new_appeal'], $lang['main_menu']['settings'],$lang['main_menu']['myMr'],];
                                                    if(in_array($text,$arr)){
                                                        self::setStep($telegramId, "new_appeal");
                                                        self::sendMessage($telegramId, $lang['main_menu']['new_appeal']);
                                                    return;
                                                    }
                                                    $appeal = Appeal::create([
                                                        "user_id" => $user->id,
                                                        "body" => $text,
                                                    ]);
                                                    FunctionService::notifyAdminsOfAppeal($user, $appeal);
                                                    self::sendMessage(
                                                        $telegramId,
                                                        $lang['main_menu']['yes'],
                                                        self::mainMenu($user->language)
                                                    );
                                                    
                                                    
                                                    self::setStep($telegramId, "main_menu");
                                                    break;
                                                    
                                                    /* ---------- SOZLAMALAR ﻿---------- */
                                                    case "settings":
                                                        if ($text === $lang['main_menu']['lang_ch']) {
                                                            self::sendMessage($telegramId, "Tilni tanlang:", [
                                                                "keyboard" => [
                                                                    [["text" => "O'zbekcha"], ["text" => "Русский"]],
                                                                    [["text" =>$lang['main_menu']['back']]],
                                                                ],
                                                                "resize_keyboard" => true,
                                                            ]);
                                                            self::setStep($telegramId, "lang_change");
                                                        } elseif ($text === $lang['main_menu']['back']) {
                                                            self::sendMessage(
                                                                $telegramId,
                                                                $lang['main_menu']['back_menu'],
                                                                self::mainMenu($user->language)
                                                            );
                                                            self::setStep($telegramId, "main_menu");
                                                        } else {
                                                            self::sendMessage(
                                                                $telegramId,
                                                                $lang['main_menu']['no'],
                                                                self::settingsMenu($user->language)
                                                            );
                                                        }
                                                        break;
                                                        
                                                        case "lang_change":
                                                            if (in_array($text, ["O'zbekcha", "Русский"])) {
                                                                $user->language = $text;
                                                                $user->save();
                                                                self::sendMessage(
                                                                    $telegramId,
                                                                    $lang['main_menu']['new_lang'],
                                                                    self::settingsMenu($user->language)
                                                                );
                                                                self::setStep($telegramId, "settings");
                                                            } else {
                                                                self::sendMessage(
                                                                    $telegramId,
                                                                    $lang['please']
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
                                                        
                                                        public static function sendContactRequest($chatId, $lang)
                                                        {
                                                            Http::post(
                                                                "https://api.telegram.org/bot" .
                                                                env("TELEGRAM_BOT_TOKEN") .
                                                                "/sendMessage",
                                                                [
                                                                    "chat_id" => $chatId,
                                                                    "text" => $lang['snd_phone'],
                                                                    "reply_markup" => json_encode([
                                                                        "keyboard" => [
                                                                            [
                                                                                [
                                                                                    "text" => $lang['txt_phone'],
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
                                                            protected static function listAppeals($chatId, User $user, $lang): void
                                                            {
                                                                $appeals = $user
                                                                ->appeals()
                                                                ->latest()
                                                                ->take(5)
                                                                ->get();
                                                                if ($appeals->isEmpty()) {
                                                                    self::sendMessage(
                                                                        $chatId,
                                                                        $lang['main_menu']['null'],
                                                                        self::mainMenu($user->language)
                                                                    );
                                                                    return;
                                                                }
                                                                
                                                                $text = $lang['mymr'];
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
                                                                self::sendMessage($chatId, $text, self::mainMenu($user->language));
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
                                                                $token = env('TELEGRAM_BOT_TOKEN'); // .env fayldan TOKEN oladi
                                                                $url = "https://api.telegram.org/bot{$token}/{$method}";
                                                                
                                                                $response = Http::post($url, $params);
                                                                
                                                                if ($response->successful()) {
                                                                    return $response->json();
                                                                }
                                                                return null;
                                                            }
                                                            
                                                        }
                                                        