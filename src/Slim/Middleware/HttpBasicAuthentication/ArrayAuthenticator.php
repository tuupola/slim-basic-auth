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

    public function authenticate($user, $pass)
    {
        return isset($this->options["users"][$user]) && $this->options["users"][$user] === $pass;
    }
}
