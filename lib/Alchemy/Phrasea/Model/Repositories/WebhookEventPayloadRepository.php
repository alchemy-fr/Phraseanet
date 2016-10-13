<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\WebhookEventPayload;
use Doctrine\ORM\EntityRepository;

class WebhookEventPayloadRepository extends EntityRepository
{

    public function save(WebhookEventPayload $payload)
    {
        $this->_em->persist($payload);
        $this->_em->persist($payload->getDelivery());

        $this->_em->flush([ $payload, $payload->getDelivery() ]);
    }
}
