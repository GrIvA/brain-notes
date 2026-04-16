<?php

namespace App\Services;

use App\Models\LanguageModel;
use Exception;
use XMLReader;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LanguageService
{
    private $container = null;
    private $settings = null;
    private $model = null;

    private $activeLanguages = [];
    private $currentLanguage = 0;

    private $files = [];

    private $translations = [];

    /**
     * LanguageService constructor.
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->settings = $this->container->get('settings')['languages'];

        $this->model = $container->get(LanguageModel::class);
        $this->activeLanguages = $this->getActiveLanguages();
        $this->files = $this->getTranslationFileNames();
    }

    /**
     * get translation of variable
     * @param string $name
     * @return string
     */
    public function translate($name)
    {
        return isset($this->translations[$name]) ? $this->translations[$name] : $name;
    }

    public function getAbrByID($langId)
    {
        return array_key_exists($langId, $this->activeLanguages) ? $this->activeLanguages[$langId]['abr'] : null;
    }

    /***********
     * SETTERS *
     ***********/
    /**
     * @param int $langId
     * @throws Exception
     */
    public function setCurrentLanguage($langId)
    {
        if ($this->currentLanguage != $langId) {
            $this->currentLanguage = $langId;

            //try to load file with translations
            $full_path = $this->settings['file_path']
                . $this->files[$this->getCurrentLanguageID()];
            if (!file_exists($full_path)) {
                $log = $this->container->get(LoggerInterface::class); /* @var $log LoggerInterface */
                $log->error('File with translation information not found: ' . $full_path);
                return;
            }

            $xml = new XMLReader();
            $xml->open($full_path);
            while ($xml->read()) {
                if ($xml->name == 'resources') {
                    while ($xml->read()) {
                        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'string') {
                            $this->translations[$xml->getAttribute('name')] = str_replace(
                                ['\\"', '\\\''],
                                ['"', '\''],
                                $xml->readInnerXML()
                            );
                        }
                    }
                }
            }
            $xml->close();
        }
    }

    /***********
     * GETTERS *
     ***********/
    public function getCurrentLanguageID()
    {
        return $this->currentLanguage;
    }

    public function getActiveLanguagesInfo()
    {
        return $this->activeLanguages;
    }

    public function getTranslateVariables()
    {
        return $this->translations;
    }

    public function getCurrentFile()
    {
        //try to load file with translations
        $full_path = $this->settings['file_path']
            . $this->files[$this->getCurrentLanguageID()];
        if (file_exists($full_path)) {
            return file_get_contents($full_path);
        } else {
            return '';
        }
    }

    /***********
     * PRIVATE *
     ***********/
    private function getTranslationFileNames()
    {
        $result = [];
        foreach ($this->activeLanguages as $key => $info) {
            $result[$key] = str_replace(
                '###',
                $info['abr'],
                $this->settings['file_mask']
            );
        }
        return $result;
    }

    private function getActiveLanguages()
    {
        $langs = $this->model->getActiveLanguages();
        $result = [];
        foreach ($langs as $lang) {
            $result[$lang['id']] = ['abr' => $lang['abr'], 'locale' => $lang['locale']];
        }
        return $result;
    }
}
