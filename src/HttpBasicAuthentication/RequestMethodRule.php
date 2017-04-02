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

use Psr\Http\Message\ServerRequestInterface;

final class RequestMethodRule implements RuleInterface
{
    protected $options = [
        "ignore" => ["OPTIONS"]
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(ServerRequestInterface $request)
    {
        return !in_array($request->getMethod(), $this->options["ignore"]);
    }
}
