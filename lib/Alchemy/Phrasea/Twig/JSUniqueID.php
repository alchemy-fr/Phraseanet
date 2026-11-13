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

class JSUniqueID extends \Twig\Extension\AbstractExtension
{
    /**
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('JSUniqueID', [$this, 'JSUniqueID']),
        ];
    }

    public function JSUniqueID($prefix = null, $suffix = null)
    {
        return $prefix . 'id' . str_replace([',', '.'], '-', microtime(true)) . base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36) . $suffix;
    }
}
