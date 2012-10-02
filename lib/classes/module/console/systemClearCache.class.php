<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemClearCache extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Empty cache directories, clear Memcached, Redis if avalaible');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();

        $finder
            ->exclude('.git')
            ->exclude('.svn')
            ->in(array(
                __DIR__ . '/../../../../tmp/cache_minify/',
                __DIR__ . '/../../../../tmp/cache_twig/'
            ));

        $filesystem = new Filesystem();

        $filesystem->remove($finder);

        if (setup::is_installed()) {
            $this->getService('phraseanet.cache-service')->flushAll();
        }

        $output->write('Finished !', true);

        return 0;
    }
}
