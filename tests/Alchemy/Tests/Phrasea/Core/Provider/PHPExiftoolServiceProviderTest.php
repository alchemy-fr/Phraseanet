<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PHPExiftoolServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\PHPExiftoolServiceProvider', 'exiftool.reader', 'PHPExiftool\Reader'],
            ['Alchemy\Phrasea\Core\Provider\PHPExiftoolServiceProvider', 'exiftool.writer', 'PHPExiftool\Writer'],
            ['Alchemy\Phrasea\Core\Provider\PHPExiftoolServiceProvider', 'exiftool.preview-extractor', 'PHPExiftool\PreviewExtractor'],
        ];
    }
}
