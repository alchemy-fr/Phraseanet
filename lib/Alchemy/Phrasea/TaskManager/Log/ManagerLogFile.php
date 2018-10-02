<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Log;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ManagerLogFile extends AbstractLogFile implements LogFileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        $x = '/^scheduler(|(-.*))\.log$/';
        $f = new Finder();
        $versions = [];
        /** @var \SplFileInfo $file */
        foreach($f->files()->in($this->root) as $file) {
            $matches = [];
            if(preg_match($x, $file->getBasename(), $matches)) {
                $versions[] = $matches[1];
            }
        }
        return $versions;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($version = '')
    {
        return sprintf('%s/scheduler%s.log', $this->root, $version);
    }
}
