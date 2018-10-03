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

use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Assert\Assertion;

class OrderElementView
{
    /**
     * @var OrderElement
     */
    private $element;

    /**
     * @var RecordReferenceInterface
     */
    private $record;

    /**
     * @var User
     */
    private $user;

    /**
     * @var \media_subdef[]
     */
    private $subdefs = [];

    /**
     * OrderElementViewModel constructor.
     *
     * @param OrderElement $element
     * @param RecordReferenceInterface $record
     * @param User $user
     */
    public function __construct(OrderElement $element, RecordReferenceInterface $record, User $user)
    {
        $this->element = $element;
        $this->record = $record;
        $this->user = $user;
    }

    /**
     * @return OrderElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @return RecordReferenceInterface
     */
    public function getRecordReference()
    {
        return $this->record;
    }

    /**
     * @return User
     */
    public function getAuthenticatedUser()
    {
        return $this->user;
    }

    /**
     * @param \media_subdef[] $subdefs
     * @return void
     */
    public function setOrderableMediaSubdefs($subdefs)
    {
        Assertion::allIsInstanceOf($subdefs, \media_subdef::class);

        $this->subdefs = $subdefs instanceof \Traversable ? iterator_to_array($subdefs) : $subdefs;
    }

    /**
     * @return \media_subdef[]
     */
    public function getOrderableMediaSubdefs()
    {
        return $this->subdefs;
    }
}
