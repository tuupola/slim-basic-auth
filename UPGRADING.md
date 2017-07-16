# Updgrading from 2.x to 3.x

## New namespace
For most cases it is enough just to update the classname. Instead of using the old `Slim\Middleware` namespace:

```php
$app->add(new Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

You should now use `Tuupola\Middleware` instead:

```php
$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));

````

## Changed parameter names

Parameters `callback` and `passthrough` were renamed to `before` and `ignore`. In other words instead of doing:

```php
$app->add(new Slim\Middleware\HttpBasicAuthentication([
    "passthrough" => ["/token"],
    "callback" => function ($request, $response, $arguments) {
        print_r($arguments);
    }
]));
```

You should now do the following instead:

```php
$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "ignore" => ["/token"],
    "before" => function ($request, $response, $arguments) {
        print_r($arguments);
    }
]));
```

Note that `before()` should now return an instance of `Psr\Http\Message\RequestInterface`. Anything else will be ignored.

## Most setter are removed

Most public setters and getters were removed. If you had code like following:

```php
$auth = (new Slim\Middleware\HttpBasicAuthentication)
    ->setPath(["/admin", "/api"])
    ->setRealm("Protected"),
    ->setUsers([
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]);

$app->add($auth);
```

Settings should now be passed in constructor instead:

```php
$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => ["/admin", "/api"],
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```
