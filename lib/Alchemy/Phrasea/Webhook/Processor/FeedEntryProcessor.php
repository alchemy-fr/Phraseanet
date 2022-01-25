<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Repositories\FeedEntryRepository;

class FeedEntryProcessor implements ProcessorInterface
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var FeedEntryRepository
     */
    private $entryRepository;

    /**
     * @var \User_Query
     */
    private $userQuery;

    public function __construct(Application $application, FeedEntryRepository $entryRepository, \User_Query $userQuery)
    {
        $this->application = $application;
        $this->entryRepository = $entryRepository;
        $this->userQuery = $userQuery;
    }

    public function process(WebhookEvent $event)
    {
        $data = $event->getData();

        if (!isset($data['entry_id'])) {
            return null;
        }

        $entry = $this->entryRepository->find($data['entry_id']);

        if (null === $entry) {
            return null;
        }

        $feed = $entry->getFeed();

        $query = $this->userQuery;

        $query->include_phantoms(true)
            ->include_invite(false)
            ->include_templates(false)
            ->email_not_null(true);

        if ($feed->getCollection($this->application)) {
            $query->on_base_ids([$feed->getCollection($this->application)->get_base_id()]);
        }

        $start = 0;
        $perLoop = 100;
        $users = [];

        do {
            $results = $query->limit($start, $perLoop)->execute()->get_results();
            foreach ($results as $user) {
                $users[] = [
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname() ?: null,
                    'lastname' => $user->getLastname() ?: null,
                ];
            }
            $start += $perLoop;
        } while (count($results) > 0);

        return [
            'event'                 => $event->getName(),
            'webhookId'             => $event->getId(),
            'version'               => WebhookEvent::WEBHOOK_VERSION,
            'url'                   => $data['url'],
            'instance_name'         => $data['instance_name'],
            'users_were_notified'   => isset($data['notify_email']) ? (bool) $data['notify_email'] : false,
            'feed' => [
                'id'    => $feed->getId(),
                'title' => $feed->getTitle(),
                'description' => $feed->getSubtitle(),
            ],
            'entry' => [
                'id'     => $entry->getId(),
                'author' => [
                    'name'  => $entry->getAuthorName(),
                    'email' => $entry->getAuthorEmail()
                ],
                'title'         => $entry->getTitle(),
                'description'   => $entry->getSubtitle(),
            ],
            'users'         => $users,
            'event_time'    => $data['event_time']
        ];
    }
}
