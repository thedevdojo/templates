<?php

namespace DevDojo\Templates;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\MenuItem;
use Illuminate\Events\Dispatcher;
use TCG\Voyager\Models\Permission;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Laravel\Folio\Folio;
use Illuminate\Support\Facades\File;

class TemplatesServiceProvider extends ServiceProvider
{
    private $models = [
            'Template',
            'TemplateOptions',
        ];

    /**
     * Register is loaded every time the voyager templates hook is used.
     *
     * @return none
     */
    public function register()
    {
        if ( request()->is(config('voyager.prefix')) || request()->is(config('voyager.prefix').'/*') || app()->runningInConsole() ) {

            try {
                DB::connection()->getPdo();
                $this->addTemplatesTable();
            } catch (\Exception $e) {
                \Log::error("Error connecting to database: ".$e->getMessage());
            }

            app(Dispatcher::class)->listen('voyager.menu.display', function ($menu) {
                $this->addTemplateMenuItem($menu);
            });

            app(Dispatcher::class)->listen('voyager.admin.routing', function ($router) {
                $this->addTemplateRoutes($router);
            });
        }

        // publish config
        $this->publishes([dirname(__DIR__).'/config/templates.php' => config_path('templates.php')], 'templates-config');

        // load helpers
        @include __DIR__.'/helpers.php';
    }

    /**
     * Register the menu options and selected template.
     *
     * @return void
     */
    public function boot()
    {
        try{

            $this->loadViewsFrom(__DIR__.'/../resources/views', 'templates');

            $template = '';

            if (Schema::hasTable('templates')) {
                $template = $this->rescue(function () {
                    return \DevDojo\Templates\Models\Template::where('active', '=', 1)->first();
                });
                if(Cookie::get('template')){
                    $template_cookied = \DevDojo\Templates\Models\Template::where('folder', '=', Cookie::get('template'))->first();
                    if(isset($template_cookied->id)){
                        $template = $template_cookied;
                    }
                }
            }

            view()->share('template', $template);

            $this->templates_folder = config('templates.templates_folder', resource_path('views/templates'));

            $this->loadDynamicMiddleware($this->templates_folder, $template);
            $this->registerTemplateComponents($template);
            $this->registerTemplateFolioDirectory($template);

            // Make sure we have an active template
            if (isset($template)) {
                $this->loadViewsFrom($this->templates_folder.'/'.@$template->folder, 'template');
            }
            $this->loadViewsFrom($this->templates_folder, 'templates_folder');

        } catch(\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * Admin template routes.
     *
     * @param $router
     */
    public function addTemplateRoutes($router)
    {
        $namespacePrefix = '\\DevDojo\\Templates\\Http\\Controllers\\';
        $router->get('templates', ['uses' => $namespacePrefix.'TemplatesController@index', 'as' => 'template.index']);
        $router->get('templates/activate/{template}', ['uses' => $namespacePrefix.'TemplatesController@activate', 'as' => 'template.activate']);
        $router->get('templates/options/{template}', ['uses' => $namespacePrefix.'TemplatesController@options', 'as' => 'template.options']);
        $router->post('templates/options/{template}', ['uses' => $namespacePrefix.'TemplatesController@options_save', 'as' => 'template.options.post']);
        $router->get('templates/options', function () {
            return redirect(route('voyager.template.index'));
        });
        $router->delete('templates/delete', ['uses' => $namespacePrefix.'TemplatesController@delete', 'as' => 'template.delete']);
    }

    private function registerTemplateComponents($template){
        Blade::anonymousComponentPath(resource_path('views/templates/' . $template->folder . '/components/elements'));
        Blade::anonymousComponentPath(resource_path('views/templates/' . $template->folder . '/components'));
    }

    private function registerTemplateFolioDirectory($template){
        if (File::exists(resource_path('views/templates/' . $template->folder . '/pages'))) {
            Folio::path(resource_path('views/templates/' . $template->folder . '/pages'))->middleware([
                '*' => [
                    //
                ],
            ]);
        }
    }

    /**
     * Adds the Template icon to the admin menu.
     *
     * @param TCG\Voyager\Models\Menu $menu
     */
    public function addTemplateMenuItem(Menu $menu)
    {
        if ($menu->name == 'admin') {
            $url = route('voyager.template.index', [], false);
            $menuItem = $menu->items->where('url', $url)->first();
            if (is_null($menuItem)) {
                $menu->items->add(MenuItem::create([
                    'menu_id' => $menu->id,
                    'url' => $url,
                    'title' => 'Templates',
                    'target' => '_self',
                    'icon_class' => 'voyager-paint-bucket',
                    'color' => null,
                    'parent_id' => null,
                    'order' => 98,
                ]));
                $this->ensurePermissionExist();

                return redirect()->back();
            }
        }
    }

    /**
     * Add Permissions for templates if they do not exist yet.
     *
     * @return none
     */
    protected function ensurePermissionExist()
    {
        $permission = Permission::firstOrNew([
            'key' => 'browse_templates',
            'table_name' => 'admin',
        ]);
        if (!$permission->exists) {
            $permission->save();
            $role = Role::where('name', 'admin')->first();
            if (!is_null($role)) {
                $role->permissions()->attach($permission);
            }
        }
    }

    private function loadDynamicMiddleware($templates_folder, $template){
        if (empty($template)) {
            return;
        }
        $middleware_folder = $templates_folder . '/' . $template->folder . '/middleware';
        if(file_exists( $middleware_folder )){
            $middleware_files = scandir($middleware_folder);
            foreach($middleware_files as $middleware){
                if($middleware != '.' && $middleware != '..'){
                    include($middleware_folder . '/' . $middleware);
                    $middleware_classname = 'Templates\\Middleware\\' . str_replace('.php', '', $middleware);
                    if(class_exists($middleware_classname)){
                        // Dynamically Load The Middleware
                        $this->app->make('Illuminate\Contracts\Http\Kernel')->prependMiddleware($middleware_classname);
                    }
                }
            }
        }
    }

    /**
     * Add the necessary Templates tables if they do not exist.
     */
    private function addTemplatesTable()
    {
        if (!Schema::hasTable('templates') && config('templates.create_tables')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('folder', 191)->unique();
                $table->boolean('active')->default(false);
                $table->string('version')->default('');
                $table->timestamps();
            });

            Schema::create('template_options', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('template_id')->unsigned()->index();
                $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    // Duplicating the rescue function that's available in 5.5, just in case
    // A user wants to use this hook with 5.4

    function rescue(callable $callback, $rescue = null)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);
            return value($rescue);
        }
    }
}
