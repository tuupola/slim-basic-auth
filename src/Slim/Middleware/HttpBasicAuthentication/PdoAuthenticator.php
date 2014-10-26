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
    use \Witchcraft\MagicMethods;
    use \Witchcraft\MagicProperties;

    private $options;

    public function __construct(array $options = array())
    {

        /* Default options. */
        $this->options = array(
            "table" => "users",
            "username" => "username",
            "hash" => "hash"
        );

        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function authenticate($username, $pass)
    {
        $statement = $this->pdo->prepare(
            "SELECT *
             FROM {$this->table}
             WHERE {$this->username} = ?
             LIMIT 1"
        );

        $statement->execute(array($username));

        if ($user = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return password_verify($pass, $user[$this->hash]);
        }

        return false;
    }

    /* For magic properties */
    public function setTable($table)
    {
        $this->options["table"] = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->options["table"];
    }

    public function setUsername($username)
    {
        $this->options["username"] = $username;
        return $this;
    }

    public function getUsername()
    {
        return $this->options["username"];
    }

    public function setHash($hash)
    {
        $this->options["hash"] = $hash;
        return $this;
    }

    public function getHash()
    {
        return $this->options["hash"];
    }

    public function setPdo(\PDO $pdo)
    {
        $this->options["pdo"] = $pdo;
        return $this;
    }

    public function getPdo()
    {
        return $this->options["pdo"];
    }
}
