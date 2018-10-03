<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;

class BinariesProbe extends BinariesRequirements implements ProbeInterface
{
    public function __construct(array $binaries)
    {
        parent::__construct(array_filter([
            'php_binary'         => isset($binaries['php_binary']) ? $binaries['php_binary'] : null,
            'pdf2swf_binary'     => isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : null,
            'unoconv_binary'     => isset($binaries['unoconv_binary']) ? $binaries['unoconv_binary'] : null,
            'swf_extract_binary' => isset($binaries['swf_extract_binary']) ? $binaries['swf_extract_binary'] : null,
            'swf_render_binary'  => isset($binaries['swf_render_binary']) ? $binaries['swf_render_binary'] : null,
            'mp4box_binary'      => isset($binaries['mp4box_binary']) ? $binaries['mp4box_binary'] : null,
            'pdftotext_binary'   => isset($binaries['pdftotext_binary']) ? $binaries['pdftotext_binary'] : null,
            'ffmpeg_binary'      => isset($binaries['ffmpeg_binary']) ? $binaries['ffmpeg_binary'] : null,
            'ffprobe_binary'     => isset($binaries['ffprobe_binary']) ? $binaries['ffprobe_binary'] : null,
        ]));
    }

    /**
     * {@inheritdoc}
     *
     * @return BinariesProbe
     */
    public static function create(Application $app)
    {
        return new static($app['conf']->get(['main', 'binaries']));
    }
}
