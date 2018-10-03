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

class SearchResultTransformerResolver implements TransformerResolver
{
    /**
     * @var \ArrayAccess|\League\Fractal\TransformerAbstract[]
     */
    private $transformers;

    /**
     * @param TransformerAbstract[]|\ArrayAccess $transformers
     */
    public function __construct($transformers)
    {
        $this->transformers = $transformers;
    }

    public function resolve($scopeIdentifier)
    {
        if (!isset($this->transformers[$scopeIdentifier])) {
            throw new \RuntimeException(sprintf('Unknown scope identifier: %s', $scopeIdentifier));
        }

        return $this->transformers[$scopeIdentifier];
    }
}
