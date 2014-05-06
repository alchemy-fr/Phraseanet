<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
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
        return array(
            'fitIn' => new \Twig_Function_Method($this, 'fitIn')
        );
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
            $top = ($box['height'] - $height) / 2;
        } else {
            if ($box['height'] > $content['height']) {
                $height = $content['height'];
            } else {
                $height = $box['height'];
            }

            $width = $height * $contentRatio;
            $top = ($box['height'] - $content['height'] / 2);
        }

        return array(
            'width' => round($width),
            'height' => round($height),
            'top' => round($top)
        );
    }
}
