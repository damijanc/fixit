<?php

namespace Tests;

use App\Services\AI\Provider\ProviderSettingsStore;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\InMemoryProviderSettingsStore;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ProviderSettingsStore::class, new InMemoryProviderSettingsStore);
    }
}
