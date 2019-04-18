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
use Alchemy\Phrasea\Metadata\TagFactory;
use Alchemy\Phrasea\TaskManager\Editor\WriteMetadataEditor;
use MediaAlchemyst\Specification\Image;
use PHPExiftool\Driver\Metadata;
use PHPExiftool\Driver\Value;
use PHPExiftool\Driver\Tag;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Writer as ExifWriter;
use PHPExiftool\Exception\TagUnknown;
use PHPExiftool\Writer;

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
    protected function doJob(JobData $jobData)
    {
        $settings = simplexml_load_string($jobData->getTask()->getSettings());
        $clearDoc = (bool) (string) $settings->cleardoc;
        $MWG = (bool) (string) $settings->mwg;

        foreach ($jobData->getApplication()->getDataboxes() as $databox) {
            $connection = $databox->get_connection();

            $statement = $connection->prepare('SELECT record_id, coll_id, jeton FROM record WHERE (jeton & :token > 0)');
            $statement->execute(['token' => PhraseaTokens::WRITE_META]);
            $rs = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();

            foreach ($rs as $row) {
                $record_id = $row['record_id'];
                $token = $row['jeton'];

                $record = $databox->get_record($record_id);
                $type = $record->getType();

                $subdefs = [];
                $specs = [];
                foreach ($record->get_subdefs() as $name => $subdef) {
                    $write_document = (($token & PhraseaTokens::WRITE_META_DOC) && $name == 'document');
                    $write_subdef = (($token & PhraseaTokens::WRITE_META_SUBDEF) && $this->isSubdefMetadataUpdateRequired($databox, $type, $name));

                    if (($write_document || $write_subdef) && $subdef->is_physically_present()) {
                        $subdefs[$name] = $subdef->getRealPath();
                        if($name != 'document'){
                            $specs[$name] = $subdef->getDataboxSubdef()->getSpecs();
                        }
                    }
                }

                $metadata = new Metadata\MetadataBag();

                if ($record->getUuid()) {
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\XMPExif\ImageUniqueID(),
                            new Value\Mono($record->getUuid())
                        )
                    );
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\ExifIFD\ImageUniqueID(),
                            new Value\Mono($record->getUuid())
                        )
                    );
                    $metadata->add(
                        new Metadata\Metadata(
                            new Tag\IPTC\UniqueDocumentID(),
                            new Value\Mono($record->getUuid())
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
                        $fieldValues = $field->get_values();

                        if ($fieldStructure->is_multi()) {
                            $values = array();
                            foreach ($fieldValues as $value) {
                                $values[] = $value->getValue();
                            }

                            $value = new Value\Multi($values);
                        } else {
                            $fieldValue = array_pop($fieldValues);
                            $value = $fieldValue->getValue();

                            $value = new Value\Mono($value);
                        }
                    } catch(\Exception $e) {
                        // the field is not set in the record, erase it
                        if ($fieldStructure->is_multi()) {
                            $value = new Value\Multi(array(''));
                        }
                        else {
                            $value = new Value\Mono('');
                        }
                    }

                    $metadata->add(
                        new Metadata\Metadata($fieldStructure->get_tag(), $value)
                    );
                }

                $writer = $this->getMetadataWriter($jobData->getApplication());
                $writer->reset();

                if($MWG) {
                    $writer->setModule(ExifWriter::MODULE_MWG, true);
                }

                foreach ($subdefs as $name => $file) {
                    $resolution = [];

                    if( $name != 'document' && $specs[$name] instanceof Image){
                        $resolution[] = $specs[$name]->getResolutionX();  //xresolution
                        $resolution[] = $specs[$name]->getResolutionY();  //yresolution
                    }

                    $writer->erase($name != 'document' || $clearDoc, true);
                    try {
                        $writer->write($file, $metadata, null, $resolution);

                        $this->log('info',sprintf('meta written for sbasid=%1$d - recordid=%2$d (%3$s)', $databox->get_sbas_id(), $record_id, $name));
                    } catch (PHPExiftoolException $e) {
                        $this->log('error',sprintf('meta NOT written for sbasid=%1$d - recordid=%2$d (%3$s) because "%s"', $databox->get_sbas_id(), $record_id, $name, $e->getMessage()));
                    }
                }

                $statement = $connection->prepare('UPDATE record SET jeton=jeton & ~:token WHERE record_id = :record_id');
                $statement->execute([
                    'record_id' => $record_id,
                    'token' => PhraseaTokens::WRITE_META,
                ]);
                $statement->closeCursor();
            }
        }
    }

    /**
     * @param Application $app
     * @return Writer
     */
    private function getMetadataWriter(Application $app)
    {
        return $app['exiftool.writer'];
    }

    /**
     * @param \databox $databox
     * @param string $subdefType
     * @param string $subdefName
     * @return bool
     */
    private function isSubdefMetadataUpdateRequired(\databox $databox, $subdefType, $subdefName)
    {
        if ($databox->get_subdef_structure()->hasSubdef($subdefType, $subdefName)) {
            return $databox->get_subdef_structure()->get_subdef($subdefType, $subdefName)->isMetadataUpdateRequired();
        }

        return false;
    }
}
