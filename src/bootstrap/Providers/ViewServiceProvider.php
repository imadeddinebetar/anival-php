<?php

namespace Bootstrap\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Core\View\Contracts\ViewFactoryInterface;

class ViewServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('view', function () {
            $viewPath = $this->app->basePath('resources/views');
            $cachePath = storage_path('cache/views');

            if (!is_dir($cachePath)) {
                @mkdir($cachePath, 0755, true);
            }

            $filesystem = new Filesystem();

            // Use the container-resolved Illuminate dispatcher for Blade internals
            $eventDispatcher = $this->app->get('events');

            $viewResolver = new EngineResolver();

            $bladeCompiler = new BladeCompiler($filesystem, $cachePath);

            // Register @csrf directive
            $bladeCompiler->directive('csrf', function () {
                return '<?php echo csrf_field(); ?>';
            });

            // Register @method directive
            $bladeCompiler->directive('method', function ($method) {
                return "<?php echo method_field({$method}); ?>";
            });

            $viewResolver->register('blade', function () use ($bladeCompiler) {
                return new CompilerEngine($bladeCompiler);
            });

            $viewResolver->register('php', function () {
                return new PhpEngine(new Filesystem());
            });

            $viewFinder = new FileViewFinder($filesystem, [$viewPath]);
            $factory = new Factory($viewResolver, $viewFinder, $eventDispatcher);

            return new \Core\View\Internal\View($factory);
        });

        $this->app->bind(ViewFactoryInterface::class, 'view');
        $this->app->bind(\Core\View\Internal\View::class, 'view');
    }
}
