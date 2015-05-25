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

class PdoAuthenticator implements AuthenticatorInterface
{
    private $options;

    public function __construct(array $options = array())
    {

        /* Default options. */
        $this->options = array(
            "table" => "users",
            "user" => "user",
            "hash" => "hash"
        );

        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function __invoke(array $arguments)
    {
        $user = $arguments["user"];
        $password = $arguments["password"];

        $driver = $this->options["pdo"]->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = $this->sql();

        $statement = $this->options["pdo"]->prepare($sql);
        $statement->execute(array($user));

        if ($user = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return password_verify($password, $user[$this->options["hash"]]);
        }

        return false;
    }

    public function sql()
    {
        $driver = $this->options["pdo"]->getAttribute(\PDO::ATTR_DRIVER_NAME);

        /* Workaround to test without sqlsrv with Travis */
        if (defined("__PHPUNIT_ATTR_DRIVER_NAME__")) {
            $driver = __PHPUNIT_ATTR_DRIVER_NAME__;
        }

        if ("sqlsrv" === $driver) {
            $sql =
                "SELECT TOP 1 *
                 FROM {$this->options['table']}
                 WHERE {$this->options['user']} = ?";
        } else {
            $sql =
                "SELECT *
                 FROM {$this->options['table']}
                 WHERE {$this->options['user']} = ?
                 LIMIT 1";
        }

        return preg_replace("!\s+!", " ", $sql);
    }
}
