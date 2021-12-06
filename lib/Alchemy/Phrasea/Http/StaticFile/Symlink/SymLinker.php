<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile\Symlink;

use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;

// use Symfony\Component\Filesystem\Filesystem;

/**
 * Create & retrieve symlinks
 */
class SymLinker
{
    protected $encoder;
    protected $fs;
    protected $symlinkDir;

    public function __construct(SymLinkerEncoder $encoder, Filesystem $fs, $symlinkDir)
    {
        $this->encoder = $encoder;
        $this->fs = $fs;
        $this->symlinkDir = rtrim($symlinkDir, '/');
    }

    public function getSymlinkDir()
    {
        return $this->symlinkDir;
    }

    public function symlink($pathFile)
    {
        $this->fs->symlink($pathFile, $this->getSymlinkPath($pathFile)) ;
    }

    public function unlink($pathFile)
    {
        $this->fs->remove($this->getSymlinkPath($pathFile));
    }

    public function getSymlink($pathFile)
    {
        return $this->encoder->encode($pathFile);
    }

    public function getSymlinkBasePath($pathFile)
    {
        $symlinkName = $this->getSymlink($pathFile);

        return sprintf('%s/%s/%s.jpg',
            substr($symlinkName, 0, 2),
            substr($symlinkName, 2, 2),
            substr($symlinkName, 4)
        );
    }

    public function getSymlinkPath($pathFile)
    {
        return sprintf(
            '%s/%s',
            $this->symlinkDir,
            $this->getSymlinkBasePath($pathFile)
        );
    }

    public function hasSymlink($pathFile)
    {
        return file_exists($this->getSymlinkPath($pathFile));
    }
}
