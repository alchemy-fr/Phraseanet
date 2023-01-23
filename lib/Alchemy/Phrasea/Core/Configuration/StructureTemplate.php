<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

/**
 * Class StructureTemplate
 * @package Alchemy\Phrasea\Core\Configuration
 */
class StructureTemplate
{
    const TEMPLATE_EXTENSION = 'xml';
    const DEFAULT_TEMPLATE = 'en-simple';

    /** @var  string */
    private $rootPath;

    /** @var  \SplFileInfo[] */
    private $templates;
    /** @var  string[] */
    private $names;

    /**
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
        $this->names = $this->templates = null;    // lazy loaded, not yet set
    }

    private function load()
    {
        if(!is_null($this->templates)) {
            return;     // already loaded
        }

        $templateList = new \DirectoryIterator($this->rootPath . '/lib/conf.d/data_templates');

        $this->templates = [];
        $this->names     = [];

        foreach ($templateList as $template) {
            if ($template->isDot()
                || !$template->isFile()
                || $template->getExtension() !== self::TEMPLATE_EXTENSION
            ) {
                continue;
            }

            $name = $template->getBasename('.' . self::TEMPLATE_EXTENSION);
            // beware that the directoryiterator returns a reference on a static, so clone()
            $this->templates[$name] = clone($template);
            $this->names[]          = $name;
        }
    }

    /**
     * @param $templateName
     * @return null|\SplFileInfo
     */
    public function getByName($templateName)
    {
        $this->load();

        if (!array_key_exists($templateName, $this->templates)) {
            return null;
        }

        return $this->templates[$templateName];
    }

    /**
     * @param $index
     * @return null|\SplFileInfo
     */
    public function getNameByIndex($index)
    {
        $this->load();

        return $this->names[$index];
    }

    /**
     * @return \string[]
     */
    public function getNames()
    {
        $this->load();

        return $this->names;
    }

    public function toString()
    {
        $this->load();

        return implode(', ', $this->names);
    }

    /**
     * @return string
     */
    public function getDefault()
    {
        $this->load();

        return $this->getByName(self::DEFAULT_TEMPLATE) ? self::DEFAULT_TEMPLATE : $this->getNameByIndex(0);
    }
}