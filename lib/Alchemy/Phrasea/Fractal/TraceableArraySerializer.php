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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TraceableArraySerializer extends ArraySerializer
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function collection($resourceKey, array $data)
    {
        /** @var GetSerializationEvent $event */
        $event = $this->dispatcher->dispatch(new GetSerializationEvent($resourceKey, $data), 'fractal.serializer.collection');

        $serialization = parent::collection($resourceKey, $data);

        $event->setSerialization($serialization);

        return $serialization;
    }

    public function item($resourceKey, array $data)
    {
        /** @var GetSerializationEvent $event */
        $event = $this->dispatcher->dispatch(new GetSerializationEvent($resourceKey, $data), 'fractal.serializer.item');

        $serialization = parent::item($resourceKey, $data);

        $event->setSerialization($serialization);

        return $serialization;
    }

    public function null($resourceKey)
    {
        /** @var GetSerializationEvent $event */
        $event = $this->dispatcher->dispatch(new GetSerializationEvent($resourceKey, null), 'fractal.serializer.null');

        $serialization = parent::null($resourceKey);

        $event->setSerialization($serialization);

        return $serialization;
    }

}
