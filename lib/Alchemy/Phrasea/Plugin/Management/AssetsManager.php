<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Plugin\Schema\Manifest;
use Symfony\Component\Filesystem\Exception\IOException;

// use Symfony\Component\Filesystem\Filesystem;

/**
 * Manages plugins assets
 */
class AssetsManager
{
    private $fs;
    private $pluginsDirectory;
    private $rootPath;

    public function __construct(Filesystem $fs, $pluginsDirectory, $rootPath)
    {
        $this->fs = $fs;
        $this->pluginsDirectory = $pluginsDirectory;
        $this->rootPath = $rootPath;
    }

    /**
     * Updates plugins assets so that they are available online.
     *
     * @param Manifest $manifest
     *
     * @throws RuntimeException
     */
    public function update(Manifest $manifest)
    {
        try {
            $this->fs->mirror(
                $this->pluginsDirectory . DIRECTORY_SEPARATOR . $manifest->getName() . DIRECTORY_SEPARATOR . 'public',
                $this->rootPath . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $manifest->getName()
            );
        } catch (IOException $e) {
            throw new RuntimeException(
                sprintf('Unable to copy assets for plugin %s', $manifest->getName()), $e->getCode(), $e
            );
        }
    }

    /**
     * Removes assets for the plugin named with the given name
     *
     * @param string $name
     *
     * @throws RuntimeException
     */
    public function remove($name)
    {
        try {
            $this->fs->remove($this->rootPath . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $name);
        } catch (IOException $e) {
            throw new RuntimeException(
                sprintf('Unable to remove assets for plugin %s', $name), $e->getCode(), $e
            );
        }
    }

    /**
     * Twig function to generate asset URL.
     *
     * @param string $name
     * @param string $asset
     *
     * @return string
     */
    public static function twigPluginAsset($name, $asset)
    {
        return sprintf('/plugins/%s/%s', $name, ltrim($asset, '/'));
    }
}
