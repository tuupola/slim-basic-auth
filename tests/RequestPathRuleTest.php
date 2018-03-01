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

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class RequestPathTest extends TestCase
{

    public function testShouldAcceptArrayAndStringAsPath()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/admin/protected"))
            ->withMethod("GET");

        $rule = new RequestPathRule(["path" => "/admin"]);
        $this->assertTrue($rule($request));

        $rule = new RequestPathRule(["path" => ["/admin"]]);
        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateEverything()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/"))
            ->withMethod("GET");

        $rule = new RequestPathRule(["path" => "/"]);
        $this->assertTrue($rule($request));

        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateOnlyApi()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/"))
            ->withMethod("GET");

        $rule = new RequestPathRule(["path" => "/api"]);
        $this->assertFalse($rule($request));

        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateCreateAndList()
    {
        /* Authenticate only create and list actions */
        $rule = new RequestPathRule([
            "path" => ["/api/create", "/api/list"]
        ]);

        /* Should not authenticate */
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $this->assertFalse($rule($request));

        /* Should authenticate */
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api/create"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));

        /* Should authenticate */
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api/list"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));

        /* Should not authenticate */
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api/ping"))
            ->withMethod("GET");
        $this->assertFalse($rule($request));
    }

    public function testShouldIgnoreLogin()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $rule = new RequestPathRule([
            "path" => "/api",
            "ignore" => ["/api/login"]
        ]);
        $this->assertTrue($rule($request));

        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api/login"))
            ->withMethod("GET");

        $this->assertFalse($rule($request));
    }

    public function testBug50ShouldAuthenticateMultipleSlashes()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/"))
            ->withMethod("GET");
        $rule = new RequestPathRule(["path" => "/v1/api"]);
        $this->assertFalse($rule($request));
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/v1/api"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/v1//api"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/v1//////api"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com//v1/api"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com//////v1/api"))
            ->withMethod("GET");
        $this->assertTrue($rule($request));
    }
}
