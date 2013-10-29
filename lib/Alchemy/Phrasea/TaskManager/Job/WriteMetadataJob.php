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

use Alchemy\Phrasea\TaskManager\Editor\WriteMetadataEditor;
use PHPExiftool\Driver\Metadata;
use PHPExiftool\Driver\Value;
use PHPExiftool\Driver\Tag;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Writer;

class WriteMetadataJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return _('task::writemeta:ecriture des metadatas');
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'WriteMetadata';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return _("task::writemeta:(re)ecriture des metadatas dans les documents (et subdefs concernees)");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new WriteMetadataEditor();
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());
        $clearDoc = (Boolean) (string) $settings->cleardoc;

        // move this in service provider configuration
        $app['exiftool.writer']->setModule(Writer::MODULE_MWG, true);

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            $connbas = $databox->get_connection();
            $subdefgroups = $databox->get_subdef_structure();
            $metasubdefs = array();

            foreach ($subdefgroups as $type => $subdefs) {
                foreach ($subdefs as $sub) {
                    $name = $sub->get_name();
                    if ($sub->meta_writeable()) {
                        $metasubdefs[$name . '_' . $type] = true;
                    }
                }
            }

            $sql = 'SELECT record_id, coll_id, jeton
                 FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)';

            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row ) {
                $record_id = $row['record_id'];
                $jeton = $row['jeton'];

                $record = $databox->get_record($record_id);

                $type = $record->get_type();
                $subdefs = $record->get_subdefs();

                $tsub = array();

                foreach ($subdefs as $name => $subdef) {
                    $write_document = (($jeton & JETON_WRITE_META_DOC) && $name == 'document');
                    $write_subdef = (($jeton & JETON_WRITE_META_SUBDEF) && isset($metasubdefs[$name . '_' . $type]));

                    if (($write_document || $write_subdef) && $subdef->is_physically_present()) {
                        $tsub[$name] = $subdef->get_pathfile();
                    }
                }

                $metadatas = new Metadata\MetadataBag();

                if ($record->get_uuid()) {
                    $metadatas->add(
                        new Metadata\Metadata(
                            new Tag\XMPExif\ImageUniqueID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                    $metadatas->add(
                        new Metadata\Metadata(
                            new Tag\ExifIFD\ImageUniqueID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                    $metadatas->add(
                        new Metadata\Metadata(
                            new Tag\IPTC\UniqueDocumentID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                }

                foreach ($record->get_caption()->get_fields() as $field) {
                    $meta = $field->get_databox_field();
                    /* @var $meta \databox_field */

                    $datas = $field->get_values();

                    if ($meta->is_multi()) {
                        $values = array();
                        foreach ($datas as $data) {
                            $values[] = $data->getValue();
                        }

                        $value = new Value\Multi($values);
                    } else {
                        $data = array_pop($datas);
                        $value = new Value\Mono($data->getValue());
                    }

                    $metadatas->add(
                        new Metadata\Metadata($meta->get_tag(), $value)
                    );
                }

                foreach ($tsub as $name => $file) {
                    $app['exiftool.writer']->erase($name != 'document' || $clearDoc, true);

                    try {
                        $app['exiftool.writer']->write($file, $metadatas);
                        $this->log('debug', sprintf('meta written for sbasid=%1$d - recordid=%2$d (%3$s)', $databox->get_sbas_id(), $record_id, $name));
                    } catch (PHPExiftoolException $e) {
                        $this->log('error', sprintf('meta was not written for sbasid=%d - recordid=%d (%s) because "%s"', $databox->get_sbas_id(), $record_id, $name, $e->getMessage()));
                    }
                }

                $sql = 'UPDATE record SET jeton=jeton & ~' . JETON_WRITE_META . '
                    WHERE record_id = :record_id';
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':record_id' => $row['record_id']));
                $stmt->closeCursor();
            }
        }
    }
}
