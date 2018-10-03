<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Databox;

final class DataboxEvents
{
    const UNMOUNTED = 'databox.unmounted';
    const CREATED = 'databox.created';
    const MOUNTED = 'databox.mounted';
    const DELETED = 'databox.deleted';
    const STRUCTURE_CHANGED = 'databox.structure_changed';
    const THESAURUS_CHANGED = 'databox.thesaurus_changed';
    const REINDEX_ASKED = 'databox.reindex_asked';
    const TOU_CHANGED = 'databox.tou_changed';
}
