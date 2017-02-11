<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Tuupola\Middleware;

use Tuupola\Middleware\HttpBasicAuthentication\AuthenticatorInterface;
use Tuupola\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
use Tuupola\Middleware\HttpBasicAuthentication\RequestMethodRule;
use Tuupola\Middleware\HttpBasicAuthentication\RequestPathRule;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpBasicAuthentication
{
    private $rules;
    private $options = [
        "secure" => true,
        "relaxed" => ["localhost", "127.0.0.1"],
        "users" => null,
        "path" => null,
        "passthrough" => null,
        "realm" => "Protected",
        "authenticator" => null,
        "before" => null,
        "after" => null,
        "error" => null
    ];

    public function __construct($options = [])
    {
        /* Setup stack for rules */
        $this->rules = new \SplStack;

        /* Store passed in options overwriting any defaults */
        $this->hydrate($options);

        /* If array of users was passed in options create an authenticator */
        if (is_array($this->options["users"])) {
            $this->options["authenticator"] = new ArrayAuthenticator([
                "users" => $this->options["users"]
            ]);
        }

        /* If nothing was passed in options add default rules. */
        if (!isset($options["rules"])) {
            $this->addRule(new RequestMethodRule([
                "passthrough" => ["OPTIONS"]
            ]));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== $this->options["path"]) {
            $this->addRule(new RequestPathRule([
                "path" => $this->options["path"],
                "passthrough" => $this->options["passthrough"]
            ]));
        }

        /* There must be an authenticator either passed via options */
        /* or added because $this->options["users"] was an array. */
        if (null === $this->options["authenticator"]) {
            throw new \RuntimeException("Authenticator or users array must be given");
        }
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $host = $request->getUri()->getHost();
        $scheme = $request->getUri()->getScheme();
        $server_params = $request->getServerParams();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $next($request, $response);
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($host, $this->options["relaxed"])) {
                $message = sprintf(
                    "Insecure use of middleware over %s denied by configuration.",
                    strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        /* Just in case. */
        $params = ["user" => null, "password" => null];

        if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine("Authorization"), $matches)) {
            list($params["user"], $params["password"]) = explode(":", base64_decode($matches[1]), 2);
        }

        /* Check if user authenticates. */
        if (false === $this->options["authenticator"]($params)) {
            /* Set response headers before giving it to error callback */
            $response = $response
                ->withStatus(401)
                ->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"', $this->options["realm"]));

            return $this->error($request, $response, [
                "message" => "Authentication failed"
            ]);
        }

        /* Modify $request before calling next middleware. */
        if (is_callable($this->options["before"])) {
            $before_request = $this->options["before"]($request, $response, $params);
            if ($before_request instanceof RequestInterface) {
                $request = $before_request;
            }
        }

        /* Everything ok, call next middleware. */
        $response = $next($request, $response);

        /* Modify $response before returning. */
        if (is_callable($this->options["after"])) {
            $after_response = $this->options["after"]($request, $response, $params);
            if ($after_response instanceof ResponseInterface) {
                return $after_response;
            }
        }

        return $response;
    }

    public function hydrate($data = [])
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = "set" . ucwords($key);
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                /* Try to use setter */
                call_user_func([$this, $method], $value);
            } else {
                /* Or fallback to setting property directly */
                $this->$key = $value;
            }
        }
    }

    private function shouldAuthenticate(RequestInterface $request)
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($request)) {
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
    public function error(RequestInterface $request, ResponseInterface $response, $arguments)
    {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($request, $response, $arguments);
            if ($handler_response instanceof ResponseInterface) {
                return $handler_response;
            }
        }
        return $response;
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

    public function getPassthrough()
    {
        return $this->options["passthrough"];
    }

    private function setPassthrough($passthrough)
    {
        $this->options["passthrough"] = $passthrough;
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
     * Get the before handler
     *
     * @return string
     */
    public function getBefore()
    {
        return $this->options["before"];
    }

    /**
     * Set the before handler
     *
     * @return self
     */
    public function setBefore($before)
    {
        $this->options["before"] = $before->bindTo($this);
        return $this;
    }

    /**
     * Get the after handler
     *
     * @return string
     */
    public function getAfter()
    {
        return $this->options["after"];
    }

    /**
     * Set the after handler
     *
     * @return self
     */
    public function setAfter($after)
    {
        $this->options["after"] = $after->bindTo($this);
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
