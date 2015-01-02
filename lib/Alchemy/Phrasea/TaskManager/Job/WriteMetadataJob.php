<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\TaskManager\Editor\WriteMetadataEditor;
use PHPExiftool\Driver\Metadata;
use PHPExiftool\Driver\Value;
use PHPExiftool\Driver\Tag;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Writer;
use PHPExiftool\Driver\TagFactory;
use PHPExiftool\Writer as ExifWriter;
use PHPExiftool\Exception\TagUnknown;

class WriteMetadataJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('task::writemeta:ecriture des metadatas');
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
        return $this->translator->trans("task::writemeta:(re)ecriture des metadatas dans les documents (et subdefs concernees)");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new WriteMetadataEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());
        $clearDoc = (Boolean) (string) $settings->cleardoc;
        $MWG = (Boolean) (string) $settings->mwg;

        // move this in service provider configuration
        // $app['exiftool.writer']->setModule(Writer::MODULE_MWG, true);

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            $conn = $databox->get_connection();
            $metaSubdefs = [];

            foreach ($databox->get_subdef_structure() as $type => $definitions) {
                foreach ($definitions as $sub) {
                    $name = $sub->get_name();
                    if ($sub->meta_writeable()) {
                        $metaSubdefs[$name . '_' . $type] = true;
                    }
                }
            }

            $sql = 'SELECT record_id, coll_id, jeton FROM record WHERE (jeton & ' . PhraseaTokens::TOKEN_WRITE_META . ' > 0)';

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                $record_id = $row['record_id'];
                $token = $row['jeton'];

                $record = $databox->get_record($record_id);
                $type = $record->get_type();

                $subdefs = [];
                foreach ($record->get_subdefs() as $name => $subdef) {
                    $write_document = (($token & PhraseaTokens::TOKEN_WRITE_META_DOC) && $name == 'document');
                    $write_subdef = (($token & PhraseaTokens::TOKEN_WRITE_META_SUBDEF) && isset($metaSubdefs[$name . '_' . $type]));

                    if (($write_document || $write_subdef) && $subdef->is_physically_present()) {
                        $subdefs[$name] = $subdef->get_pathfile();
                    }
                }

                $metadata = new Metadata\MetadataBag();

                if ($record->get_uuid()) {
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\XMPExif\ImageUniqueID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\ExifIFD\ImageUniqueID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\IPTC\UniqueDocumentID(),
                            new Value\Mono($record->get_uuid())
                        )
                    );
                }

                $caption = $record->get_caption();
                foreach($databox->get_meta_structure() as $fieldStructure) {

                    $tagName = $fieldStructure->get_tag()->getTagname();
                    $fieldName = $fieldStructure->get_name();

                    // skip fields with no src
                    if($tagName == '') {
                        continue;
                    }

                    // check exiftool known tags to skip Phraseanet:tf-*
                    try {
                        TagFactory::getFromRDFTagname($tagName);
                    } catch (TagUnknown $e) {
                        continue;
                    }

                    try {
                        $field = $caption->get_field($fieldName);
                        $data = $field->get_values();

                        if ($fieldStructure->is_multi()) {
                            $values = array();
                            foreach ($data as $value) {
                                $values[] = $value->getValue();
                            }

                            $value = new Value\Multi($values);
                        } else {
                            $data = array_pop($data);
                            $value = $data->getValue();

                            $value = new Value\Mono($value);
                        }
                    } catch(\Exception $e) {
                        // the field is not set in the record, erase it
                        if ($fieldStructure->is_multi()) {
                            $value = new Value\Multi(Array(''));
                        }
                        else {
                            $value = new Value\Mono('');
                        }
                    }

                    $metadata->add(
                        new Metadata\Metadata($fieldStructure->get_tag(), $value)
                    );
                }

                $app['exiftool.writer']->reset();

                if($MWG) {
                    $app['exiftool.writer']->setModule(ExifWriter::MODULE_MWG, true);
                }

                foreach ($subdefs as $name => $file) {
                    $app['exiftool.writer']->erase($name != 'document' || $clearDoc, true);
                    try {
                        $app['exiftool.writer']->write($file, $metadata);

                        $this->log(sprintf('meta written for sbasid=%1$d - recordid=%2$d (%3$s)', $databox->get_sbas_id(), $record_id, $name), self::LOG_INFO);
                    } catch (PHPExiftoolException $e) {
                        $this->log(sprintf('meta NOT written for sbasid=%1$d - recordid=%2$d (%3$s) because "%s"', $databox->get_sbas_id(), $record_id, $name, $e->getMessage()), self::LOG_ERROR);
                    }
                }

                $sql = 'UPDATE record SET jeton=jeton & ~' . PhraseaTokens::TOKEN_WRITE_META . ' WHERE record_id = :record_id';
                $stmt = $conn->prepare($sql);
                $stmt->execute([':record_id' => $record_id]);
                $stmt->closeCursor();
            }
        }
    }
}
