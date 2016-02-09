<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        $path = $this->container['cache.path'];

        $paths = [
            $root . '/config/configuration.yml',
            $root . '/config/services.yml',
            $root . '/config/connexions.yml',
            $root . '/config/config.yml',
            $root . '/config/config.inc',
            $root . '/config/connexion.inc',
            $root . '/config/_GV.php',
            $root . '/config/_GV.php.old',
            $root . '/config/configuration-compiled.php',
            $this->container['tmp.download.path'],
            $this->container['tmp.lazaret.path'],
            $this->container['tmp.caption.path'],
            $this->container['tmp.path'] . '/sessions',
            $this->container['tmp.path'] . '/locks',
            $path . '/cache_registry.php',
            $path . '/cache_registry.yml',
            $path . '/serializer',
            $path . '/doctrine',
            $path . '/twig',
            $path . '/translations',
            $path . '/minify',
            $path . '/profiler',
        ];

        $files = $directories = [];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $directories[] = $path;
            } elseif (is_file($path)) {
                $files[] = $path;
            }
        }

        $this->container['filesystem']->remove($files);
        $this->container['filesystem']->remove(Finder::create()->in($directories));

        return 0;
    }
}
