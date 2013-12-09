<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ExecutableFinder;

class setup
{
    public static function create_global_values(Application $app, $datas = [])
    {
        $GV = require(__DIR__ . "/../../lib/conf.d/_GV_template.inc");

        $vars = [];
        $error = false;

        foreach ($GV as $section) {
            foreach ($section['vars'] as $variable) {
                if (isset($datas[$variable['name']]) === false) {
                    if (isset($variable['default'])) {
                        if ($variable['type'] === 'boolean') {
                            if ($variable['default'] === true)
                                $datas[$variable['name']] = '1';
                            else
                                $datas[$variable['name']] = '0';
                        } else {
                            $datas[$variable['name']] = $variable['default'];
                        }
                    }
                }

                $type = $variable['type'];
                switch ($variable['type']) {
                    case \registry::TYPE_STRING:
                    case \registry::TYPE_BINARY:
                    case \registry::TYPE_TEXT:
                    case \registry::TYPE_TIMEZONE:
                        $datas[$variable['name']] = (string) trim($datas[$variable['name']]);
                        break;
                    case \registry::TYPE_ENUM:
                        if (!isset($variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        } elseif (!is_array($variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        } elseif (!in_array($datas[$variable['name']], $variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        }
                        break;
                    case \registry::TYPE_ENUM_MULTI:
                        if (!isset($datas[$variable['name']]))
                            $datas[$variable['name']] = null;
                        $datas[$variable['name']] = ($datas[$variable['name']]);
                        break;
                    case \registry::TYPE_BOOLEAN:
                        $datas[$variable['name']] = strtolower($datas[$variable['name']]) === 'true' ? '1' : '0';
                        break;
                    case \registry::TYPE_INTEGER:
                        $datas[$variable['name']] = (int) trim($datas[$variable['name']]);
                        break;
                    default:
                        $error = true;
                        break;
                }

                if (isset($variable['required']) && $variable['required'] === true && trim($datas[$variable['name']]) === '')
                    $variable['error'] = 'required';

                if (isset($variable['end_slash'])) {
                    if ($variable['end_slash'] === true) {
                        $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::addEndSlash($datas[$variable['name']]) : '';
                    }
                    if ($variable['end_slash'] === false) {
                        $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::delEndSlash($datas[$variable['name']]) : '';
                    }
                }

                if ($variable['type'] !== 'integer' && $variable['type'] !== 'boolean')
                    $datas[$variable['name']] = $datas[$variable['name']];

                $vars[$variable['name']] = ['value' => $datas[$variable['name']], 'type'  => $type];
            }
        }

        if ($error === false) {
            foreach ($vars as $key => $values) {
                if ($key == 'GV_sit' && null !== $app['phraseanet.registry']->get('GV_sit')) {
                    continue;
                }
                $app['phraseanet.registry']->set($key, $values['value'], $values['type']);
            }

            return true;
        }

        return false;
    }

    public static function discover_binaries()
    {
        $phpFinder = new PhpExecutableFinder();
        $finder = new ExecutableFinder();

        return [
            'php' => [
                'name'               => 'PHP CLI',
                'binary'             => $phpFinder->find()
            ],
            'phraseanet_indexer' => [
                'name'    => 'Indexeur Phrasea',
                'binary'  => $finder->find('phraseanet_indexer')
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
            'recess' => [
                'name'   => 'Recesss',
                'binary' => $finder->find('recess')
            ],
        ];
    }
}
