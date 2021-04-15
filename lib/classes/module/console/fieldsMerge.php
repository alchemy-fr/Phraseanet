<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;

class module_console_fieldsMerge extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Merges databox documentation fields');

        $this->addArgument('sbas_id', InputArgument::REQUIRED, 'Databox sbas_id');
        $this->addArgument('destination', InputArgument::REQUIRED, 'Metadata structure id destination');
        $this->addArgument('source', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Metadata structure ids for source');

        $this->addOption(
            'separator'
            , ''
            , InputOption::VALUE_OPTIONAL
            , 'Separator for concatenation (if destination is monovalued)'
            , ';'
        );

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("");

        try {
            /** @var databox $databox */
            $databox = $this->getService('phraseanet.appbox')->get_databox((int) $input->getArgument('sbas_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid databox id </error>");

            return 1;
        }

        $sources = [];

        foreach ($input->getArgument('source') as $source_id) {
            $sources[] = $databox->get_meta_structure()->get_element($source_id);
        }

        if (count($sources) === 0) {
            throw new \Exception('No sources to proceed');
        }

        $separator = ' ' . $input->getOption('separator') . ' ';

        $destination = $databox->get_meta_structure()->get_element($input->getArgument('destination'));

        $types = $multis = [];

        foreach ($sources as $source) {
            array_push($types, $source->get_type());
            array_push($multis, $source->is_multi());
        }

        $types = array_unique($types);
        $multis = array_unique($multis);

        if (count(array_unique($types)) > 1) {
            $output->writeln(
                sprintf("Warning, trying to merge inconsistent types : <comment>%s</comment>\n"
                    , implode(', ', $types)
                )
            );
        }

        if (count(array_unique($multis)) > 1) {
            $output->writeln(
                sprintf(
                    "Warning, trying to merge <comment>mono and multi</comment> values fields\n"
                )
            );
        }

        $field_names = [];

        foreach ($sources as $source) {
            $field_names[] = $source->get_name();
        }

        if (count($multis) == 1) {
            if ($multis[0] === false && ! $destination->is_multi()) {
                $output->writeln(
                    sprintf(
                        "You are going to merge <info>mono valued fields</info> in a "
                        . "<info>monovalued field</info>, fields will be "
                        . "<comment>concatenated</comment> in the following order : %s"
                        , implode($separator, $field_names)
                    )
                );
                $this->displayHelpConcatenation($output);
            } elseif ($multis[0] === true && ! $destination->is_multi()) {
                $output->writeln(
                    sprintf(
                        "You are going to merge <info>multi valued</info> fields in a "
                        . "<info>monovalued field</info>, fields will be "
                        . "<comment>concatenated</comment> in the following order : %s"
                        , implode(' ', $field_names)
                    )
                );
                $this->displayHelpConcatenation($output);
            } elseif ($multis[0] === false && $destination->is_multi()) {
                $output->writeln(
                    sprintf(
                        "You are going to merge <info>mono valued fields</info> in a "
                        . "<info>multivalued field</info>"
                    )
                );
            } elseif ($multis[0] === true && $destination->is_multi()) {
                $output->writeln(
                    sprintf(
                        "You are going to merge <info>multi valued fields</info> in a "
                        . "<info>multivalued field</info>"
                    )
                );
            }
        } elseif ($destination->is_multi()) {
            $output->writeln(
                sprintf(
                    "You are going to merge <info>mixed valued</info> fields in a "
                    . "<info>multivalued</info> field"
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    "You are going to merge <info>mixed valued</info> fields in a "
                    . "<info>monovalued field</info>, fields will be "
                    . "<comment>concatenated</comment> in the following order : %s"
                    , implode($separator, $field_names)
                )
            );
            $this->displayHelpConcatenation($output);
        }

        $start = 0;
        $quantity = 100;

        $builder = $databox->get_connection()->createQueryBuilder();
        $builder
            ->select('r.record_id')
            ->from('record', 'r')
            ->orderBy('r.record_id', 'ASC')
            ->setFirstResult($start)
            ->setMaxResults($quantity)
        ;
        do {
            $stmt = $builder->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $row) {
                $record = $databox->get_record($row['record_id']);

                $datas = [];

                foreach ($sources as $source) {
                    try {

                        $values = $record->get_caption()->get_field($source->get_name())->get_values();

                        foreach ($values as $captionValue) {
                            $datas[] = $captionValue->getValue();
                            $captionValue->delete();
                        }
                    } catch (\Exception $e) {

                    }
                }

                $datas = array_unique($datas);

                if ( ! $destination->is_multi()) {
                    $datas = implode($separator, $datas);
                }

                foreach ((array) $datas as $data) {
                    $record->set_metadatas([[
                            'meta_struct_id' => $destination->get_id(),
                            'meta_id'        => null,
                            'value'          => $data,
                        ]], true);
                }

                unset($record);
            }

            // order to write metas for those records
            $this->container['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent(array_column($results, 'record_id'), $input->getArgument('sbas_id'))
            );

            $start += $quantity;
        } while (count($results) > 0);

        return 0;
    }

    protected function displayHelpConcatenation(OutputInterface $output)
    {

        $output->writeln("\nYou can choose the concatenation order in the "
            . "commandline (first option is first value) and set a separator "
            . "with the --separator option)");

        return $this;
    }
}
