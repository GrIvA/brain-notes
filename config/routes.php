<?php

use App\Controllers\SiteController;
use App\Responder\RedirectHandler;
use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Middleware\ApiContentTypeMiddleware;
use App\Middleware\AuthMiddleware;

//use App\Controllers\Api\TestRegistryController;
use App\Controllers\Api\SecurityController;
use App\Controllers\Api\NotebookController;
use App\Controllers\Pages\LoginPage;
use App\Controllers\Pages\RegisterPage;
use App\Controllers\Pages\NotePage;
use App\Controllers\Api\SectionController;
use App\Controllers\Api\TagController;
use App\Controllers\Api\NoteController;
use App\Controllers\Api\AuthController;

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
    $app->get('/login',    LoginPage::class . ':handle')->setName('login');
    $app->get('/register', RegisterPage::class . ':handle')->setName('register');

    $app->get('/note/{id}', NotePage::class . ':handle')->setName('note_view')->add(AuthMiddleware::class);

    $app->post('/login', AuthController::class . ':login');
    $app->post('/register', AuthController::class . ':register');
    $app->post('/logout', AuthController::class . ':logout')->add(AuthMiddleware::class);

    // API Routes
    $app->group('/api/v1', function ($group) {
        //$group->get('/test-registry', TestRegistryController::class);

        // Security / IP Blocking
        $group->get('/security/ips',        SecurityController::class . ':listIps');
        $group->patch('/security/ips/{ip}', SecurityController::class . ':setStatus');

        // Notebooks
        $group->get('/notebooks',         NotebookController::class . ':index');
        $group->post('/notebooks',        NotebookController::class . ':store');
        $group->patch('/notebooks/{id}',  NotebookController::class . ':update');
        $group->delete('/notebooks/{id}', NotebookController::class . ':delete');

        // Sections
        $group->get('/notebooks/{id}/tree',  SectionController::class . ':tree');
        $group->post('/sections',            SectionController::class . ':store');
        $group->patch('/sections/{id}/move', SectionController::class . ':move');
        $group->delete('/sections/{id}',     SectionController::class . ':delete');

        // Notes
        $group->get('/notes/search-by-tags', TagController::class . ':search');
        $group->get('/notes/list',               NoteController::class . ':listFiltered');
        $group->patch('/notes/move',             NoteController::class . ':move');
        $group->post('/notes',                   NoteController::class . ':store');
        $group->get('/notes/{id}',               NoteController::class . ':show');
        $group->put('/notes/{id}',               NoteController::class . ':update');
        $group->delete('/notes/{id}',            NoteController::class . ':delete');
        $group->delete('/notes/{note_id}/tags/{tag_id}', TagController::class . ':remove');

        // Tags
        $group->get('/tags',                      TagController::class . ':index');
        $group->post('/notes/{note_id}/tags',     TagController::class . ':sync');
        $group->post('/notes/{note_id}/tags/add', TagController::class . ':add');

    })->add(ApiContentTypeMiddleware::class)
      ->add(AuthMiddleware::class);
};
