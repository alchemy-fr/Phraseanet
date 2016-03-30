<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Databox\SubdefGroup;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Media\SubdefGenerator;
use databox_subdef;
use databox_subdefsStructure;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use media_subdef;

class BuildSubdefs extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Build subviews for given subview names and record types');
        $this->addArgument('databox', InputArgument::REQUIRED, 'The databox id');
        $this->addArgument('type', InputArgument::REQUIRED, 'Type(s) of document(s) to rebuild ex. "image,video", or "ALL"');
        $this->addArgument('subdefs', InputArgument::REQUIRED, 'Name(s) of sub-definition(s) to re-build, ex. "thumbnail,preview", or "ALL"');
        $this->addOption('min_record', 'min', InputOption::VALUE_OPTIONAL, 'Min record id');
        $this->addOption('max_record', 'max', InputOption::VALUE_OPTIONAL, 'Max record id');
        $this->addOption('with-substitution', 'wsubstit', InputOption::VALUE_NONE, 'Regenerate subdefs for substituted records as well');
        $this->addOption('substitution-only', 'substito', InputOption::VALUE_NONE, 'Regenerate subdefs for substituted records only');
        $this->addOption('dry', '', InputOption::VALUE_NONE, 'dry run, list but do nothing');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $availableTypes = array('document', 'audio', 'video', 'image', 'flash', 'map');

        $min = $input->getOption('min_record');
        $max = $input->getOption('max_record');
        $substitutionOnly = $input->getOption('substitution-only') ? true : false;
        $withSubstitution = $input->getOption('with-substitution') ? true : false;
        $dry = $input->getOption('dry') ? true : false;

        if ($withSubstitution && $substitutionOnly) {
            throw new InvalidArgumentException('--substitution-only and --with-substitution are mutually exclusive');
        }

        $databox = $this->container->findDataboxById($input->getArgument('databox'));
        $connection = $databox->get_connection();

        $subdefsNameByType = [];

        $typesOption =  $input->getArgument('type');
        $typesArray = explode(',', $typesOption);

        $subdefsOption = $input->getArgument('subdefs');
        $subdefsArray = explode(',', $subdefsOption);

        /** @var SubdefGroup $sg */
        foreach($databox->get_subdef_structure() as $sg)
        {
            if($typesOption == "ALL" || in_array($sg->getName(), $typesArray)) {
                $subdefsNameByType[$sg->getName()] = [];
                /** @var databox_subdef $sd */
                foreach ($sg as $sd) {
                    if($subdefsOption == "ALL" || in_array($sd->get_name(), $subdefsArray)) {
                        $subdefsNameByType[$sg->getName()][] = $sd->get_name();
                    }
                }
            }
        }

        $recordsType = array_keys($subdefsNameByType);

        list($sql, $params, $types) = $this->generateSQL($connection, $recordsType, $min, $max);

        $sqlCount = sprintf('SELECT COUNT(*) FROM (%s) AS c', $sql);
        $output->writeln($sqlCount);

        $totalRecords = (int)$connection->executeQuery($sqlCount, $params, $types)->fetchColumn();

        if ($totalRecords === 0) {
            return;
        }

        $progress = null;
        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $progress = new ProgressBar($output, $totalRecords);
            $progress->start();
            $progress->display();
        }

        $rows = $connection->executeQuery($sql, $params, $types)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $type = $row['type'];
            $output->write(sprintf(' [#%s] (%s)', $row['record_id'], $type));

            try {
                $record = $databox->get_record($row['record_id']);

                $subdefNamesToDo = array_flip($subdefsNameByType[$type]);    // do all subdefs ?

                /** @var media_subdef $subdef */
                foreach ($record->get_subdefs() as $subdef) {
                    if(!in_array($subdef->get_name(), $subdefsNameByType[$type])) {
                        continue;
                    }
                    if($subdef->is_substituted()) {
                        if(!$withSubstitution && !$substitutionOnly) {
                            unset($subdefNamesToDo[$subdef->get_name()]);
                            continue;
                        }
                    }
                    else {
                        if($substitutionOnly) {
                            unset($subdefNamesToDo[$subdef->get_name()]);
                            continue;
                        }
                    }
                    // here an existing subdef must be re-done
                    if(!$dry) {
                        $subdef->remove_file();
                        $subdef->set_substituted(false);
                    }
                }

                $subdefNamesToDo = array_keys($subdefNamesToDo);
                if(!empty($subdefNamesToDo)) {
                    if(!$dry) {
                        /** @var SubdefGenerator $subdefGenerator */
                        $subdefGenerator = $this->container['subdef.generator'];
                        $subdefGenerator->generateSubdefs($record, $subdefNamesToDo);
                    }

                    if($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln(sprintf(" subdefs[%s] done", join(',', $subdefNamesToDo)));
                    }
                }
                else {
                    if($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln(sprintf(" nothing to do"));
                    }
                }
            }
            catch(\Exception $e) {
                if($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf("failed"));
                }
            }

            if($progress) {
                $progress->advance();
            }
        }

        if($progress) {
            $progress->finish();
            $output->writeln("");
        }
    }

    /**
     * @param string[] $recordTypes
     * @param null|int $min
     * @param null|int $max
     * @return array
     */
    protected function generateSQL(Connection $connection, array $recordTypes, $min, $max)
    {
        $sql = "SELECT record_id, type FROM record WHERE parent_record_id=0";

        $types = array_map(function($v) use($connection){return $connection->quote($v);}, $recordTypes);

        if(!empty($types)) {
            $sql .= ' AND type IN(' . join(',', $types) . ')';
        }
        if (null !== $min) {
            $sql .= ' AND (record_id >= ' . $connection->quote($min) . ')';
        }
        if (null !== $max) {
            $sql .= ' AND (record_id <= ' . $connection->quote($max) . ')';
        }

        return array($sql, [], []);
    }
}
