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

class RequestMethodRule implements RuleInterface
{
    protected $options;

    public function __construct($options = array())
    {

        /* Default options. */
        $this->options = array(
            "passthrough" => array("OPTIONS")
        );

        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(\Slim\Slim $app)
    {
        $environment = \Slim\Environment::getInstance();
        return !in_array($environment["REQUEST_METHOD"], $this->options["passthrough"]);
    }
}
