<?php

use App\Controllers\SiteController;
use App\Responder\RedirectHandler;
use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {

    $app->get('/', function (ServerRequestInterface $req, ResponseInterface $res): ResponseInterface {
        return RedirectHandler::redirectToUrl( $res, SiteController::PAGE_HOME );
    });

    $app->get('/hello', function (ServerRequestInterface $req, ResponseInterface $res) use ($app) {
        $fenom = $app->getContainer()->get('tmpl');

        $res->getBody()
            ->write($fenom->fetch('pages/hello.tpl', ['ip' => $_SERVER['REMOTE_ADDR']]));

        return $res;
    })->setName('hello');

    $app->get('/home', 'Homepage:handle')->setName('home');
};
