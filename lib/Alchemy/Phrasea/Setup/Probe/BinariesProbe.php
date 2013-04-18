<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;

class BinariesProbe extends BinariesRequirements implements ProbeInterface
{
    const REQUIRED_PHP_VERSION = '5.3.3';

    public function __construct(\registryInterface $registry)
    {
        parent::__construct(array_filter(array(
            'php_binary'         => $registry->get('php_binary'),
            'convert_binary'     => $registry->get('convert_binary'),
            'pdf2swf_binary'     => $registry->get('pdf2swf_binary'),
            'unoconv_binary'     => $registry->get('unoconv_binary'),
            'swf_extract_binary' => $registry->get('swf_extract_binary'),
            'swf_render_binary'  => $registry->get('swf_render_binary'),
            'mp4box_binary'      => $registry->get('mp4box_binary'),
            'pdftotext_binary'   => $registry->get('pdftotext_binary'),
            'composite_binary'   => $registry->get('composite_binary'),
            'ffmpeg_binary'      => $registry->get('ffmpeg_binary'),
            'ffprobe_binary'     => $registry->get('ffprobe_binary'),
        )));
    }

    /**
     * {@inheritdoc}
     *
     * @return BinariesProbe
     */
    public static function create(Application $app)
    {
        return new static($app['phraseanet.registry']);
    }
}
