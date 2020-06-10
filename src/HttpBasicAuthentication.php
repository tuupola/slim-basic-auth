<?php
declare(strict_types=1);

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2018 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Tuupola\Middleware;

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Middleware\DoublePassTrait;
use Tuupola\Middleware\HttpBasicAuthentication\AuthenticatorInterface;
use Tuupola\Middleware\HttpBasicAuthentication\ArrayAuthenticator;
use Tuupola\Middleware\HttpBasicAuthentication\RequestMethodRule;
use Tuupola\Middleware\HttpBasicAuthentication\RequestPathRule;

final class HttpBasicAuthentication implements MiddlewareInterface
{
    use DoublePassTrait;

    private $rules;
    private $options = [
        "secure" => true,
        "relaxed" => ["localhost", "127.0.0.1"],
        "users" => null,
        "path" => null,
        "ignore" => null,
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
            $this->rules->push(new RequestMethodRule([
                "ignore" => ["OPTIONS"]
            ]));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== $this->options["path"]) {
            $this->rules->push(new RequestPathRule([
                "path" => $this->options["path"],
                "ignore" => $this->options["ignore"]
            ]));
        }

        /* There must be an authenticator either passed via options */
        /* or added because $this->options["users"] was an array. */
        if (null === $this->options["authenticator"]) {
            throw new \RuntimeException("Authenticator or users array must be given");
        }
    }

    /**
     * Process a request in PSR-15 style and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();
        $scheme = $request->getUri()->getScheme();
        $server_params = $request->getServerParams();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $handler->handle($request);
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
            $explodedCredential = explode(":", base64_decode($matches[1]), 2);
            if (count($explodedCredential) == 2) {
                list($params["user"], $params["password"]) = $explodedCredential;
            }
        }

        /* Check if user authenticates. */
        if (false === $this->options["authenticator"]($params)) {
            /* Set response headers before giving it to error callback */
            $response = (new ResponseFactory)
                ->createResponse(401)
                ->withHeader(
                    "WWW-Authenticate",
                    sprintf('Basic realm="%s"', $this->options["realm"])
                );

            return $this->processError($response, [
                "message" => "Authentication failed"
            ]);
        }

        /* Modify $request before calling next middleware. */
        if (is_callable($this->options["before"])) {
            $response = (new ResponseFactory)->createResponse(200);
            $before_request = $this->options["before"]($request, $params);
            if ($before_request instanceof ServerRequestInterface) {
                $request = $before_request;
            }
        }

        /* Everything ok, call next middleware. */
        $response = $handler->handle($request);

        /* Modify $response before returning. */
        if (is_callable($this->options["after"])) {
            $after_response = $this->options["after"]($response, $params);
            if ($after_response instanceof ResponseInterface) {
                return $after_response;
            }
        }

        return $response;
    }

    /**
     * Hydrate all options from given array.
     */
    private function hydrate(array $data = []): void
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = lcfirst(ucwords($key));
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                /* Try to use setter */
                call_user_func([$this, $method], $value);
            } else {
                /* Or fallback to setting option directly */
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Test if current request should be authenticated.
     */
    private function shouldAuthenticate(ServerRequestInterface $request): bool
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
     * Execute the error handler.
     */
    private function processError(ResponseInterface $response, array $arguments): ResponseInterface
    {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($response, $arguments);
            if ($handler_response instanceof ResponseInterface) {
                return $handler_response;
            }
        }
        return $response;
    }

    /**
     * Set path where middleware should bind to.
     */
    private function path($path): void
    {
        $this->options["path"] = (array) $path;
    }
    /**
     * Set path which middleware ignores.
     */
    private function ignore($ignore): void
    {
        $this->options["ignore"] = (array) $ignore;
    }

    /**
     * Set the authenticator.
     */
    private function authenticator(callable $authenticator): void
    {
        $this->options["authenticator"] = $authenticator;
    }

    /**
     * Set the users array.
     */
    private function users(array $users): void
    {
        $this->options["users"] = $users;
    }

    /**
     * Set the secure flag.
     */
    private function secure(bool $secure): void
    {
        $this->options["secure"] = $secure;
    }

    /**
     * Set hosts where secure rule is relaxed.
     */
    private function relaxed(array $relaxed): void
    {
        $this->options["relaxed"] = $relaxed;
    }

    /**
     * Set the handler which is called before other middlewares.
     */
    private function before(Closure $before): void
    {
        $this->options["before"] = $before->bindTo($this);
    }

    /**
     * Set the handler which is called after other middlewares.
     */
    private function after(Closure $after): void
    {
        $this->options["after"] = $after->bindTo($this);
    }

    /**
     * Set the handler which is if authentication fails.
     */
    private function error(callable $error): void
    {
        $this->options["error"] = $error;
    }

    /**
     * Set the rules
     */
    private function rules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Set the rules which determine if current request should be authenticated.
     *
     * Rules must be callables which return a boolean. If any of the rules return
     * boolean false current request will not be authenticated.
     *
     * @param array $rules
     */
    public function withRules(array $rules): self
    {
        $new = clone $this;
        /* Clear the stack */
        unset($new->rules);
        $new->rules = new \SplStack;

        /* Add the rules */
        foreach ($rules as $callable) {
            $new = $new->addRule($callable);
        }
        return $new;
    }

    /**
     * Add a rule to the rules stack.
     *
     * Rules must be callables which return a boolean. If any of the rules return
     * boolean false current request will not be authenticated.
     */
    public function addRule(callable $callable): self
    {
        $new = clone $this;
        $new->rules = clone $this->rules;
        $new->rules->push($callable);
        return $new;
    }
}
