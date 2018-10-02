<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record\Structure;

final class RecordStructureEvents
{
    const FIELD_UPDATED = 'record_structure.field.changed';
    const FIELD_DELETED = 'record_structure.field.deleted';
    const STATUS_BIT_UPDATED = 'record_structure.status_bit.updated';
    const STATUS_BIT_DELETED = 'record_structure.status_bit.deleted';
}
