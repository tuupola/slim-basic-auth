<?php

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

namespace Tuupola\Middleware\HttpBasicAuthentication;

use PHPUnit\Framework\TestCase;

class ArrayAuthenticatorTest extends TestCase
{
    public function testShouldReturnTrue()
    {
        $authenticator = new ArrayAuthenticator([
            "users" => [
                "root" => "t00r",
                "somebody" => "passw0rd",
                "wheel" => '$2y$10$Tm03qGT4FLqobzbZcfLDcOVIwZEpg20QZYffleeA2jfcClLpufYpy',
                "dovecot" => '$2y$12$BlC21Ah2CuO7xlplqyysEejr1rwnj.uh2IEW9TX0JPgTnLNJk6XOC',
            ]
        ]);
        $this->assertTrue($authenticator(["user" => "root", "password" => "t00r"]));
        $this->assertTrue($authenticator(["user" => "somebody", "password" => "passw0rd"]));
        $this->assertTrue($authenticator(["user" => "wheel", "password" => "gashhash"]));
        $this->assertTrue($authenticator(["user" => "dovecot", "password" => "prettyfly"]));
    }

    public function testShouldReturnFalse()
    {
        $authenticator = new ArrayAuthenticator([
            "users" => [
                "root" => "t00r",
                "somebody" => "passw0rd",
                "luser" => '$2y$10$Tm03qGT4FLqobzbZcfLDcOVIwZEpg20QZYffleeA2jfcClLpufYpy',
            ]
        ]);
        $this->assertFalse($authenticator(["user" => "root", "password" => "nosuch"]));
        $this->assertFalse($authenticator(["user" => "nosuch", "password" => "nosuch"]));

        /* Should handle as hash and not cleartext */
        $this->assertFalse($authenticator([
            "user" => "luser",
            "password" => '$2y$10$Tm03qGT4FLqobzbZcfLDcOVIwZEpg20QZYffleeA2jfcClLpufYpy'
        ]));
    }
}
