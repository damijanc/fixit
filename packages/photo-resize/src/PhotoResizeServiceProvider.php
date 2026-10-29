<?php

declare(strict_types=1);

namespace Fixit\PhotoResize;

use Illuminate\Support\ServiceProvider;

class PhotoResizeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PhotoResize::class);
    }
}
