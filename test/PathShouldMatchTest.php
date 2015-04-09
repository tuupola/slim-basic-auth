<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2014 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Test;

use \Slim\Middleware\HttpBasicAuthentication\PathShouldMatch;

class PathShouldMatchTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldAuthenticateEverything()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/"
        ));

        $rule = new PathShouldMatch(array("path" => "/"));
        $this->assertTrue($rule(new \Slim\Slim));

        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/"
        ));
        $this->assertTrue($rule(new \Slim\Slim));
    }

    public function testShouldAuthenticateOnlyAdmin()
    {
        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/"
        ));

        $rule = new PathShouldMatch(array("path" => "/admin"));
        $this->assertFalse($rule(new \Slim\Slim));

        \Slim\Environment::mock(array(
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "/admin/"
        ));
        $this->assertTrue($rule(new \Slim\Slim));
    }
}
