<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Thesaurus;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindConceptsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('thesaurus:find:concepts')
            ->setDescription('Infer concepts using thesaurus')
            ->addArgument(
                'term',
                InputArgument::REQUIRED,
                'Reverse search a term to infer concepts'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify input locale'
            )
            ->addOption(
                'raw',
                null,
                InputOption::VALUE_NONE,
                'Only output raw concepts'
            )
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $term = $input->getArgument('term');
        $raw = $input->getOption('raw');

        if (!$raw) {
            $output->writeln(sprintf('Finding linked concepts: <comment>%s</comment>', $term));
            $output->writeln(str_repeat('-', 20));
        }

        $thesaurus = $this->container['thesaurus'];
        $locale = $input->getOption('locale');
        $concepts = $thesaurus->findConcepts($term, null, $locale);

        if (count($concepts)) {
            $output->writeln($concepts);
        } elseif (!$raw) {
            $output->writeln('No concept found');
        }
    }
}
