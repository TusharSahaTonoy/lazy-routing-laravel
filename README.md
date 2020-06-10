
# Lazy Routing
A PHP laravel package for auto route declaration. After the installation user don't need to declare route in web.php file for each method. 
## Installation
*Step 1:* In your laravel application composer require the package
````
composer require tushar/lazy-routing
````
*Step 2:* Publish the package.
````
php artisan vendor:publish --provider="Tushar\LazyRouting\LazyServiceProvider"
````
*Step 3:* Create a normal controller in app\Http\Controllers.

*Step 4:* Declare const  LAZY_CONFIG inside your controller class.
````
class  TestController  extends  Controller

{
	const  LAZY_CONFIG  = [
		"url_path" => "testy", //path prefix
		'route_generation' => true, // optional (default true)
	] ;
}
````

- Here **url_path** is the route path prefix for this controller class. Like, in this example, the route will be created is  
-> www.domain.com/testy/path-one
-> www.domain.com/testy/path-two

- **route_generation** value is useful when you don't want to generate route for a specific controller. By default it is true and declaration of this variable is not mandatory.

## Method Declaration
### Get Method
Put "get" in front of your method name to declare as get route. And use **camelCase** to declare your method name.
````
public  function  getGreatUser($user)
{
	return "Hello $user";
}
````
This method will generate a get route like (if "url_path" => "testy") 
url: domain.com/testey/greate-user/Jhon 
route name: testy.getGreatUser [format : url_path.method_full_name]
Result -> Hello Jhon

### Post Method
Similar to the get method. Put “post” in front of your method name to declare as get route. And use **camelCase** to declare your method name.
````
public  function  postSaveUser(Request $request )
{
	return $request->all();
}
````
url: domain.com/testey/save-user
route name: testy.postSaveUser
#### If you don't put get or post in front of your method name route will not be generated for that method.

You can see artisan route list to verify your routes.
````
php artisan route:list
````