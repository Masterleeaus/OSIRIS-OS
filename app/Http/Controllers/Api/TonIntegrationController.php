<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use App\Services\TonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TonIntegrationController extends Controller
{
    protected $telegramService;
    protected $tonService;

    public function __construct(TelegramService $telegramService, TonService $tonService)
    {
        $this->telegramService = $telegramService;
        $this->tonService = $tonService;
    }

    /**
     * Handle Telegram webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram webhook received:', $update);

        // Handle the update
        $result = $this->telegramService->handleWebhookUpdate($update);

        if (!$result['success']) {
            Log::error('Error handling webhook update:', [
                'error' => $result['error'] ?? 'Unknown error',
                'update' => $update
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set webhook URL for Telegram bot
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setWebhook(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $result = $this->telegramService->setWebhook($request->url);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook set successfully',
                'data' => $result['data'] ?? null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Failed to set webhook',
        ], 500);
    }

    /**
     * Get account info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccountInfo(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
        ]);

        try {
            $info = $this->tonService->getAccountInfo($request->address);
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send TON
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTon(Request $request)
    {
        $request->validate([
            'from_address' => 'required|string',
            'to_address' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'private_key' => 'required_without:mnemonic|string|nullable',
            'mnemonic' => 'required_without:private_key|string|nullable',
            'passphrase' => 'nullable|string',
        ]);

        try {
            $result = $this->tonService->sendTon(
                $request->from_address,
                $request->to_address,
                $request->amount,
                $request->private_key,
                $request->mnemonic,
                $request->passphrase ?? ''
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to send TON');
            }

            return response()->json([
                'success' => true,
                'message' => 'TON sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a new wallet
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateWallet(Request $request)
    {
        $request->validate([
            'mnemonic' => 'nullable|string',
            'passphrase' => 'nullable|string',
        ]);

        try {
            $result = $this->tonService->generateWallet(
                $request->mnemonic,
                $request->passphrase ?? ''
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to generate wallet');
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet generated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
