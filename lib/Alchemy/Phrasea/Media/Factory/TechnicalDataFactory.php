<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Factory;

use Alchemy\Phrasea\Media\FloatTechnicalData;
use Alchemy\Phrasea\Media\IntegerTechnicalData;
use Alchemy\Phrasea\Media\StringTechnicalData;
use Alchemy\Phrasea\Media\TechnicalData;

class TechnicalDataFactory
{
    /**
     * @param string $name
     * @param string $value
     * @return TechnicalData
     */
    public function createFromNameAndValue($name, $value)
    {
        if (ctype_digit($value)) {
            return new IntegerTechnicalData($name, $value);
        } elseif (preg_match('/[0-9]?\.[0-9]+/', $value)) {
            return new FloatTechnicalData($name, $value);
        }

        return new StringTechnicalData($name, $value);
    }
}
