<?php

namespace App;

trait Keyboards
{
    protected static function mainMenu($lang): array
    {
        
        return [
            'keyboard' => [
                [['text' => '📤 Yangi murojaat yuborish'], ['text' => '📋 Mening murojaatlarim']],
                [['text' => '⚙️ Sozlamalar']],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
    }
    
    protected static function settingsMenu(): array
    {
        return [
            'keyboard' => [
                [['text' => "🌐 Tilni o‘zgartirish"]],
                [['text' => '◀️ Orqaga']],
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
