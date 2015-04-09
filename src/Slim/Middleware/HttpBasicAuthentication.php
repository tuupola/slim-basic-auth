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

namespace Slim\Middleware;

 use \Slim\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
 use \Slim\Middleware\HttpBasicAuthentication\RequestMethodPassthrough;
 use \Slim\Middleware\HttpBasicAuthentication\PathShouldMatch;

class HttpBasicAuthentication extends \Slim\Middleware
{
    public $options;
    protected $stack;

    public function __construct($options = null)
    {

        /* Default options. */
        $this->options = array(
            "users" => array(),
            "path" => "/",
            "realm" => "Protected",
            "environment" => "HTTP_AUTHORIZATION",
            "rules" => null
        );

        /* Pass all options. Extra stuff get ignored anyway. */
        $this->options["authenticator"] = new ArrayAuthenticator($options);

        if ($options) {
            $this->options = array_merge($this->options, (array)$options);
        }

        /* Setup stack for rules */
        $this->stack = new \SplStack;

        /* Add default rule if nothing was passed in options. */
        /* Pass empty array to disable all rules except path matching. */
        if (null === $this->options["rules"]) {
            $this->addRule(new RequestMethodPassthrough);
        }

        /* Path match rule is always added */
        $this->addRule(new PathShouldMatch(array(
            "path" => $this->options["path"]
        )));
    }

    public function addRule($callable)
    {
        $this->stack->push($callable);
        return $this;
    }

    public function call()
    {
        $environment = $this->app->environment;

        if ($this->shouldAuthenticate()) {
            /* Just in case. */
            $user = false;
            $pass = false;

            /* If using PHP in CGI mode. */
            if (isset($_SERVER[$this->options["environment"]])) {
                if (preg_match("/Basic\s+(.*)$/i", $_SERVER[$this->options["environment"]], $matches)) {
                    list($user, $pass) = explode(":", base64_decode($matches[1]));
                }
            } else {
                $user = $environment["PHP_AUTH_USER"];
                $pass = $environment["PHP_AUTH_PW"];
            }

            /* Check if user authenticates. */
            if ($this->options["authenticator"]($user, $pass)) {
                $this->next->call();
            } else {
                $this->app->response->status(401);
                $this->app->response->header("WWW-Authenticate", sprintf('Basic realm="%s"', $this->options["realm"]));
                return;
            }
        } else {
            $this->next->call();
        }
    }

    public function shouldAuthenticate()
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->stack as $rule) {
            if (false === $rule($this->app)) {
                return false;
            }
        }
        return true;
    }
}
