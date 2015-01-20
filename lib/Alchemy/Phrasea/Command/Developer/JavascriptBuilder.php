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
use Symfony\Component\Process\ProcessBuilder;
use Alchemy\Phrasea\Exception\RuntimeException;

/**
 * This command builds javascript files
 */
class JavascriptBuilder extends Command
{
    public function __construct()
    {
        parent::__construct('assets:build-javascript');

        $this->setDescription('Builds Phraseanet JavaScript files');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $files = array(
            $this->container['root.path'] . '/www/skins/build/bootstrap/js/bootstrap.js' => array(
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-transition.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-alert.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-button.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-carousel.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-collapse.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-dropdown.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-modal.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-tooltip.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-popover.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-scrollspy.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-tab.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-typeahead.js',
                $this->container['root.path'] . '/www/assets/bootstrap/js/bootstrap-affix.js',
            )
        );

        $output->writeln('Building JavaScript assets');
        foreach ($files as $target => $sources) {
            $this->buildJavascript($input, $output, $target, $sources);

            $minifiedTarget = substr($target, 0, -3) . '.min.js';
            $this->buildMinifiedJavascript($input, $output, $minifiedTarget, $target);
        }
    }

    private function buildJavascript(InputInterface $input, OutputInterface $output, $target, $sources)
    {
        $output->writeln("\t".basename($target));
        $this->container['filesystem']->remove($target);

        $process = ProcessBuilder::create(array_merge(array('cat'), $sources))->getProcess();
        if ($input->getOption('verbose')) {
            $output->writeln("Executing ".$process->getCommandLine()."\n");
        }
        $process->run();

        if (!$process->isSuccessFul()) {
            throw new RuntimeException(sprintf('Failed to generate %s', $target));
        }

        $this->container['filesystem']->mkdir(dirname($target));
        file_put_contents($target, $process->getOutput());
    }

    private function buildMinifiedJavascript(InputInterface $input, OutputInterface $output, $target, $source)
    {
        $output->writeln("\t".basename($target));
        $this->container['filesystem']->remove($target);

        $output = $this->container['driver.uglifyjs']->command(array($source, '-nc'));

        file_put_contents($target, $output);
    }
}
