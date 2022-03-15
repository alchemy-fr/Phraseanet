<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Media\SubdefGenerator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildMissingSubdefs extends Command
{
    private $generator;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('<fg=yellow;>(Deprecated)</> Builds subviews that previously failed to be generated / did not exist when records were added');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $progressBar = new ProgressBar($output);
        $generatedSubdefs = 0;

        foreach ($this->container->getDataboxes() as $databox) {
            $sql = 'SELECT record_id FROM record WHERE parent_record_id = 0';
            $result = $databox->get_connection()->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $progressBar->start(count($result));

            foreach ($result as $row) {
                $record = $databox->get_record($row['record_id']);

                $generatedSubdefs += $this->generateRecordMissingSubdefs($record);

                $progressBar->advance();
            }

            $progressBar->finish();
        }

        $this->container['monolog']->addInfo($generatedSubdefs . " subdefs done");
        $stop = microtime(true);
        $duration = $stop - $start;

        $this->container['monolog']->addInfo(sprintf("process took %s, (%f sd/s.)", $this->getFormattedDuration($duration), round($generatedSubdefs / $duration, 3)));
        $progressBar->finish();
    }

    /**
     * Generate subdef generation and return number of subdef
     * @param \record_adapter $record
     * @return int
     */
    protected function generateRecordMissingSubdefs(\record_adapter $record)
    {
        $wanted_subdefs = $record->get_missing_subdefs();

        if (!empty($wanted_subdefs)) {
            $this->getSubdefGenerator()->generateSubdefs($record, $wanted_subdefs);

            foreach ($wanted_subdefs as $subdef) {
                $this->container['monolog']->addInfo("generate " . $subdef . " for record " . $record->getRecordId());
            }
        }

        return count($wanted_subdefs);
    }

    /**
     * @return SubdefGenerator
     */
    protected function getSubdefGenerator()
    {
        if (null === $this->generator) {
            $this->generator = $this->container['subdef.generator'];
        }

        return $this->generator;
    }
}
