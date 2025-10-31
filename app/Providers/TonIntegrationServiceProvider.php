<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ton\Client;
use App\Services\TonService;
use App\Services\TelegramService;
use App\Services\CrossChainService;
use App\Services\WebSocketService;
use App\Services\TonWebSocketHandler;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use React\EventLoop\Factory as LoopFactory;

class TonIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TON client as a singleton
        $this->app->singleton('ton.client', function ($app) {
            $network = config('ton.network', 'mainnet');
            $config = config("ton.networks.{$network}", config('ton.networks.mainnet'));
            
            return new Client([
                'network' => [
                    'server' => $config['endpoint'],
                    'api_key' => $config['api_key']
                ]
            ]);
        });

        // Register TonService
        $this->app->singleton(TonService::class, function ($app) {
            return new TonService($app->make('ton.client'));
        });

        // Register TelegramService
        $this->app->singleton(TelegramService::class, function ($app) {
            return new TelegramService();
        });

        // Register CrossChainService
        $this->app->singleton(CrossChainService::class, function ($app) {
            return new CrossChainService($app->make('ton.client'));
        });

        // Register WebSocketService
        $this->app->singleton(WebSocketService::class, function ($app) {
            return new WebSocketService(
                $app->make(ChannelManager::class)
            );
        });

        // Register TonWebSocketHandler as a singleton
        $this->app->singleton(TonWebSocketHandler::class, function ($app) {
            $loop = LoopFactory::create();
            return new TonWebSocketHandler(
                $app->make('ton.client'),
                $app->make(WebSocketService::class),
                $loop
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/ton.php' => config_path('ton.php'),
        ], 'ton-config');

        // Start WebSocket handler when the application is ready
        $this->app->booted(function () {
            if (config('websockets.enabled', true)) {
                $this->app->make(TonWebSocketHandler::class)->start();
            }
        });

        // Register commands if we're using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register any console commands here if needed
            ]);
        }
    }
}
