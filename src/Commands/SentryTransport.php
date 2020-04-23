<?php

namespace Lxj\Laravel\Sentry\Commands;

use Illuminate\Console\Command;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Sentry\SentryLaravel\SentryLaravelServiceProvider;
use Symfony\Component\Console\Input\InputOption;

class SentryTransport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentry:transport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Redis to Sentry Transport';

    protected $redisOptions = [
        'queue_name' => 'queue:sentry:transport',
        'connection' => 'sentry',
    ];

    /** @var Connection */
    protected $redis;

    /** @var \Raven_Client */
    protected $sentryClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redisOptions = array_merge(
            $this->redisOptions, config(SentryLaravelServiceProvider::$abstract . '.redis_options', [])
        );
    }

    protected function configure()
    {
        $this->addOption(
            'interval',
            null,
            InputOption::VALUE_OPTIONAL,
            'Consumption interval(ms)',
            5
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $redisClient = $this->getRedisClient();
        if (is_null($redisClient)) {
            Log::error('Redis to Sentry Transport Redis client is null');
            return;
        }

        if (empty($this->redisOptions['queue_name'])) {
            Log::error('Redis to Sentry Transport Redis queue name is empty');
            return;
        }

        while (true) {
            $rawData = $redisClient->rpop($this->redisOptions['queue_name']);
            if (!empty($rawData)) {
                $data = json_decode($rawData, true);
                if ((!json_last_error()) && (!empty($data)) && (!empty($data['data']))) {
                    try {
                        $this->report($data['data'], $data['server'], $data['public_key'], $data['secret_key']);
                    } catch (\Throwable $e) {
                        Log::error(
                            'Redis to Sentry Transport error, exception: ' . $e->getMessage() . '|' .
                            $e->getTraceAsString()
                        );
                        Log::error(
                            'Redis to Sentry Transport error, data: ' . $rawData
                        );
                    }
                }
            }

            usleep(intval(doubleval($this->option('interval')) * 1000));
        }

        return;
    }

    protected function report($data, $server, $publicKey, $secretKey)
    {
        $sentryClient = $this->getSentryClient();

        $sentryClient->close_curl_resource();

        $oldServer = $sentryClient->server;
        $sentryClient->server = $server;

        $oldPublicKey = $sentryClient->public_key;
        $sentryClient->public_key = $publicKey;

        $oldSecretKey = $sentryClient->secret_key;
        $sentryClient->secret_key = $secretKey;

        try {
            $sentryClient->send($data);
        } catch (\Throwable $e) {
            $sentryClient->server = $oldServer;
            $sentryClient->public_key = $oldPublicKey;
            $sentryClient->secret_key = $oldSecretKey;
        }
    }

    protected function getSentryClient()
    {
        if (is_null($this->sentryClient)) {
            /** @var \Raven_Client $sentryClient */
            $sentryClient = app(SentryLaravelServiceProvider::$abstract);
            $sentryClient->setTransport(null);
            $this->sentryClient = $sentryClient;
        }

        return $this->sentryClient;
    }

    protected function getRedisClient()
    {
        if (is_null($this->redis)) {
            if (!empty($this->redisOptions['connection'])) {
                $this->redis = Redis::connection($this->redisOptions['connection']);
            }
        }

        return $this->redis;
    }
}
