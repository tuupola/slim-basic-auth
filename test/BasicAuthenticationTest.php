<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2015 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Test;

use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;
use \Slim\Middleware\HttpBasicAuthentication\RuleInterface;

use \Slim\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
use \Slim\Middleware\HttpBasicAuthentication\RequestMethodRule;
use \Slim\Middleware\HttpBasicAuthentication\RequestPathRule;

class HttpBasicAuthenticationTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeCreatedInEasyMode()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Mordor",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $users = $auth->getUsers();
        $rules = $auth->getRules();

        $this->assertEquals("t00r", $users["root"]);
        $this->assertEquals("/admin", $auth->getPath());
        $this->assertEquals("Mordor", $auth->getRealm());
        $this->assertEquals("HTTP_AUTHORIZATION", $auth->getEnvironment());
        $this->assertInstanceOf(
            "\Slim\Middleware\HttpBasicAuthentication\ArrayAuthenticator",
            $auth->getAuthenticator()
        );
        $this->assertInstanceOf(
            "\Slim\Middleware\HttpBasicAuthentication\RequestPathRule",
            $rules->pop()
        );
        $this->assertInstanceOf(
            "\Slim\Middleware\HttpBasicAuthentication\RequestMethodRule",
            $rules->pop()
        );
    }

    public function testShouldBeCreatedInNormalMode()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "realm" => "Mordor",
            "authenticator" => new ArrayAuthenticator(array(
                "users" => array(
                    "root" => "t00r",
                    "user" => "passw0rd"
                )
            )),
            "rules" => array(
                new TrueRule,
                new FalseRule,
                new RequestMethodRule(array("passthrough" => array("OPTIONS")))
            )
        ));

        //$users = $auth->getUsers();
        $rules = $auth->getRules();

        //$this->assertEquals("t00r", $users["root"]);
        //$this->assertEquals("/admin", $auth->getPath());
        $this->assertEquals("Mordor", $auth->getRealm());
        $this->assertEquals("HTTP_AUTHORIZATION", $auth->getEnvironment());
        $this->assertInstanceOf(
            "\Slim\Middleware\HttpBasicAuthentication\ArrayAuthenticator",
            $auth->getAuthenticator()
        );
        $this->assertInstanceOf(
            "\Slim\Middleware\HttpBasicAuthentication\RequestMethodRule",
            $rules->pop()
        );
        $this->assertInstanceOf(
            "\Test\FalseRule",
            $rules->pop()
        );
        $this->assertInstanceOf(
            "\Test\TrueRule",
            $rules->pop()
        );
    }

    public function testShouldFailWithoutAuthenticator()
    {
        $this->setExpectedException("RuntimeException");
        $auth = new \Slim\Middleware\HttpBasicAuthentication();
    }

    public function testSettersShouldBeChainable()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "authenticator" => new FalseAuthenticator,
            "rules" => array(
                new FalseRule
            )
        ));

        $this->assertInstanceOf("\Test\FalseAuthenticator", $auth->getAuthenticator());
        $this->assertInstanceOf("\Test\FalseRule", $auth->getRules()->pop());

        $auth
            ->setAuthenticator(new TrueAuthenticator)
            ->setRules(array(new TrueRule))
            ->addRule(new FalseRule);

        $this->assertInstanceOf("\Test\TrueAuthenticator", $auth->getAuthenticator());
        $this->assertInstanceOf("\Test\FalseRule", $auth->getRules()->pop());
        $this->assertInstanceOf("\Test\TrueRule", $auth->getRules()->pop());

    }

    public function testShouldReturn200WithoutPassword()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/foo/bar"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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

    public function testShouldReturn401WithoutPassword()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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

    public function testShouldReturn200WithPassword()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo",
            "PHP_AUTH_USER" => "root",
            "PHP_AUTH_PW" => "t00r",
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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

    public function testShouldReturn200WithOptions()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo",
            "REQUEST_METHOD" => "OPTIONS"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->options("/admin/foo", function () {
            echo "Allow: GET";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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
        $this->assertEquals("Allow: GET", $app->response()->body());
    }

    public function testShouldReturn200WithoutPasswordWithAnonymousFunction()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        /* Disable authentication by returning false from a rule */
        $auth->addrule(function (\Slim\Slim $app) {
            return false;
        });

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(200, $app->response()->status());
        $this->assertEquals("Admin", $app->response()->body());
    }

    public function testShouldReturn401WithFalseFromCallback()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo",
            "PHP_AUTH_USER" => "root",
            "PHP_AUTH_PW" => "t00r",
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            ),
            "callback" => function ($arguments) use ($app) {
                return false;
            }
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(401, $app->response()->status());
        $this->assertEquals("", $app->response()->body());
    }

    public function testShouldCallErrorHandlerWith401()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            ),
            "error" => function ($arguments) use ($app) {
                $app->response->write("ERROR: " . $arguments["message"]);
            }
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(401, $app->response()->status());
        $this->assertEquals("ERROR: Authentication failed", $app->response()->body());
    }

    /*** CGI MODE **********************************************************/

    public function testShouldReturn200WithPasswordInCgiMode()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));

        $_SERVER["HTTP_AUTHORIZATION"] = "Basic cm9vdDp0MDBy";

        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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

    public function testShouldHonorCgiEnviromentOption()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));

        $_SERVER["FOO_BAR"] = "Basic cm9vdDp0MDBy";

        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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


    public function testShouldReturn200WithTrueAuthenticator()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new TrueAuthenticator()
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(200, $app->response()->status());
        $this->assertEquals("Admin", $app->response()->body());
    }

    public function testShouldReturn401WithFalseAuthenticator()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new FalseAuthenticator()
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(401, $app->response()->status());
        $this->assertEquals("", $app->response()->body());
    }

    public function testShouldReturn200WithAnonymousFunction()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(200, $app->response()->status());
        $this->assertEquals("Admin", $app->response()->body());
    }

    public function testShouldReturn401WithAnonymousFunction()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return false;
            }
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();

        $this->assertEquals(401, $app->response()->status());
        $this->assertEquals("", $app->response()->body());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->setExpectedException("RuntimeException");

        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/public/foo",
            "SERVER_NAME" => "dev.example.com",
            "slim.url_scheme" => "http"
        ));
        $app = new \Slim\Slim();

        $app->get("/public/foo", function () {
            echo "Success";
        });

        $app->get("/api/foo", function () {
            echo "Foo";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/api",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $auth->setApplication($app);
        $auth->setNextMiddleware($app);
        $auth->call();
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/public/foo",
            "SERVER_NAME" => "localhost",
            "slim.url_scheme" => "http"
        ));

        $app = new \Slim\Slim();

        $app->get("/public/foo", function () {
            echo "Success";
        });

        $app->get("/api/foo", function () {
            echo "Foo";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "secure" => true,
            "path" => "/api",
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



    /*** OTHER *************************************************************/

    public function testShouldGetAndSetSecure()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ));
        $this->assertTrue($auth->getSecure());
        $auth->setSecure(false);
        $this->assertFalse($auth->getSecure());
    }

    public function testShouldGetAndSetRelaxed()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ));
        $relaxed = array("localhost", "dev.example.com");
        $auth->setRelaxed($relaxed);
        $this->assertEquals($relaxed, $auth->getRelaxed());
    }

    public function testShouldGetAndSetErrorHandler()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ));
        $error = function () {
            return "ERROR";
        };
        $auth->setError($error);
        $this->assertEquals($error, $auth->getError());
    }

    public function testShouldGetAndSetCallback()
    {
        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ));
        $callback = function () {
            return "It's got Electrolytes.";
        };
        $auth->setCallback($callback);
        $this->assertEquals($callback, $auth->getCallback());
    }

    /*** BUGS *************************************************************/

    public function testBug2UrlShouldMatchRegex()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/status/foo"
        ));
        $app = new \Slim\Slim();
        $app->get("/status/foo", function () {
            echo "Status";
        });
        $app->get("/status(/?)", function () {
            echo "Status";
        });
        $app->get("/stat", function () {
            echo "Stat";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
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


    public function testBug3ShouldReturn401WithoutTrailingSlash()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin"
        ));
        $app = new \Slim\Slim();
        $app->get("/admin(/?)", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/",
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

    public function testBug3ShouldReturn401WithTrailingSlash()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/"
        ));
        $app = new \Slim\Slim();
        $app->get("/admin(/?)", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/",
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
}
