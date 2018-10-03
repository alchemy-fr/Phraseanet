<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Record;

use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordUpdateSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            RecordEvents::ROTATE => 'onRecordChange',
        ];
    }

    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function onRecordChange(RecordEvent $recordEvent)
    {
        $record = $recordEvent->getRecord();

        $databoxId = $record->getDataboxId();
        $recordId = $record->getRecordId();

        $recordAdapter = $this->appbox->get_databox($databoxId)->getRecordRepository()->find($recordId);

        if ($recordAdapter) {
            $recordAdapter->touch();
        }
    }
}
