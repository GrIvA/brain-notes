<?php

use Slim\App;
use Slim\Factory\AppFactory;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use SlimErrorRenderer\Middleware\ExceptionHandlingMiddleware;
use SlimErrorRenderer\Middleware\NonFatalErrorHandlingMiddleware;

use Medoo\Medoo;
use Analog\Logger;
use App\Controllers\Pages\Homepage;
use App\Middleware\LanguageMiddleware;
use App\Middleware\PageAliasMiddleware;
use App\Models\LanguageModel;
use App\Models\PageAliasModel;
use App\Models\RegistryModel;
use App\Models\UserModel;
use App\Models\NotebookModel;
use App\Models\SectionModel;
use App\Models\NoteModel;
use App\Models\TagModel;
use App\Models\IpModel;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\LanguageService;
use App\Helpers\SQLiteLogger;

return [
    'settings' => function () {
        return require SETDIR.'settings.php';
    },

    // Create app instance
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);
        // Register routes
        (require SETDIR . 'routes.php')($app);

        // Register middlewares
        (require SETDIR . 'middleware.php')($app);

        return $app;
    },

    'dbase' => function (ContainerInterface $container) {
        $dbSettings = $container->get('settings')['db_connect'];
        return new Medoo($dbSettings);
    },

    LanguageModel::class => function (ContainerInterface $container) {
        return new LanguageModel(
            $container->get('dbase'),
            $container->get('settings')['languages']['table']
        );
    },

    PageAliasModel::class => function (ContainerInterface $container) {
        return new PageAliasModel($container->get('dbase'));
    },

    RegistryModel::class => function (ContainerInterface $container) {
        return new RegistryModel($container->get('dbase'));
    },

    UserModel::class => function (ContainerInterface $container) {
        return new UserModel($container->get('dbase'));
    },

    NotebookModel::class => function (ContainerInterface $container) {
        return new NotebookModel($container->get('dbase'));
    },

    SectionModel::class => function (ContainerInterface $container) {
        return new SectionModel($container->get('dbase'));
    },

    NoteModel::class => function (ContainerInterface $container) {
        return new NoteModel($container->get('dbase'));
    },

    TagModel::class => function (ContainerInterface $container) {
        return new TagModel($container->get('dbase'));
    },

    IpModel::class => function (ContainerInterface $container) {
        return new IpModel(
            $container->get('dbase'),
            $container->get(LoggerInterface::class)
        );
    },

    AuthMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['jwt'];
        return new AuthMiddleware(
            $container->get(UserModel::class),
            $container->get(RegistryModel::class),
            $settings['secret'],
            [],
            $container->get(AuthService::class)
        );
    },

    AuthService::class => function (ContainerInterface $container) {
        return new AuthService(
            $container->get('settings')['jwt'],
            $container->get(RegistryModel::class),
            $container->get(LoggerInterface::class)
        );
    },

    LanguageService::class => function (ContainerInterface $container) {
        return new LanguageService($container);
    },

    LanguageMiddleware::class => function (ContainerInterface $container) {
        return new LanguageMiddleware(
            $container->get(LanguageService::class),
            $container->get('settings')['languages']
        );
    },

    PageAliasMiddleware::class => function (ContainerInterface $container) {
        return new PageAliasMiddleware($container);
    },

    IpSecurityMiddleware::class => function (ContainerInterface $container) {
        return new IpSecurityMiddleware($container);
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    // Logger
    LoggerInterface::class => function (ContainerInterface $container) {
        $loggerSettings = $container->get('settings')['logger'];

        $logger = new Logger('app');

        $logger->handler(SQLiteLogger::init(
            $container->get('dbase'),
            $loggerSettings['table']
        ));
        //$l->handler(Analog\Handler\PDO::init($c['dbase']->pdo, $c['settings']['log_table_name']));

        // a liitle trick to save to the log remote addr
        //$_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'];

        return $logger;
    },

    // Error handling: https://samuel-gfeller.ch/docs/Error-Handling
    ExceptionHandlingMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');

        return new ExceptionHandlingMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $settings['error']['log_errors'] ? $container->get(LoggerInterface::class) : null,
            $settings['error']['display_error_details'],
            $settings['public']['main_contact_email'] ?? null
        );
    },

    // Add error middleware for notices and warnings
    NonFatalErrorHandlingMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['error'];

        return new NonFatalErrorHandlingMiddleware(
            $settings['display_error_details'],
            $settings['log_errors'] ? $container->get(LoggerInterface::class) : null,
        );
    },

    'Homepage' => function($c) { return new Homepage($c); },

    'tmpl' => function ( ContainerInterface $container ) {
        $fenomSettings = $container->get('settings')['fenom'];

        $fenom = Fenom::factory(
            ROOTDIR . $fenomSettings['template_dir'],
            ROOTDIR . $fenomSettings['cache_dir'],
            $fenomSettings['options']
        );

        // add some custom modificators for better usage
        /*
         * translate - use {'VNZ000021'|translate} - translate variable
         * getPageURL - use {11|getPageURL} - generate URL for page with id = 11
         * link - use {'bla-bla <a>hello</a> bla-bla'|link:href:title:blank_flag} - generate link inside language variable
         * money_format - use {$var|money_format:is_float:result_separator} - generate correct money format view
         * phone - use {$var|mobile} generate human phone view
         * giftcard - use {$var|giftcard} generate human gift card vie
         *
         * doNotShow - {doNotShow} bla-bla {/doNotShow} - Just ignore all what putting inside block
         */
        $fenom->addModifier('giftcard', function ($num) {
            $groups = sscanf($num, '%2s%2s%2s%2s%2s%2s%2s%2s%2s%2s');
            $groups = array_filter($groups, function ($it) {
                return !is_null($it);
            });
            return implode('-', $groups);
        });
        $fenom->addModifier('phone', function ($phone) {
            global $app;
            $country_code = str_replace('+', '', $app->getContainer()['settings']['additional_params']['country_code']);

            $phone = substr($phone, strlen($country_code));
            $groups = sscanf($phone, '%3s%2s%2s%2s%2s%2s%2s%2s%2s%2s%2s');
            $groups = array_filter($groups, function ($it) {
                return !is_null($it);
            });
            return sprintf('<span class="phone_format">+%s %s</span>', $country_code, implode('-', $groups));
        });
        $fenom->addModifier('money_format', function ($value, $is_float = true, $result_separator = "'") {
            return number_format($value, (($is_float) ? 2 : 0), '.', $result_separator);
        });
        $fenom->addModifier('link', function ($str, $href, $title = '', $blank = true) {
            $pos = strpos($str, '<a>');
            if ($pos === false) {
                return $str;
            }
            return substr_replace(
                $str,
                sprintf('<a title="%s" href="%s" %s>', $title, $href, ($blank ? 'target="_blank"' : '')),
                $pos,
                3
            );
        });
        $fenom->addModifier('translate', function ($var) use ($container) {
            $lang = $container->get(LanguageService::class);
            return $lang->translate($var);
        });
        $fenom->addModifier('getPageURL', function ($route) use ($container) {
            $ls = $container->get(LanguageService::class);
            $lang_current_id = $ls->getCurrentLanguageID();
            $alias = $container->get(PageAliasModel::class)->findByRouteName($route, $lang_current_id);
            if (is_null($alias)) {
                //$log = $c['log'];
                $container->get(LoggerInterface::class)->warning('No page alias for route: '.$route);
            }

            return $ls->getAbrByID($lang_current_id) . '/' . $alias['alias'];
        });
        /*
        $fenom->addModifier('logo', function ($url) use ($c) {
            // {$common.additional_params.image_server}
            $result = file_exists(WEBDIR . 'images/' . $url)
                ? '/images/' . $url
                : $c['settings']['additional_params']['image_server'] . $url;
            return $result;
        });
        $fenom->addModifier('markdown', function ($text) use ($c) {
            if (empty($text)) {
                return '';
            }
            $parser = $c['Parsedown'];

            return $parser->setMarkupEscaped(false)->text($text);
        });
         */

        $fenom->addBlockFunction('doNotShow', function (array $params, $content) {
            return '';
        });

        return $fenom;
    },
];
