<?php

/*
 * This file is part of Slim HTTP Basic Authentication middleware
 *
 * Copyright (c) 2013-2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Test;

use Slim\Middleware\HttpBasicAuthentication\RuleInterface;
use Psr\Http\Message\RequestInterface;

class TrueRule implements RuleInterface
{
    public function __invoke(RequestInterface $request)
    {
        return true;
    }
}
