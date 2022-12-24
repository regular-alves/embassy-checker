<?php

namespace EmbassyChecker\Models;

use Telegram\Bot\Api;

class TelegramSender {
    private array $chatIds = [];
    private Api $bot;

    public function __construct() {
        $this->bot = new Api( $_ENV['TELEGRAM_TOKEN'] );
        $this->chatIds = explode( ',', $_ENV['TELEGRAM_USER_ID'] );
    }

    /**
     * @deprecated
     */
    private function getChats(): void {
        $response = $this->bot->getUpdates();
        $chatIds = [];

        foreach ( $response as $update ) {
            $chatIds[] = $update->getChat()->id;
        }

        $this->chatIds = array_unique( $chatIds );
    }

    public function sendMessage( string $message, bool $urgent = false ): array {
        $messageIds = [];

        foreach ( $this->chatIds as $chatId ) {
            $response = $this->bot->sendMessage([
                'chat_id' => $chatId, 
                'text' => $message,
                'disable_notification' => ! $urgent
            ]);

            $messageIds[$chatId] = $response->getMessageId(); 
        }

        return $messageIds;
    }
}