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
    /**
     * Create a Slim app with a request.
     *
     * @param string[] $env environment to give to \Slim\Http\Environment.
     * SCRIPT_NAME will default to /index.php.
     *
     * @return \Slim\App;
     */
    private function createApp(array $env)
    {
        $request = \Slim\Http\Request::createFromEnvironment(
                \Slim\Http\Environment::mock($env + array(
                "SCRIPT_NAME" => "/index.php"
            ))
        );

        return new \Slim\App(
            new \Slim\Container(compact("request"))
        );
    }

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
        $app = $this->createApp(array(
            "REQUEST_URI" => "/foo/bar"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithoutPassword()
    {
        $app = $this->createApp(["REQUEST_URI" => "/admin/foo"]);
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => array("/admin"),
            "realm" => "Protected",
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithPassword()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo",
            "PHP_AUTH_USER" => "root",
            "PHP_AUTH_PW" => "t00r"
        ));

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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }

    public function testShouldReturn200WithOptions()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo",
            "REQUEST_METHOD" => "OPTIONS"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Allow: GET", $response->getBody());
    }

    public function testShouldReturn200WithoutPasswordWithAnonymousFunction()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
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
        $auth->addrule(function (\Psr\Http\Message\ServerRequestInterface $request) {

            return false;
        });

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }

    public function testShouldReturn401WithFalseFromCallback()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo",
            "PHP_AUTH_USER" => "root",
            "PHP_AUTH_PW" => "t00r",
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldCallErrorHandlerWith401()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("ERROR: Authentication failed", $response->getBody());
    }

    /*** CGI MODE **********************************************************/

    public function testShouldReturn200WithPasswordInCgiMode()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));

        $_SERVER["HTTP_AUTHORIZATION"] = "Basic cm9vdDp0MDBy";

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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }

    public function testShouldHonorCgiEnviromentOption()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));

        $_SERVER["FOO_BAR"] = "Basic cm9vdDp0MDBy";

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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }


    public function testShouldReturn200WithTrueAuthenticator()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }

    public function testShouldReturn401WithFalseAuthenticator()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithAnonymousFunction()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return true;
            }
        ));

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Admin", $response->getBody());
    }

    public function testShouldReturn401WithAnonymousFunction()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/foo"
        ));
        $app->get("/foo/bar", function () {
            echo "Success";
        });
        $app->get("/admin/foo", function () {
            echo "Admin";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return false;
            }
        ));

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/api/foo",
            "SERVER_NAME" => "dev.example.com",
            "HTTPS" => false,
        ));

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
            ),
        ));

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody(), 'RuntimeException') !== false);
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/public/foo",
            "SERVER_NAME" => "localhost",
            "slim.url_scheme" => "http"
        ));


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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
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
        $app = $this->createApp(array(
            "REQUEST_URI" => "/status/foo"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Status", $response->getBody());
    }


    public function testBug3ShouldReturn401WithoutTrailingSlash()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug3ShouldReturn401WithTrailingSlash()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/admin/"
        ));
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

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug9ShouldAllowUnauthenticatedHttp()
    {
        $app = $this->createApp(array(
            "REQUEST_URI" => "/public/foo",
            "SERVER_NAME" => "dev.example.com",
            "slim.url_scheme" => "http"
        ));

        $app->get("/public/foo", function () {
            echo "Success";
        });

        $app->get("/api/foo", function () {
            echo "Foo";
        });

        $auth = new \Slim\Middleware\HttpBasicAuthentication(array(
            "path" => array("/api", "/bar"),
            "users" => array(
                "root" => "t00r",
                "user" => "passw0rd"
            )
        ));

        $app->add($auth);
        $response = $app->run(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }
}
