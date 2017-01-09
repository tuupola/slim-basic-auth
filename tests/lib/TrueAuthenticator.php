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

namespace Test;

use Tuupola\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

class TrueAuthenticator implements AuthenticatorInterface
{
    public function __invoke(array $arguments)
    {
        return true;
    }
}
