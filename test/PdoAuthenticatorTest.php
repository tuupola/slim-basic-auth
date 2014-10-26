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

namespace Test;

use \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator;

class PdoAuthenticatorTest extends \PHPUnit_Framework_TestCase
{

    public $pdo;

    public function setup()
    {
        $this->pdo = new \PDO("sqlite::memory:");
        //$this->pdo = new \PDO("sqlite:/tmp/test.db");

        $this->pdo->exec(
            "CREATE TABLE users (
                username VARCHAR(32) NOT NULL,
                hash VARCHAR(255) NOT NULL
            )"
        );

        $username = "root";
        $hash = password_hash("t00r", PASSWORD_DEFAULT);

        $status = $this->pdo->exec(
            "INSERT INTO users (username, hash) VALUES ('{$username}', '{$hash}')"
        );
    }

    public function testShouldReturnTrue()
    {
        $authenticator = new PdoAuthenticator(array(
            "pdo" => $this->pdo
        ));
        $this->assertTrue($authenticator->authenticate("root", "t00r"));
    }

    public function testShouldReturnFalse()
    {
        $authenticator = new PdoAuthenticator(array(
            "pdo" => $this->pdo
        ));
        $this->assertFalse($authenticator->authenticate("root", "nosuch"));
        $this->assertFalse($authenticator->authenticate("nosuch", "nosuch"));
    }
}
