<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Application\Helper\AclAware;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;

class OrderValidator
{
    use AclAware;

    /**
     * @param User $acceptor
     * @param OrderElement[] $elements
     * @return bool
     */
    public function isGrantedValidation(User $acceptor, $elements)
    {
        $acceptableCollections = $this->getAclForUser($acceptor)->getOrderMasterCollectionsBaseIds();

        $elementsCollections = [];

        foreach ($elements as $element) {
            $elementsCollections[$element->getBaseId()] = true;
        }

        return empty(array_diff(array_keys($elementsCollections), $acceptableCollections));
    }
}
