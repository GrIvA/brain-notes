<?php

namespace App\Models;

use Medoo\Medoo;

class PageAliasModel
{
    private $db;
    public $dbError = null;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Пошук маршруту за аліасом та мовою.
     */
    public function findByAlias(string $alias, int $langId)
    {
        $result = $this->db->get(
            'page_aliases',
            ['[>]page' => ['page_id']],
            ['route', 'page_id', 'is_login'],
            [
                'AND' => [
                    'alias' => $alias,
                    'language_id' => $langId
                ]
            ]
        );
        $this->dbError = $this->db->error;
        return $result;
    }

    /**
     * Пошук маршруту за прямим шляхом.
     */
    public function findByRoute(string $route)
    {
        $result = $this->db->get(
            'page_aliases',
            ['[>]page' => ['page_id']],
            ['route', 'page_id', 'is_login'],
            [
                'route' => $route
            ]
        );
        $this->dbError = $this->db->error;
        return $result;
    }

    /**
     * Пошук інформації про аліас за ID сторінки та ID мови.
     */
    public function getAliasInfo(int $pageId, int $langId, string $langTable)
    {
        $result = $this->db->get(
            'page_aliases',
            ['[><]'.$langTable => ['language_id' => 'id']],
            [$langTable.'.abr', 'page_aliases.alias'],
            [
                'AND' => [
                    'page_aliases.language_id' => $langId,
                    'page_aliases.page_id' => $pageId
                ]
            ]
        );
        $this->dbError = $this->db->error;
        return $result;
    }

    /**
     * Пошук сторінки за назвою маршруту (routeName).
     */
    public function findByRouteName(string $routeName)
    {
        // Припускаємо, що у таблиці `page` є поле `route`, яке ми трактуємо як назву
        $result = $this->db->get(
            'page',
            ['page_id', 'route'],
            ['route' => $routeName]
        );
        $this->dbError = $this->db->error;
        return $result;
    }
}
