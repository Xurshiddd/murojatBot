<?php

namespace App;

trait Keyboards
{
    protected static function mainMenu($lang): array
    {
        if($lang === "O'zbekcha"){
            $l = [
                "yangi" => "📤 Yangi murojaat yuborish",
                "myMr" => "📋 Mening murojaatlarim",
                "settings" => "⚙️ Sozlamalar",
            ];
        }else{
            $l = [
                "yangi" => "📤 Отправить новое обращение",
                "myMr" => "📋 Мои обращения",
                "settings" => "⚙️ Настройки",
            ];
        }
        return [
            'keyboard' => [
                [['text' => $l['yangi']], ['text' => $l['myMr']]],
                [['text' => $l['settings']]],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
    }
    
    protected static function settingsMenu($lang): array
    {
        if($lang === "O'zbekcha"){
            $l = [
                "lang_ch" => "🌐 Tilni o‘zgartirish",
                "back" => "◀️ Orqaga",
            ];
        }else{
            $l = [
                "lang_ch" => "🌐 Изменить язык",
                "back" => "◀️ Назад",
            ];
        }
        return [
            'keyboard' => [
                [['text' => $l['lang_ch']]],
                [['text' => $l['back']]],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
    }
    protected static function adminMainMenu(): array
    {
        return [
            'keyboard' => [
                [['text' => '🕓 Kutayotgan murojaatlar']],
                [['text' => '◀️ Orqaga']]
            ],
            'resize_keyboard' => true
        ];
    }
    
}
