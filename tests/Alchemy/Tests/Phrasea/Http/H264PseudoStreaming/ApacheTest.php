<?php

namespace Alchemy\Tests\Phrasea\Http\H264PseudoStream;

use Alchemy\Phrasea\Http\H264PseudoStreaming\Apache;

class ApacheTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideMappingsAndFiles
     */
    public function testGetUrl(array $mapping, $expectedRegExp, $pathfile)
    {
        $mode = new Apache($mapping);
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

        $mapping = array(array(
                             'directory'   => $dir,
                             'mount-point' => 'mp4-videos',
                             'passphrase'  => '123456',
                         ));

        return array(
            array(array(), null, '/path/to/file'),
            array($mapping, null, '/path/to/file'),
            array($mapping, '/^\/mp4-videos\/[a-zA-Z0-9]+\/[0-9a-f]+\/to\/file$/', $file),
        );
    }
}
