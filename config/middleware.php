<?php

use App\Middleware\LanguageMiddleware;
use App\Middleware\PageAliasMiddleware;

// Slim middlewares are LIFO (last in, first out) so when responding, the order is backwards
return function (Slim\App $app) {

    $app->addBodyParsingMiddleware();

    // Global Auth Handling
    $app->add(\App\Middleware\AuthMiddleware::class);

    // Page alias to route Middleware
    $app->add(PageAliasMiddleware::class);

    // Language handling
    $app->add(LanguageMiddleware::class);

    // Handle exceptions and display error page
    $app->add(\App\Middleware\ExceptionHandlingMiddleware::class);

    // IP Security Blocking (Must be outermost to block requests early)
    $app->add(\App\Middleware\IpSecurityMiddleware::class);
};
