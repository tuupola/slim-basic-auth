<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Slim\Middleware\HttpBasicAuthentication;

use Slim\Middleware\HttpBasicAuthentication;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class HttpBasicAuthenticationTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeCreatedInEasyMode()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "passthrough" => "/admin/ping",
            "realm" => "Mordor",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $users = $auth->getUsers();
        $rules = $auth->getRules();

        $this->assertEquals("t00r", $users["root"]);
        $this->assertEquals("/admin", $auth->getPath());
        $this->assertEquals("/admin/ping", $auth->getPassthrough());
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
        $auth = new HttpBasicAuthentication([
            "realm" => "Mordor",
            "authenticator" => new ArrayAuthenticator([
                "users" => [
                    "root" => "t00r",
                    "user" => "passw0rd"
                ]
            ]),
            "rules" => [
                new \Test\TrueRule,
                new \Test\FalseRule,
                new RequestMethodRule(["passthrough" => ["OPTIONS"]])
            ]
        ]);

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
        $auth = new HttpBasicAuthentication();
    }

    public function testSettersShouldBeChainable()
    {
        $auth = new HttpBasicAuthentication([
            "authenticator" => new \Test\FalseAuthenticator,
            "rules" => [
                new \Test\FalseRule
            ]
        ]);

        $this->assertInstanceOf("\Test\FalseAuthenticator", $auth->getAuthenticator());
        $this->assertInstanceOf("\Test\FalseRule", $auth->getRules()->pop());

        $auth
            ->setAuthenticator(new \Test\TrueAuthenticator)
            ->setRules([new \Test\TrueRule])
            ->addRule(new \Test\FalseRule);

        $this->assertInstanceOf("\Test\TrueAuthenticator", $auth->getAuthenticator());
        $this->assertInstanceOf("\Test\FalseRule", $auth->getRules()->pop());
        $this->assertInstanceOf("\Test\TrueRule", $auth->getRules()->pop());
    }

    public function testShouldReturn200WithoutPassword()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/public"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithoutPassword()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => ["/admin"],
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithPassword()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["PHP_AUTH_USER" => "root", "PHP_AUTH_PW" => "t00r"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithOptions()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("OPTIONS");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithoutPasswordWithAnonymousFunction()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $auth->addrule(function ($request) {
            return false;
        });

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithFalseFromCallback()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["PHP_AUTH_USER" => "root", "PHP_AUTH_PW" => "t00r"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "callback" => function ($request, $response, $arguments) {
                return false;
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldCallErrorHandlerWith401()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "error" => function ($request, $response, $arguments) {
                $response->getBody()->write("ERROR: " . $arguments["message"]);
                return $response;
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("ERROR: Authentication failed", $response->getBody());
    }

    public function testErrorHandlerShouldAlterHeaders()
    {

        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "error" => function ($request, $response, $arguments) {
                return $response
                    ->withStatus(302)
                    ->withHeader("Location", "/foo/bar");
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /*** CGI MODE **********************************************************/

    public function testShouldReturn200WithPasswordInCgiMode()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["HTTP_AUTHORIZATION" => "Basic cm9vdDp0MDBy"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldHonorCgiEnviromentOption()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["FOO_BAR" => "Basic cm9vdDp0MDBy"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "environment" => "FOO_BAR",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    /*** OTHER *************************************************************/

    public function testShouldReturn200WithTrueAuthenticator()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new \Test\TrueAuthenticator()
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithFalseAuthenticator()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["PHP_AUTH_USER" => "root", "PHP_AUTH_PW" => "t00r"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new \Test\FalseAuthenticator()
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithAnonymousFunction()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return true;
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithAnonymousFunction()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["PHP_AUTH_USER" => "root", "PHP_AUTH_PW" => "t00r"]
        );
        $request = $request
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return false;
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->setExpectedException("RuntimeException");

        $request = (new Request())
            ->withUri(new Uri("http://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["PHP_AUTH_USER" => "root", "PHP_AUTH_PW" => "t00r"]
        );
        $request = $request
            ->withUri(new Uri("http://localhost/admin/item"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);
    }

    public function testShouldGetAndSetSecure()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $this->assertTrue($auth->getSecure());
        $auth->setSecure(false);
        $this->assertFalse($auth->getSecure());
    }

    public function testShouldGetAndSetRelaxed()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $relaxed = ["localhost", "dev.example.com"];
        $auth->setRelaxed($relaxed);
        $this->assertEquals($relaxed, $auth->getRelaxed());
    }

    public function testShouldGetAndSetErrorHandler()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $error = function () {
            return "ERROR";
        };
        $auth->setError($error);
        $this->assertEquals($error, $auth->getError());
    }

    public function testShouldGetAndSetCallback()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $callback = function () {
            return "It's got Electrolytes.";
        };
        $auth->setCallback($callback);
        $this->assertEquals($callback, $auth->getCallback());
    }

    /*** BUGS *************************************************************/

    public function testBug2UrlShouldMatchRegex()
    {
        $request = (new Request())
            ->withUri(new Uri("http://example.com/status/foo"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/stat",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testBug3ShouldReturn401WithoutTrailingSlash()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug3ShouldReturn401WithTrailingSlash()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/admin"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug9ShouldAllowUnauthenticatedHttp()
    {
        $request = (new Request())
            ->withUri(new Uri("http://example.com/public/foo"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => ["/api", "/bar"],
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testBug31ShouldAllowColonInPassword()
    {
        $request = ServerRequestFactory::fromGlobals(
            ["HTTP_AUTHORIZATION" => "Basic Zm9vOmJhcjpwb3A="]
        );

        $request = $request
            ->withUri(new Uri("https://example.com/api/foo"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => ["/api", "/bar"],
            "realm" => "Protected",
            "users" => [
                "foo" => "bar:pop"
            ]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }
}
