<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
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
        $n = 0;

        foreach ($this->container['phraseanet.appbox']->get_databoxes() as $databox) {

            $subdefStructure = $databox->get_subdef_structure();

            $sql = 'SELECT record_id FROM record WHERE parent_record_id = 0';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                $record = $databox->get_record($row['record_id']);

                try {
                    $record->get_hd_file();
                } catch (FileNotFoundException $e) {
                    continue;
                }

                $group = $subdefStructure->getSubdefGroup($record->get_type());

                if ($group) {
                    foreach ($group as $subdef) {

                        $todo = false;

                        if ( ! $record->has_subdef($subdef->get_name())) {
                            $todo = true;
                        }
                        if (in_array($subdef->get_name(), ['preview', 'thumbnail', 'thumbnailgif'])) {
                            try {
                                $sub = $record->get_subdef($subdef->get_name());
                                if ( ! $sub->is_physically_present()) {
                                    $todo = true;
                                }
                            } catch (\Exception_Media_SubdefNotFound $e) {
                                $todo = true;
                            }
                        }

                        if ($todo) {
                            $record->generate_subdefs($databox, $this->container, [$subdef->get_name()]);
                            $this->container['monolog']->addInfo("generate " . $subdef->get_name() . " for record " . $record->get_record_id());
                            $n ++;
                        }
                    }
                }

                unset($record);
            }
        }

        $this->container['monolog']->addInfo($n . " subdefs done");
        $stop = microtime(true);
        $duration = $stop - $start;

        $this->container['monolog']->addInfo(sprintf("process took %s, (%f sd/s.)", $this->getFormattedDuration($duration), round($n / $duration, 3)));

        return;
    }

}
