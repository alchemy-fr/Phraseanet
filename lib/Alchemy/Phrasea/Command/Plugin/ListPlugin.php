<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Plugin\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListPlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:list');

        $this
            ->setDescription('Lists installed plugins')
            ->addOption('json', 'j', InputOption::VALUE_NONE, 'Output result in JSON');
    }

    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {
        $plugins = array_map(function (Plugin $plugin) use ($input) {
            if ($plugin->isErroneous()) {
                return $this->formatErroneousPlugin($input, $plugin);
            }

            return $this->formatPlugin($input, $plugin);
        }, $this->container['plugins.manager']->listPlugins());

        if ($input->getOption('json')) {
            $output->writeln(json_encode(['plugins' => array_values($plugins)]));
        } else {
            $table = $this->getHelperSet()->get('table');
            $table
                ->setHeaders(['Name', 'Version', 'Description'])
                ->setRows($plugins)
            ;

            $table->render($output);
        }

        return 0;
    }

    private function formatPlugin(InputInterface $input, Plugin $plugin)
    {
        if ($input->getOption('json')) {
            return [
                'name'        => $plugin->getName(),
                'version'     => $plugin->getManifest()->getVersion(),
                'description' => $plugin->getManifest()->getDescription(),
                'error'       => false,
            ];
        }

        return [
            $plugin->getName(),
            $plugin->getManifest()->getVersion(),
            $plugin->getManifest()->getDescription(),
        ];
    }

    private function formatErroneousPlugin(InputInterface $input, Plugin $plugin)
    {
        if ($input->getOption('json')) {
            return [
                'name'        => $plugin->getName(),
                'error'       => true,
                'description' => 'Error : '.$plugin->getError()->getMessage(),
                'version'     => null,
            ];
        }

        return [
            '<error>' . $plugin->getName() . '</error>',
            '<error>Error : ' . $plugin->getError()->getMessage() . '</error>',
            '',
        ];
    }
}
