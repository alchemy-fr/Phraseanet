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

class BytesConverter extends \Twig_Extension
{
    protected $unit;

    public function __construct()
    {
        $this->unit = [];
        $this->unit['B'] = 'Bytes'; // 8 bits
        $this->unit['KB'] = 'Kilobytes'; // 1024 bytes
        $this->unit['MB'] = 'Megabytes'; // 1048576 bytes
        $this->unit['GB'] = 'Gigabytes'; // 1073741824 bytes
        $this->unit['TB'] = 'Terabytes'; // 1099511627776 bytes
        $this->unit['PB'] = 'Petabytes'; // 1.12589991e15 bytes
        $this->unit['EB'] = 'Exabytes'; // 1.1529215e18 bytes
        $this->unit['ZB'] = 'Zettabytes'; // 1.18059162e21 bytes
        $this->unit['YB'] = 'Yottabytes'; // 1.20892582e24 bytes
    }

    public function getName()
    {
        return 'bytes_converter';
    }

    public function getFilters()
    {
        return [
            'bytesTo*' => new \Twig_Filter_Method($this, 'bytes2Filter')
        ];
    }

    public function bytes2Filter($suffix, $bytes, $precision = 2)
    {
        $auto = ['Human', 'Auto'];
        $unit = array_keys($this->unit);

        if ($bytes <= 0) {
            return '0 ' . $unit[0];
        }

        if ($suffix == '') {
            $suffix = 'Auto';
        }

        if (array_search($suffix, array_merge($auto, $unit)) === false) {
            throw new \Exception('Sorry, you have to specify a legal Byte value or "Human" for automatic. Legal options are: Human, ' . implode(', ', $unit));
        }

        switch ($suffix) {
            case '':
            case 'Human':
            case 'Auto':
                return round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision) . ' ' . $unit[$i];
            default:
                $i = array_search($suffix, $unit);

                return round($bytes / pow(1024, $i), $precision) . ' ' . $unit[$i];
        }
    }
}
