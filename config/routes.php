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

    // Auth Routes
    $app->get('/login', \App\Controllers\Pages\LoginPage::class . ':handle')->setName('login');
    $app->get('/register', \App\Controllers\Pages\RegisterPage::class . ':handle')->setName('register');
    
    $app->post('/login', \App\Controllers\Api\AuthController::class . ':login');
    $app->post('/register', \App\Controllers\Api\AuthController::class . ':register');
    $app->post('/logout', \App\Controllers\Api\AuthController::class . ':logout')->add(\App\Middleware\AuthMiddleware::class);

    // API Routes
    $app->group('/api/v1', function ($group) {
        $group->get('/test-registry', \App\Controllers\Api\TestRegistryController::class);

        // Security / IP Blocking
        $group->get('/security/ips', \App\Controllers\Api\SecurityController::class . ':listIps');
        $group->patch('/security/ips/{ip}', \App\Controllers\Api\SecurityController::class . ':setStatus');

        // Notebooks
        $group->get('/notebooks', \App\Controllers\Api\NotebookController::class . ':index');
        $group->post('/notebooks', \App\Controllers\Api\NotebookController::class . ':store');
        $group->delete('/notebooks/{id}', \App\Controllers\Api\NotebookController::class . ':delete');

        // Sections
        $group->get('/notebooks/{id}/tree', \App\Controllers\Api\SectionController::class . ':tree');
        $group->post('/sections', \App\Controllers\Api\SectionController::class . ':store');
        $group->patch('/sections/{id}/move', \App\Controllers\Api\SectionController::class . ':move');
        $group->delete('/sections/{id}', \App\Controllers\Api\SectionController::class . ':delete');

        // Notes
        $group->get('/notes/search-by-tags', \App\Controllers\Api\TagController::class . ':search');
        $group->patch('/notes/move', \App\Controllers\Api\NoteController::class . ':move');
        $group->post('/notes', \App\Controllers\Api\NoteController::class . ':store');
        $group->get('/notes/{id}', \App\Controllers\Api\NoteController::class . ':show');
        $group->put('/notes/{id}', \App\Controllers\Api\NoteController::class . ':update');
        $group->delete('/notes/{id}', \App\Controllers\Api\NoteController::class . ':delete');

        // Tags
        $group->get('/tags', \App\Controllers\Api\TagController::class . ':index');
        $group->post('/notes/{note_id}/tags', \App\Controllers\Api\TagController::class . ':sync');

    })->add(\App\Middleware\ApiContentTypeMiddleware::class)
      ->add(\App\Middleware\AuthMiddleware::class);
};
