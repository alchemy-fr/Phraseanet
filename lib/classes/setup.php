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

/**
 *
 * This file MUST NOT contains any default PHP function as
 * mb_*, curl_*, bind_text_domain, _
 *
 * This file is intended to be loaded on setup test
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class setup
{
    public static function create_global_values(Application $app, $datas = array())
    {
        $GV = require(__DIR__ . "/../../lib/conf.d/_GV_template.inc");

        $debug = $log_errors = false;
        $vars = array();

        $error = false;
        $extra_conf = '';

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

                if ($variable['name'] === 'GV_debug' && $datas[$variable['name']] === '1')
                    $debug = true;
                if ($variable['name'] === 'GV_log_errors' && $datas[$variable['name']] === '1')
                    $log_errors = true;

                if ($variable['type'] !== 'integer' && $variable['type'] !== 'boolean')
                    $datas[$variable['name']] = $datas[$variable['name']];

                $vars[$variable['name']] = array('value' => $datas[$variable['name']], 'type'  => $type);
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

        return array(
            'php' => array(
                'name'               => 'PHP CLI',
                'binary'             => $phpFinder->find()
            ),
            'phraseanet_indexer' => array(
                'name'    => 'Indexeur Phrasea',
                'binary'  => $finder->find('phraseanet_indexer')
            ),
            'convert' => array(
                'name'      => 'ImageMagick (convert)',
                'binary'    => $finder->find('convert')
            ),
            'composite' => array(
                'name'    => 'ImageMagick (composite)',
                'binary'  => $finder->find('composite')
            ),
            'pdf2swf' => array(
                'name'    => 'PDF 2 SWF',
                'binary'  => $finder->find('pdf2swf')
            ),
            'unoconv' => array(
                'name'       => 'Unoconv',
                'binary'     => $finder->find('unoconv')
            ),
            'swfextract' => array(
                'name'      => 'SWFextract',
                'binary'    => $finder->find('swfextract')
            ),
            'swfrender' => array(
                'name'   => 'SWFrender',
                'binary' => $finder->find('swfrender')
            ),
            'MP4Box' => array(
                'name'   => 'MP4Box',
                'binary' => $finder->find('MP4Box')
            ),
            'xpdf'   => array(
                'name'   => 'XPDF',
                'binary' => $finder->find('xpdf')
            ),
            'ffmpeg' => array(
                'name'   => 'FFmpeg',
                'binary' => $finder->find('ffmpeg')
            ),
            'recess' => array(
                'name'   => 'Recesss',
                'binary' => $finder->find('recess')
            ),
        );
    }
}
