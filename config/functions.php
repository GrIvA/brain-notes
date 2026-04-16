<?php
/**
 * Autoload functions available everywhere across the application.
 */

use App\Models\PageAliasModel;
use App\Services\LanguageService;
use Psr\Log\LoggerInterface;

/**
 * Convert all applicable characters to HTML entities.
 *
 * @param string|null $text The string
 *
 * @return string The html encoded string
 */
function html(?string $text = null): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getAliasByPageID(int $pageId, int $langId = null, array $params = [])
{
    global $app;

    if (empty($pageId)) {
        return '';
    }

    $c = $app->getContainer();
    $s = $c->get('settings')['languages'];
    /** @var PageAliasModel $model */
    $model = $c->get(PageAliasModel::class);
    $current_lang_id = $c->get(LanguageService::class)->getCurrentLanguageID();

    if (!empty($langId)) {
        $current_lang_id = $langId;
    }

    $result = $model->getAliasInfo($pageId, $current_lang_id, $s['table']);

    if (empty($result)) {
        $log = $c->get(LoggerInterface::class); /* @var $log \Analog\Logger */
        if ($model->dbError !== null) {
            $log->critical(
                'DB error: '. print_r($model->dbError, true). ' information: '
                . print_r(['page_id' => $pageId, 'language_id' => $langId], true)
            );
        }
        $log->warning('No page alias for language: '.$langId.' (page id: '.$pageId.')');
        $result = [];
    }

    return '/'.implode('/', $result). (count($params) ? '/' . implode('/', $params) : '');
}
