<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Process\ProcessBuilder;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class recordutils_image extends recordutils
{

    /**
     *
     * @param  int    $fontSize
     * @param  int    $angle
     * @param  string $fontFace
     * @param  string $string
     * @param  int    $width
     * @return Array
     */
    protected function wrap($fontSize, $angle, $fontFace, $string, $width)
    {
        $ret = array();

        // str 'Op' used to calculate linespace
        $testbox = imagettfbbox($fontSize, $angle, $fontFace, 'Op');
        $height = abs($testbox[1] - ($dy = $testbox[7]));

        foreach (explode("\n", $string) as $lig) {
            if ($lig == '') {
                $ret[] = '';
            } else {
                $buff = '';
                foreach (explode(' ', $lig) as $wrd) {
                    $test = $buff . ($buff ? ' ' : '') . $wrd;
                    $testbox = imagettfbbox($fontSize, $angle, $fontFace, $test);
                    if (abs($testbox[2] - $testbox[0]) > $width) {
                        if ($buff == '') {
                            $ret[] = $test;
                        } else {
                            $ret[] = $buff;
                            $buff = $wrd;
                        }
                    } else {
                        $buff = $test;
                    }
                }
                if ($buff != '')
                    $ret[] = $buff;
            }
        }

        return(array('l'  => $ret, 'h'  => $height, 'dy' => $dy));
    }

    /**
     *
     * @param  int     $bas
     * @param  int     $rec
     * @param  boolean $hd
     * @return string
     */
    public static function stamp(\media_subdef $subdef, $bas, $rec, $hd = false)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $registry = $appbox->get_registry();
        $base_id = $subdef->get_record()->get_base_id();

        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
            return $subdef->get_pathfile();
        }

        if ( ! $subdef->is_physically_present()) {
            return $subdef->get_pathfile();
        }

        if ( ! $registry->get('convert_binary')) {
            return $subdef->get_pathfile();
        }

        $domprefs = new DOMDocument();

        if (false === $domprefs->loadXML($subdef->get_record()->get_collection()->get_prefs())) {
            return $subdef->get_pathfile();
        }

        if (false === $sxxml = simplexml_load_string($subdef->get_record()->get_caption()->serialize(caption_record::SERIALIZE_XML))) {
            return $subdef->get_pathfile();
        }

        $xpprefs = new DOMXPath($domprefs);

        $pathIn = $subdef->get_path() . $subdef->get_file();
        $pathOut = $subdef->get_path() . 'stamp_' . $subdef->get_file();
        $pathTmpStamp = $registry->get('GV_RootPath') . 'tmp/' . time() . '-stamptmp_' . $subdef->get_file();

        if ($xpprefs->query('/baseprefs/stamp')->length == 0) {
            return $subdef->get_pathfile();
        }

        $vars = $xpprefs->query('/baseprefs/stamp/*/var');
        for ($i = 0; $i < $vars->length; $i ++ ) {
            $varval = '';
            $n = $vars->item($i);
            switch (strtoupper($n->getAttribute('name'))) {
                case 'DATE':
                    if ( ! ($format = $n->getAttribute('format')))
                        $format = 'Y/m/d H:i:s';
                    $varval = date($format);
                    @unlink($pathOut);
                    break;
                case 'RECORD_ID':
                    $varval = $rec;
                    break;
            }
            $n->parentNode->replaceChild($domprefs->createTextNode($varval), $n);
        }

        if (is_file($pathOut)) {
            return $pathOut;
        }

        $fields = $xpprefs->query('/baseprefs/stamp/*/field');
        for ($i = 0; $i < $fields->length; $i ++ ) {
            $fldval = '';
            $n = $fields->item($i);
            $fieldname = $n->getAttribute('name');

            $x = $sxxml->description->{$fieldname};
            if (is_array($x)) {
                foreach ($x as $v)
                    $fldval .= ( $fldval ? '; ' : '') . (string) $v;
            } else {
                $fldval .= ( $fldval ? '; ' : '') . (string) $x;
            }
            $n->parentNode->replaceChild($domprefs->createTextNode($fldval), $n);
        }

        $domprefs->normalizeDocument();

        if ( ! ($tailleimg = @getimagesize($pathIn))) {
            return false;
        }

        $image_width = $tailleimg[0];
        $image_height = $tailleimg[1];

        $text_xpos = 0;
        $text_width = $image_width;

        $logofile = $registry->get('GV_RootPath') . 'config/stamp/' . $base_id;
        $logopos = null;
        $imlogo = null; // gd image
        $logo_phywidth = $logo_phyheight = 0; // physical size
        $logo_reswidth = $logo_resheight = 0; // resized size

        if (is_array($logosize = @getimagesize($logofile))) {
            $logo_reswidth = $logo_phywidth = $logosize[0];
            $logo_resheight = $logo_phyheight = $logosize[1];

            $v = $xpprefs->query('/baseprefs/stamp/logo');
            if ($v->length > 0) {
                $logopos = @strtoupper($v->item(0)->getAttribute('position'));
                if (($logowidth = trim($v->item(0)->getAttribute('width'))) != '') {
                    if (substr($logowidth, -1) == '%')
                        $logo_reswidth = (int) ($logowidth * $image_width / 100);
                    else
                        $logo_reswidth = (int) $logowidth;
                    $logo_resheight = (int) ($logo_phyheight *
                        ($logo_reswidth / $logo_phywidth));
                }

                if (($logopos == 'LEFT' || $logopos == 'RIGHT') &&
                    $logo_phywidth > 0 && $logo_phyheight > 0) {
                    switch ($logosize['mime']) {
                        case 'image/gif':
                            $imlogo = @imagecreatefromgif($logofile);
                            break;
                        case 'image/png':
                            $imlogo = @imagecreatefrompng($logofile);
                            break;
                        case 'image/jpeg':
                        case 'image/pjpeg':
                            $imlogo = @imagecreatefromjpeg($logofile);
                            break;
                    }
                    if ($imlogo) {
                        if ($logo_reswidth > $image_width / 2) {
                            // logo too large, resize please
                            $logo_reswidth = (int) ($image_width / 2);
                            $logo_resheight = (int) ($logo_phyheight *
                                ($logo_reswidth / $logo_phywidth));
                        }

                        $text_width -= ( $text_xpos = $logo_reswidth);
                    }
                }
            }
        }
        $txth = 0;
        $txtblock = array();
        $texts = $xpprefs->query('/baseprefs/stamp/text');
        $fontsize = "100%";
        for ($i = 0; $i < $texts->length; $i ++ ) {
            if (($tmpfontsize = trim($texts->item($i)->getAttribute('size'))) != '') {
                if (substr($tmpfontsize, -1) == '%')
                    $tmpfontsize = (int) ($tmpfontsize * $image_width / 4000);
                else
                    $tmpfontsize = (int) $tmpfontsize;
                $fontsize = $tmpfontsize;
            }

            if ($fontsize < 2)
                $fontsize = 2;
            elseif ($fontsize > 300)
                $fontsize = 300;

            $txtline = $texts->item($i)->nodeValue;

            if ($txtline != '') {
                $txtlines = recordutils_image::wrap(
                        $fontsize, 0, __DIR__ . '/arial.ttf', $txtline, $text_width
                );

                foreach ($txtlines['l'] as $txtline) {
                    $txtblock[] = array(
                        'x'  => $text_xpos,
                        'dy' => $txtlines['dy'],
                        'w'  => $text_width,
                        'h'  => $txtlines['h'],
                        't'  => $txtline,
                        's'  => $fontsize
                    );
                    $txth += $txtlines['h'];
                }
            }
        }

        $stampheight = max($logo_resheight, $txth);

        $im = imagecreatetruecolor($image_width, $stampheight);

        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $image_width, $stampheight, $white);
        imagecolordeallocate($im, $white);

        if ($imlogo) {
            if ($logo_reswidth != $logo_phywidth) {
                imagecopyresampled($im, $imlogo, 0, 0, //  dst_x, dst_y
                                   0, 0, //  src_x, src_y
                                   $logo_reswidth, //  dst_w
                                   $logo_resheight, //  dst_h
                                   $logo_phywidth, //  src_w
                                   $logo_phyheight  //  src_h
                );
            } else {
                imagecopy($im, $imlogo, 0, 0, //  dst_x, dst_y
                          0, 0, //  src_x, src_y
                          $logo_phywidth, //  src_w
                          $logo_phyheight  //  src_h
                );
            }
        }

        if (count($txtblock) >= 0) {
            $black = imagecolorallocate($im, 0, 0, 0);
            $txt_ypos = 0; //$txtblock[0]['h'];
            foreach ($txtblock as $block) {
                imagettftext($im, $block['s'], 0, $block['x'], $txt_ypos - $block['dy'], $black, __DIR__ . '/arial.ttf', $block['t']);
                $txt_ypos += $block['h'];
            }
            imagecolordeallocate($im, $black);
        }
        imagejpeg($im, $pathTmpStamp, 80);

        imagedestroy($im);

        $newh = $image_height + $stampheight;

        $builder = ProcessBuilder::create(array($registry->get('convert_binary')));
        $builder->add('-extent')
            ->add($image_width . 'x' . $newh)
            ->add('-draw')
            ->add('image SrcOver 0,' . $image_height . ' ' . $image_width . ',' . $stampheight . '"' . $pathTmpStamp . '"')
            ->add($pathIn)
            ->add($pathOut);

        $builder->getProcess()->run();

        unlink($pathTmpStamp);

        if (is_file($pathOut)) {
            return $pathOut;
        }

        return $subdef->get_pathfile();
    }

    /**
     *
     * @param \media_subdef $subdef
     * @return boolean|string
     */
    public static function watermark(\media_subdef $subdef)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $registry = $appbox->get_registry();
        $base_id = $subdef->get_record()->get_base_id();

        if ($subdef->get_name() !== 'preview') {
            return $subdef->get_pathfile();
        }

        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
            return $subdef->get_pathfile();
        }

        if ( ! $subdef->is_physically_present()) {
            return $subdef->get_pathfile();
        }

        $pathIn = $subdef->get_path() . $subdef->get_file();

        $pathOut = $subdef->get_path() . 'watermark_' . $subdef->get_file();

        if (!is_file($pathIn)) {
            return false;
        }

        if (is_file($pathOut)) {
            return $pathOut;
        }

        if ($registry->get('GV_pathcomposite') &&
            file_exists($registry->get('GV_RootPath') . 'config/wm/' . $base_id)) {

            $builder = ProcessBuilder::create(array(
                    $registry->get('composite_binary'),
                    $registry->get('GV_RootPath') . 'config/wm/' . $base_id,
                    $pathIn,
                    '-strip', '-watermark', '90%', '-gravity', 'center',
                    $pathOut
                ));

            $builder->getProcess()->run();
        } elseif ($registry->get('convert_binary')) {
            $collname = phrasea::bas_names($base_id);
            $tailleimg = @getimagesize($pathIn);
            $max = ($tailleimg[0] > $tailleimg[1] ? $tailleimg[0] : $tailleimg[1]);

            $tailleText = (int) ($max / 30);

            if ($tailleText < 8)
                $tailleText = 8;

            if ($tailleText > 12)
                $decalage = 2;
            else
                $decalage = 1;

            $builder = ProcessBuilder::create(array(
                $registry->get('convert_binary'),
                '-fill', 'white', '-draw', 'line 0,0 ' . $tailleimg[0] . ',' . $tailleimg[1] . '',
                '-fill', 'black', '-draw', 'line 1,0 ' . $tailleimg[0] + 1 . ',' . $tailleimg[1] . '',
                '-fill', 'white', '-draw', 'line ' . $tailleimg[0] . ',0 0,' . $tailleimg[1] . '',
                '-fill', 'black', '-draw', 'line ' . ($tailleimg[0] + 1) . ',0 0,' . $tailleimg[1] . '',
                '-fill', 'white', '-gravity', 'NorthWest', '-pointsize', $tailleText, '-draw', 'text 0,0 ' . $collname,
                '-fill', 'black', '-gravity', 'NorthWest', '-pointsize', $tailleText, '-draw', 'text ' . $decalage . ', 1 ' . $collname,
                '-fill', 'white', '-gravity', 'center', '-pointsize', $tailleText, '-draw', 'text 0,0 ' . $collname,
                '-fill', 'black', '-gravity', 'center', '-pointsize', $tailleText, '-draw', 'text ' . $decalage . ', 1 ' . $collname,
                '-fill', 'white', '-gravity', 'SouthEast', '-pointsize', $tailleText, '-draw', 'text 0,0 ' . $collname,
                '-fill', 'black', '-gravity', 'SouthEast', '-pointsize', $tailleText, '-draw', 'text ' . $decalage . ', 1 ' . $collname,
                $pathIn, $pathOut
            ));

            $process = $builder->getProcess();
            $process->run();
        }

        if (is_file($pathOut)) {
            return $pathOut;
        }

        return false;
    }
}
