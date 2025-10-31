<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrossChainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrossChainController extends Controller
{
    protected $crossChainService;

    public function __construct(CrossChainService $crossChainService)
    {
        $this->crossChainService = $crossChainService;
    }

    /**
     * Get account information across multiple chains
     */
    public function getAccountInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'chain' => 'sometimes|string|in:ton,polkadot,ethereum',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $info = $this->crossChainService->getAccountInfo(
                $request->address,
                $request->input('chain', 'ton')
            );

            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'chain' => 'sometimes|string|in:ton,polkadot,ethereum',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transactions = $this->crossChainService->getTransactionHistory(
                $request->address,
                $request->input('chain', 'ton'),
                [
                    'limit' => $request->input('limit', 10),
                    'offset' => $request->input('offset', 0),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get asset balance
     */
    public function getAssetBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'asset' => 'required|string',
            'chain' => 'sometimes|string|in:ton,polkadot,ethereum',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $balance = $this->crossChainService->getAssetBalance(
                $request->address,
                $request->asset,
                $request->input('chain', 'ton')
            );

            return response()->json([
                'success' => true,
                'data' => $balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estimate transaction fee
     */
    public function estimateFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|string',
            'to' => 'required|string',
            'amount' => 'required|numeric|min:0.000001',
            'asset' => 'sometimes|string',
            'chain' => 'sometimes|string|in:ton,polkadot,ethereum',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $fee = $this->crossChainService->estimateFee(
                $request->from,
                $request->to,
                $request->amount,
                $request->input('asset', 'TON'),
                $request->input('chain', 'ton')
            );

            return response()->json([
                'success' => true,
                'data' => $fee,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tx_hash' => 'required|string',
            'chain' => 'sometimes|string|in:ton,polkadot,ethereum',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $status = $this->crossChainService->getTransactionStatus(
                $request->tx_hash,
                $request->input('chain', 'ton')
            );

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
