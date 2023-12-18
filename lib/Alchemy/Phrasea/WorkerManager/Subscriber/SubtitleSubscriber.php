<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Core\Event\Record\RecordAutoSubtitleEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubtitleSubscriber implements EventSubscriberInterface
{
    private $messagePublisher;

    /** @var WorkerJobRepository  $repoWorkerJob*/
    private $repoWorkerJob;

    /** @var callable  */
    private $repoWorkerJobLocator;

    public function __construct(callable $repoWorkerJobLocator, MessagePublisher $messagePublisher)
    {
        $this->repoWorkerJobLocator     = $repoWorkerJobLocator;
        $this->messagePublisher         = $messagePublisher;
    }

    public function onRecordAutoSubtitle(RecordAutoSubtitleEvent $event)
    {
        if (!empty($event->getLanguageDestination())) {
            $data = [
                "databoxId"                     => $event->getRecord()->getDataboxId(),
                "recordId"                      => $event->getRecord()->getRecordId(),
                "languageSource"                => $event->getLanguageSource(),
                "languageDestination"           => $event->getLanguageDestination(),
                "authenticatedUserId"           => $event->getAuthenticatedUserId(),
                "type"                          => MessagePublisher::SUBTITLE_TYPE  // used to specify the final Q to publish message
            ];

            $payload = [
                'message_type' => MessagePublisher::MAIN_QUEUE_TYPE,
                'payload' => $data
            ];

            $this->messagePublisher->publishMessage($payload, MessagePublisher::MAIN_QUEUE_TYPE);
        } else {
            $this->messagePublisher->pushLog("There is no language destination selected when trying do do autosubtitle!");
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::RECORD_AUTO_SUBTITLE  => 'onRecordAutoSubtitle',
        ];
    }

    /**
     * @return WorkerJobRepository
     */
    private function getRepoWorkerJob()
    {
        $callable = $this->repoWorkerJobLocator;

        return $callable();
    }
}
