<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Twig;

class Fit extends \Twig_Extension
{

    public function getName()
    {
        return 'fit';
    }

    public function getFunctions()
    {
        return [
            'fitIn' => new \Twig_Function_Method($this, 'fitIn')
        ];
    }

    public function fitIn(array $content, array $box)
    {
        $contentRatio = $content['width'] / $content['height'];
        $boxRatio = $box['width'] / $box['height'];

        if ($contentRatio > $boxRatio) {
            if ($box['width'] > $content['width']) {
                $width = $content['width'];
            } else {
                $width = $box['width'];
            }

            $height = $width / $content['width'] * $content['height'];

            $left = 0;
            $top = 0;

            if ($contentRatio > 1) {
                // mode landscape
                $top = ($box['height'] - $height) / 2;
            } elseif ($contentRatio < 1) {
                // mode portrait
                $left = ($box['width'] - $width) / 2;
            } else {
                // square mode
                $top = ($box['height'] - $height) / 2;
                $left = ($box['width'] - $width) / 2;
            }
        } else {
            if ($box['height'] > $content['height']) {
                $height = $content['height'];
            } else {
                $height = $box['height'];
            }

            $width = $height * $contentRatio;

            $left = 0;
            $top = 0;

            if ($contentRatio > 1) {
                // mode landscape
                $top = ($box['height'] - $height) / 2;
            } elseif ($contentRatio < 1) {
                // mode portrait
                $left = ($box['width'] - $width) / 2;;
            } else {
                // square mode
                $top = ($box['height'] - $height) / 2;
                $left = ($box['width'] - $width) / 2;;
            }
        }

        return [
            'width' => round($width),
            'height' => round($height),
            'top' => round($top),
            'left' => round($left)
        ];
    }
}
