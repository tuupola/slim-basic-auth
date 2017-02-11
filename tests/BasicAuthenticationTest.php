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

namespace Tuupola\Middleware\HttpBasicAuthentication;

use Tuupola\Middleware\HttpBasicAuthentication;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Zend\Diactoros\Stream;

use Test\TrueRule;
use Test\FalseRule;
use Test\TrueAuthenticator;
use Test\FalseAuthenticator;

use Tuupola\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
use Tuupola\Middleware\HttpBasicAuthentication\RequestPathRule;
use Tuupola\Middleware\HttpBasicAuthentication\RequestMethodRule;

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
        $this->assertInstanceOf(
            ArrayAuthenticator::class,
            $auth->getAuthenticator()
        );
        $this->assertInstanceOf(
            RequestPathRule::class,
            $rules->pop()
        );
        $this->assertInstanceOf(
            RequestMethodRule::class,
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
                new TrueRule,
                new FalseRule,
                new RequestMethodRule(["passthrough" => ["OPTIONS"]])
            ]
        ]);

        $rules = $auth->getRules();

        $this->assertEquals("Mordor", $auth->getRealm());
        $this->assertInstanceOf(
            ArrayAuthenticator::class,
            $auth->getAuthenticator()
        );
        $this->assertInstanceOf(
            RequestMethodRule::class,
            $rules->pop()
        );
        $this->assertInstanceOf(
            FalseRule::class,
            $rules->pop()
        );
        $this->assertInstanceOf(
            TrueRule::class,
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
            "authenticator" => new FalseAuthenticator,
            "rules" => [
                new FalseRule
            ]
        ]);

        $this->assertInstanceOf(FalseAuthenticator::class, $auth->getAuthenticator());
        $this->assertInstanceOf(FalseRule::class, $auth->getRules()->pop());

        $auth
            ->setAuthenticator(new TrueAuthenticator)
            ->setRules([new TrueRule])
            ->addRule(new FalseRule);

        $this->assertInstanceOf(TrueAuthenticator::class, $auth->getAuthenticator());
        $this->assertInstanceOf(FalseRule::class, $auth->getRules()->pop());
        $this->assertInstanceOf(TrueRule::class, $auth->getRules()->pop());
    }

    public function testShouldReturn200WithoutPassword()
    {
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

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
        $request = (new Request)
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
        $request = (new Request)
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

    public function testShouldReturn401WithFromAfter()
    {
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "after" => function ($request, $response, $arguments) {
                return $response
                    ->withBody(new Stream("php://memory"))
                    ->withStatus(401)
                    ->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"', $this->getRealm()));
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", (string) $response->getBody());
    }

    public function testShouldAlterResponseWithAfter()
    {
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "after" => function ($request, $response, $arguments) {
                return $response->withHeader("X-Brawndo", "plants crave");
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("plants crave", (string) $response->getHeaderLine("X-Brawndo"));
    }

    public function testShouldCallErrorHandlerWith401()
    {
        $request = (new Request)
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

        $request = (new Request)
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

    /*** OTHER *************************************************************/

    public function testShouldReturn200WithTrueAuthenticator()
    {
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

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

    public function testShouldModifyRequestUsingBefore()
    {
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "before" => function ($request, $response, $arguments) {
                return $request->withAttribute("user", $arguments["user"]);
            }
        ]);

        $next = function (Request $request, Response $response) {
            $user = $request->getAttribute("user");
            $response->getBody()->write($user);
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("root", (string) $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->setExpectedException("RuntimeException");

        $request = (new Request)
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
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/item"))
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

    public function testShouldGetAndSetBefore()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $before = function () {
            return "It's got Electrolytes.";
        };
        $auth->setBefore($before);
        $this->assertEquals($before, $auth->getBefore());
    }

    public function testShouldGetAndSetAfter()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($user, $pass) {
                return true;
            }
        ]);
        $after = function () {
            return "That is what plants crave.";
        };
        $auth->setAfter($after);
        $this->assertEquals($after, $auth->getAfter());
    }


    /*** BUGS *************************************************************/

    public function testBug2UrlShouldMatchRegex()
    {
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
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
        $request = (new Request)
            ->withUri(new Uri("https://example.com/api/foo"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Basic Zm9vOmJhcjpwb3A=");

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
