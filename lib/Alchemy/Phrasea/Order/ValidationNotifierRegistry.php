<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ValidationNotifierRegistry
{
    /**
     * @var ValidationNotifier[]
     */
    private $notifiers = [];

    /**
     * @param string $notificationMethodName
     * @param ValidationNotifier $notifier
     */
    public function registerNotifier($notificationMethodName, ValidationNotifier $notifier)
    {
        $this->notifiers[$notificationMethodName] = $notifier;
    }

    /**
     * @param string $notificationMethodName
     * @return ValidationNotifier
     */
    public function getNotifier($notificationMethodName)
    {
        if (! isset($this->notifiers[$notificationMethodName])) {
            throw new InvalidArgumentException(sprintf('Undefined notifier for method: %s', $notificationMethodName));
        }

        return $this->notifiers[$notificationMethodName];
    }
}
