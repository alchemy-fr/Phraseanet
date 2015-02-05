<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Uninstaller extends Command
{
    public function __construct()
    {
        parent::__construct('system:uninstall');

        $this->setDescription('Uninstall Phraseanet');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->container['root.path'];

        foreach ([
            $root.'/config/configuration.yml',
            $root.'/config/services.yml',
            $root.'/config/connexions.yml',
            $root.'/config/config.yml',
            $root.'/config/config.inc',
            $root.'/config/connexion.inc',
            $root.'/config/_GV.php',
            $root.'/config/_GV.php.old',
            $root.'/config/configuration-compiled.php',
            ] as $file) {
            if ($this->container['filesystem']->exists($file)) {
                unlink($file);
            }
        }

        foreach ([
            $this->container['tmp.download.path'],
            $this->container['tmp.lazaret.path'],
            $this->container['tmp.caption.path'],
            $this->container['tmp.path'].'/sessions',
            $this->container['tmp.path'].'/locks',
        ] as $resource) {
            if (is_dir($resource)) {
                $finder = new Finder();
                foreach ($finder->files()->in($resource) as $file) {
                    $this->container['filesystem']->remove($file);
                }
            } elseif (is_file($resource)) {
                $this->container['filesystem']->remove($resource);
            }
        }

        foreach ($this->container['cache.paths'] as $path) {
            foreach ([
                 $path.'/cache_registry.php',
                 $path.'/cache_registry.yml',
                 $path.'/serializer',
                 $path.'/doctrine',
                 $path.'/twig',
                 $path.'/translations',
                 $path.'/minify',
                 $path.'/profiler',
            ] as $resource) {
                if (is_dir($resource)) {
                    $finder = new Finder();
                    foreach ($finder->files()->in($resource) as $file) {
                        $this->container['filesystem']->remove($file);
                    }
                } elseif (is_file($resource)) {
                    $this->container['filesystem']->remove($resource);
                }
            }
        }

        return 0;
    }
}
