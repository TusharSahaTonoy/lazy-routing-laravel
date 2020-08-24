<?php

use Illuminate\Support\Facades\Route;

$dir = new RecursiveDirectoryIterator(base_path('app\Http\Controllers'));
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.+[a-zA-Z0-9]+Controller\.php/', RegexIterator::GET_MATCH);

$_GET['error'] = false;
foreach ($files as $file) {

    $controller = trim(strrchr($file[0], '\\'), "\\.php");

    generateRoutes($controller);
}

// ---> error flash
$console = new \Symfony\Component\Console\Output\ConsoleOutput();
if (config('lazy_config.error_flash') && $_GET['error'])
    $console->writeln("Lazy Routing: You can disable the error flash in config/lazy_config.php. \n");

// ----->  Generate routes        
function generateRoutes($controller)
{

    $console = new \Symfony\Component\Console\Output\ConsoleOutput();

    try {
        $controller_class = new ReflectionClass('App\Http\Controllers' . '\\' . $controller);
    } catch (ReflectionException $e) {
        if (config('lazy_config.error_flash')) {
            $console->writeln("Lazy Routing: " . $e->getMessage() . ". May be $controller in not in main controller folder. \n");
            $_GET['error']  = true;
        }
        return;
    }

    $calss_config = $controller_class->getConstants();

    if (!isset($calss_config['LAZY_CONFIG'])) {
        return;
    } else if (!isset($calss_config['LAZY_CONFIG']['url_path'])) {
        if (config('lazy_config.error_flash')) {
            $console->writeln("Lazy Routing: 'url_path' value not found in $controller. Lazy Routing will not work in the $controller.\n");
            $_GET['error']  = true;
        }
        return;
    }

    if (!($calss_config['LAZY_CONFIG']['route_generation'] ?? true))
        return;

    $controller_methods = $controller_class->getMethods(ReflectionMethod::IS_PUBLIC);

    Route::group(['namespace' => 'App\Http\Controllers', 'middleware' => 'web'], function () use ($controller, $controller_methods, $calss_config) {


        foreach ((object)$controller_methods as $method) {

            if ($method->class == "Illuminate\Routing\Controller" || $method->class == "App\Http\Controllers\Controller") {
                continue;
            }
            if ($method->name[0] != 'g' && $method->name[0] != 'p') {
                continue;
            }

            $route_prefix = $calss_config['LAZY_CONFIG']['route_prefix'] ??  $calss_config['LAZY_CONFIG']['url_path'];

            $slug = preg_split('/(?=[A-Z])/', $method->name);

            $route_name = isset($method->getStaticVariables()["LAZY_ROUTE"]) ? $method->getStaticVariables()["LAZY_ROUTE"] : $route_prefix . '.' . $method->name;

            // unseting get or pont in fornt
            unset($slug[0]);

            $path = null;
            $method_name = $calss_config['LAZY_CONFIG']['url_path'] . (empty($slug) ? "" : "/") . strtolower(implode('-', $slug));

            $url = isset($method->getStaticVariables()["LAZY_URL"]) ? $method->getStaticVariables()["LAZY_URL"] : $method_name;

            if ($method->name[0] == 'g') {

                foreach ((object) $method->getParameters() as $param) {

                    if ($param->isDefaultValueAvailable()) {
                        $path .= '/{' . $param->name . '?}';
                    } else {
                        $path .= '/{' . $param->name . '}';
                    }
                }

                Route::get($url . $path, $controller . '@' . $method->name)->name($route_name);
            } else if ($method->name[0] == 'p') {
                foreach ((object) $method->getParameters() as $param) {

                    if ($param->hasType()) {
                        continue;
                    }

                    if ($param->isDefaultValueAvailable()) {
                        $path .= '/{' . $param->name . '?}';
                    } else {
                        $path .= '/{' . $param->name . '}';
                    }
                }

                Route::post($url . $path, $controller . '@' . $method->name)->name($route_name);
            }
        }
    });
}
