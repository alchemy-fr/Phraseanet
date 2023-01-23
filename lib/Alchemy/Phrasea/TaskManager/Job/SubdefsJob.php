<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;
use Doctrine\DBAL\Connection;
use MediaAlchemyst\Transmuter\Image2Image;

class SubdefsJob extends AbstractJob
{
    public function getName()
    {
        return $this->translator->trans('task::subdef:creation des sous definitions');
    }

    public function getJobId()
    {
        return 'Subdefs';
    }

    public function getDescription()
    {
        return $this->translator->trans("task::subdef:creation des sous definitions des documents d'origine");
    }

    public function getEditor()
    {
        return new SubdefsEditor($this->translator);
    }

    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());
        $thumbnailExtraction = (bool) (string) $settings->embedded;

        Image2Image::$lookForEmbeddedPreview = $thumbnailExtraction;

        $documentTypes = [
            'image',
            'video',
            'audio',
            'document',
            'flash',
            'unknown'
        ];

        $sqlParameters = [];

        foreach($documentTypes as $type) {
            if (!isset($settings->{"type_" . $type}) || !\p4field::isno($settings->{"type_" . $type})) {
                $sqlParameters[] = $type;
            }
        }

        if(empty($sqlParameters)) {
            return;
        }

        $app->getApplicationBox()->get_connection();

        $allDb = count($settings->xpath('sbas[text()="0"]')) > 0;
        foreach ($app->getDataboxes() as $databox) {
            if (!$this->isStarted()) {
                break;
            }

            if(!$allDb && count($settings->xpath("sbas[text()=".$databox->get_sbas_id() ."]")) == 0) {
                continue;
            }

            $conn = $databox->get_connection();

            $sql = 'SELECT coll_id, record_id FROM record'
                . ' WHERE jeton & :token > 0 AND type IN(:types)'
                . ' ORDER BY record_id DESC LIMIT 0, 30';
            $resultSet = $conn->fetchAll(
                $sql,
                [
                    'token' => PhraseaTokens::MAKE_SUBDEF,
                    'types' => $sqlParameters,
                ],
                [
                    'token' => \PDO::PARAM_INT,
                    'types' => Connection::PARAM_STR_ARRAY,
                ]
            );

            $i = 0;
            foreach ($resultSet as $row) {
                if (!$this->isStarted()) {
                    break;
                }
                $this->log('info', sprintf("Generate subdefs for : sbasid=%s / databox=%s / recordid=%s ", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id']));

                try {
                    $record = $databox->get_record($row['record_id']);
                    $this->getSubdefGenerator($app)->generateSubdefs($record);
                } catch (\Exception $e) {
                    $this->log('warning', sprintf("Generate subdefs failed for : sbasid=%s / databox=%s / recordid=%s : %s", $databox->get_sbas_id(), $databox->get_dbname() , $row['record_id'], $e->getMessage()));
                }

                // subdef created, ask to rewrite metadata
                $sql = 'UPDATE record'
                    . ' SET jeton=(jeton & ~(:flag_and)) | :flag_or, moddate=NOW()'
                    . ' WHERE record_id=:record_id';

                $conn->executeUpdate($sql, [
                    ':record_id' => $row['record_id'],
                    ':flag_and' => PhraseaTokens::MAKE_SUBDEF,
                    ':flag_or' => (PhraseaTokens::WRITE_META_SUBDEF | PhraseaTokens::TO_INDEX)
                ]);

                unset($record);
                $i++;

                if ($i % 5 === 0) {
                    $this->flushIndexerQueue($app);
                }
            }
        }

        $this->flushIndexerQueue($app);
    }

    /**
     * @param Application $app
     * @return SubdefGenerator
     */
    private function getSubdefGenerator(Application $app)
    {
        return $app['subdef.generator'];
    }

    private function flushIndexerQueue(Application $app)
    {
        $app['elasticsearch.indexer']->flushQueue();
    }
}
