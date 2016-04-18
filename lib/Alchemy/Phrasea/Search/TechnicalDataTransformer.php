<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Alchemy\Phrasea\Media\TechnicalData;
use League\Fractal\TransformerAbstract;

class TechnicalDataTransformer extends TransformerAbstract
{
    public function transform(TechnicalData $technicalData)
    {
        return [
            'name' => $technicalData->getName(),
            'value' => $technicalData->getValue(),
        ];
    }
}
