<?php


namespace App\Controllers;

use App\Abstract\AbstractController;
use App\Services\LanguageService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class SiteController extends AbstractController
{
    const PAGE_HOME = 1;
    const PAGE_ERROR = 2;
    const PAGE_SIGN_IN = 3;

    const HANDLING_STATUS_OK = 1;
    const HANDLING_STATUS_ERROR = 2;

    const TOKEN_LIFETIME = 60 * 60; // sec
    const SITE_ATTRIBUTE_LANGUAGE = 'current_language';

    /* @var $user User */
    protected $user = null;

    // PUBLIC

    public function collectHandleParams(ServerRequestInterface $req, array $args, bool $use_escape = true)
    {
        $this->user = $req->getAttribute('user');
        $this->parseArgs($req, $args, $use_escape);
        $this->getCommonParams();
    }

    // PRIVATE
    private function getLanguageSelectorInfo(): array
    {
        $lang = $this->container->get(LanguageService::class);       /* @var $lang LanguageService */
        $result = [];
        $pageParams = [];

        foreach ($lang->getActiveLanguagesInfo() as $langId => $info) {
            $result[$langId] = [
                'abr'   => $info['abr'],
                'title' => $info['locale'],
                'href'  => getAliasByPageID($this->getPageID(), $langId, $pageParams)
            ];
        }
        return $result;
    }

    // PROTECTED

    /*
     * @param $data array
     * @param $res
     * @return mixed
     */
    protected function resultHandling(array $data, ResponseInterface $res): ResponseInterface
    {
        /*
        if ($this->user instanceof User) {
            $token = $this->user->getToken();
            $res = $res->withHeader('usc', $token);
            setcookie('usc', $token, time() + 60*60, '/');
        }
         */
        if (isset($data['redirect_url'])) {
            // need redirect to other page
            $url = getAliasByPageID($data['redirect_url'])
                . (isset($data['redirect_postfix']) ? '/'.$data['redirect_postfix'] : '');
            $http_code = !empty($data['http_code']) && is_numeric($data['http_code'])
                ? (int)$data['http_code']
                : 307;
            
            return \App\Responder\RedirectHandler::redirectToUrl($res, $url, [], $http_code);
        }

        // Render view
        if (!empty($_COOKIE['dbg']) && $_COOKIE['dbg'] == 'wd308') {
            r($this->params);
        }

        $res->getBody()
            ->write(
                $this->container->get('tmpl')
                    ->fetch('pages/' . $this->getTemplateName().'.tpl', $this->params)
            );
        return $res;
    }

    protected function getCommonParams(): void
    {
        $lang = $this->container->get(LanguageService::class); /* @var $lang LanguageService */
        $tagModel = $this->container->get(\App\Models\TagModel::class);
        
        $this->params['common'] = [
            'languages' => $this->getLanguageSelectorInfo(),
            'language_id' => $lang->getCurrentLanguageID(),
            'page_id' => $this->getPageID(),
            'title_of_page' => '',
            'theme' => $_COOKIE['pico-theme'] ?? 'light',
            'active_notebook_id' => (int)($_COOKIE['active_notebook_id'] ?? 0),
            'all_tags' => $this->user 
                ? $tagModel->getCombinedTags((int)$this->user->getId()) 
                : $tagModel->getPublicTags(),
        ];

        $this->params['user'] = $this->user ? $this->user->toArray() : null;

        /*
        $tags = new Tags($this->container);
        $this->params['tags'] = $tags->getAllTags();

        $this->params['articles_info'] = $this->container['Article']->getArticlesForSideMenu();
         */
        $this->getAdditionalPageParams();
    }

    abstract protected function getPageID(): int;
    abstract protected function getTemplateName(): string;
    abstract protected function getAdditionalPageParams(): void;
}

