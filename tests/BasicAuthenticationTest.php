<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2018 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Tuupola\Middleware\HttpBasicAuthentication;

use Equip\Dispatch\MiddlewareCollection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Test\TrueRule;
use Test\FalseRule;
use Test\TrueAuthenticator;
use Test\FalseAuthenticator;
use Tuupola\Middleware\HttpBasicAuthentication;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Http\Factory\ServerRequestFactory;
use Tuupola\Http\Factory\StreamFactory;

class HttpBasicAuthenticationTest extends TestCase
{
    public function testShouldFailWithoutAuthenticator()
    {
        $this->expectException("RuntimeException");
        $auth = new HttpBasicAuthentication();
    }

    public function testShouldReturn200WithoutPassword()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithoutPassword()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => ["/admin"],
            "realm" => "Not sure",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithOptions()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("OPTIONS", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithoutPasswordWithAnonymousFunction()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

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

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithIgnore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/ping");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "ignore" => "/admin/ping",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithFromAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "after" => function ($response, $arguments) {
                return $response
                    ->withBody((new StreamFactory)->createStream())
                    ->withStatus(401)
                    ->withHeader("WWW-Authenticate", 'Basic realm="Go away!"');
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "after" => function ($response, $arguments) {
                return $response->withHeader("X-Brawndo", "plants crave");
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("plants crave", (string) $response->getHeaderLine("X-Brawndo"));
    }

    public function testShouldCallErrorHandlerWith401()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "error" => function ($response, $arguments) {
                $response->getBody()->write("ERROR: " . $arguments["message"]);
                return $response;
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("ERROR: Authentication failed", $response->getBody());
    }

    public function testErrorHandlerShouldAlterHeaders()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "error" => function ($response, $arguments) {
                return $response
                    ->withStatus(302)
                    ->withHeader("Location", "/foo/bar");
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /*** OTHER *************************************************************/

    public function testShouldReturn200WithTrueAuthenticator()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new \Test\TrueAuthenticator()
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithFalseAuthenticator()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => new \Test\FalseAuthenticator()
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithAnonymousFunction()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return true;
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401WithAnonymousFunction()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "authenticator" => function ($arguments) {
                return false;
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldModifyRequestUsingBefore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin/item")
            ->withHeader("Authorization", "Basic cm9vdDp0MDBy");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/admin",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ],
            "before" => function ($request, $arguments) {
                return $request->withAttribute("user", $arguments["user"]);
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $this->expectException("RuntimeException");

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://localhost/api");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "secure" => true,
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldRelaxInsecureViaSetting()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "secure" => true,
            "relaxed" => ["localhost", "example.com"],
            "path" => "/api",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
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

    public function testShouldHandlePsr15()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/");

        $response = (new ResponseFactory)->createResponse();

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };
        $collection = new MiddlewareCollection([
            new HttpBasicAuthentication([
                "users" => [
                    "root" => "t00r",
                    "user" => "passw0rd"
                ]
            ])
        ]);
        $response = $collection->dispatch($request, $default);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldHandleRulesArrayBug()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (ServerRequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new HttpBasicAuthentication([
                "users" => [
                    "root" => "t00r",
                    "user" => "passw0rd"
                ],
                "rules" => [
                    new RequestPathRule([
                        "path" => ["/api"],
                        "ignore" => ["/api/login"],
                    ]),
                    new RequestMethodRule([
                        "ignore" => ["OPTIONS"],
                    ])
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/login");

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    /*** BUGS *************************************************************/

    public function testBug2UrlShouldMatchRegex()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/status/foo");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/stat",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testBug3ShouldReturn401WithoutTrailingSlash()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug3ShouldReturn401WithTrailingSlash()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/admin");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => "/",
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testBug9ShouldAllowUnauthenticatedHttp()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public/foo");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => ["/api", "/bar"],
            "realm" => "Protected",
            "users" => [
                "root" => "t00r",
                "user" => "passw0rd"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testBug31ShouldAllowColonInPassword()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/foo")
            ->withHeader("Authorization", "Basic Zm9vOmJhcjpwb3A=");

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => ["/api", "/bar"],
            "realm" => "Protected",
            "users" => [
                "foo" => "bar:pop"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testPull59ShouldNotErrorWithMalformedCredentials()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/foo")
            ->withHeader("Authorization", "Basic Zm9vCg=="); /* foo */

        $response = (new ResponseFactory)->createResponse();

        $auth = new HttpBasicAuthentication([
            "path" => ["/api", "/bar"],
            "realm" => "Protected",
            "users" => [
                "foo" => "bar"
            ]
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }
}
