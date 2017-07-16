<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2017 Mika Tuupola
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
    public function testShouldFailWithoutAuthenticator()
    {
        $this->setExpectedException("RuntimeException");
        $auth = new HttpBasicAuthentication();
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
            "realm" => "Not sure",
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
        $this->assertEquals('Basic realm="Not sure"', $response->getHeaderline("WWW-Authenticate"));
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

        $auth = $auth->addrule(function ($request) {
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

    public function testShouldReturn200WithIgnore()
    {
        $request = (new Request)
            ->withUri(new Uri("https://example.com/admin/ping"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "ignore" => "/admin/ping",
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
                    ->withHeader("WWW-Authenticate", 'Basic realm="Go away!"');
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Go away!"', $response->getHeaderline("WWW-Authenticate"));
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
            ->withUri(new Uri("http://localhost/api"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "secure" => true,
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

    public function testShouldRelaxInsecureViaSetting()
    {
        $request = (new Request)
            ->withUri(new Uri("http://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new HttpBasicAuthentication([
            "secure" => true,
            "relaxed" => ["localhost", "example.com"],
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

    public function testShouldBeImmutable()
    {
        $auth = new HttpBasicAuthentication([
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $auth2 = $auth->addRule(new TrueRule);
        $auth3 = $auth->withRules([new TrueRule]);

        /* Closure kludge to test private properties. */
        $self = $this;

        $closure = function () use ($self) {
            $self->assertEquals(2, count($this->rules));
        };
        call_user_func($closure->bindTo($auth, HttpBasicAuthentication::class));

        $closure = function () use ($self) {
            $self->assertEquals(3, count($this->rules));
        };
        call_user_func($closure->bindTo($auth2, HttpBasicAuthentication::class));

        $closure = function () use ($self) {
            $self->assertEquals(1, count($this->rules));
        };
        call_user_func($closure->bindTo($auth3, HttpBasicAuthentication::class));
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
