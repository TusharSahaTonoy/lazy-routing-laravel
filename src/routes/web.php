<?php

use Illuminate\Support\Facades\Route;

$dir = new RecursiveDirectoryIterator(base_path('app\Http\Controllers'));
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.+[a-zA-Z0-9]+Controller\.php/', RegexIterator::GET_MATCH);

foreach($files as $file) {

    $controller = trim(strrchr($file[0], '\\'),"\\.php");

    generateRoutes($controller);
}

// ----->  Generate routes        
function generateRoutes  ($controller)
{
    
    $console = new \Symfony\Component\Console\Output\ConsoleOutput();

    try {
        $controller_class = new ReflectionClass('App\Http\Controllers'.'\\'.$controller);
    } catch (ReflectionException $e) {
        if(config('lazy_router.error_flash'))
            $console->writeln("Lazy Routing: ".$e->getMessage().". May be $controller in not in main controller folder. \n");
        return;
    }

    $calss_config = $controller_class->getConstants();

    if(!isset($calss_config['LAZY_CONFIG']['url_path']))
    {
        if(config('lazy_router.error_flash'))
            $console->writeln("Lazy Routing: const LAZY_CONFIG or url_path value not found in $controller. Lazy Routing will not work in the $controller.\n");
        return;
    }

    if(!($calss_config['LAZY_CONFIG']['route_generation']??true) )
        return;

    $controller_methods = $controller_class->getMethods(ReflectionMethod::IS_PUBLIC);

    Route::group(['namespace' => 'App\Http\Controllers','middleware' => 'web'], function () use ($controller,$controller_methods,$calss_config) {
        

        foreach ((object)$controller_methods as $method) {

            if($method->class == "Illuminate\Routing\Controller" || $method->class == "App\Http\Controllers\Controller" &&  ($method->name[0] != 'g' || $method->name[0] != 'p'))
            {
                continue;
            }

            $slug = preg_split('/(?=[A-Z])/', $method->name);
            $method_type = $slug[0];
            unset($slug[0]);
            $method_name = $calss_config['LAZY_CONFIG']['url_path'].'/'.strtolower(implode('-', $slug));

            if ($method_type == 'get')
            {
                $path = null;

                foreach ((object) $method->getParameters() as $param) {

                    if($param->isDefaultValueAvailable())
                    {
                        $path .='/{'.$param->name.'?}';
                    }
                    else
                    {
                        $path .='/{'.$param->name.'}';
                    }
                }

                Route::get($method_name.$path, $controller.'@'.$method->name)->name($calss_config['LAZY_CONFIG']['url_path'].'.'.$method->name);
            }
            else if ($method_type == 'post')
            {
                $path = null;
                foreach ((object) $method->getParameters() as $param) {

                    if($param->hasType())
                    {
                        continue;
                    }

                    if($param->isDefaultValueAvailable())
                    {
                        $path .='/{'.$param->name.'?}';
                    }
                    else
                    {
                        $path .='/{'.$param->name.'}';
                    }
                }

                Route::post($method_name.$path, $controller.'@'.$method->name)->name($calss_config['LAZY_CONFIG']['url_path'].'.'.$method->name);
            }
        }

    });

    if(config('lazy_router.error_flash'))
        $console->writeln("Lazy Routing: You can disable in config/lazy_config.pho. \n");
}






