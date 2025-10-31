<?php

namespace App\Services;

use Ton\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

class TonService
{
    protected $client;
    protected $network;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->network = config('ton.network', 'mainnet');
    }

    /**
     * Get account information by address
     *
     * @param string $address
     * @return array
     */
    public function getAccountInfo(string $address): array
    {
        try {
            $response = $this->client->getAddressInformation([
                'address' => $address,
            ]);

            return $response->toArray();
        } catch (Exception $e) {
            Log::error('Error getting TON account info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send TON coins
     *
     * @param string $fromAddress
     * @param string $toAddress
     * @param float $amount
     * @param string $privateKey
     * @param string $mnemonic
     * @param string $passphrase
     * @return array
     */
    public function sendTon(
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $privateKey = null,
        string $mnemonic = null,
        string $passphrase = ''
    ): array {
        try {
            $params = [
                'from' => $fromAddress,
                'to' => $toAddress,
                'value' => $this->toNano($amount),
                'bounce' => true,
            ];

            if ($privateKey) {
                $params['private_key'] = $privateKey;
            } elseif ($mnemonic) {
                $params['mnemonic'] = $mnemonic;
                if ($passphrase) {
                    $params['passphrase'] = $passphrase;
                }
            } else {
                throw new \InvalidArgumentException('Either private key or mnemonic must be provided');
            }

            $response = $this->client->sendTransaction($params);
            
            return [
                'success' => true,
                'transaction_hash' => $response->transaction_id,
                'message' => 'Transaction sent successfully'
            ];
        } catch (Exception $e) {
            Log::error('Error sending TON: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert TON to nanoTON (1 TON = 1,000,000,000 nanoTON)
     *
     * @param float $amount
     * @return int
     */
    public function toNano(float $amount): int
    {
        return (int)($amount * 1000000000);
    }

    /**
     * Convert nanoTON to TON
     *
     * @param int $nano
     * @return float
     */
    public function fromNano(int $nano): float
    {
        return $nano / 1000000000;
    }

    /**
     * Generate a new wallet
     *
     * @param string|null $mnemonic
     * @param string $passphrase
     * @return array
     */
    public function generateWallet(string $mnemonic = null, string $passphrase = ''): array
    {
        try {
            $params = [
                'workchain_id' => config('ton.wallet.workchain', 0),
                'wallet_version' => config('ton.wallet.version', 'v4r2'),
            ];

            if ($mnemonic) {
                $params['mnemonic'] = $mnemonic;
                if ($passphrase) {
                    $params['passphrase'] = $passphrase;
                }
            }

            $response = $this->client->generateWallet($params);
            
            return [
                'success' => true,
                'address' => $response->address,
                'public_key' => $response->public_key,
                'private_key' => $response->secret_key,
                'mnemonic' => $response->mnemonic_phrase ?? null,
                'workchain' => $response->workchain_id,
            ];
        } catch (Exception $e) {
            Log::error('Error generating TON wallet: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
