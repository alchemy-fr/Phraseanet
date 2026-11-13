<?php

namespace MediaVorus\Media;

use JMS\Serializer\SerializerBuilder;
use MediaVorus\TestCase;

abstract class MediaTestCase extends TestCase
{
    protected function getSerializer()
    {
        return SerializerBuilder::create()
            ->enableEnumSupport()
            ->build();
    }
}
