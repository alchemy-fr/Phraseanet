<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Thesaurus;

final class ThesaurusEvents
{
    const IMPORTED = 'thesaurus.imported';
    const FIELD_LINKED = 'thesaurus.field-linked';
    const CANDIDATE_ACCEPTED_AS_CONCEPT = 'thesaurus.candidate-accepted-as-concept';
    const CANDIDATE_ACCEPTED_AS_SYNONYM = 'thesaurus.candidate-accepted-as-synonym';
    const SYNONYM_LNG_CHANGED = 'thesaurus.synonym-lng-changed';
    const SYNONYM_POSITION_CHANGED = 'thesaurus.synonym-position-changed';
    const SYNONYM_TRASHED = 'thesaurus.synonym-trashed';
    const CONCEPT_TRASHED = 'thesaurus.concept-trashed';
    const CONCEPT_DELETED = 'thesaurus.concept-deleted';
    const SYNONYM_ADDED = 'thesaurus.synonym-added';
    const CONCEPT_ADDED = 'thesaurus.concept-added';
    const REINDEX_REQUIRED = 'thesaurus.reindex-required';
}
