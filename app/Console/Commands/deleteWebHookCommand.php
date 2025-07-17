<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class deleteWebHookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-web-hook-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the webhook for the Telegram bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deleting webhook...');

        $telegram = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));

        try {
            $response = $telegram->deleteWebhook();
            $this->info('Webhook deleted successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to delete webhook: ' . $e->getMessage());
        }
        $this->info('Webhook deletion command executed successfully!');
    }
}
