<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Management;

use Symfony\Component\Finder\Finder;

class PluginsExplorer implements \IteratorAggregate, \Countable
{
    private $pluginsDirectory;

    public function __construct($pluginsDirectory)
    {
        $this->pluginsDirectory = $pluginsDirectory;
    }

    public function getIterator()
    {
        return $this->getFinder()->getIterator();
    }

    public function count()
    {
        return $this->getFinder()->count();
    }

    private function getFinder()
    {
        $finder = Finder::create();

        return $finder
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->useBestAdapter()
            ->directories()
            ->in($this->pluginsDirectory)
            ->depth(0);
    }
}
