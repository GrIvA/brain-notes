<?php

namespace App\Models;

use Medoo\Medoo;

class LanguageModel
{
    private $db;
    private $table;

    public function __construct(Medoo $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Отримання списку всіх активних мов.
     */
    public function getActiveLanguages()
    {
        return $this->db->select(
            $this->table, 
            ['id', 'abr', 'locale'], 
            ['available' => 1]
        );
    }
}
