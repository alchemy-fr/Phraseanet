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

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Media\SubdefGenerator;
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
        $this->addArgument('type', InputArgument::REQUIRED, 'Types of the document to rebuild');
        $this->addArgument('subdefs', InputArgument::REQUIRED, 'Names of sub-definition to re-build');
        $this->addOption('max_record', 'max', InputOption::VALUE_OPTIONAL, 'Max record id');
        $this->addOption('min_record', 'min', InputOption::VALUE_OPTIONAL, 'Min record id');
        $this->addOption('with-substitution', 'wsubstit', InputOption::VALUE_NONE, 'Regenerate subdefs for substituted records as well');
        $this->addOption('substitution-only', 'substito', InputOption::VALUE_NONE, 'Regenerate subdefs for substituted records only');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $availableTypes = array('document', 'audio', 'video', 'image', 'flash', 'map');

        $typesOption =  $input->getArgument('type');

        $recordsType = explode(',', $typesOption);
        $recordsType = array_filter($recordsType, function($type) use($availableTypes) {
            return in_array($type, $availableTypes);
        });

        if (count($recordsType) === 0) {
            $output->write(sprintf('Invalid records type provided %s', implode(', ', $availableTypes)));
            return;
        }

        $subdefsOption = $input->getArgument('subdefs');
        $subdefsName = explode(',', $subdefsOption);

        if (count($subdefsOption) === 0) {
            $output->write('No subdef options provided');
            return;
        }

        $min = $input->getOption('min_record');
        $max = $input->getOption('max_record');
        $substitutionOnly = $input->getOption('substitution-only');
        $withSubstitution = $input->getOption('with-substitution');

        if (false !== $withSubstitution && false !== $substitutionOnly) {
            throw new InvalidArgumentException('Conflict, you can not ask for --substitution-only && --with-substitution parameters at the same time');
        }

        list($sql, $params, $types) = $this->generateSQL($subdefsName, $recordsType, $min, $max, $withSubstitution, $substitutionOnly);

        $databox = $this->container->findDataboxById($input->getArgument('databox'));
        $connection = $databox->get_connection();

        $sqlCount = sprintf('SELECT COUNT(*) FROM (%s) AS c', $sql);
        $output->writeln($sqlCount);
        $totalRecords = (int)$connection->executeQuery($sqlCount, $params, $types)->fetchColumn();

        if ($totalRecords === 0) {
            return;
        }

        $progress = new ProgressBar($output, $totalRecords);

        $progress->start();

        $progress->display();

        $rows = $connection->executeQuery($sql, $params, $types)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $output->write(sprintf(' (#%s)', $row['record_id']));

            $record = $databox->get_record($row['record_id']);

            $subdefs = array_filter($record->get_subdefs(), function(media_subdef $subdef) use ($subdefsName) {
                return in_array($subdef->get_name(), $subdefsName);
            });

            /** @var media_subdef $subdef */
            foreach ($subdefs as $subdef) {
                $subdef->remove_file();
                if (($withSubstitution && $subdef->is_substituted()) || $substitutionOnly) {
                    $subdef->set_substituted(false);
                }
            }

            /** @var SubdefGenerator $subdefGenerator */
            $subdefGenerator = $this->container['subdef.generator'];
            $subdefGenerator->generateSubdefs($record, $subdefsName);

            $progress->advance();
        }

        $progress->finish();
    }

    /**
     * @param string[] $subdefNames
     * @param string[] $recordTypes
     * @param null|int $min
     * @param null|int $max
     * @param bool $withSubstitution
     * @param bool $substitutionOnly
     * @return array
     */
    protected function generateSQL(array $subdefNames, array $recordTypes, $min, $max, $withSubstitution, $substitutionOnly)
    {
        $sql = "SELECT DISTINCT(r.record_id) AS record_id"
            . " FROM record r LEFT JOIN subdef s ON (r.record_id = s.record_id AND s.name IN (?))"
            . " WHERE r.type IN (?)";

        $types = array(Connection::PARAM_STR_ARRAY, Connection::PARAM_STR_ARRAY);
        $params = array($subdefNames, $recordTypes);

        if (null !== $min) {
            $sql .= " AND (r.record_id >= ?)";

            $params[] = (int)$min;
            $types[] = \PDO::PARAM_INT;
        }
        if (null !== $max) {
            $sql .= " AND (r.record_id <= ?)";

            $params[] = (int)$max;
            $types[] = \PDO::PARAM_INT;
        }

        if (false === $withSubstitution) {
            $sql .= " AND (ISNULL(s.substit) OR s.substit = ?)";
            $params[] = $substitutionOnly ? 1 : 0;
            $types[] = \PDO::PARAM_INT;
        }

        return array($sql, $params, $types);
    }
}
