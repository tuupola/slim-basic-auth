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

use \Slim\Middleware\HttpBasicAuthentication\DefaultValidator;

class DefaultValidatorTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldNotAuthenticateOptions()
    {
        \Slim\Environment::mock(array(
            "REQUEST_METHOD" => "OPTIONS"
        ));

        $validator = new DefaultValidator();

        $this->assertFalse($validator());
    }

    public function testShouldAuthenticatePost()
    {
        \Slim\Environment::mock(array(
            "REQUEST_METHOD" => "POST"
        ));

        $validator = new DefaultValidator();

        $this->assertTrue($validator());
    }

    public function testShouldAuthenticateGet()
    {
        \Slim\Environment::mock(array(
            "REQUEST_METHOD" => "GET"
        ));

        $validator = new DefaultValidator();

        $this->assertTrue($validator());
    }

    public function testShouldConfigurePassthru()
    {
        \Slim\Environment::mock(array(
            "REQUEST_METHOD" => "GET"
        ));

        $validator = new DefaultValidator(array(
            "passthru" => array("GET")
        ));

        $this->assertFalse($validator());
    }
}
