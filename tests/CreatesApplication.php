<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Environment variables that the test suite relies on but the local .env
     * would otherwise override.
     *
     * CI exports these for real; phpunit.xml <env> alone is not enough on
     * machines where .env wins: Dotenv reads $_SERVER first, phpunit writes
     * only putenv()/$_ENV, and the immutable adapter then treats the .env
     * value as externally defined. Forcing all three sources here mirrors CI.
     *
     * @var array<string, string>
     */
    private const TESTING_ENV = [
        'APP_ENV' => 'testing',
        'CACHE_DRIVER' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
    ];

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $this->normalizeTestingEnvironment();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Force the testing environment into every env source before Dotenv loads.
     */
    private function normalizeTestingEnvironment(): void
    {
        foreach (self::TESTING_ENV as $name => $value) {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
