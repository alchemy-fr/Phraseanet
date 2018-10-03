<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Schema;

class Manifest
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function getKeywords()
    {
        return $this->get('keywords');
    }

    public function getAuthors()
    {
        return $this->get('authors');
    }

    public function getHomepage()
    {
        return $this->get('homepage');
    }

    public function getLicense()
    {
        return $this->get('license');
    }

    public function getVersion()
    {
        return $this->get('version');
    }

    public function getMinimumPhraseanetVersion()
    {
        return $this->get('minimum-phraseanet-version');
    }

    public function getMaximumPhraseanetVersion()
    {
        return $this->get('maximum-phraseanet-version');
    }

    public function getServices()
    {
        return $this->get('services') ? : [];
    }

    public function getCommands()
    {
        return $this->get('commands') ? : [];
    }

    public function getTwigPaths()
    {
        return $this->get('twig-paths') ? : [];
    }

    public function getExtra()
    {
        return $this->get('extra');
    }

    private function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
