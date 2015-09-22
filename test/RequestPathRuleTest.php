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

use \Slim\Middleware\HttpBasicAuthentication\RequestPathRule;

class RequestPathRuleTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAcceptArrayAndStringAsPath()
    {
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/admin/protected"
            ))
        );

        $rule = new RequestPathRule(array(
            "path" => "/admin",
        ));
        $this->assertTrue($rule($request));

        $rule = new RequestPathRule(array(
            "path" => array("/admin"),
        ));

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateEverything()
    {
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/"
            ))
        );

        $rule = new RequestPathRule(array("path" => "/"));
        $this->assertTrue($rule($request));

        $adminRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/admin/"
            ))
        );
        $this->assertTrue($rule($adminRequest));
    }


    public function testShouldAuthenticateOnlyAdmin()
    {
        $request = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/"
            ))
        );

        $rule = new RequestPathRule(array("path" => "/admin"));
        $this->assertFalse($rule($request));

        $adminRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/admin/"
            ))
        );
        $this->assertTrue($rule($adminRequest));
    }

    public function testShouldAuthenticateCreateAndList()
    {
        /* Authenticate only create and list actions */
        $rule = new RequestPathRule(array("path" => array(
            "/api/create", "/api/list"
        )));

        /* Should not authenticate */
        $apiRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/api"
            ))
        );
        $this->assertFalse($rule($apiRequest));

        /* Should authenticate */
        $createRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/api/create"
            ))
        );
        $this->assertTrue($rule($createRequest));

        /* Should authenticate */
        $listRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/api/list"
            ))
        );
        $this->assertTrue($rule($listRequest));

        /* Should not authenticate */
        $pingRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/api/ping"
            ))
        );
        $this->assertFalse($rule($pingRequest));
    }

    public function testShouldPassthroughLogin()
    {
        $protectedRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/admin/protected"
            ))
        );

        $rule = new RequestPathRule(array(
            "path" => "/admin",
            "passthrough" => array("/admin/login")
        ));
        $this->assertTrue($rule($protectedRequest));

        $loginRequest = \Slim\Http\Request::createFromEnvironment(
            \Slim\Http\Environment::mock(array(
                "SCRIPT_NAME" => "/index.php",
                "REQUEST_URI" => "/admin/login"
            ))
        );
        $this->assertFalse($rule($loginRequest));
    }
}
