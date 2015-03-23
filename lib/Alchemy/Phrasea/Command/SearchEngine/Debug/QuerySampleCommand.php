<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\SearchEngine\Debug;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QuerySampleCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:query:sample')
            ->setDescription('Generate sample queries from grammar')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $grammarPath = $this->container['query_parser.grammar_path'];
        $output->writeln(sprintf('Generating sample queries from <comment>%s</comment>', $grammarPath));
        $output->writeln(str_repeat('-', 20));

        $parser = $this->container['query_parser'];

        // UNIFORM

        // $sampler = new \Hoa\Compiler\Llk\Sampler\Uniform(
        //     $parser,
        //     new \Hoa\Regex\Visitor\Isotropic(new \Hoa\Math\Sampler\Random()),
        //     7
        // );

        // for($i = 0; $i < 10; ++$i) {
        //     $output->writeln(sprintf('%d => %s', $i, $sampler->uniform()));
        // }

        // BOUNDED EXAUSTIVE

        $sampler = new \Hoa\Compiler\Llk\Sampler\BoundedExhaustive(
            $parser,
            new \Hoa\Regex\Visitor\Isotropic(new \Hoa\Math\Sampler\Random()),
            6
        );

        // COVERAGE

        // $sampler = new \Hoa\Compiler\Llk\Sampler\Coverage(
        //     $parser,
        //     new \Hoa\Regex\Visitor\Isotropic(new \Hoa\Math\Sampler\Random())
        // );

        foreach($sampler as $i => $data) {
            $output->writeln(sprintf('%d => %s', $i, $data));
        }

        $output->writeln(str_repeat('-', 20));
    }
}
