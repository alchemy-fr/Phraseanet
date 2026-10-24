<?php

/*
 * This file is part of the League\Fractal package.
 *
 * (c) Phil Sturgeon <me@philsturgeon.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Fractal\Serializer;

/**
 * Array serializer rendering null resources as an explicit null instead of an empty array.
 */
class ExplicitNullArraySerializer extends ArraySerializer
{
    /**
     * {@inheritDoc}
     */
    public function null(?string $resourceKey = null): ?array
    {
        return null;
    }
}
