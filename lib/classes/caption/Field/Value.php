<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Utilities\StringHelper;
use Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface;

class caption_Field_Value implements cache_cacheableInterface
{
    const RETRIEVE_VALUES = true;
    const DONT_RETRIEVE_VALUES = false;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var ControlProviderInterface|null
     */
    protected $vocabularyType;

    /**
     * @var mixed
     */
    protected $vocabularyId;

    /**
     * @var databox_field
     */
    protected $databox_field;

    /**
     * @var record_adapter
     */
    protected $record;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Query to ask to the search engine to bounce to the current value;
     * This property is set if the value is matched against a thesaurus value;
     *
     * @var string
     */
    protected $qjs;

    /**
     * Tells whether the value is matched against a thesaurus value.
     * @var bool
     */
    protected $isThesaurusValue;

    protected static $localCache = [];

    /**
     * @param  Application $app
     * @param  databox_field $databox_field
     * @param  record_adapter $record
     * @param  mixed $id
     * @param  bool $retrieveValues
     */
    public function __construct(Application $app, databox_field $databox_field, record_adapter $record, $id, $retrieveValues = self::RETRIEVE_VALUES)
    {
        $this->id = (int) $id;
        $this->databox_field = $databox_field;
        $this->record = $record;
        $this->app = $app;

        if ($retrieveValues === self::RETRIEVE_VALUES) {
            $this->retrieveValues();
        }
    }

    /**
     * @return string
     */
    public function getQjs()
    {
        return $this->qjs;
    }

    /**
     * @param string $value
     * @param string $vocabularyType
     * @param string $vocabularyId
     */
    public function injectValues($value, $vocabularyType, $vocabularyId)
    {
        $this->value = StringHelper::crlfNormalize($value);

        $this->fetchVocabulary($vocabularyType, $vocabularyId);

        if ($this->vocabularyType) {
            if (!$this->databox_field->getVocabularyControl()) {
                // Vocabulary Control has been deactivated
                $this->removeVocabulary();
            } elseif ($this->databox_field->getVocabularyControl()->getType() !== $this->vocabularyType->getType()) {
                // Vocabulary Control has changed
                $this->removeVocabulary();
            } elseif (!$this->vocabularyType->validate($this->vocabularyId)) {
                // Current Id is not available anymore
                $this->removeVocabulary();
            } elseif ($this->vocabularyType->getValue($this->vocabularyId) !== $this->value) {
                // String equivalence has changed
                $this->set_value($this->vocabularyType->getValue($this->vocabularyId));
            }
        }
    }

    protected function retrieveValues()
    {
        try {
            $data = $this->get_data_from_cache();
            $cacheRefreshNeeded = false;
        } catch (\Exception $e) {
            $data = $this->databox_field->get_databox()->get_connection()
                ->fetchAssoc(
                    'SELECT value, VocabularyType as vocabularyType, VocabularyId as vocabularyId FROM metadatas WHERE id = :id',
                    [':id' => $this->id]
                );

            if (!is_array($data)) {
                $data = [
                    'value' => null,
                    'vocabularyId' => null,
                    'vocabularyType' => null,
                ];
            }

            $cacheRefreshNeeded = true;
        }

        $this->injectValues($data['value'], $data['vocabularyType'], $data['vocabularyId']);

        if ($cacheRefreshNeeded) {
            $this->set_data_to_cache([
                'value'          => $this->value,
                'vocabularyId'   => $this->vocabularyId,
                'vocabularyType' => $this->vocabularyType ? $this->vocabularyType->getType() : null,
            ]);
        }

        return $this;
    }

    /**
     * @return ControlProviderInterface|null
     */
    public function getVocabularyType()
    {
        return $this->vocabularyType;
    }

    public function getVocabularyId()
    {
        return $this->vocabularyId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getResource()
    {
        return $this->vocabularyType ? $this->vocabularyType->getResource($this->vocabularyId) : null;
    }

    public function getDatabox_field()
    {
        return $this->databox_field;
    }

    public function getRecord()
    {
        return $this->record;
    }

    public function delete()
    {
        $this->getConnection()->delete('metadatas', ['id' => $this->id]);

        $this->delete_data_from_cache();
        $this->databox_field->delete_data_from_cache();

        $this->record->get_caption()->delete_data_from_cache();

        return $this;
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeVocabulary()
    {
        $this->getConnection()->executeUpdate(
            'UPDATE metadatas SET VocabularyType = NULL, VocabularyId = NULL WHERE id = :meta_id',
            ['meta_id' => $this->getId()]
        );

        $this->vocabularyId = null;
        $this->vocabularyType = null;

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @param ControlProviderInterface $vocabulary
     * @param mixed $vocab_id
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setVocab(ControlProviderInterface $vocabulary, $vocab_id)
    {
        $this->getConnection()->executeUpdate(
            'UPDATE metadatas SET VocabularyType = :VocabType, VocabularyId = :VocabularyId WHERE id = :meta_id',
            [
                'VocabType'    => $vocabulary->getType(),
                'VocabularyId' => $vocab_id,
                'meta_id' => $this->getId(),
            ]
        );

        $this->set_value($vocabulary->getValue($vocab_id));

        return $this;
    }

    /**
     * @param ControlProviderInterface|null $vocabulary
     * @param mixed|null $vocabularyId
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function changeVocabulary(ControlProviderInterface $vocabulary = null, $vocabularyId = null)
    {
        if (isset($vocabulary, $vocabularyId)) {
            return $this->setVocab($vocabulary, $vocabularyId);
        }

        return $this->removeVocabulary();
    }

    /**
     * @param string $value
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function set_value($value)
    {
        $this->value = $value;

        $this->getConnection()->executeUpdate(
            'UPDATE metadatas SET value = :value WHERE id = :meta_id',
            [
                'meta_id' => $this->id,
                'value' => $value,
            ]
        );

        $this->delete_data_from_cache();

        return $this;
    }

    public static function create(Application $app, databox_field $databox_field, \record_adapter $record, $value, ControlProviderInterface $vocabulary = null, $vocabularyId = null)
    {
        $connection = $databox_field->get_connection();

        // Check consistency
        // if a field is mono and already has a value, we override it by "set_value()"
        if (!$databox_field->is_multi()) {
            try {
                $field = $record->get_caption()->get_field($databox_field->get_name());
                $values = $field->get_values();
            } catch (Exception $exception) {
                // Field was not found, so no values found either
                $values = [];
            }
            if (!empty($values)) {
                /** @var caption_Field_Value $caption_field_value */
                $caption_field_value = reset($values);
                $caption_field_value->set_value($value);
                $caption_field_value->changeVocabulary($vocabulary, $vocabularyId);

                return $caption_field_value;
            }
        }

        // here we create a new field
        $data = [
            'record_id' => $record->getRecordId(),
            'meta_struct_id' => $databox_field->get_id(),
            'value' => $value,
            'VocabularyType' => $vocabulary ? $vocabulary->getType() : null,
            'VocabularyId' => $vocabulary ? $vocabularyId : null,
        ];

        $connection->insert('metadatas', $data);

        $meta_id = $connection->lastInsertId();

        $caption_field_value = new self($app, $databox_field, $record, $meta_id, self::DONT_RETRIEVE_VALUES);
        $caption_field_value->injectValues($data['value'], $data['VocabularyType'], $data['VocabularyId']);

        $databox_field->delete_data_from_cache();
        $caption_field_value->delete_data_from_cache();

        return $caption_field_value;
    }

    /**
     * @return string
     */
    public function highlight_thesaurus()
    {
        $this->isThesaurusValue = false;

        $value = $this->getValue();
        $databox = $this->databox_field->get_databox();
        $XPATH_thesaurus = $databox->get_xpath_thesaurus();

        $tbranch = $this->databox_field->get_tbranch();

        if (!$tbranch || !$XPATH_thesaurus) {
            return $value;
        }

        // ---------------- new code ----------------------
        $cleanvalue = str_replace(["[[em]]", "[[/em]]", "'"], ["", "", "&apos;"], $value);

        list($term_noacc, $context_noacc) = $this->splitTermAndContext($cleanvalue);
        $term_noacc = $this->app['unicode']->remove_indexer_chars($term_noacc);
        $context_noacc = $this->app['unicode']->remove_indexer_chars($context_noacc);

        // find all synonyms in all related branches
        $q = "(" . $tbranch . ")//sy[@w='" . $term_noacc . "'";
        if ($context_noacc) {
            $q .= " and @k='" . $context_noacc . "']";
        } else {
            $q .= " and not(@k)]";
        }
        $q .= "/../sy";

        $nodes = $XPATH_thesaurus->query($q);

        // loop on every sy found
        $bestnode = null;
        $bestnote = 0;
        foreach ($nodes as $node) {
            $note = 0;
            $note += ($node->getAttribute("lng") == $this->app['locale']) ? 4 : 0;
            $note += ($node->getAttribute("w") == $term_noacc) ? 2 : 0;
            if ($context_noacc != "") {
                $note += ($node->getAttribute("k") == $context_noacc) ? 1 : 0;
            }
            if ($note > $bestnote) {
                $bestnode = $node;
            }
        }

        if ($bestnode) {
            list($term, $context) = $this->splitTermAndContext(str_replace(["[[em]]", "[[/em]]"], ["", ""], $value));
            // a value has been found in thesaurus, update value & set the query to bounce to the value
            $this->value = $bestnode->getAttribute('v');
            $this->qjs = $term . ($context ? '[' . $context . ']' : '');
            $this->isThesaurusValue = true;
        }

        return $this->value;
    }

    /**
     * @return bool
     */
    public function isThesaurusValue()
    {
        if (null === $this->isThesaurusValue) {
            throw new LogicException('Value was not checked against thesaurus yet. Call hightlight_thesaurus() first');
        }

        return $this->isThesaurusValue;
    }

    /**
     * @param  string $word
     * @return string[]
     */
    protected function splitTermAndContext($word)
    {
        $term = trim($word);
        $context = '';

        if (($po = strpos($term, '(')) !== false && ($pc = strpos($term, ')', $po)) !== false) {
            $context = trim(substr($term, $po + 1, $pc - $po - 1));
            $term = trim(substr($term, 0, $po));
        }

        return [$term, $context];
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'caption_fieldvalue_' . $this->record->getDatabox()->get_sbas_id() . '_' . $this->id . '_' . ($option ? '_' . $option : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        if (isset(self::$localCache[$this->get_cache_key($option)])) {
            return self::$localCache[$this->get_cache_key($option)];
        }

        throw new Exception('no value');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  mixed         $value
     * @param  string        $option
     * @param  int           $duration
     * @return caption_field
     */
    public function set_data_to_cache($value, $option = null, $duration = 360000)
    {
        return self::$localCache[$this->get_cache_key($option)] = $value;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        $this->value = $this->vocabularyId = $this->vocabularyType = null;
        $this->record->delete_data_from_cache(record_adapter::CACHE_TITLE);
        $this->record->get_caption()->delete_data_from_cache();

        unset(self::$localCache[$this->get_cache_key($option)]);
    }

    public function __toString()
    {
        return $this->value;
    }

    public static function purge()
    {
        self::$localCache = [];
    }

    /**
     * @param string $vocabularyType
     * @param mixed $vocabularyId
     */
    private function fetchVocabulary($vocabularyType, $vocabularyId)
    {
        try {
            $this->vocabularyType = $vocabularyType ? $this->app['vocabularies'][strtolower($vocabularyType)] : null;
            $this->vocabularyId = $vocabularyId;
        } catch (\InvalidArgumentException $e) {
            // Invalid or unknown Vocabulary
        }
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->databox_field->get_connection();
    }
}
