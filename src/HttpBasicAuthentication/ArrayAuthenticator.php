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

namespace Slim\Middleware\HttpBasicAuthentication;

class ArrayAuthenticator implements AuthenticatorInterface
{

    public $options;

    public function __construct($options = null)
    {

        /* Default options. */
        $this->options = array(
            "users" => array()
        );

        if ($options) {
            $this->options = array_merge($this->options, (array)$options);
        }
    }

    public function __invoke(array $arguments)
    {
        $user = $arguments["user"];
        $password = $arguments["password"];
        return isset($this->options["users"][$user]) && $this->options["users"][$user] === $password;
    }
}
