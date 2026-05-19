<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $modules = array_filter(glob($modulesPath . '/*'), 'is_dir');

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleNamespace = "App\\Modules\\{$moduleName}";

            // 1. Load Migrations dynamically from Module/database/migrations
            $migrationsPath = $modulePath . '/database/migrations';
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }

            // 2. Load Views dynamically from Module/resources/views
            $viewsPath = $modulePath . '/resources/views';
            if (is_dir($viewsPath)) {
                $this->loadViewsFrom($viewsPath, Str::snake($moduleName));
            }

            // 3. Load Web and API Routes dynamically from Module/routes/
            $routesPath = $modulePath . '/routes';
            if (is_dir($routesPath)) {
                // Web routes
                if (file_exists($routesPath . '/web.php')) {
                    Route::middleware(['web', 'tenant'])
                        ->group($routesPath . '/web.php');
                }

                // API routes
                if (file_exists($routesPath . '/api.php')) {
                    Route::middleware(['api', 'tenant'])
                        ->prefix('api')
                        ->group($routesPath . '/api.php');
                }
            }

            // 4. Register Console Commands dynamically from Module/Console/Commands
            $consolePath = $modulePath . '/Console/Commands';
            if (is_dir($consolePath)) {
                $this->registerCommands($consolePath, "{$moduleNamespace}\\Console\\Commands");
            }

            // 5. Register Livewire components dynamically from Module/Http/Livewire
            $livewirePath = $modulePath . '/Http/Livewire';
            if (is_dir($livewirePath)) {
                $this->registerLivewireComponents($livewirePath, "{$moduleNamespace}\\Http\\Livewire", Str::snake($moduleName));
            }
        }
    }

    /**
     * Dynamically discover and register Livewire components inside app/Modules/{module}/Http/Livewire.
     */
    protected function registerLivewireComponents(string $path, string $namespace, string $moduleLower): void
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return;
        }

        // Register the namespace prefix so Livewire's dynamic resolver can map 'namespace::component'
        \Livewire\Livewire::addNamespace($moduleLower, null, $namespace);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $normalizedFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->getPathname());
                $cleanRelative = str_replace([$normalizedPath, '.php'], ['', ''], $normalizedFilePath);
                $cleanRelative = str_replace(DIRECTORY_SEPARATOR, '\\', $cleanRelative);
                $className = $namespace . $cleanRelative;

                if (class_exists($className)) {
                    $componentName = Str::kebab(str_replace('\\', '-', ltrim($cleanRelative, '\\')));
                    \Livewire\Livewire::component("{$moduleLower}::{$componentName}", $className);
                }
            }
        }
    }

    /**
     * Dynamically discover and register console commands inside app/Modules/{module}/Console/Commands.
     */
    protected function registerCommands(string $path, string $namespace): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $commands = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $normalizedFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->getPathname());
                $relativePath = str_replace([$normalizedPath, '.php'], ['', ''], $normalizedFilePath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $className = $namespace . $relativePath;

                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isSubclassOf(\Illuminate\Console\Command::class) && !$reflection->isAbstract()) {
                        $commands[] = $className;
                    }
                }
            }
        }

        if (!empty($commands)) {
            $this->commands($commands);
        }
    }
}
