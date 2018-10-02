<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemTemplateGenerator extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Generates Twig templates files');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $tplDirs = [
            realpath(__DIR__ . '/../../../../templates/web/'),
            realpath(__DIR__ . '/../../../../templates/mobile/')
        ];

        $n_ok = $n_error = 0;

        // Twig fails if there's no request
        $this->container['request'] = new Request();
        // Twig must be initialized in order to access loader
        $this->container['twig'];

        foreach ($tplDirs as $tplDir) {
            $this->container['twig.loader.filesystem']->setPaths([$tplDir]);
            $finder = new Finder();
            foreach ($finder->files()->in([$tplDir]) as $file) {
                try {
                    $this->container['twig']->loadTemplate(str_replace($tplDir, '', $file->getPathname()));
                    $output->writeln('' . $file . '');
                    $n_ok ++;
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    $n_error ++;
                }
            }
        }

        $output->writeln("");
        $output->write(sprintf('%d templates generated. ', $n_ok));

        if ($n_error > 0) {
            $output->write(sprintf('<error>%d templates failed.</error>', $n_error));
        }

        $output->writeln("");

        return $n_error;
    }
}
