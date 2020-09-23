<?php

/*

Copyright (c) 2013-2020 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

/**
 * @see       https://github.com/tuupola/slim-basic-auth
 * @license   https://www.opensource.org/licenses/mit-license.php
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
