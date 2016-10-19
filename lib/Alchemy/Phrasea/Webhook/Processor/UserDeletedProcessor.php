<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class UserDeletedProcessor implements ProcessorInterface
{

    public function process(WebhookEvent $event)
    {
        $data = $event->getData();

        if (! isset($data['user_id'])) {
            return null;
        }

        return array(
            'event' => $event->getName(),
            'user' => array(
                'id' => $data['user_id'],
                'email' => $data['email'],
                'login' => $data['login']
            )
        );
    }
}
