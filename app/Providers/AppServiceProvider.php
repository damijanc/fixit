<?php

namespace App\Providers;

use App\Services\AI\AgentFactory;
use App\Services\AI\Provider\EncryptedFileProviderSettingsStore;
use App\Services\AI\Provider\PlatformFactory;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettingsStore;
use App\Services\AI\Provider\SymfonyAiPlatformFactory;
use App\Services\AI\SymfonyAgentFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Output\NullOutput;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ProviderCatalog::class,
            fn (): ProviderCatalog => ProviderCatalog::fromConfig((array) config('ai')),
        );

        $this->app->singleton(
            ProviderSettingsStore::class,
            fn ($app): EncryptedFileProviderSettingsStore => new EncryptedFileProviderSettingsStore(
                EncryptedFileProviderSettingsStore::defaultPath(),
                EncryptedFileProviderSettingsStore::legacyPath(),
                $app->make(ProviderCatalog::class),
            ),
        );

        $this->app->singleton(
            PlatformFactory::class,
            fn (): SymfonyAiPlatformFactory => new SymfonyAiPlatformFactory(
                timeout: (int) config('ai.timeout', 60),
            ),
        );

        $this->app->singleton(
            ProviderRegistry::class,
            fn ($app): ProviderRegistry => new ProviderRegistry(
                $app->make(ProviderCatalog::class),
                $app->make(ProviderSettingsStore::class),
                $app->make(PlatformFactory::class),
            ),
        );

        $this->app->bind(AgentFactory::class, SymfonyAgentFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureRepairSchema();
        $this->configureDefaults();
    }

    /**
     * NativePHP does not run migrations on device. Create the small session
     * schema on first app boot; console runs (bootstrap/CI) still migrate
     * explicitly to avoid recursion.
     */
    private function ensureRepairSchema(): void
    {
        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        try {
            $database = config('database.connections.sqlite.database');

            if (is_string($database) && $database !== ':memory:' && ! is_file($database)) {
                @touch($database);
            }

            if (! Schema::hasTable('repair_sessions')) {
                Artisan::call('migrate', ['--force' => true], new NullOutput);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
