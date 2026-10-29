<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

final readonly class ProviderDefinition
{
    /**
     * @param  array<string, ModelDefinition>  $models
     */
    public function __construct(
        public string $id,
        public string $name,
        public ProviderDriver $driver,
        public string $baseUrl,
        public string $keyHint,
        public array $models,
        public ?string $inferenceProvider = null,
    ) {
        if ($models === []) {
            throw new \InvalidArgumentException("AI provider [{$id}] has no models configured.");
        }
    }

    public function model(string $id): ?ModelDefinition
    {
        return $this->models[$id] ?? null;
    }

    public function defaultModel(): ModelDefinition
    {
        return array_values($this->models)[0];
    }

    /**
     * @return list<ModelDefinition>
     */
    public function modelList(): array
    {
        return array_values($this->models);
    }
}
