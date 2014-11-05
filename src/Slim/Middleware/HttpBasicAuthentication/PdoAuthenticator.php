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

    public function authenticate($user, $pass)
    {
        $statement = $this->options["pdo"]->prepare(
            "SELECT *
             FROM {$this->options['table']}
             WHERE {$this->options['user']} = ?
             LIMIT 1"
        );

        $statement->execute(array($user));

        if ($user = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return password_verify($pass, $user[$this->options["hash"]]);
        }

        return false;
    }
}
