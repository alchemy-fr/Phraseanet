<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Metadata\TagFactory;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionWritemetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use DateTime;
use Exception;
use Monolog\Logger;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\Tag;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Value\Multi;
use PHPExiftool\Exception\TagUnknown;
use PHPExiftool\Writer;
use Psr\Log\LoggerInterface;
use record_adapter;

class WriteMetadatasWorker implements WorkerInterface
{
    use ApplicationBoxAware;
    use DispatcherAware;
    use EntityManagerAware;

    /** @var Logger  */
    private $logger;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var  Writer $writer */
    private $writer;

    private $repoWorker;

    public function __construct(
        Writer $writer,
        LoggerInterface $logger,
        MessagePublisher $messagePublisher,
        WorkerRunningJobRepository $repoWorker
    )
    {
        $this->writer           = $writer;
        $this->logger           = $logger;
        $this->messagePublisher = $messagePublisher;
        $this->repoWorker       = $repoWorker;
    }

    public function process(array $payload)
    {
        // mandatory args
        if (!isset($payload['recordId']) || !isset($payload['databoxId']) || !isset($payload['subdefName'])) {
            // bad payload
            $this->logger->error(sprintf("%s (%s) : bad payload", __FILE__, __LINE__));
            return;
        }

        $databoxId   = $payload['databoxId'];
        $recordId    = $payload['recordId'];
        $subdefName  = $payload['subdefName'];

        $MWG         = $payload['MWG'] ?? false;
        $clearDoc    = $payload['clearDoc'] ?? false;
        $databox     = $this->findDataboxById($databoxId);

        // try to "lock" the file, will return null if already locked
        $workerRunningJobId = $this->repoWorker->canWriteMetadata($payload);

        if (is_null($workerRunningJobId)) {
            // the file is written by another worker, delay to retry later
            $this->messagePublisher->publishDelayedMessage(
                [
                    'message_type'  => MessagePublisher::WRITE_METADATAS_TYPE,
                    'payload'       => $payload
                ],
                MessagePublisher::WRITE_METADATAS_TYPE
            );

            return ;
        }

        // here we can work

        try {
            $record  = $databox->get_record($recordId);
        } catch (\Exception $e) {
            $this->repoWorker->markFinished($workerRunningJobId, "error " . $e->getMessage());

            return;
        }

        if ($record->getMimeType() == 'image/svg+xml') {

            $this->logger->error("Can't write meta on svg file!");

            // tell that we have finished to work on this file ("unlock")
            $this->repoWorker->markFinished($workerRunningJobId, "Can't write meta on svg file!");

            return;
        }

        $this->repoWorker->reconnect();

        try {
            $subdef = $record->get_subdef($subdefName);
        }
        catch (Exception $e) {
            $workerMessage = "Exception catched when try to get subdef " .$subdefName. " from DB for the recordID: " .$recordId;
            $this->logger->error($workerMessage);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionWritemeta() */
            $this->dispatch(WorkerEvents::SUBDEFINITION_WRITE_META, new SubdefinitionWritemetaEvent(
                $record,
                $subdefName,
                SubdefinitionWritemetaEvent::FAILED,
                $workerMessage,
                $count,
                $workerRunningJobId
            ));

            // the subscriber will mark the job as errored, no need to do it here
            return ;
        }

        if (!$subdef->is_physically_present()) {
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionWritemeta() */
            $this->dispatch(WorkerEvents::SUBDEFINITION_WRITE_META, new SubdefinitionWritemetaEvent(
                $record,
                $subdefName,
                SubdefinitionWritemetaEvent::FAILED,
                'Subdef is not physically present!',
                $count,
                $workerRunningJobId
            ));

            // the subscriber will mark the job as errored, no need to do it here
            return ;
        }

        // here we can try to do the job

        $metadata = new MetadataBag();

        // add Uuid in metadatabag
        if ($record->getUuid()) {
            $metadata->add(
                new Metadata(
                    new Tag\XMPExif\ImageUniqueID(),
                    new Mono($record->getUuid())
                )
            );
            $metadata->add(
                new Metadata(
                    new Tag\ExifIFD\ImageUniqueID(),
                    new Mono($record->getUuid())
                )
            );
            $metadata->add(
                new Metadata(
                    new Tag\IPTC\UniqueDocumentID(),
                    new Mono($record->getUuid())
                )
            );
        }

        // read document fields and add to metadatabag
        $caption = $record->get_caption();
        foreach ($databox->get_meta_structure() as $fieldStructure) {

            $tagName = $fieldStructure->get_tag()->getTagname();
            $fieldName = $fieldStructure->get_name();

            // skip fields with no src
            if ($tagName == '' || $tagName == 'Phraseanet:no-source') {
                continue;
            }

            // check exiftool known tags to skip Phraseanet:tf-*
            try {
                $tag = TagFactory::getFromRDFTagname($tagName);
                if(!$tag->isWritable()) {
                    continue;
                }
            } catch (TagUnknown $e) {
                continue;
            }

            try {
                $field = $caption->get_field($fieldName);
                $fieldValues = $field->get_values();

                if ($fieldStructure->is_multi()) {
                    $values = array();
                    foreach ($fieldValues as $value) {
                        $values[] = $this->removeNulChar($value->getValue());
                    }

                    $value = new Multi($values);
                } else {
                    $fieldValue = array_pop($fieldValues);
                    $value = $this->removeNulChar($fieldValue->getValue());

                    // fix the dates edited into phraseanet
                    if($fieldStructure->get_type() === $fieldStructure::TYPE_DATE) {
                        try {
                            $value = self::fixDate($value); // will return NULL if the date is not valid
                        }
                        catch (Exception $e) {
                            $value = null;    // do NOT write back to iptc
                        }
                    }

                    if($value !== null) {   // do not write invalid dates
                        $value = new Mono($value);
                    }
                }
            }
            catch(Exception $e) {
                // the field is not set in the record, erase it
                if ($fieldStructure->is_multi()) {
                    $value = new Multi(array(''));
                }
                else {
                    $value = new Mono('');
                }
            }

            if($value !== null) {   // do not write invalid data
                $metadata->add(
                    new Metadata($fieldStructure->get_tag(), $value)
                );
            }
        }

        $this->writer->reset();

        if ($MWG) {
            $this->writer->setModule(Writer::MODULE_MWG, true);
        }

        $this->writer->erase($subdef->get_name() != 'document' || $clearDoc, true);

        // write meta in file
        try {
            $this->writer->write($subdef->getRealPath(), $metadata);

            $this->messagePublisher->pushLog(sprintf('metadatas written %s databoxname=%s databoxid=%d recordid=%d',
                $subdef->get_name(), $databox->get_viewname(), $databox->get_sbas_id(), $recordId), 'info');
        }
        catch (Exception $e) {

            // try to interpret the reason of the failure, to see if we retry or stop
            $msg = $e->getMessage();

            $matches = null;
            $stopInfo = false;
            if( preg_match('/\\(looks more like a .*\\)/', $msg,$matches) ) {
                $stopInfo = $matches[0];
            }

            $workerMessage = sprintf('meta NOT written for databoxId=%1$d - recordId=%2$d (%3$s) because "%4$s"',
                $databox->get_sbas_id(),
                $recordId,
                $subdef->get_name(),
                $msg
            );
            $this->logger->error($workerMessage);

            if(!$stopInfo) {
                // we don't know what failed so we retry
                $count = isset($payload['count']) ? $payload['count'] + 1 : 2;

                /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionWritemeta() */
                $this->dispatch(WorkerEvents::SUBDEFINITION_WRITE_META, new SubdefinitionWritemetaEvent(
                    $record,
                    $subdefName,
                    SubdefinitionWritemetaEvent::FAILED,
                    $workerMessage,
                    $count,
                    $workerRunningJobId
                ));
                // the subscriber will "unlock" the row, no need to do it here
            }
            else {
                // failed with known error message like "Not a valid JPG (looks more like a PSD)"
                // mark write metas finished
                $this->updateJeton($record);

                // tell that we have finished to work on this file (=unlock)
                $this->repoWorker->markFinished($workerRunningJobId, $stopInfo);
            }
            return ;
        }

        // mark write metas finished
        $this->updateJeton($record);

        // tell that we have finished to work on this file (=unlock)
        $this->repoWorker->markFinished($workerRunningJobId);
    }

    private function removeNulChar($value)
    {
        return str_replace("\0", "", $value);
    }

    private function updateJeton(record_adapter $record)
    {
        $connection = $record->getDatabox()->get_connection();

        $connection->beginTransaction();
        $stmt = $connection->prepare('UPDATE record SET jeton=(jeton & ~(:token)), moddate=NOW() WHERE record_id = :record_id');

        $stmt->execute([
            ':record_id'    => $record->getRecordId(),
            ':token'        => PhraseaTokens::WRITE_META,
        ]);

        $connection->commit();
        $stmt->closeCursor();
    }

    /**
     * re-format a phraseanet date for iptc writing
     * return NULL if the date is not valid
     *
     * @param string $value
     * @return string|null
     */
    private static function fixDate($value)
    {
        $date = null;
        try {
            $a = explode(';', preg_replace('/\D+/', ';', trim($value)));
            switch (count($a)) {
                case 3:     // yyyy;mm;dd
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2]);
                    $date = $date->format('Y-m-d H:i:s');
                    break;
                case 6:     // yyyy;mm;dd;hh;mm;ss
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':' . $a[4] . ':' . $a[5]);
                    $date = $date->format('Y-m-d H:i:s');
                    break;
            }
        }
        catch (Exception $e) {
            $date = null;
        }

        return $date;
    }
}
