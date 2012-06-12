<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Alchemy\Phrasea\Vocabulary;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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

    /**
     *
     * @param  databox_field        $databox_field
     * @param  record_adapter       $record
     * @param  type                 $id
     * @return \caption_Field_Value
     */
    public function __construct(databox_field $databox_field, record_adapter $record, $id)
    {
        $this->id = (int) $id;
        $this->databox_field = $databox_field;
        $this->record = $record;

        $this->retrieveValues();
    }

    protected function retrieveValues()
    {
        try {
            $datas = $this->get_data_from_cache();

            $this->value = $datas['value'];
            $this->VocabularyType = $datas['vocabularyType'] ? Vocabulary\Controller::get($datas['vocabularyType']) : null;
            $this->VocabularyId = $datas['vocabularyId'];

            return $this;
        } catch (\Exception $e) {
            
        }

        $connbas = $this->databox_field->get_databox()->get_connection();

        $sql = 'SELECT record_id, value, VocabularyType, VocabularyId
            FROM metadatas WHERE id = :id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->value = $row ? $row['value'] : null;

        try {
            $this->VocabularyType = $row['VocabularyType'] ? Vocabulary\Controller::get($row['VocabularyType']) : null;
            $this->VocabularyId = $row['VocabularyId'];
        } catch (\Exception $e) {
            
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

        $datas = array(
            'value'          => $this->value,
            'vocabularyId'   => $this->VocabularyId,
            'vocabularyType' => $this->VocabularyType ? $this->VocabularyType->getType() : null,
        );

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
        $stmt->execute(array(':id' => $this->id));
        $stmt->closeCursor();

        $this->delete_data_from_cache();
        $this->databox_field->delete_data_from_cache();
        
        $sbas_id = $this->record->get_sbas_id();
        $this->record->get_caption()->delete_data_from_cache();

        try {
            $registry = registry::get_instance();
            $sphinx_rt = sphinxrt::get_instance($registry);

            $sbas_params = phrasea::sbas_params();

            if (isset($sbas_params[$sbas_id])) {
                $params = $sbas_params[$sbas_id];
                $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
                $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "metas_realtime" . $sbas_crc, $this->id);
                $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "docs_realtime" . $sbas_crc, $this->record->get_record_id());
            }
        } catch (Exception $e) {
            unset($e);
        }

        return $this;
    }

    public function removeVocabulary()
    {
        $connbas = $this->databox_field->get_connection();

        $params = array(
            ':VocabType'    => null
            , ':VocabularyId' => null
            , ':meta_id'      => $this->getId()
        );

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

        $params = array(
            ':VocabType'    => $vocabulary->getType()
            , ':VocabularyId' => $vocab_id
            , ':meta_id'      => $this->getId()
        );

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

        $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
        $connbas = $this->databox_field->get_connection();

        $params = array(
            ':meta_id' => $this->id
            , ':value'   => $value
        );

        $sql_up = 'UPDATE metadatas SET value = :value WHERE id = :meta_id';
        $stmt_up = $connbas->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->delete_data_from_cache();

        try {
            $registry = registry::get_instance();
            $sphinx_rt = sphinxrt::get_instance($registry);

            $sbas_params = phrasea::sbas_params();

            if (isset($sbas_params[$sbas_id])) {
                $params = $sbas_params[$sbas_id];
                $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
                $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "", $this->id);
                $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "", $this->record->get_record_id());
            }
        } catch (Exception $e) {
            
        }

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
        $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
        try {
            $registry = registry::get_instance();

            $sbas_params = phrasea::sbas_params();

            if (isset($sbas_params[$sbas_id])) {
                $params = $sbas_params[$sbas_id];
                $sbas_crc = crc32(
                    str_replace(
                        array('.', '%')
                        , '_'
                        , sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])
                    )
                );

                $sphinx_rt = sphinxrt::get_instance($registry);
                $sphinx_rt->replace_in_metas(
                    "metas_realtime" . $sbas_crc
                    , $this->id
                    , $this->databox_field->get_id()
                    , $this->record->get_record_id()
                    , $sbas_id
                    , phrasea::collFromBas($this->record->get_base_id())
                    , ($this->record->is_grouping() ? '1' : '0')
                    , $this->record->get_type()
                    , $value
                    , ($this->databox_field->isBusiness() ? '1' : '0')
                    , $this->record->get_creation_date()
                );

                $all_datas = array();

                foreach ($this->record->get_caption()->get_fields(null, true) as $field) {
                    if ( ! $field->is_indexable()) {
                        continue;
                    }

                    $all_datas[] = $field->get_serialized_values();
                }

                $all_datas = implode(' ', $all_datas);

                $sphinx_rt->replace_in_documents(
                    "docs_realtime" . $sbas_crc, //$this->id,
                    $this->record->get_record_id(), $all_datas, $sbas_id, phrasea::collFromBas($this->record->get_base_id()), ($this->record->is_grouping() ? '1' : '0'), $this->record->get_type(), $this->record->get_creation_date()
                );
            }
        } catch (Exception $e) {
            unset($e);
        }

        return $this;
    }

    public static function create(databox_field &$databox_field, record_Interface $record, $value, Vocabulary\ControlProvider\ControlProviderInterface $vocabulary = null, $vocabularyId = null)
    {
        $connbas = $databox_field->get_connection();

        /**
         * Check consistency
         */
        if ( ! $databox_field->is_multi()) {
            try {
                $field = $record->get_caption()->get_field($databox_field->get_name());
                $caption_field_value = array_pop($field->get_values());
                /* @var $value \caption_Field_Value */
                $caption_field_value->set_value($value);

                if ( ! $vocabulary || ! $vocabularyId) {
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

        $params = array(
            ':record_id' => $record->get_record_id(),
            ':field'     => $databox_field->get_id(),
            ':value'     => $value,
            ':VocabType' => $vocabulary ? $vocabulary->getType() : null,
            ':VocabId'   => $vocabulary ? $vocabularyId : null,
        );

        $stmt_ins = $connbas->prepare($sql_ins);
        $stmt_ins->execute($params);

        $stmt_ins->closeCursor();
        $meta_id = $connbas->lastInsertId();

        $caption_field_value = new self($databox_field, $record, $meta_id);
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

        if ( ! $tbranch || ! $XPATH_thesaurus) {
            return $value;
        }

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $unicode = new unicode();

        $DOM_branchs = $XPATH_thesaurus->query($tbranch);

        $fvalue = $value;

        $cleanvalue = str_replace(array("<em>", "</em>", "'"), array("", "", "&apos;"), $fvalue);

        list($term_noacc, $context_noacc) = $this->splitTermAndContext($cleanvalue);
        $term_noacc = $unicode->remove_indexer_chars($term_noacc);
        $context_noacc = $unicode->remove_indexer_chars($context_noacc);
        if ($context_noacc) {
            $q = "//sy[@w='" . $term_noacc . "' and @k='" . $context_noacc . "']";
        } else {
            $q = "//sy[@w='" . $term_noacc . "' and not(@k)]";
        }
        $qjs = $link = "";
        foreach ($DOM_branchs as $DOM_branch) {
            $nodes = $XPATH_thesaurus->cache_query($q, $DOM_branch);
            if ($nodes->length > 0) {
                $lngfound = false;
                foreach ($nodes as $node) {
                    if ($node->getAttribute("lng") == $session->get_I18n()) {
                        // le terme est dans la bonne langue, on le rend cliquable
                        list($term, $context) = $this->splitTermAndContext($fvalue);
                        $term = str_replace(array("<em>", "</em>"), array("", ""), $term);
                        $context = str_replace(array("<em>", "</em>"), array("", ""), $context);
                        $qjs = $term;
                        if ($context) {
                            $qjs .= " [" . $context . "]";
                        }
                        $link = $fvalue;

                        $lngfound = true;
                        break;
                    }

                    $synonyms = $XPATH_thesaurus->query("sy[@lng='" . $session->usr_i18 . "']", $node->parentNode);
                    foreach ($synonyms as $synonym) {
                        $k = $synonym->getAttribute("k");
                        if ($synonym->getAttribute("w") != $term_noacc || $k != $context_noacc) {
                            $link = $qjs = $synonym->getAttribute("v");
                            if ($k) {
                                $link .= " (" . $k . ")";
                                $qjs .= " [" . $k . "]";
                            }

                            $lngfound = true;
                            break;
                        }
                    }
                }
                if ( ! $lngfound) {
                    list($term, $context) = $this->splitTermAndContext($fvalue);
                    $term = str_replace(array("<em>", "</em>"), array("", ""), $term);
                    $context = str_replace(array("<em>", "</em>"), array("", ""), $context);
                    $qjs = $term;
                    if ($context) {
                        $qjs .= " [" . $context . "]";
                    }
                    $link = $fvalue;
                }
            }
        }
        if ($qjs) {
            $value = "<a class=\"bounce\" onclick=\"bounce('" . $databox->get_sbas_id() . "','"
                . str_replace("'", "\'", $qjs)
                . "', '"
                . str_replace("'", "\'", $this->databox_field->get_name())
                . "');return(false);\">"
                . $link
                . "</a>";
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

        return array($term, $context);
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
        $databox = $this->record->get_databox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
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
        $databox = $this->record->get_databox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        $databox = $this->record->get_databox();
        $this->value = $this->VocabularyId = $this->VocabularyType = null;
        
        try {
            $this->record->get_caption()->get_field($this->databox_field->get_name())->delete_data_from_cache();
        } catch (\Exception $e) {
            
        }

        return $databox->delete_data_from_cache($this->get_cache_key($option));
    }
}
