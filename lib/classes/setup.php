<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ExecutableFinder;

class setup
{
    public static function discover_binaries()
    {
        $phpFinder = new PhpExecutableFinder();
        $finder = new ExecutableFinder();

        return [
            'php' => [
                'name'               => 'PHP CLI',
                'binary'             => $phpFinder->find()
            ],
            'convert' => [
                'name'      => 'ImageMagick (convert)',
                'binary'    => $finder->find('convert')
            ],
            'composite' => [
                'name'    => 'ImageMagick (composite)',
                'binary'  => $finder->find('composite')
            ],
            'pdf2swf' => [
                'name'    => 'PDF 2 SWF',
                'binary'  => $finder->find('pdf2swf')
            ],
            'unoconv' => [
                'name'       => 'Unoconv',
                'binary'     => $finder->find('unoconv')
            ],
            'swfextract' => [
                'name'      => 'SWFextract',
                'binary'    => $finder->find('swfextract')
            ],
            'swfrender' => [
                'name'   => 'SWFrender',
                'binary' => $finder->find('swfrender')
            ],
            'MP4Box' => [
                'name'   => 'MP4Box',
                'binary' => $finder->find('MP4Box')
            ],
            'xpdf'   => [
                'name'   => 'XPDF',
                'binary' => $finder->find('xpdf')
            ],
            'ffmpeg' => [
                'name'   => 'FFmpeg',
                'binary' => $finder->find('ffmpeg')
            ],
        ];
    }
}
