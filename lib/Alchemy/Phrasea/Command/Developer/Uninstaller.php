<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
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
            $root.'/tmp/configuration-compiled.php',
            $root.'/config/configuration.yml',
            $root.'/config/services.yml',
            $root.'/config/connexions.yml',
            $root.'/config/config.yml',
            $root.'/config/config.inc',
            $root.'/config/connexion.inc',
            $root.'/config/_GV.php',
            $root.'/config/_GV.php.old',
            $root.'/tmp/cache_registry.php',
            $root.'/tmp/cache_registry.yml',
            ] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach ([
            $root.'/tmp/serializer',
            $root.'/tmp/cache_twig',
            $root.'/tmp/translations',
            $root.'/tmp/cache_minify',
            $root.'/tmp/download',
            $root.'/tmp/locks',
            $root.'/tmp/cache',
            ] as $dir) {
            if (is_dir($dir)) {
                $finder = new Finder();
                foreach ($finder->files()->in($dir) as $file) {
                    unlink($file);
                }
            }
        }

        return 0;
    }
}
