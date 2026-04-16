<?php

namespace App\Middleware;

use App\Services\LanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LanguageMiddleware implements MiddlewareInterface
{
    private $languageService;
    private $defaultLangId;
    private $langTable;

    public function __construct(LanguageService $languageService, array $settings)
    {
        $this->languageService = $languageService;
        $this->defaultLangId = $settings['default_id'];
        $this->langTable = $settings['table'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $pathString = $uri->getPath();
        $path = array_values(array_filter(explode('/', $pathString)));

        if (!empty($path) && is_array($path) && strlen($path[0]) == 2) {
            $languages = $this->languageService->getActiveLanguagesInfo();
            
            $language_id = $this->defaultLangId;
            foreach ($languages as $id => $info) {
                if ($info['abr'] == $path[0]) {
                    $language_id = $id;
                    break;
                }
            }
            
            if ($language_id !== $this->defaultLangId || (isset($languages[$language_id]) && $languages[$language_id]['abr'] == $path[0])) {
                unset($path[0]);
            }
        } else {
            $language_id = $this->defaultLangId;
        }

        $this->languageService->setCurrentLanguage($language_id);

        // set new path & attribute with lang_id to request object
        $newPath = '/' . implode('/', $path);
        $req = $request->withUri($uri->withPath($newPath))->withAttribute('lang_id', $language_id);

        return $handler->handle($req);
    }
}
