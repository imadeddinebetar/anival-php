<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * View Cache Command
 *
 * Pre-compiles all Blade templates for improved performance.
 * @internal
 */
class ViewCacheCommand extends Command
{
    protected string $name = 'view:cache';
    protected string $description = 'Pre-compile all Blade templates';
    protected Application $app;
    protected Filesystem $filesystem;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
        $this->filesystem = new Filesystem();
    }

    /**
     * Execute the command
     */
    public function handle(array $args = [], array $options = []): int
    {
        echo "Compiling Blade templates...\n";

        $viewPath = $this->app->basePath('resources/views');
        $cachePath = $this->app->storagePath('cache/views');

        if (!is_dir($viewPath)) {
            echo "No views directory found.\n";
            return 0;
        }

        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }

        $compiler = new BladeCompiler($this->filesystem, $cachePath);

        $files = $this->filesystem->files($viewPath);
        $count = 0;

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $this->compileBladeFile($file->getPathname(), $compiler);
                $count++;
            }
        }

        // Also compile from subdirectories
        $this->compileDirectory($viewPath, $compiler, $count);

        echo "Views compiled successfully! ({$count} templates)\n";

        return 0;
    }

    /**
     * Recursively compile Blade templates from a directory
     */
    protected function compileDirectory(string $directory, BladeCompiler $compiler, int &$count): void
    {
        $directories = $this->filesystem->directories($directory);

        foreach ($directories as $dir) {
            $files = $this->filesystem->files($dir);

            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $this->compileBladeFile($file->getPathname(), $compiler);
                    $count++;
                }
            }

            $this->compileDirectory($dir, $compiler, $count);
        }
    }

    /**
     * Compile a single Blade file
     */
    protected function compileBladeFile(string $path, BladeCompiler $compiler): void
    {
        $compiler->compile($path);
    }
}
