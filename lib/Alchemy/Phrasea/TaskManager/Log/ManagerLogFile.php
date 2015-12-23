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

class ManagerLogFile extends AbstractLogFile implements LogFileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        return array('');
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($version)
    {
        return sprintf('%s/scheduler%s.log', $this->root, $version ? ('-'.$version) : '' );
    }
}
