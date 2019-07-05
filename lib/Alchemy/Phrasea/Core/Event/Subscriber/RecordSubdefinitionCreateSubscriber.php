<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreateEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Model\RecordInterface;
use Assert\Assertion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordSubdefinitionCreateSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            RecordEvents::SUB_DEFINITIONS_CREATE => 'onSubdefinitionCreate',
        );
    }

    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onSubdefinitionCreate(SubDefinitionsCreateEvent $event)
    {
        static $into = false;

        // prevent recursion
        if ($into) {
            return;
        }
        $into = true;

        $message = $event->convertToMessage();
        $event = RecordEvent::restoreFromMessage($message, $this->app);

        $record = $this->convertToRecordAdapter($event->getRecord());
        $this->getSubdefGenerator($this->app)->generateSubdefs(
            $record,
            $event->getSubDefinitionsNames()
        );

        $into = false;
    }

    /**
     * @param RecordInterface $record
     * @return \databox
     */
    private function getRecordDatabox(RecordInterface $record)
    {
        return $this->app->getApplicationBox()->get_databox($record->getDataboxId());
    }

    /**
     * @param RecordInterface $record
     * @return \record_adapter
     * @throws \Assert\AssertionFailedException
     */
    private function convertToRecordAdapter(RecordInterface $record)
    {
        if ($record instanceof \record_adapter) {
            return $record;
        }

        $databox = $this->getRecordDatabox($record);

        $recordAdapter = $databox->getRecordRepository()->find($record->getRecordId());

        Assertion::isInstanceOf($recordAdapter, \record_adapter::class);

        return $recordAdapter;
    }


    /**
     * @param Application $app
     * @return SubdefGenerator
     */
    private function getSubdefGenerator(Application $app)
    {
        return $app['subdef.generator'];
    }

}
