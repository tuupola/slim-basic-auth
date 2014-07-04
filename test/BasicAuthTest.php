<?php

/*
 * HTTP Basic Authentication
 *
 * Copyright (c) 2013-2014 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

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

    /*** CGI MODE **********************************************************/

    public function testShouldReturn200WithPasswordInCgiMode() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));

        $_SERVER["HTTP_AUTHORIZATION"] = "Basic cm9vdDp0MDBy";

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

    public function testShouldHonorCgiEnviromentOption() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));

        $_SERVER["FOO_BAR"] = "Basic cm9vdDp0MDBy";

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
            "environment" => "FOO_BAR",
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

    /*** OTHER *************************************************************/

    public function testBug2() {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/status/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/status/foo", function() {
            echo "Status";
        });
        $app->get("/status(/?)", function() {
            echo "Status";
        });
        $app->get("/stat", function() {
            echo "Stat";
        });

        $auth = new \Slim\Middleware\HttpBasicAuth(array(
            "path" => "/stat",
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
        $this->assertEquals("Status", $app->response()->body());
    }

}