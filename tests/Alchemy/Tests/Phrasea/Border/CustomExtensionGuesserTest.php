<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\CustomExtensionGuesser;

class CustomExtensionGuesserTest extends \PhraseanetTestCase
{
    public function testGuess()
    {
        $conf = [
            'mpeg' => 'video/x-romain-neutron',
        ];

        $guesser = new CustomExtensionGuesser($conf);
        $this->assertNull($guesser->guess(__FILE__));
        $this->assertEquals('video/x-romain-neutron', $guesser->guess('/path/to/video.mpeg'));
    }
}
