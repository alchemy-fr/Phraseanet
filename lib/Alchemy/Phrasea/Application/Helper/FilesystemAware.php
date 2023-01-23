<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Neutron\TemporaryFilesystem\Manager;

// use Symfony\Component\Filesystem\Filesystem;

trait FilesystemAware
{
    private $filesystem;
    private $temporaryFilesystem;

    /**
     * Set Locator to use to locate FileSystem
     *
     * @param callable $locator
     * @return $this
     */
    public function setFileSystemLocator(callable $locator)
    {
        $this->filesystem = $locator;

        return $this;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }

        if (null === $this->filesystem) {
            throw new \LogicException('Filesystem locator was not set');
        }

        $instance = call_user_func($this->filesystem);
        if (!$instance instanceof Filesystem) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                Filesystem::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->filesystem = $instance;

        return $this->filesystem;
    }

    /**
     * Set Locator to use to locate Temporary FileSystem
     *
     * @param callable $locator
     * @return $this
     */
    public function setTemporaryFileSystemLocator(callable $locator)
    {
        $this->temporaryFilesystem = $locator;

        return $this;
    }

    /**
     * @return Manager
     */
    public function getTemporaryFilesystem()
    {
        if ($this->temporaryFilesystem instanceof Manager) {
            return $this->temporaryFilesystem;
        }

        if (null === $this->temporaryFilesystem) {
            throw new \LogicException('Filesystem locator was not set');
        }

        $instance = call_user_func($this->temporaryFilesystem);
        if (!$instance instanceof Manager) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                Manager::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->temporaryFilesystem = $instance;

        return $this->temporaryFilesystem;
    }
}
