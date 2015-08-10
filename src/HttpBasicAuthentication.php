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

use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;
use \Slim\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
use \Slim\Middleware\HttpBasicAuthentication\RequestMethodRule;
use \Slim\Middleware\HttpBasicAuthentication\RequestPathRule;

class HttpBasicAuthentication extends \Slim\Middleware
{
    private $rules;
    private $options = array(
        "secure" => true,
        "relaxed" => array("localhost", "127.0.0.1"),
        "users" => null,
        "path" => null,
        "realm" => "Protected",
        "environment" => "HTTP_AUTHORIZATION",
        "authenticator" => null,
        "callback" => null,
        "error" => null
    );

    public function __construct($options = array())
    {
        /* Setup stack for rules */
        $this->rules = new \SplStack;

        /* Store passed in options overwriting any defaults */
        $this->hydrate($options);

        /* If array of users was passed in options create an authenticator */
        if (is_array($this->options["users"])) {
            $this->options["authenticator"] = new ArrayAuthenticator(array(
                "users" => $this->options["users"]
            ));
        }

        /* If nothing was passed in options add default rules. */
        if (!isset($options["rules"])) {
            $this->addRule(new RequestMethodRule(array(
                "passthrough" => array("OPTIONS")
            )));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== ($this->options["path"])) {
            $this->addRule(new RequestPathRule(array(
                "path" => $this->options["path"]
            )));
        }

        /* There must be an authenticator either passed via options */
        /* or added because $this->options["users"] was an array. */
        if (null === $this->options["authenticator"]) {
            throw new \RuntimeException("Authenticator or users array must be given");
        }
    }

    public function call()
    {
        $environment = $this->app->environment;
        $scheme = $environment["slim.url_scheme"];

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate()) {
            $this->next->call();
            return;
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($environment["SERVER_NAME"], $this->options["relaxed"])) {
                $message = sprintf(
                    "Insecure use of middleware over %s denied by configuration.",
                    strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        /* Just in case. */
        $user = false;
        $password = false;

        /* If using PHP in CGI mode. */
        if (isset($_SERVER[$this->options["environment"]])) {
            if (preg_match("/Basic\s+(.*)$/i", $_SERVER[$this->options["environment"]], $matches)) {
                list($user, $password) = explode(":", base64_decode($matches[1]));
            }
        } else {
            $user = $environment["PHP_AUTH_USER"];
            $password = $environment["PHP_AUTH_PW"];
        }

        $params = array("user" => $user, "password" => $password);

        /* Check if user authenticates. */
        if (false === $this->options["authenticator"]($params)) {
            $this->app->response->status(401);
            $this->app->response->header("WWW-Authenticate", sprintf('Basic realm="%s"', $this->options["realm"]));
            $this->error(array(
                "message" => "Authentication failed"
            ));
            return;
        }

        /* If callback returns false return with 401 Unauthorized. */
        if (is_callable($this->options["callback"])) {
            if (false === $this->options["callback"]($params)) {
                $this->app->response->status(401);
                $this->app->response->header("WWW-Authenticate", sprintf('Basic realm="%s"', $this->options["realm"]));
                $this->error(array(
                    "message" => "Callback returned false"
                ));
                return;
            }
        }


        /* Everything ok, call next middleware. */
        $this->next->call();
    }

    private function hydrate($data = array())
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
    }

    private function shouldAuthenticate()
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($this->app)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Call the error handler if it exists
     *
     * @return void
     */
    public function error($params)
    {
        if (is_callable($this->options["error"])) {
            $this->options["error"]($params);
        }
    }

    public function getAuthenticator()
    {
        return $this->options["authenticator"];
    }

    public function setAuthenticator($authenticator)
    {
        $this->options["authenticator"] = $authenticator;
        return $this;
    }

    public function getUsers()
    {
        return $this->options["users"];
    }

    /* Do not mess with users right now */
    private function setUsers($users)
    {
        $this->options["users"] = $users;
        return $this;
    }

    public function getPath()
    {
        return $this->options["path"];
    }

    /* Do not mess with path right now */
    private function setPath($path)
    {
        $this->options["path"] = $path;
        return $this;
    }

    public function getRealm()
    {
        return $this->options["realm"];
    }

    public function setRealm($realm)
    {
        $this->options["realm"] = $realm;
        return $this;
    }

    public function getEnvironment()
    {
        return $this->options["environment"];
    }

    public function setEnvironment($environment)
    {
        $this->options["environment"] = $environment;
        return $this;
    }

    /**
     * Get the secure flag
     *
     * @return boolean
     */
    public function getSecure()
    {
        return $this->options["secure"];
    }

    /**
     * Set the secure flag
     *
     * @return self
     */
    public function setSecure($secure)
    {
        $this->options["secure"] = !!$secure;
        return $this;
    }

    /**
     * Get hosts where secure rule is relaxed
     *
     * @return string
     */
    public function getRelaxed()
    {
        return $this->options["relaxed"];
    }

    /**
     * Set hosts where secure rule is relaxed
     *
     * @return self
     */
    public function setRelaxed(array $relaxed)
    {
        $this->options["relaxed"] = $relaxed;
        return $this;
    }

    /**
     * Get the callback
     *
     * @return string
     */
    public function getCallback()
    {
        return $this->options["callback"];
    }

    /**
     * Set the callback
     *
     * @return self
     */
    public function setCallback($callback)
    {
        $this->options["callback"] = $callback;
        return $this;
    }

    /**
     * Get the error handler
     *
     * @return string
     */
    public function getError()
    {
        return $this->options["error"];
    }

    /**
     * Set the error handler
     *
     * @return self
     */
    public function setError($error)
    {
        $this->options["error"] = $error;
        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function setRules(array $rules)
    {
        /* Clear the stack */
        unset($this->rules);
        $this->rules = new \SplStack;

        /* Add the rules */
        foreach ($rules as $callable) {
            $this->addRule($callable);
        }
        return $this;
    }

    public function addRule($callable)
    {
        $this->rules->push($callable);
        return $this;
    }
}
