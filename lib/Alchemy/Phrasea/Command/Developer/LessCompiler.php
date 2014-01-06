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

/**
 * This command builds less file
 */
class LessCompiler extends Command
{
    public function __construct()
    {
        parent::__construct('assets:compile-less');

        $this->setDescription('Compiles Phraseanet LESS files');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->container['phraseanet.less-assets'] as $source => $target) {
            $this->container['filesystem']->mkdir(dirname($target));
            $this->container['filesystem']->copy($source, $target);
        }

        $output->writeln('Building LESS assets');
        $this->container['phraseanet.less-builder']->build($this->container['phraseanet.less-mapping'], $output);

        return 0;
    }
}
