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

use Alchemy\Phrasea\Application;

/**
 * Class StructureTemplate
 * @package Alchemy\Phrasea\Core\Configuration
 */
class StructureTemplate
{

    const TEMPLATE_EXTENSION = 'xml';

    private $templates;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function getAvailable()
    {

        $templateList = new \DirectoryIterator($this->app['root.path'] . '/lib/conf.d/data_templates');

        if (empty($templateList)) {
            throw new \Exception('No available structure template');
        }

        $templates = [];
        $abbreviationLength = 2;
        foreach ($templateList as $template) {
            if ($template->isDot()
                || !$template->isFile()
                || $template->getExtension() !== self::TEMPLATE_EXTENSION
            ) {
                continue;
            }

            $name = $template->getFilename();
            $abbreviation = strtolower(substr($name, 0, $abbreviationLength));

            if (array_key_exists($abbreviation, $templates)) {
                $abbreviation = strtolower(substr($name, 0, ++$abbreviationLength));
            }

            $templates[$abbreviation] = $template->getBasename('.' . self::TEMPLATE_EXTENSION);
        }

        $this->templates = $templates;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->templates) {
            return '';
        }

        $templateToString = '';
        $cpt = 1;
        $templateLength = count($this->templates);
        foreach ($this->templates as $key => $value) {

            if (($templateLength - 1) == $cpt) {
                $separator = ' and ';
            }
            elseif (end($this->templates) == $value) {
                $separator = '';
            }
            else {
                $separator = ', ';
            }

            $templateToString .= $key . ' (' . $value . ')' . $separator;
            $cpt++;
        }

        return $templateToString;
    }

    /**
     * @param $template
     * @return mixed
     * @throws \Exception
     */
    public function getTemplateName($template = 'en')
    {

        if (!array_key_exists($template, $this->templates)) {
            throw new \Exception('Not found template : ' . $template);
        }

        return $this->templates[$template];
    }

    /**
     * @return mixed
     */
    public function getTemplates()
    {
        return $this->templates;
    }

}