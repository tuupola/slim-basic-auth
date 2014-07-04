<?php

require "vendor/autoload.php";

$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuth(array(
    "path" => "/admin",
    "realm" => "Here be dragons.",
    "environment" => "REDIRECT_HTTP_AUTHORIZATION",
    "users" => array(
        "root" => "t00r",
        "user" => "passw0rd"
    )
)));

$app->get("/", function() {
    echo "Success";
});
$app->get("/admin", function() {
    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();
    echo $info;
});

$app->run();