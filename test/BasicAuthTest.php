<?php

require_once dirname(__FILE__) . "/../vendor/autoload.php";

class BasicAuthTest extends PHPUnit_Framework_TestCase {

    public function testShouldReturn200WithoutPassword() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/foo/bar"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function() {
            echo "Success";
        });

        $auth = new \Slim\Middleware\HttpBasicAuth(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(200, $app->response()->status());
        $this->assertEquals("Success", $app->response()->body());
    }

    public function testShouldReturn401WithoutPassword() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function() {
            echo "Success";
        });
        $app->get("/admin", function() {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuth(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(401, $app->response()->status());
        $this->assertEquals("", $app->response()->body());
    }

    public function testShouldReturn200WithPassword() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo",
            "PHP_AUTH_USER" => "root",
            "PHP_AUTH_PW" => "t00r",
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function() {
            echo "Success";
        });
        $app->get("/admin/foo", function() {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuth(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(200, $app->response()->status());
        $this->assertEquals("Admin", $app->response()->body());
    }

}