<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * Columns created with DBAL 2 still carry the "(DC2Type:json_array)" comment, which DBAL 3
 * no longer knows: this type lets the schema introspection read them, so that the schema
 * update converts them to the "json" type declared by the entities.
 */
class JsonArray extends JsonType
{
    const JSON_ARRAY = 'json_array';

    public function getName()
    {
        return static::JSON_ARRAY;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
