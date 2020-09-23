<?php

/*

Copyright (c) 2013-2020 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

/**
 * @see       https://github.com/tuupola/slim-basic-auth
 * @license   https://www.opensource.org/licenses/mit-license.php
 */

namespace Tuupola\Middleware\HttpBasicAuthentication;

use PHPUnit\Framework\TestCase;

class PdoAuthenticatorTest extends TestCase
{

    public $pdo;

    public function setup(): void
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
