<?php

/* Maintain BC for a while. TODO: Remove when going 1.0 */
class_alias("Slim\Middleware\HttpBasicAuthentication", "Slim\Middleware\HttpBasicAuth");
