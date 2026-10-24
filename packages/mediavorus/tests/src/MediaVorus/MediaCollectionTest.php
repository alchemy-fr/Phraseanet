<?php

namespace MediaVorus;

use MediaVorus\Media\MediaInterface;
use MediaVorus\Filter\MediaType;

class MediaCollectionTest extends TestCase
{

    public function testMatch()
    {
        $mediavorus = $this->createMediaVorus();

        $collection = $mediavorus->inspectDirectory(__DIR__ . '/../../files/');
        $audio = $collection->match(new MediaType(MediaInterface::TYPE_AUDIO));

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $audio);
        $this->assertGreaterThan(0, $audio->count());

        foreach ($audio as $audio) {
            $this->assertEquals(MediaInterface::TYPE_AUDIO, $audio->getType());
        }

        $notAudio = $collection->match(new MediaType(MediaInterface::TYPE_AUDIO), true);
        $this->assertGreaterThan(0, $notAudio->count());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $notAudio);

        foreach ($notAudio as $audio) {
            $this->assertFalse(MediaInterface::TYPE_AUDIO === $audio->getType());
        }
    }
}
