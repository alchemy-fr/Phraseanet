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
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;
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
            ->addArgument(
                'context',
                InputArgument::OPTIONAL,
                'Restrict search to a specific term context'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify input locale'
            )
            ->addOption(
                'strict',
                null,
                InputOption::VALUE_NONE,
                'Strict mode (enforce term context matching)'
            )
            ->addOption(
                'broad',
                null,
                InputOption::VALUE_NONE,
                'Keep broad concepts (discards narrower concepts)'
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
        $context = $input->getArgument('context');
        $raw = $input->getOption('raw');

        if (!$raw) {
            $message = sprintf('Finding linked concepts: <comment>%s</comment>', $term);
            if ($context) {
                $message .= sprintf(' (with context <comment>%s</comment>)', $context);
            }
            $output->writeln($message);
            $output->writeln(str_repeat('-', 20));
        }

        $thesaurus = $this->container['thesaurus'];
        $term = new Term($term, $context);
        $locale = $input->getOption('locale');
        $strict = $input->getOption('strict');
        $concepts = $thesaurus->findConcepts($term, $locale, null, $strict);

        if ($input->getOption('broad')) {
            $concepts = Concept::pruneNarrowConcepts($concepts);
        }

        if (count($concepts)) {
            $output->writeln($concepts);
        } elseif (!$raw) {
            $output->writeln('No concept found');
        }
    }
}
