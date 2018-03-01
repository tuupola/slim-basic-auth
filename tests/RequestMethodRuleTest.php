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

class RequestMethodRuleTest extends TestCase
{

    public function testShouldNotAuthenticateOptions()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS");

        $response = new Response;
        $rule = new RequestMethodRule;

        $this->assertFalse($rule($request));
    }

    public function testShouldAuthenticatePost()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("POST");

        $response = new Response;
        $rule = new RequestMethodRule;

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateGet()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;
        $rule = new RequestMethodRule;

        $this->assertTrue($rule($request));
    }

    public function testShouldConfigureIgnore()
    {
        $request = (new ServerRequest())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;
        $rule = new RequestMethodRule;

        $rule = new RequestMethodRule([
            "ignore" => ["GET"]
        ]);

        $this->assertFalse($rule($request));
    }
}
