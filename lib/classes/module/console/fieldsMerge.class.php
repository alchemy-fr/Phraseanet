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
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_fieldsMerge extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Merge databox structure fields');

        $this->addOption(
            'source'
            , 'f'
            , InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
            , 'Metadata structure ids for source'
            , array()
        );

        $this->addOption(
            'destination'
            , 'd'
            , InputOption::VALUE_REQUIRED
            , 'Metadata structure id destination'
        );

        $this->addOption(
            'sbas_id'
            , 's'
            , InputOption::VALUE_REQUIRED
            , 'Databox sbas_id'
        );

        $this->addOption(
            'separator'
            , ''
            , InputOption::VALUE_OPTIONAL
            , 'Separator for concatenation (if destination is monovalued)'
            , ';'
        );

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("");

        if ( ! $input->getOption('sbas_id'))
            throw new \Exception('Missing argument sbas_id');

        try {
            $databox = \databox::get_instance((int) $input->getOption('sbas_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid databox id </error>");

            return 1;
        }

        $sources = array();

        foreach ($input->getOption('source') as $source_id) {
            $sources[] = $databox->get_meta_structure()->get_element($source_id);
        }

        if (count($sources) === 0)
            throw new \Exception('No sources to proceed');

        if ( ! $input->getOption('destination'))
            throw new \Exception('Missing argument destination');

        $separator = ' ' . $input->getOption('separator') . ' ';

        $destination = $databox->get_meta_structure()->get_element($input->getOption('destination'));

        $types = $multis = array();

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

        $field_names = array();
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

        do {
            $sql = 'SELECT record_id FROM record
                  ORDER BY record_id LIMIT ' . $start . ', ' . $quantity;
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $row) {
                $record = $databox->get_record($row['record_id']);

                $datas = array();

                foreach ($sources as $source) {
                    try {
                        $value = $record->get_caption()->get_field($source->get_name())->get_value();
                    } catch (\Exception $e) {
                        $value = array();
                    }
                    if ( ! is_array($value)) {
                        $value = array($value);
                    }

                    $datas = array_merge($datas, $value);
                }

                $datas = array_unique($datas);

                if ( ! $destination->is_multi()) {
                    $datas = array(implode($separator, $datas));
                }

                try {
                    $record->get_caption()->get_field($destination->get_name())->set_value($datas);
                } catch (\Exception $e) {
                    $record->set_metadatas(
                        array(
                        array(
                            'meta_struct_id' => $destination->get_id()
                            , 'meta_id'        => null
                            , 'value'          => $datas
                        )
                        )
                        , true
                    );
                }
                unset($record);
            }

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
