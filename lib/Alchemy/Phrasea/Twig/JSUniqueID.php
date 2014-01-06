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

class JSUniqueID extends \Twig_Extension
{

    /**
     *
     * @return string
     */
    public function getName()
    {
        return 'alchemy_phrasea_jsuniqueid';
    }

    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            'JSUniqueID' => new \Twig_Filter_Method($this, 'JSUniqueID'),
        ];
    }

    public function JSUniqueID($prefix = null, $suffix = null)
    {
        return $prefix . 'id' . str_replace([',', '.'], '-', microtime(true)) . base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36) . $suffix;
    }
}
