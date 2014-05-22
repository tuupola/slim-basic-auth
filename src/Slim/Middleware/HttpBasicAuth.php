<?php

namespace Slim\Middleware;

/**
 * HTTP Basic Authentication
 *
 * Provides HTTP Basic Authentication on given routes
 *
 * @package    Slim
 * @author     Mika Tuupola <tuupola@appelsiini.net>
 */
class HttpBasicAuth extends \Slim\Middleware {

    public $options;

    public function __construct($options = null) {

        /* Default options. */
        $this->options = array(
            "users" => array(),
            "path" => "/",
            "realm" => "Protected"
        );

        if ($options) {
            $this->options = array_merge($this->options, (array)$options);
        }
    }

    public function call() {
        $request = $this->app->request;

        /* If path matches what is given on initialization. */
        // TODO: @taelkim how can 'subdirectory' support  
        // ex) ( /admin/functionA , /admin/functionB, /admin/functionC ) as (/admin/)
        // excepts: /old/foo/bar/admin/freeFunctionA
        // maybe likes ... ^/admin/*
        if ($request->getPathInfo() == $this->options["path"]) {
            $user = $request->headers("PHP_AUTH_USER");
            $pass = $request->headers("PHP_AUTH_PW");

            /* Check if user and passwords matches. */
            if (isset($this->options["users"][$user]) && $this->options["users"][$user] === $pass) {
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
}
