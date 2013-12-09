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
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Exception\Exception as ImagineException;
use MediaVorus\Media\MediaInterface;
use MediaVorus\Media\Image;

class recordutils_image extends recordutils
{
    /**
     * @param Application   $app
     * @param \media_subdef $subdef
     *
     * @return string The path to the stamped file
     */
    public static function stamp(Application $app, \media_subdef $subdef)
    {
        static $palette;

        if (null === $palette) {
            $palette = new RGB();
        }

        $xmlToColor = function ($attr, $ret = [255, 255, 255]) use ($palette) {
            try {
                return $palette->color($attr, 0);
            } catch (ImagineException $e) {
                return $palette->color($ret);
            }
        };

        $base_id = $subdef->get_record()->get_base_id();

        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
            return $subdef->get_pathfile();
        }

        if (!$subdef->is_physically_present()) {
            return $subdef->get_pathfile();
        }

        $rotation = null;
        try {
            $image = $app['mediavorus']->guess($subdef->get_pathfile());
            if (MediaInterface::TYPE_IMAGE === $image->getType()) {
                $rotation = $image->getOrientation();
            }
        } catch (Exception $e) {
            // getting orientation failed but we don't care the reason
        }

        $domprefs = new DOMDocument();

        if (false === $domprefs->loadXML($subdef->get_record()->get_collection()->get_prefs())) {
            return $subdef->get_pathfile();
        }

        if (false === $sxxml = simplexml_load_string($subdef->get_record()->get_caption()->serialize(caption_record::SERIALIZE_XML))) {
            return $subdef->get_pathfile();
        }

        $xpprefs = new DOMXPath($domprefs);
        $stampNodes = $xpprefs->query('/baseprefs/stamp');
        if ($stampNodes->length == 0) {
            return $subdef->get_pathfile();
        }

        $pathIn = $subdef->get_path() . $subdef->get_file();
        $pathOut = $subdef->get_path() . 'stamp_' . $subdef->get_file();

        $vars = $xpprefs->query('/baseprefs/stamp/*/var');

        // no way to cache when date changes
        for ($i = 0; $i < $vars->length; $i++) {
            if (strtoupper($vars->item($i)->getAttribute('name')) == 'DATE') {
                @unlink($pathOut);
                break;
            }
        }

        // get from cache ?
        if (is_file($pathOut)) {
            return $pathOut;
        }

        // open the document
        $image_in = $app['imagine']->open($pathIn);
        $image_size = $image_in->getSize();
        switch ($rotation) {
            case Image::ORIENTATION_90:
                $image_width = $image_size->getHeight();
                $image_height = $image_size->getWidth();
                $image_in->rotate(90);
                $rotation = '90';
                break;
            case Image::ORIENTATION_270:
                $image_width = $image_size->getHeight();
                $image_height = $image_size->getWidth();
                $image_in->rotate(270);
                break;
            case Image::ORIENTATION_180:
                $image_width = $image_size->getWidth();
                $image_height = $image_size->getHeight();
                $image_in->rotate(180);
                break;
            default:
                $image_width = $image_size->getWidth();
                $image_height = $image_size->getHeight();
                break;
        }

        // open the logo
        $logo_phywidth = $logo_phyheight = 0; // physical size
        $logo_file = $app['root.path'] . '/config/stamp/' . $base_id;
        try {
            $logo_obj = $app['imagine']->open($logo_file);
            $logo_size = $logo_obj->getSize();
            $logo_phywidth = $logo_size->getWidth();
            $logo_phyheight = $logo_size->getHeight();
        } catch (ImagineException $e) {

        }

        $tables = [
            'TOP' => ['h'    => 0, 'rows' => []],
            'TOP-OVER' => ['h'    => 0, 'rows' => []],
            'BOTTOM' => ['h'    => 0, 'rows' => []],
            'BOTTOM-OVER' => ['h'    => 0, 'rows' => []]
        ];

        for ($istamp = 0; $istamp < $stampNodes->length; $istamp++) {
            $stamp = $stampNodes->item($istamp);

            $stamp_background = $xmlToColor($stamp->getAttribute('background'), [255, 255, 255]);

            $stamp_position = strtoupper(trim($stamp->getAttribute('position')));
            if (!in_array($stamp_position, ['TOP', 'TOP-OVER', 'BOTTOM-OVER', 'BOTTOM'])) {
                $stamp_position = 'BOTTOM';
            }

            // replace "var" nodes with their value
            $vars = $xpprefs->query('*/var', $stamp);
            for ($i = 0; $i < $vars->length; $i++) {
                $varval = '';
                $n = $vars->item($i);
                switch (strtoupper($n->getAttribute('name'))) {
                    case 'DATE':
                        if (!($format = $n->getAttribute('format'))) {
                            $format = 'Y/m/d H:i:s';
                        }
                        $varval = date($format);
                        @unlink($pathOut);  // no cache possible when date changes
                        break;
                    case 'RECORD_ID':
                        $varval = $subdef->get_record()->get_record_id();
                        break;
                }
                $n->parentNode->replaceChild($domprefs->createTextNode($varval), $n);
            }

            // replace "field" nodes with their values
            $fields = $xpprefs->query('*/field', $stamp);
            for ($i = 0; $i < $fields->length; $i++) {
                $fldval = '';
                $n = $fields->item($i);
                $fieldname = $n->getAttribute('name');

                $x = $sxxml->description->{$fieldname};
                if (is_array($x)) {
                    foreach ($x as $v) {
                        $fldval .= ( $fldval ? '; ' : '') . (string) $v;
                    }
                } else {
                    $fldval .= ( $fldval ? '; ' : '') . (string) $x;
                }
                $n->parentNode->replaceChild($domprefs->createTextNode($fldval), $n);
            }

            $domprefs->normalizeDocument();

            $text_width = $image_width;

            $logopos = null;

            // compute logo position / size
            $logo_reswidth = 0;
            $logo_resheight = 0;
            if ($logo_phywidth > 0 && $logo_phyheight > 0) {

                $v = $xpprefs->query('logo', $stamp);
                if ($v->length > 0) {
                    $logo_reswidth = $logo_phywidth;
                    $logo_resheight = $logo_phyheight;
                    $logopos = @strtoupper($v->item(0)->getAttribute('position'));
                    if (($logowidth = trim($v->item(0)->getAttribute('width'))) != '') {
                        if (substr($logowidth, -1) == '%') {
                            $logo_reswidth = (int) ($logowidth * $image_width / 100);
                        } else {
                            $logo_reswidth = (int) $logowidth;
                        }
                        $logo_resheight = (int) ($logo_phyheight * ($logo_reswidth / $logo_phywidth));
                    }

                    if ($logopos == 'LEFT' || $logopos == 'RIGHT') {
                        if ($logo_reswidth > $image_width / 2) {
                            // logo too large, resize please
                            $logo_reswidth = (int) ($image_width / 2);
                            $logo_resheight = (int) ($logo_phyheight * ($logo_reswidth / $logo_phywidth));
                        }
                        $text_width -= $logo_reswidth;
                        if ($logopos == 'LEFT') {
                            $logo_xpos = 0;
                        } else {
                            $logo_xpos = ($image_width - $logo_reswidth);
                        }
                    }
                }
            }

            // compute text blocks
            $txth = 0;
            $txtblock = [];
            $texts = $xpprefs->query('text', $stamp);
            $fontsize = "100%";
            for ($i = 0; $i < $texts->length; $i++) {
                if (($tmpfontsize = trim($texts->item($i)->getAttribute('size'))) != '') {
                    if (substr($tmpfontsize, -1) == '%') {
                        $tmpfontsize = (int) ($tmpfontsize * $image_width / 4000);
                    } else {
                        $tmpfontsize = (int) $tmpfontsize;
                    }
                    $fontsize = $tmpfontsize;
                }

                if ($fontsize < 2) {
                    $fontsize = 2;
                } elseif ($fontsize > 300) {
                    $fontsize = 300;
                }

                $txtline = $texts->item($i)->nodeValue;

                if ($txtline != '') {
                    $wrap = static::wrap($app['imagine'], $fontsize, 0, __DIR__ . '/arial.ttf', $txtline, $text_width);
                    $txtblock[] = [
                        'fontsize'  => $fontsize,
                        'fontcolor' => $xmlToColor($texts->item($i)->getAttribute('color'), [0, 0, 0]),
                        'h'     => $wrap['toth'],
                        'lines' => $wrap['l']
                    ];
                    $txth += $wrap['toth'];
                }
            }

            $stampheight = max($logo_resheight, $txth);

            if ($stamp_position == 'TOP-OVER' || $stamp_position == 'BOTTOM-OVER') {
                if ($tables[$stamp_position]['h'] + $stampheight > $image_height) {
                    $stampheight = $image_height - $tables[$stamp_position]['h'];
                }
            }
            if ($stampheight <= 0) {
                continue;
            }

            // create the block
            $imfg = $app['imagine']->create(new Box($image_width, $stampheight), $stamp_background);

            // copy the logo
            if ($logo_reswidth > 0 && $logo_resheight > 0) {
                if ($logo_reswidth != $logo_phywidth) {
                    $imfg->paste(
                        $logo_obj->copy()->resize(new Box($logo_reswidth, $logo_resheight)),
                        new Point($logo_xpos, 0)
                    );
                } else {
                    $imfg->paste($logo_obj, new Point($logo_xpos, 0));
                }
            }

            // fill with text
            $draw = $imfg->draw();
            $txt_ypos = 0;
            foreach ($txtblock as $block) {
                $font = $app['imagine']->font(__DIR__ . '/arial.ttf', $block['fontsize'], $block['fontcolor']);
                foreach ($block['lines'] as $line) {
                    if ($line['t'] != '') {
                        $draw->text($line['t'], $font, new Point($logo_reswidth, $txt_ypos), 0);
                    }
                    $txt_ypos += $line['h'];
                }
            }

            // memo into one of the 4 buffer
            $tables[$stamp_position]['rows'][] = [
                'x0'  => 0,
                'y0'  => $tables[$stamp_position]['h'],
                'w'   => $image_width,
                'h'   => $stampheight,
                'img' => $imfg
            ];

            $tables[$stamp_position]['h'] += $stampheight;
        }

        $newh = $tables['TOP']['h'] + $image_height + $tables['BOTTOM']['h'];

        // create the output image
        $image_out = $app['imagine']->create(new Box($image_width, $newh), $palette->color("FFFFFF", 64));

        // paste the input image into
        $image_out->paste($image_in, new Point(0, $tables['TOP']['h']));

        // fix the coordinates
        foreach ($tables['TOP-OVER']['rows'] as $k => $row) {
            $tables['TOP-OVER']['rows'][$k]['y0'] += $tables['TOP']['h'];
        }
        foreach ($tables['BOTTOM-OVER']['rows'] as $k => $row) {
            $tables['BOTTOM-OVER']['rows'][$k]['y0'] += $tables['TOP']['h'] + $image_height - $tables['BOTTOM-OVER']['h'];
        }
        foreach ($tables['BOTTOM']['rows'] as $k => $row) {
            $tables['BOTTOM']['rows'][$k]['y0'] += $tables['TOP']['h'] + $image_height;
        }

        // paste blocks
        foreach (['TOP', 'TOP-OVER', 'BOTTOM-OVER', 'BOTTOM'] as $ta) {
            foreach ($tables[$ta]['rows'] as $row) {
                if ($row['h'] > 0) {
                    $image_out->paste($row['img'], new Point($row['x0'], $row['y0']));
                }
            }
        }

        // save the output
        $image_out->save($pathOut);

        if (is_file($pathOut)) {
            return $pathOut;
        }

        return $subdef->get_pathfile();
    }

    /**
     *
     * @param Application   $app
     * @param \media_subdef $subdef
     *
     * @return boolean|string
     */
    public static function watermark(Application $app, \media_subdef $subdef)
    {
        static $palette;

        if (null === $palette) {
            $palette = new RGB();
        }

        $base_id = $subdef->get_record()->get_base_id();

        if ($subdef->get_name() !== 'preview') {
            return $subdef->get_pathfile();
        }

        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
            return $subdef->get_pathfile();
        }

        if (!$subdef->is_physically_present()) {
            return false;
        }

        $pathIn = $subdef->get_path() . $subdef->get_file();

        if (!is_file($pathIn)) {
            return false;
        }

        $pathOut = $subdef->get_path() . 'watermark_' . $subdef->get_file();

        // cache
        if (is_file($pathOut)) {
            return $pathOut;
        }

        $in_image = $app['imagine']->open($pathIn);
        $in_size = $in_image->getSize();
        $in_w = $in_size->getWidth();
        $in_h = $in_size->getHeight();

        $wm_file = $app['root.path'] . '/config/wm/' . $base_id;
        if (file_exists($wm_file)) {
            $wm_image = $app['imagine']->open($wm_file);
            $wm_size = $wm_image->getSize();
            $wm_w = $wm_size->getWidth();
            $wm_h = $wm_size->getHeight();

            if (($wm_w / $wm_h) > ($in_w / $in_h)) {
                $wm_size = $wm_size->widen($in_w);
            } else {
                $wm_size = $wm_size->heighten($in_h);
            }
            $wm_image->resize($wm_size);

            $in_image->paste($wm_image, new Point(($in_w - $wm_size->getWidth()) >> 1, ($in_h - $wm_size->getHeight()) >> 1))->save($pathOut);
        } else {
            $collname = $subdef->get_record()->get_collection()->get_name();
            $draw = $in_image->draw();
            $black = $palette->color("000000");
            $white = $palette->color("FFFFFF");
            $draw->line(new Point(0, 1), new Point($in_w - 2, $in_h - 1), $black);
            $draw->line(new Point(1, 0), new Point($in_w - 1, $in_h - 2), $white);
            $draw->line(new Point(0, $in_h - 2), new Point($in_w - 2, 0), $black);
            $draw->line(new Point(1, $in_h - 1), new Point($in_w - 1, 1), $white);

            $fsize = max(8, (int) (max($in_w, $in_h) / 30));
            $fonts = [
                $app['imagine']->font(__DIR__ . '/arial.ttf', $fsize, $black),
                $app['imagine']->font(__DIR__ . '/arial.ttf', $fsize, $white)
            ];
            $testbox = $fonts[0]->box($collname, 0);
            $tx_w = min($in_w, $testbox->getWidth());
            $tx_h = min($in_h, $testbox->getHeight());

            $x0 = max(1, ($in_w - $tx_w) >> 1);
            $y0 = max(1, ($in_h - $tx_h) >> 1);
            for ($i = 0; $i <= 1; $i++) {
                $x = max(1, ($in_w >> 2) - ($tx_w >> 1));
                $draw->text($collname, $fonts[$i], new Point($x - $i, $y0 - $i), 0);
                $x = max(1, $in_w - $x - $tx_w);
                $draw->text($collname, $fonts[$i], new Point($x - $i, $y0 - $i), 0);

                $y = max(1, ($in_h >> 2) - ($tx_h >> 1));
                $draw->text($collname, $fonts[$i], new Point($x0 - $i, $y - $i), 0);
                $y = max(1, $in_h - $y - $tx_h);
                $draw->text($collname, $fonts[$i], new Point($x0 - $i, $y - $i), 0);
            }
        }

        $in_image->save($pathOut);

        if (is_file($pathOut)) {
            return $pathOut;
        }

        return false;
    }
    /**
     *
     * @param  int    $fontSize
     * @param  int    $angle
     * @param  string $fontFace
     * @param  string $string
     * @param  int    $width
     * @return array
     */
    protected static function wrap(ImagineInterface $imagine, $fontSize, $angle, $fontFace, $string, $width)
    {
        static $palette;

        if (null === $palette) {
            $palette = new RGB();
        }

        // str 'Op' used to calculate linespace
        $font = $imagine->font($fontFace, $fontSize, $palette->color("000000", 0));
        $testbox = $font->box("0p", $angle);
        $height = $testbox->getHeight();
        $testbox = $font->box("M", $angle); // 1 em
        $dy = $testbox->getHeight();
        $toth = 0;
        $ret = [];

        foreach (explode("\n", $string) as $lig) {
            if ($lig == '') {
                $ret[] = ['w' => 0, 'h' => $dy, 't' => ''];
                $toth += $dy;
            } else {
                $twords = [];
                $iword = -1;
                $lastc = '';
                $length = strlen($lig);
                for ($i = 0; $i < $length; $i++) {
                    $c = $lig[$i];
                    if ($iword == -1 || (ctype_space($c) && !ctype_space($lastc))) {
                        $twords[++$iword] = [($part = 0) => '', 1           => ''];
                    }
                    if (!ctype_space($c) && $part == 0) {
                        $part++;
                    }
                    $twords[$iword][$part] .= $lastc = $c;
                }
                if ($iword >= 0 && $twords[0][1] != '') {
                    $buff = '';
                    $lastw = $lasth = 0;
                    foreach ($twords as $i => $wrd) {
                        $test = $buff . $wrd[0] . $wrd[1];
                        $testbox = $font->box($test, $angle);
                        $w = $testbox->getWidth();
                        $h = $testbox->getHeight();
                        if ($i > 0 && $testbox->getWidth() > $width) {
                            $ret[] = ['w'   => $lastw, 'h'   => $lasth, 't'   => $buff];
                            $toth += $lasth;
                            $buff = $wrd[1];
                        } else {
                            $buff = $test;
                        }
                        $lastw = $w;
                        $lasth = $h;
                    }
                    if ($buff != '') {
                        $ret[] = ['w' => $lastw, 'h' => $lasth, 't' => $buff];
                        $toth += $lasth;
                    }
                }
            }
        }

        return ['toth' => $toth, 'l'    => $ret, 'h'    => $height, 'dy'   => $dy];
    }
}
