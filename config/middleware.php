<?php

use App\Middleware\LanguageMiddleware;
use App\Middleware\PageAliasMiddleware;
use SlimErrorRenderer\Middleware\ExceptionHandlingMiddleware;
use SlimErrorRenderer\Middleware\NonFatalErrorHandlingMiddleware;

// Slim middlewares are LIFO (last in, first out) so when responding, the order is backwards
return function (Slim\App $app) {

    // Global Auth Handling
    $app->add(\App\Middleware\AuthMiddleware::class);

    // Page alias to route Middleware
    $app->add(PageAliasMiddleware::class);

    // Language handling
    $app->add(LanguageMiddleware::class);

    // Handle and log notices and warnings (throws ErrorException if displayErrorDetails is true)
    $app->add(NonFatalErrorHandlingMiddleware::class);

    // Handle exceptions and display error page
    $app->add(ExceptionHandlingMiddleware::class);
};
