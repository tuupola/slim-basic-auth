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

use \Slim\Middleware\HttpBasicAuthentication\RequestMethodRule;

class RequestMethodRuleTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldNotAuthenticateOptions()
    {

        $rule = new RequestMethodRule();
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "REQUEST_METHOD" => "OPTIONS"
            )
        ));

        $this->assertFalse($rule($request));
    }

    public function testShouldAuthenticatePost()
    {
        $rule = new RequestMethodRule();
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "REQUEST_METHOD" => "POST"
            ))
        );

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateGet()
    {
        $rule = new RequestMethodRule();
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "REQUEST_METHOD" => "GET"
            ))
        );

        $this->assertTrue($rule($request));
    }

    public function testShouldConfigurePassThrough()
    {
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "REQUEST_METHOD" => "GET"
            ))
        );

        $rule = new RequestMethodRule(array(
            "passthrough" => array("GET")
        ));

        $this->assertFalse($rule($request));
    }
}
