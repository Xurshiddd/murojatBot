<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramBotController;
use Telegram\Bot\Laravel\Facades\Telegram;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('telegram/webhook', [TelegramBotController::class, 'handleWebhook']);
// Route::post('telegram/webhook', function(Request $request){
//     $update = Telegram::getWebhookUpdate(); // JSON to'g'ri ajratiladi

//     if ($update->isType('callback_query')) {
//         $callback = $update->getCallbackQuery();
//         \Log::info('CALLBACK QUERY KELDI', [
//             'data' => $callback->getData(),
//             'from' => $callback->getFrom(),
//         ]);
//     } elseif ($update->isType('message')) {
//         $message = $update->getMessage();
//         \Log::info('XABAR KELDI', [
//             'text' => $message->getText(),
//             'from' => $message->getFrom(),
//         ]);
//     } else {
//         \Log::info('BOSHQA UPDATE', [
//             'type' => $update->detectType(),
//             'update' => $update,
//         ]);
//     }

//     return 'ok';
// });