<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Fractal;

use League\Fractal\TransformerAbstract;

interface TransformerResolver
{
    /**
     * @param string $scopeIdentifier full scope name, empty string for root
     * @return TransformerAbstract
     */
    public function resolve($scopeIdentifier);
}
