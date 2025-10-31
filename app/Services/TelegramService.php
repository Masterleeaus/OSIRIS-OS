<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TelegramService
{
    protected $botToken;
    protected $botUsername;
    protected $apiUrl;

    public function __construct()
    {
        $this->botToken = config('ton.telegram.bot_token');
        $this->botUsername = config('ton.telegram.bot_username');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Set webhook for Telegram bot
     *
     * @param string $url
     * @return array
     */
    public function setWebhook(string $url): array
    {
        try {
            $response = Http::post("{$this->apiUrl}/setWebhook", [
                'url' => $url,
            ]);

            $data = $response->json();

            if (!$data['ok']) {
                throw new Exception($data['description'] ?? 'Failed to set webhook');
            }

            return [
                'success' => true,
                'message' => 'Webhook set successfully',
                'data' => $data
            ];
        } catch (Exception $e) {
            Log::error('Error setting Telegram webhook: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send message to Telegram user
     *
     * @param int $chatId
     * @param string $text
     * @param array $options
     * @return array
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $response = Http::post("{$this->apiUrl}/sendMessage", $params);
            $data = $response->json();

            if (!$data['ok']) {
                throw new Exception($data['description'] ?? 'Failed to send message');
            }

            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data
            ];
        } catch (Exception $e) {
            Log::error('Error sending Telegram message: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a TON transaction request to user
     *
     * @param int $chatId
     * @param string $fromAddress
     * @param string $toAddress
     * @param float $amount
     * @return array
     */
    public function sendTransactionRequest(
        int $chatId,
        string $fromAddress,
        string $toAddress,
        float $amount
    ): array {
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ Confirm Transaction',
                        'callback_data' => json_encode([
                            'type' => 'confirm_transaction',
                            'from' => $fromAddress,
                            'to' => $toAddress,
                            'amount' => $amount,
                        ])
                    ],
                    [
                        'text' => '❌ Cancel',
                        'callback_data' => json_encode(['type' => 'cancel_transaction'])
                    ]
                ]
            ]
        ];

        return $this->sendMessage($chatId, "💱 *TON Transaction*\n\n" .
            "*From:* `{$fromAddress}`\n" .
            "*To:* `{$toAddress}`\n" .
            "*Amount:* {$amount} TON\n\n" .
            "Please confirm the transaction:",
            [
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode($keyboard)
            ]
        );
    }

    /**
     * Handle incoming webhook update
     *
     * @param array $update
     * @return array
     */
    public function handleWebhookUpdate(array $update): array
    {
        try {
            if (isset($update['message'])) {
                return $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

            return [
                'success' => false,
                'error' => 'Unsupported update type'
            ];
        } catch (Exception $e) {
            Log::error('Error handling Telegram webhook: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle incoming message
     *
     * @param array $message
     * @return array
     */
    protected function handleMessage(array $message): array
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        // Simple command handler
        switch (strtolower($text)) {
            case '/start':
                return $this->sendWelcomeMessage($chatId);
            case '/balance':
                return $this->sendMessage($chatId, 'Please use /connect to connect your TON wallet first.');
            case '/help':
                return $this->sendHelpMessage($chatId);
            default:
                return $this->sendMessage(
                    $chatId,
                    "🤖 I'm a TON wallet bot. Use /help to see available commands."
                );
        }
    }

    /**
     * Handle callback query (button clicks)
     *
     * @param array $callbackQuery
     * @return array
     */
    protected function handleCallbackQuery(array $callbackQuery): array
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = json_decode($callbackQuery['data'], true);
        $messageId = $callbackQuery['message']['message_id'];

        if (!$data) {
            return $this->answerCallbackQuery($callbackQuery['id'], 'Invalid request');
        }

        switch ($data['type']) {
            case 'confirm_transaction':
                return $this->processTransactionConfirmation($chatId, $messageId, $data);
            case 'cancel_transaction':
                return $this->editMessageText(
                    $chatId,
                    $messageId,
                    "❌ Transaction cancelled.",
                    ['reply_markup' => json_encode(['inline_keyboard' => []])]
                );
            default:
                return $this->answerCallbackQuery($callbackQuery['id'], 'Unknown action');
        }
    }

    /**
     * Send welcome message
     *
     * @param int $chatId
     * @return array
     */
    protected function sendWelcomeMessage(int $chatId): array
    {
        $message = "👋 *Welcome to TON Wallet Bot!*\n\n" .
            "I can help you manage your TON wallet and make transactions.\n\n" .
            "*Available commands:*\n" .
            "/start - Show this message\n" .
            "/balance - Check your TON balance\n" .
            "/send - Send TON to another address\n" .
            "/connect - Connect your TON wallet\n" .
            "/help - Show help information";

        return $this->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
    }

    /**
     * Send help message
     *
     * @param int $chatId
     * @return array
     */
    protected function sendHelpMessage(int $chatId): array
    {
        $message = "*TON Wallet Bot Help*\n\n" .
            "*Commands:*\n" .
            "• /start - Show welcome message\n" .
            "• /balance - Check your TON balance\n" .
            "• /send - Send TON to another address\n" .
            "• /connect - Connect your TON wallet\n" .
            "• /help - Show this help message\n\n" .
            "*How to use:*\n" .
            "1. First, connect your TON wallet using /connect\n" .
            "2. Check your balance with /balance\n" .
            "3. Send TON to others with /send\n\n" .
            "🔒 *Security Note:*\n" .
            "• Never share your private key or seed phrase with anyone\n" .
            "• This bot will never ask for your private key\n" .
            "• Always verify the recipient address before sending";

        return $this->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
    }

    /**
     * Process transaction confirmation
     *
     * @param int $chatId
     * @param int $messageId
     * @param array $data
     * @return array
     */
    protected function processTransactionConfirmation(int $chatId, int $messageId, array $data): array
    {
        // Here you would typically:
        // 1. Verify the transaction details
        // 2. Check if user has sufficient balance
        // 3. Process the transaction using TonService
        // 4. Update the message with the result

        // For now, we'll just show a success message
        return $this->editMessageText(
            $chatId,
            $messageId,
            "✅ Transaction submitted!\n\n" .
            "From: `{$data['from']}`\n" .
            "To: `{$data['to']}`\n" .
            "Amount: {$data['amount']} TON\n\n" .
            "*Status:* Processing...\n" .
            "This may take a few moments.",
            [
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]
        );
    }

    /**
     * Answer a callback query
     *
     * @param string $callbackQueryId
     * @param string $text
     * @param bool $showAlert
     * @return array
     */
    protected function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): array
    {
        try {
            $response = Http::post("{$this->apiUrl}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);

            return $response->json();
        } catch (Exception $e) {
            Log::error('Error answering callback query: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Edit message text
     *
     * @param int $chatId
     * @param int $messageId
     * @param string $text
     * @param array $options
     * @return array
     */
    protected function editMessageText(int $chatId, int $messageId, string $text, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $response = Http::post("{$this->apiUrl}/editMessageText", $params);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Error editing message: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
