<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Http\AbstractServerMode;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Guzzle\Http\Url;

class StaticMode
{
    protected $symlinker;

    public function __construct(SymLinker $symlinker)
    {
        $this->symlinker = $symlinker;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($pathFile)
    {
        $this->ensureSymlink($pathFile);

        return Url::factory(sprintf('/thumbnails/%s', $this->symlinker->getSymlinkBasePath($pathFile)));
    }

    /**
     * Creates a link if it does not exists
     *
     * @param $pathFile
     */
    private function ensureSymlink($pathFile)
    {
        if (false === $this->symlinker->hasSymlink($pathFile)) {
            $this->symlinker->symlink($pathFile);
        }
    }
}
