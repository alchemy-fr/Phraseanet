<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Fractal;

use Symfony\Component\EventDispatcher\Event;

class GetSerializationEvent extends Event
{
    private $resourceKey;
    private $data;
    private $serialization;

    public function __construct($resourceKey, $data)
    {
        $this->resourceKey = $resourceKey;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getResourceKey()
    {
        return $this->resourceKey;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function setSerialization($serialization)
    {
        $this->serialization = $serialization;
        $this->stopPropagation();
    }

    public function getSerialization()
    {
        return $this->serialization;
    }
}
