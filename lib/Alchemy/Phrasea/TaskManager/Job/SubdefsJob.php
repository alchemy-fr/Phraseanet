<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;
use MediaAlchemyst\Transmuter\Image2Image;

class SubdefsJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return _('task::subdef:creation des sous definitions');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return _("task::subdef:creation des sous definitions des documents d'origine");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new SubdefsEditor();
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());
        $thumbnailExtraction = (Boolean) (string) $settings->embedded;

        Image2Image::$lookForEmbeddedPreview = $thumbnailExtraction;

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            if (!$this->isStarted()) {
                break;
            }
            $connbas = $databox->get_connection();

            $sql = 'SELECT coll_id, record_id
                  FROM record
                  WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
                  ORDER BY record_id DESC LIMIT 0, 30';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                if (!$this->isStarted()) {
                    break;
                }
                $this->log('info', sprintf("Generate subdefs for : sbasid=%s / databox=%s / recordid=%s ", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id']));

                try {
                    $record = $databox->get_record($row['record_id']);
                    $record->generate_subdefs($databox, $app);
                } catch (\Exception $e) {
                    $this->log('warning', sprintf("Generate subdefs failed for : sbasid=%s / databox=%s / recordid=%s : %s", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id'], $e->getMessage()));
                }

                $sql = 'UPDATE record
                      SET jeton=(jeton & ~' . JETON_MAKE_SUBDEF . '), moddate=NOW()
                      WHERE record_id=:record_id';

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':record_id' => $row['record_id']));
                $stmt->closeCursor();

                // rewrite metadata
                $sql = 'UPDATE record
                    SET status=(status & ~0x03),
                        jeton=(jeton | ' . JETON_WRITE_META_SUBDEF . ')
                    WHERE record_id=:record_id';
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':record_id' => $row['record_id']));
                $stmt->closeCursor();

                unset($record);
            }
        }
    }
}
