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
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Builds subviews that previously failed to be generated / did not exist when records were added');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $progressBar = new ProgressBar($output);
        $n = 0;

        /** @var SubdefGenerator $subdefGenerator */
        $subdefGenerator = $this->container['subdef.generator'];

        foreach ($this->container->getDataboxes() as $databox) {
            $sql = 'SELECT record_id FROM record WHERE parent_record_id = 0';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $progressBar->start(count($rs));

            foreach ($rs as $row) {
                $record = $databox->get_record($row['record_id']);

                $wanted_subdefs = $record->get_missing_subdefs();

                if (count($wanted_subdefs) > 0) {
                    $subdefGenerator->generateSubdefs($record, $wanted_subdefs);

                    foreach ($wanted_subdefs as $subdef) {
                        $this->container['monolog']->addInfo("generate " .$subdef . " for record " . $record->get_record_id());
                        $n ++;
                    }
                }

                unset($record);
                $progressBar->advance();
            }

            $progressBar->finish();
        }

        $this->container['monolog']->addInfo($n . " subdefs done");
        $stop = microtime(true);
        $duration = $stop - $start;

        $this->container['monolog']->addInfo(sprintf("process took %s, (%f sd/s.)", $this->getFormattedDuration($duration), round($n / $duration, 3)));
        $progressBar->finish();

        return;
    }

}
