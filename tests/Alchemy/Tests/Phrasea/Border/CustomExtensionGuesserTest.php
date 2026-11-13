<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\CustomExtensionGuesser;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CustomExtensionGuesserTest extends \PhraseanetTestCase
{
    public function testGuess()
    {
        $conf = [
            'mpeg' => 'video/x-romain-neutron',
        ];

        $guesser = new CustomExtensionGuesser($conf);
        $this->assertTrue($guesser->isGuesserSupported());
        $this->assertNull($guesser->guessMimeType(__FILE__));
        $this->assertEquals('video/x-romain-neutron', $guesser->guessMimeType('/path/to/video.mpeg'));
        $this->assertEquals(['video/x-romain-neutron'], $guesser->getMimeTypes('MPEG'));
        $this->assertEquals([], $guesser->getMimeTypes('php'));
        $this->assertEquals(['mpeg'], $guesser->getExtensions('video/x-romain-neutron'));
    }
}
