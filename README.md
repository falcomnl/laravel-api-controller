# Laravel API Controller

This is a very simple project to provide a basecontroller to make rapid standardized API controllers in Laraval 5 and up.



### Installation

Note: make sure u have installed Laravel Passport.

```composer require falcomnl/laravel-api-controller```

### Basic usage

1. Create a API route

    in api.php create a route, eg:
    
    ```
    Route::middleware(['auth:api'])->group(function () {
        Route::apiResource('books', 'Api\BookController');
    });
    ```

2. Add a controller and extend the resource controller

    ```php
    <?php namespace App\Http\Controllers\Api;
    
    use Falcomnl\LaravelApiController\Http\Controllers\Api\ApiResourceController;
    use App\Book;
    
    class BookController extends ApiResourceController
    {
        protected $model = Book::class;
        protected $allowed_operations = ['index', 'show', 'create', 'update', 'delete'];
    
        protected function rules(bool $is_update = false): array
        {
            return [
                'name' => 'required|string|max20',
                'isbn' => 'required|numeric',
            ];
        }
    
        protected function messages(bool $is_update = false): array
        {
            return [
                
            ];
        }
    }
    
    ```

### Authorization
By default the class uses Laravel policies (https://laravel.com/docs/7.x/authorization#creating-policies). 
This can be overridden like so: ```protected $use_authorization = false;```

### Constraints
When using a parent controller, like in the url: ```https://domain.com/api/books/{book}/comments```, you can 
use the constructor to constrain the CRUD actions to the parent:

```php
<?php namespace App\Http\Controllers\Api;

use Falcomnl\LaravelApiController\Http\Controllers\Api\ApiResourceController;
use App\Comment;

class CommentController extends ApiResourceController
{
    protected $model = Comment::class;
    protected $allowed_operations = ['index', 'show', 'create'];

    public function __construct(Request $request)
    {
        $this->constraints = ['book_id' => $request->route()->parameters['book']];
        parent::__construct($request);
    }
}

```

### Order
This project uses spatie/eloquent-sortable to provide order-up/order-down endpoints. Just implement eloquent-sortable 
in your model and add 'up' and 'down' to the ```$allowed_operations``` -array in the controller.

Also, add to your routes:
```php
Route::post('books/{book}/order-up', 'Api\BookController@orderUp');
Route::post('books/{book}/order-down', 'Api\BookController@orderDown');
```

### Debugging
When a .env value ```LOG_API=true``` is set, all requests will be logged.
