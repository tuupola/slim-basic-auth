<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Tuupola\Middleware\HttpBasicAuthentication;

class PdoAuthenticatorTest extends \PHPUnit_Framework_TestCase
{

    public $pdo;

    public function setup()
    {
        $this->pdo = new \PDO("sqlite::memory:");
        //$this->pdo = new \PDO("sqlite:/tmp/test.db");

        $this->pdo->exec(
            "CREATE TABLE users (
                user VARCHAR(32) NOT NULL,
                hash VARCHAR(255) NOT NULL
            )"
        );

        $user = "root";
        $hash = password_hash("t00r", PASSWORD_DEFAULT);

        $status = $this->pdo->exec(
            "INSERT INTO users (user, hash) VALUES ('{$user}', '{$hash}')"
        );
    }

    public function testShouldReturnTrue()
    {
        $authenticator = new PdoAuthenticator([
            "pdo" => $this->pdo
        ]);
        $this->assertTrue($authenticator(["user" => "root", "password" => "t00r"]));
    }

    public function testShouldReturnFalse()
    {
        $authenticator = new PdoAuthenticator([
            "pdo" => $this->pdo
        ]);
        $this->assertFalse($authenticator(["user" => "root", "password" => "nosuch"]));
        $this->assertFalse($authenticator(["user" => "nosuch", "password" => "nosuch"]));
    }

    public function testShouldUseLimit()
    {
        $authenticator = new PdoAuthenticator([
            "pdo" => $this->pdo
        ]);

        $this->assertEquals(
            "SELECT * FROM users WHERE user = ? LIMIT 1",
            $authenticator->sql("root", "nosuch")
        );
    }

    public function testShouldUseTop()
    {
        /* Workaround to test without sqlsrv with Travis */
        define("__PHPUNIT_ATTR_DRIVER_NAME__", "sqlsrv");

        $authenticator = new PdoAuthenticator([
            "pdo" => $this->pdo
        ]);
        $this->assertEquals(
            "SELECT TOP 1 * FROM users WHERE user = ?",
            $authenticator->sql("root", "nosuch")
        );
    }
}
