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
 * @todo        disable the ability to change from multi to mono from admin
 *              panel ; propose an alternative to copy/update
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_field implements cache_cacheableInterface
{
    /**
     *
     * @var <type>
     */
    protected $id;

    /**
     *
     * @var databox
     */
    protected $databox;

    /**
     *
     * @var \PHPExiftool\Driver\Tag
     */
    protected $tag;

    /**
     *
     * @var <type>
     */
    protected $name;

    /**
     *
     * @var <type>
     */
    protected $indexable;

    /**
     *
     * @var <type>
     */
    protected $readonly;

    /**
     *
     * @var <type>
     */
    protected $required;

    /**
     *
     * @var <type>
     */
    protected $multi;

    /**
     *
     * @var <type>
     */
    protected $report;

    /**
     *
     * @var <type>
     */
    protected $type;

    /**
     *
     * @var <type>
     */
    protected $tbranch;

    /**
     *
     * @var <type>
     */
    protected $separator;

    /**
     *
     * @var <type>
     */
    protected $thumbtitle;

    /**
     *
     * @var boolean
     */
    protected $Business;
    protected $renamed = false;
    protected $metaToMerge = false;

    /**
     *
     *
     * To implement : change multi
     * Change vocab Id
     *
     */

    /**
     *
     * @var int
     */
    protected $sbas_id;
    protected static $_instance = array();
    protected $dces_element;
    protected $Vocabulary;
    protected $VocabularyRestriction = false;

    const TYPE_TEXT = "text";
    const TYPE_DATE = "date";
    const TYPE_STRING = "string";
    const TYPE_NUMBER = "number";

    /**
     * http://dublincore.org/documents/dces/
     */
    const DCES_TITLE = 'Title';
    const DCES_CREATOR = 'Creator';
    const DCES_SUBJECT = 'Subject';
    const DCES_DESCRIPTION = 'Description';
    const DCES_PUBLISHER = 'Publisher';
    const DCES_CONTRIBUTOR = 'Contributor';
    const DCES_DATE = 'Date';
    const DCES_TYPE = 'Type';
    const DCES_FORMAT = 'Format';
    const DCES_IDENTIFIER = 'Identifier';
    const DCES_SOURCE = 'Source';
    const DCES_LANGUAGE = 'Language';
    const DCES_RELATION = 'Relation';
    const DCES_COVERAGE = 'Coverage';
    const DCES_RIGHTS = 'Rights';

    /**
     *
     * @param databox $databox
     * @param <type> $id
     * @return databox_field
     */
    protected function __construct(databox &$databox, $id)
    {
        $this->set_databox($databox);
        $this->sbas_id = $databox->get_sbas_id();

        $connbas = $this->get_connection();

        $sql = "SELECT `thumbtitle`, `separator`
              , `dces_element`, `tbranch`, `type`, `report`, `multi`, `required`
              , `readonly`, `indexable`, `name`, `src`, `business`
              , `VocabularyControlType`, `RestrictToVocabularyControl`
            FROM metadatas_structure WHERE id=:id";

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->id = (int) $id;

        echo "create tag from ".$row['src']." for id $id - name is ".$row['name']."\n";
        $this->tag = self::loadClassFromTagName($row['src']);

        $this->name = $row['name'];
        $this->indexable = ! ! $row['indexable'];
        $this->readonly = ! ! $row['readonly'];
        $this->required = ! ! $row['required'];
        $this->multi = ! ! $row['multi'];
        $this->Business = ! ! $row['business'];
        $this->report = ! ! $row['report'];
        $this->type = $row['type'] ? : self::TYPE_STRING;
        $this->tbranch = $row['tbranch'];

        try {
            $this->Vocabulary = Vocabulary\Controller::get($row['VocabularyControlType']);
            $this->VocabularyRestriction = ! ! $row['RestrictToVocabularyControl'];
        } catch (Exception $e) {

        }

        if ($row['dces_element']) {
            $dc_class = 'databox_Field_DCES_' . $row['dces_element'];
            $this->dces_element = new $dc_class();
        }

        $this->separator = self::checkMultiSeparator($row['separator'], $this->multi);

        $this->thumbtitle = $row['thumbtitle'];

        return $this;
    }

    /**
     *
     * @return type \Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface
     */
    public function getVocabularyControl()
    {
        return $this->Vocabulary;
    }

    /**
     *
     * @return boolean
     */
    public function isVocabularyRestricted()
    {
        return $this->VocabularyRestriction;
    }

    /**
     *
     * @return boolean
     */
    public function isBusiness()
    {
        return $this->Business;
    }

    /**
     *
     * @param \databox $databox
     * @param int $id
     * @return \databox_field
     */
    public static function get_instance(databox &$databox, $id)
    {
        $cache_key = 'field_' . $id;
        $instance_id = $databox->get_sbas_id() . '_' . $id;
        if ( ! isset(self::$_instance[$instance_id]) || (self::$_instance[$instance_id] instanceof self) === false) {
            try {
                self::$_instance[$instance_id] = $databox->get_data_from_cache($cache_key);
            } catch (Exception $e) {
                self::$_instance[$instance_id] = new self($databox, $id);
                $databox->set_data_to_cache(self::$_instance[$instance_id], $cache_key);
            }
        }

        return self::$_instance[$instance_id];
    }

    /**
     *
     * @param databox $databox
     */
    public function set_databox(databox &$databox)
    {
        $this->databox = $databox;
    }

    /**
     *
     * @return connection_pdo
     */
    public function get_connection()
    {
        return $this->databox->get_connection();
    }

    /**
     *
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    public function delete()
    {
        caption_field::delete_all_metadatas($this);

        $connbas = $this->get_connection();
        $sql = 'DELETE FROM metadatas_structure WHERE id = :id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':id' => $this->get_id()));
        $stmt->closeCursor();

        $dom_struct = $this->databox->get_dom_structure();
        $xp_struct = $this->databox->get_xpath_structure();

        $nodes = $xp_struct->query(
            '/record/description/*[@meta_id=' . $this->id . ']'
        );

        foreach ($nodes as $node) {
            /* @var $node DOMNode */
            $node->parentNode->removeChild($node);
        }

        $this->delete_data_from_cache();
        $this->databox->saveStructure($dom_struct);

        return;
    }

    /**
     *
     * @return databox_field
     */
    public function save()
    {
        $connbas = $this->get_connection();

        $sql = 'UPDATE metadatas_structure SET
          `name` = :name,
          `src` = :source,
          `indexable` = :indexable,
          `readonly` = :readonly,
          `required` = :required,
          `separator` = :separator,
          `multi` = :multi,
          `business` = :business,
          `report` = :report,
          `type` = :type,
          `tbranch` = :tbranch,
          `thumbtitle` = :thumbtitle,
          `VocabularyControlType` = :VocabularyControlType,
          `RestrictToVocabularyControl` = :RestrictVocab
          WHERE id = :id';

        $params = array(
            ':name'                  => $this->name,
            ':source'                => $this->tag->getTagname(),
            ':indexable'             => $this->indexable ? '1' : '0',
            ':readonly'              => $this->readonly ? '1' : '0',
            ':required'              => $this->required ? '1' : '0',
            ':separator'             => $this->separator,
            ':multi'                 => $this->multi ? '1' : '0',
            ':business'              => $this->Business ? '1' : '0',
            ':report'                => $this->report ? '1' : '0',
            ':type'                  => $this->type,
            ':tbranch'               => $this->tbranch,
            ':thumbtitle'            => $this->thumbtitle,
            ':VocabularyControlType' => $this->Vocabulary ? $this->Vocabulary->getType() : null,
            ':RestrictVocab'         => $this->Vocabulary ? ($this->VocabularyRestriction ? '1' : '0') : '0',
            ':id'                    => $this->id
        );

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);

        if ($this->renamed) {
            caption_field::rename_all_metadatas($this);
            $this->renamed = false;
        }

        if ($this->metaToMerge) {
            caption_field::merge_all_metadatas($this);
            $this->metaToMerge = false;
        }

        $dom_struct = $this->databox->get_dom_structure();
        $xp_struct = $this->databox->get_xpath_structure();

        $nodes = $xp_struct->query(
            '/record/description/*[@meta_id=' . $this->id . ']'
        );

        if ($nodes->length == 0) {
            $meta = $dom_struct->createElement($this->name);
            $nodes_parent = $xp_struct->query('/record/description');
            $nodes_parent->item(0)->appendChild($meta);
        } else {
            $meta = $nodes->item(0);

            $current_name = $meta->nodeName;
            if ($this->name != $meta->nodeName) {
                $old_meta = $meta;
                $meta = $dom_struct->createElement($this->name);
                $nodes_parent = $xp_struct->query('/record/description');
                $nodes_parent->item(0)->replaceChild($meta, $old_meta);
            }
        }
        $meta->setAttribute('src', $this->tag->getTagname());
        $meta->setAttribute('index', $this->indexable ? '1' : '0');
        $meta->setAttribute('readonly', $this->readonly ? '1' : '0');
        $meta->setAttribute('required', $this->required ? '1' : '0');
        $meta->setAttribute('multi', $this->multi ? '1' : '0');
        $meta->setAttribute('report', $this->report ? '1' : '0');
        $meta->setAttribute('type', $this->type);
        $meta->setAttribute('tbranch', $this->tbranch);
        if ($this->multi) {
            $meta->setAttribute('separator', $this->separator);
        }
        $meta->setAttribute('thumbtitle', $this->thumbtitle);
        $meta->setAttribute('meta_id', $this->id);

        $this->delete_data_from_cache();
        $this->databox->saveStructure($dom_struct);

        return $this;
    }

    /**
     *
     * @param string $name
     * @return databox_field
     */
    public function set_name($name)
    {
        $previous_name = $this->name;

        $name = self::generateName($name);

        if ($name === '') {
            throw new \Exception_InvalidArgument();
        }

        $this->name = $name;

        if ($this->name !== $previous_name) {
            $this->renamed = true;
        }

        return $this;
    }

    /**
     * Get a PHPExiftool Tag from tagName
     *
     * @param type $tagName
     * @return \PHPExiftool\Driver\Tag
     * @throws Exception_Databox_metadataDescriptionNotFound
     */
    public static function loadClassFromTagName($tagName)
    {
        $tagName = str_replace('/rdf:rdf/rdf:description/', '', strtolower($tagName));

        if (trim($tagName) === '') {

            $tag = new Alchemy\Phrasea\Metadata\Tag\Nosource();
        } elseif (strpos($tagName, 'phraseanet:') === 0) {

            $tagName = str_replace('phraseanet:', '', $tagName);

            $tagName = explode('-', $tagName);
            $tagName = array_map('ucfirst', $tagName);
            $tagName = implode('', $tagName);

            $classname = '\\Alchemy\\Phrasea\\Metadata\\Tag\\' . $tagName;

            if ( ! class_exists($classname)) {
                throw new Exception_Databox_metadataDescriptionNotFound(sprintf("tagname %s not found", $tagName));
            }

            $tag = new $classname();
        } else {
            $tag = \PHPExiftool\Driver\TagFactory::getFromRDFTagname($tagName);
        }

        return $tag;
    }

    public function set_tag(\PHPExiftool\Driver\Tag $tag = null)
    {
        if ($tag === null) {
            $tag = new \Alchemy\Phrasea\Metadata\Tag\Nosource();
        }

        $this->tag = $tag;

        return $this;
    }

    /**
     *
     * @return \PHPExiftool\Driver\Tag
     */
    public function get_tag()
    {
        return $this->tag;
    }

    /**
     *
     * @return databox_Field_DCESAbstract
     */
    public function get_dces_element()
    {
        return $this->dces_element;
    }

    public function set_dces_element(databox_Field_DCESAbstract $DCES_element = null)
    {
        $sql = 'UPDATE metadatas_structure
              SET dces_element = :dces_element WHERE id = :id';

        $connbas = $this->get_connection();

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(
            ':dces_element' => $DCES_element ? $DCES_element->get_label() : null
            , ':id'           => $this->id
        ));
        $stmt->closeCursor();
        $this->dces_element = $DCES_element;

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param boolean $bool
     * @return databox_field
     */
    public function set_indexable($bool)
    {
        $this->indexable = ! ! $bool;

        return $this;
    }

    /**
     * Set a vocabulary
     *
     * @param Vocabulary\ControlProvider\ControlProviderInterface $vocabulary
     * @return \databox_field
     */
    public function setVocabularyControl(Vocabulary\ControlProvider\ControlProviderInterface $vocabulary = null)
    {
        $this->Vocabulary = $vocabulary;

        return $this;
    }

    /**
     * Set whether or not the vocabulary is restricted to the provider
     *
     * @param   boolean         $boolean
     * @return  \databox_field
     */
    public function setVocabularyRestricted($boolean)
    {
        $this->VocabularyRestriction = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param boolean $bool
     * @return databox_field
     */
    public function set_readonly($readonly)
    {
        $this->readonly = ! ! $readonly;

        return $this;
    }

    /**
     *
     * @param boolean $boolean
     * @return databox_field
     */
    public function set_business($boolean)
    {
        $this->Business = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @param boolean $bool
     * @return databox_field
     */
    public function set_required($required)
    {
        $this->required = ! ! $required;

        return $this;
    }

    /**
     *
     * @param boolean $bool
     * @return databox_field
     */
    public function set_multi($multi)
    {
        $multi = ! ! $multi;

        if ($this->multi !== $multi && ! $multi) {
            $this->metaToMerge = true;
        }

        $this->multi = $multi;

        $this->set_separator(';');

        return $this;
    }

    /**
     *
     * @param boolean $bool
     * @return databox_field
     */
    public function set_report($report)
    {
        $this->report = ! ! $report;

        return $this;
    }

    /**
     *
     * @param string $type
     * @return databox_field
     */
    public function set_type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     *
     * @param string $type
     * @return databox_field
     */
    public function set_tbranch($branch)
    {
        $this->tbranch = $branch;

        return $this;
    }

    /**
     *
     * @param string $type
     * @return databox_field
     */
    public function set_separator($separator)
    {
        $this->separator = self::checkMultiSeparator($separator, $this->multi);

        return $this;
    }

    /**
     * Return the separator depending of the multi attribute
     *
     * @param   string    $separator
     * @param   boolean   $multi
     * @return  string
     */
    protected static function checkMultiSeparator($separator, $multi)
    {
        if ( ! $multi) {
            return '';
        }

        if (strpos($separator, ';') === false) {
            $separator .= ';';
        }

        return $separator;
    }

    /**
     *
     * @param string $type
     * @return databox_field
     */
    public function set_thumbtitle($value)
    {
        $this->thumbtitle = $value;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_thumbtitle()
    {
        return $this->thumbtitle;
    }

    /**
     *
     * @return integer
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     *
     * @return string
     */
    public function get_tbranch()
    {
        return $this->tbranch;
    }

    /**
     *
     * @return string
     */
    public function get_separator()
    {
        return $this->separator;
    }

    /**
     *
     * @return boolean
     */
    public function is_indexable()
    {
        return $this->indexable;
    }

    /**
     *
     * @return boolean
     */
    public function is_readonly()
    {
        return $this->readonly;
    }

    /**
     *
     * @return boolean
     */
    public function is_required()
    {
        return $this->required;
    }

    /**
     *
     * @return boolean
     */
    public function is_multi()
    {
        return $this->multi;
    }

    /**
     *
     * @return boolean
     */
    public function is_report()
    {
        return $this->report;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Return true is the field is unknown
     *
     * @return boolean
     */
    public function is_on_error()
    {
        return false;
    }

    public static function create(databox $databox, $name)
    {
        $sorter = 0;

        $sql = 'SELECT (MAX(sorter) + 1) as sorter FROM metadatas_structure';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
            $sorter = (int) $row['sorter'];

        $sql = "INSERT INTO metadatas_structure
        (`id`, `name`, `src`, `readonly`, `indexable`, `type`, `tbranch`,
          `thumbtitle`, `multi`, `business`,
          `report`, `sorter`)
        VALUES (null, :name, '', 0, 1, 'string', '',
          null, 0,
          0, 1, :sorter)";

        $name = self::generateName($name);

        if ($name === '') {
            throw new \Exception_InvalidArgument();
        }

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute(array(':name'   => $name, ':sorter' => $sorter));
        $id = $databox->get_connection()->lastInsertId();
        $stmt->closeCursor();

        $databox->delete_data_from_cache(databox::CACHE_META_STRUCT);

        return self::get_instance($databox, $id);
    }

    public static function generateName($name)
    {
        $unicode_processor = new unicode();

        $name = $unicode_processor->remove_nonazAZ09($name, false, false);

        return $unicode_processor->remove_first_digits($name);
    }

    /**
     *
     * @return array
     */
    public function __sleep()
    {
        $vars = array();
        foreach ($this as $key => $value) {
            if (in_array($key, array('databox')))
                continue;
            $vars[] = $key;
        }

        return $vars;
    }

    /**
     *
     * @return void
     */
    public function __wakeup()
    {
        $databox = databox::get_instance($this->sbas_id);
        $this->set_databox($databox);

        return;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param string $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'field_' . $this->get_id() . ($option ? $option . '_' : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param string $option
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        return $this->databox->get_data_from_cache($this->get_cache_key($option));
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param mixed $value
     * @param string $option
     * @param int $duration
     * @return caption_field
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param string $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        return $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }
}
