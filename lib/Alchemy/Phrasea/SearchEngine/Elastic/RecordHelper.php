<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper as ThesaurusHelper;
use appbox;
use igorw;

class RecordHelper
{
    private $appbox;

    // Computation caches
    private $collectionMap;
    private $fieldStructure;

    public function __construct(appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function getUniqueRecordId($databoxId, $recordId)
    {
        return sprintf('%d_%d', $databoxId, $recordId);
    }

    public function getUniqueCollectionId($databoxId, $collectionId)
    {
        $col = $this->collectionMap();

        if (isset($col[$databoxId])) {
            if (isset($col[$databoxId][$collectionId])) {
                return (int) $col[$databoxId][$collectionId];
            }
        }

        return null;
    }

    private function collectionMap()
    {
        if (!$this->collectionMap) {
            $connection = $this->appbox->get_connection();
            $sql = 'SELECT
                        sbas_id as databox_id,
                        server_coll_id as collection_id,
                        base_id
                    FROM bas';
            $statement = $connection->query($sql);

            $map = array();
            while ($mapping = $statement->fetch()) {
                $map = igorw\assoc_in($map, [$mapping['databox_id'], $mapping['collection_id']], (int) $mapping['base_id']);
            }

            $this->collectionMap = $map;
        }

        return $this->collectionMap;
    }

    public static function normalizeFlagKey($key)
    {
        return StringUtils::slugify($key, '_');
    }

    public function getFields($includePrivate = false, $onlySearchable = true)
    {
        $fields = array();
        foreach ($this->getFieldsStructure() as $name => $options) {
            // Skip private fields
            if ($options['private'] && !$includePrivate) {
                continue;
            }
            // Skip not searchable fields
            if ($onlySearchable && !$options['searchable']) {
                continue;
            }
            $fields[] = $name;
        }

        return $fields;
    }

    public function getFieldsStructure()
    {
        if (!empty($this->fieldsStructure)) {
            return $this->fieldsStructure;
        }

        $fields = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            //printf("Databox %d\n", $databox->get_sbas_id());
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $field = array();
                // Field type
                switch ($fieldStructure->get_type()) {
                    case \databox_field::TYPE_DATE:
                        $field['type'] = 'date';
                        break;
                    case \databox_field::TYPE_NUMBER:
                        $field['type'] = 'double';
                        break;
                    case \databox_field::TYPE_STRING:
                    case \databox_field::TYPE_TEXT:
                        $field['type'] = 'string';
                        break;
                    default:
                        throw new Exception(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $fieldStructure->get_type()));
                        break;
                }

                $name = $fieldStructure->get_name();
                $field['databox_ids'][] = $databox->get_sbas_id();

                // Business rules
                $field['private'] = $fieldStructure->isBusiness();
                $field['searchable'] = $fieldStructure->is_indexable();
                $field['to_aggregate'] = (bool) $fieldStructure->isAggregable();

                // Thesaurus concept inference
                // $xpath = "/thesaurus/te[@id='T26'] | /thesaurus/te[@id='T24']";
                $helper = new ThesaurusHelper();

                // TODO Not the real option yet
                $field['thesaurus_concept_inference'] = $field['type'] === 'string';
                // TODO Find thesaurus path prefixes
                $field['thesaurus_prefix'] = '/categories';

                //printf("Field \"%s\" <%s> (private: %b)\n", $name, $field['type'], $field['private']);

                // Since mapping is merged between databoxes, two fields may
                // have conflicting names. Indexing is the same for a given
                // type so we reject only those with different types.
                if (isset($fields[$name])) {
                    // keep tracks of databox_id's where the field belongs to
                    $fields[$name]['databox_ids'][] = $databox->get_sbas_id();

                    if ($fields[$name]['type'] !== $field['type']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible types (%s vs %s)", $name, $fields[$name]['type'], $field['type']));
                    }

                    if ($fields[$name]['private'] !== $field['private']) {
                        throw new MergeException(sprintf("Field %s can't be merged, could not mix private and public fields with same name", $name));
                    }

                    if ($fields[$name]['searchable'] !== $field['searchable']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible searchable state", $name));
                    }

                    if ($fields[$name]['to_aggregate'] !== $field['to_aggregate']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible to_aggregate state", $name));
                    }
                    // TODO other structure incompatibilities

                    //printf("Merged with previous \"%s\" field\n", $name);
                }

                $fields[$name] = $field;
            }
        }

        return $this->fieldsStructure = $fields;
    }
}
