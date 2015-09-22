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

namespace Test;

use \Slim\Middleware\HttpBasicAuthentication\RuleInterface;
use Psr\Http\Message\ServerRequestInterface;

class TrueRule implements RuleInterface
{
    public function __invoke(ServerRequestInterface $app)
    {
        return true;
    }
}
