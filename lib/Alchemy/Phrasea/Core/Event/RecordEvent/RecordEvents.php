<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\RecordEvent;

final class RecordEvents
{
    const CREATED = 'record.created';
    const DELETED = 'record.deleted';
    // Change
    const COLLECTION_CHANGED = 'record.collection_changed';
    const METADATA_CHANGED = 'record.metadata_changed';
    const ORIGINAL_NAME_CHANGED = 'record.original_name_changed';
    const STATUS_CHANGED = 'record.status_changed';
    // Sub-definitions
    const SUB_DEFINITION_CREATED = 'record.sub_definition_created';
    const SUB_DEFINITION_SUBSTITUTED = 'record.sub_definition_substituted';
}
