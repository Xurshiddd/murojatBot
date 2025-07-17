<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
class setWebHookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the webhook for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->setWebhook([
            'url' => env('TELEGRAM_WEBHOOK_URL'),
        ]);
        $this->info('Webhook command executed successfully!');
    }
}
