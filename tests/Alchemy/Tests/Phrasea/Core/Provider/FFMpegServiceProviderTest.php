<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FFMpegServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\FFMpegServiceProvider', 'ffmpeg.ffprobe', 'FFMpeg\FFProbe'],
            ['Alchemy\Phrasea\Core\Provider\FFMpegServiceProvider', 'ffmpeg.ffmpeg', 'FFMpeg\FFMpeg'],
        ];
    }
}
