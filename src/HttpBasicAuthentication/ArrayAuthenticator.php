<?php
declare(strict_types=1);

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

final class ArrayAuthenticator implements AuthenticatorInterface
{

    private $options;

    public function __construct($options = null)
    {

        /* Default options. */
        $this->options = [
            "users" => []
        ];

        if ($options) {
            $this->options = array_merge($this->options, (array)$options);
        }
    }

    public function __invoke(array $arguments): bool
    {
        $user = $arguments["user"];
        $password = $arguments["password"];

        /* Unknown user. */
        if (!isset($this->options["users"][$user])) {
            return false;
        }

        if (self::isHash($this->options["users"][$user])) {
            /* Hashed password. */
            return password_verify($password, $this->options["users"][$user]);
        } else {
            /* Cleartext password. */
            return $this->options["users"][$user] === $password;
        }
    }

    private static function isHash($password): bool
    {
        return preg_match('/^\$(2|2a|2y)\$\d{2}\$.*/', $password) && (strlen($password) >= 60);
    }
}
