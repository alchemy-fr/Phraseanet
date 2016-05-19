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

use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;

class NullResource extends ResourceAbstract
{
    /**
     * @param callable|TransformerAbstract $transformer
     * @param null|string $resourceKey
     */
    public function __construct($transformer, $resourceKey = null)
    {
        parent::__construct(null, $transformer, $resourceKey);
    }
}
