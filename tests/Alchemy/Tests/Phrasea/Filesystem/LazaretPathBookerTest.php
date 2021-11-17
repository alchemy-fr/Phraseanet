<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Filesystem;

use Alchemy\Phrasea\Filesystem\LazaretPathBooker;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

// use Symfony\Component\Filesystem\Filesystem;


class LazaretPathBookerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp()
    {
        $this->root = vfsStream::setup();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::bookLazaretPathfile
     */
    public function testBookFile()
    {
        $mockedPathResolver = function ($path) {
            return $path;
        };

        $sut = new LazaretPathBooker(new Filesystem(), $this->root->url(), $mockedPathResolver);

        $file1 = $sut->bookFile('babebibobu.txt');
        $file2 = $sut->bookFile('babebibobu.txt');

        $this->assertNotEquals($file2, $file1);

        $this->assertCount(2, $this->root->getChildren());
        $this->assertTrue($this->root->hasChild(vfsStream::path($file1)));
        $this->assertTrue($this->root->hasChild(vfsStream::path($file2)));
    }
}
