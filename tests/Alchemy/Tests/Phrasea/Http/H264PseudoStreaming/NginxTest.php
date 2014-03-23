<?php

namespace Alchemy\Tests\Phrasea\Http\H264PseudoStream;

use Alchemy\Phrasea\Http\H264PseudoStreaming\Nginx;

class NginxTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideMappingsAndFiles
     */
    public function testGetUrl(array $mapping, $expectedRegExp, $pathfile)
    {
        $mode = new Nginx($mapping);
        if (null === $expectedRegExp) {
            $this->assertNull($mode->getUrl($pathfile));
        } else {
            $this->assertRegExp($expectedRegExp, (string) $mode->getUrl($pathfile));
        }
    }

    public function provideMappingsAndFiles()
    {
        $dir = sys_get_temp_dir().'/to/subdef';
        $file = $dir . '/to/file';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        if (!is_file($file)) {
            touch($file);
        }

        $mapping = [[
            'directory'   => $dir,
            'mount-point' => 'mp4-videos',
            'passphrase'  => '123456',
        ]];

        return [
            [[], null, '/path/to/file'],
            [$mapping, null, '/path/to/file'],
            [$mapping, '/^\/mp4-videos\/to\/file\?hash=[a-zA-Z0-9-_+]+&expires=[0-9]+/', $file],
        ];
    }
}
