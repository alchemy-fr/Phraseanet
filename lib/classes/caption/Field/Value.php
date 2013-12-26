<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Vocabulary;

class caption_Field_Value implements cache_cacheableInterface
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $value;

    /**
     *
     * @var type \Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface
     */
    protected $VocabularyType;

    /**
     *
     * @var int
     */
    protected $VocabularyId;

    /**
     *
     * @var databox_field
     */
    protected $databox_field;

    /**
     *
     * @var record_adapter
     */
    protected $record;
    protected $app;

    protected static $localCache = [];

    /**
     *
     * @param  Application          $app
     * @param  databox_field        $databox_field
     * @param  record_adapter       $record
     * @param  type                 $id
     * @return \caption_Field_Value
     */
    public function __construct(Application $app, databox_field $databox_field, record_adapter $record, $id)
    {
        $this->id = (int) $id;
        $this->databox_field = $databox_field;
        $this->record = $record;
        $this->app = $app;

        $this->retrieveValues();
    }

    protected function retrieveValues()
    {
        try {
            $datas = $this->get_data_from_cache();

            $this->value = $datas['value'];
            $this->VocabularyType = $datas['vocabularyType'] ? Vocabulary\Controller::get($this->app, $datas['vocabularyType']) : null;
            $this->VocabularyId = $datas['vocabularyId'];

            return $this;
        } catch (\Exception $e) {

        }

        $connbas = $this->databox_field->get_databox()->get_connection();

        $sql = 'SELECT record_id, value, VocabularyType, VocabularyId
            FROM metadatas WHERE id = :id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->value = $row ? $row['value'] : null;

        try {
            $this->VocabularyType = $row['VocabularyType'] ? Vocabulary\Controller::get($this->app, $row['VocabularyType']) : null;
            $this->VocabularyId = $row['VocabularyId'];
        } catch (\InvalidArgumentException $e) {

        }

        if ($this->VocabularyType) {
            /**
             * Vocabulary Control has been deactivated
             */
            if ( ! $this->databox_field->getVocabularyControl()) {
                $this->removeVocabulary();
            }
            /**
             * Vocabulary Control has changed
             */ elseif ($this->databox_field->getVocabularyControl()->getType() !== $this->VocabularyType->getType()) {
                $this->removeVocabulary();
            }
            /**
             * Current Id is not available anymore
             */ elseif ( ! $this->VocabularyType->validate($this->VocabularyId)) {
                $this->removeVocabulary();
            }
            /**
             * String equivalence has changed
             */ elseif ($this->VocabularyType->getValue($this->VocabularyId) !== $this->value) {
                $this->set_value($this->VocabularyType->getValue($this->VocabularyId));
            }
        }

        $datas = [
            'value'          => $this->value,
            'vocabularyId'   => $this->VocabularyId,
            'vocabularyType' => $this->VocabularyType ? $this->VocabularyType->getType() : null,
        ];

        $this->set_data_to_cache($datas);

        return $this;
    }

    public function getVocabularyType()
    {
        return $this->VocabularyType;
    }

    public function getVocabularyId()
    {
        return $this->VocabularyId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getRessource()
    {
        return $this->VocabularyType ? $this->VocabularyType->getRessource($this->VocabularyId) : null;
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
        $connbas = $this->databox_field->get_connection();

        $sql = 'DELETE FROM metadatas WHERE id = :id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();
        $this->databox_field->delete_data_from_cache();

        $this->record->get_caption()->delete_data_from_cache();

        return $this;
    }

    public function removeVocabulary()
    {
        $connbas = $this->databox_field->get_connection();

        $params = [
            ':VocabType'    => null
            , ':VocabularyId' => null
            , ':meta_id'      => $this->getId()
        ];

        $sql_up = 'UPDATE metadatas
              SET VocabularyType = :VocabType, VocabularyId = :VocabularyId
              WHERE id = :meta_id';
        $stmt_up = $connbas->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->VocabularyId = $this->VocabularyType = null;

        $this->delete_data_from_cache();

        return $this;
    }

    public function setVocab(Vocabulary\ControlProvider\ControlProviderInterface $vocabulary, $vocab_id)
    {
        $connbas = $this->databox_field->get_connection();

        $params = [
            ':VocabType'    => $vocabulary->getType()
            , ':VocabularyId' => $vocab_id
            , ':meta_id'      => $this->getId()
        ];

        $sql_up = 'UPDATE metadatas
              SET VocabularyType = :VocabType, VocabularyId = :VocabularyId
              WHERE id = :meta_id';
        $stmt_up = $connbas->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->set_value($vocabulary->getValue($vocab_id));

        return $this;
    }

    public function set_value($value)
    {
        $this->value = $value;

        $connbas = $this->databox_field->get_connection();

        $params = [
            ':meta_id' => $this->id
            , ':value'   => $value
        ];

        $sql_up = 'UPDATE metadatas SET value = :value WHERE id = :meta_id';
        $stmt_up = $connbas->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->delete_data_from_cache();

        $this->update_cache_value($value);

        return $this;
    }

    /**
     *
     * @param  array         $value
     * @return caption_field
     */
    public function update_cache_value($value)
    {
        $this->record->get_caption()->delete_data_from_cache();

        return $this;
    }

    public static function create(Application $app, databox_field $databox_field, record_Interface $record, $value, Vocabulary\ControlProvider\ControlProviderInterface $vocabulary = null, $vocabularyId = null)
    {
        $connbas = $databox_field->get_connection();

        /**
         * Check consistency
         */
        if ( ! $databox_field->is_multi()) {
            try {
                $field = $record->get_caption()->get_field($databox_field->get_name());
                $values = $field->get_values();
                $caption_field_value = array_pop($values);
                /* @var $value \caption_Field_Value */
                $caption_field_value->set_value($value);

                if (! $vocabulary || ! $vocabularyId) {
                    $caption_field_value->removeVocabulary();
                } else {
                    $caption_field_value->setVocab($vocabulary, $vocabularyId);
                }

                return $caption_field_value;
            } catch (\Exception $e) {

            }
        }

        $sql_ins = 'INSERT INTO metadatas
      (id, record_id, meta_struct_id, value, VocabularyType, VocabularyId)
      VALUES
      (null, :record_id, :field, :value, :VocabType, :VocabId)';

        $params = [
            ':record_id' => $record->get_record_id(),
            ':field'     => $databox_field->get_id(),
            ':value'     => $value,
            ':VocabType' => $vocabulary ? $vocabulary->getType() : null,
            ':VocabId'   => $vocabulary ? $vocabularyId : null,
        ];

        $stmt_ins = $connbas->prepare($sql_ins);
        $stmt_ins->execute($params);

        $stmt_ins->closeCursor();
        $meta_id = $connbas->lastInsertId();

        $caption_field_value = new self($app, $databox_field, $record, $meta_id);
        $caption_field_value->update_cache_value($value);

        $record->get_caption()->delete_data_from_cache();
        $databox_field->delete_data_from_cache();

        $caption_field_value->delete_data_from_cache();

        return $caption_field_value;
    }

    /**
     *
     * @return string
     */
    public function highlight_thesaurus()
    {
        $value = $this->getValue();

        $databox = $this->databox_field->get_databox();
        $XPATH_thesaurus = $databox->get_xpath_thesaurus();

        $tbranch = $this->databox_field->get_tbranch();

        if (! $tbranch || ! $XPATH_thesaurus) {
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
            $note += ($node->getAttribute("lng") == $this->app['locale.I18n']) ? 4 : 0;
            $note += ($node->getAttribute("w") == $term_noacc) ? 2 : 0;
            if($context_noacc != "")
                $note += ($node->getAttribute("k") == $context_noacc) ? 1 : 0;
            if ($note > $bestnote) {
                $bestnote = $note;
                $bestnode = $node;
            }
        }
        if ($bestnode) {
            list($term, $context) = $this->splitTermAndContext(str_replace(["[[em]]", "[[/em]]"], ["", ""], $value));
            $qjs = $term . ($context ? '['.$context.']' : '');

            $value = new ThesaurusValue($bestnode->getAttribute('v'), $this->databox_field, $qjs);
        }

        return $value;
    }

    /**
     *
     * @param  string $word
     * @return array
     */
    protected function splitTermAndContext($word)
    {
        $term = trim($word);
        $context = "";
        if (($po = strpos($term, "(")) !== false) {
            if (($pc = strpos($term, ")", $po)) !== false) {
                $context = trim(substr($term, $po + 1, $pc - $po - 1));
                $term = trim(substr($term, 0, $po));
            }
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
        return 'caption_fieldvalue_' . $this->id . '_' . ($option ? '_' . $option : '');
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
        $this->value = $this->VocabularyId = $this->VocabularyType = null;
        $this->record->delete_data_from_cache(record_adapter::CACHE_TITLE);

        try {
            $this->record->get_caption()->get_field($this->databox_field->get_name())->delete_data_from_cache();
        } catch (\Exception $e) {

        }

        unset(self::$localCache[$this->get_cache_key($option)]);
    }

    public static function purge()
    {
        self::$localCache = [];
    }
}
