<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use Illuminate\Console\Command;

class AiSmokeCommand extends Command
{
    protected $signature = 'fixit:ai-smoke
        {--prompt=Reply with exactly: FixIt AI connectivity OK : Prompt sent to the configured model}';

    protected $description = 'Send a short prompt through Symfony AI to verify the provider connection';

    public function handle(ModelGateway $gateway): int
    {
        try {
            $this->info($gateway->ask((string) $this->option('prompt')));
        } catch (AiException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
