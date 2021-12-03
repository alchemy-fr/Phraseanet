<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Repositories\UserRepository;

class UserRegistrationProcessor implements ProcessorInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function process(WebhookEvent $event)
    {
        $data = $event->getData();

        if (! isset($data['user_id'])) {
            return null;
        }

        $user = $this->userRepository->find($data['user_id']);

        return [
            'event'         => $event->getName(),
            'webhookId'     => $event->getId(),
            'version'       => WebhookEvent::WEBHOOK_VERSION,
            'url'           => $data['url'],
            'instance_name' => $data['instance_name'],
            'user'  => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
                'login' => $user->getLogin()
            ],
            'granted'   => $data['granted'],
            'rejected'  => $data['rejected'],
            'event_time'=> $data['event_time']
        ];
    }
}
