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
use Alchemy\Phrasea\Core\Event\Record\Structure\FieldDeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\FieldEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\FieldUpdatedEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvents;
use Alchemy\Phrasea\Metadata\TagFactory;
use Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface;
use Alchemy\Phrasea\Metadata\Tag\NoSource;
use Doctrine\DBAL\Connection;
use PHPExiftool\Exception\TagUnknown;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class databox_field implements cache_cacheableInterface
{
    protected $id;

    /** @var Application  */
    protected $app;

    /** @var databox */
    protected $databox;

    /** DO NOT IMPORT, makes PHPSTORM HANG. PHPExiftool\Driver\TagInterface */
    private $tag;

    protected $name;
    protected $indexable;
    protected $readonly;
    protected $position;
    protected $required;
    protected $multi;
    protected $report;
    protected $type;
    protected $tbranch;
    protected $generate_cterms;
    protected $gui_editable;
    protected $gui_visible;
    protected $printable;
    protected $separator;
    protected $thumbtitle;

    /** @var array */
    protected $labels = [];

    /** @var boolean */
    protected $Business;

    protected $renamed = false;

    /** @var int */
    protected $sbas_id;

    protected static $_instance = [];
    protected static $knownDCES = [
        'Contributor' => 'databox_Field_DCES_Contributor',
        'Coverage'    => 'databox_Field_DCES_Coverage',
        'Creator'     => 'databox_Field_DCES_Creator',
        'Date'        => 'databox_Field_DCES_Date',
        'Description' => 'databox_Field_DCES_Description',
        'Format'      => 'databox_Field_DCES_Format',
        'Identifier'  => 'databox_Field_DCES_Identifier',
        'Language'    => 'databox_Field_DCES_Language',
        'Publisher'   => 'databox_Field_DCES_Publisher',
        'Relation'    => 'databox_Field_DCES_Relation',
        'Rights'      => 'databox_Field_DCES_Rights',
        'Source'      => 'databox_Field_DCES_Source',
        'Subject'     => 'databox_Field_DCES_Subject',
        'Title'       => 'databox_Field_DCES_Title',
        'Type'        => 'databox_Field_DCES_Type',
    ];

    /**
     * @var databox_Field_DCESAbstract|null
     */
    private $dces_element;

    /**
     * @var ControlProviderInterface|null
     */
    protected $vocabulary_control;

    /**
     * @var string|null
     */
    protected $VocabularyType;

    /**
     * @var bool
     */
    protected $VocabularyRestriction = false;
    private   $on_error = false;
    protected $original_src;
    protected $original_dces;
    protected $aggregable;

    const TYPE_DATE = "date";
    const TYPE_STRING = "string";
    const TYPE_NUMBER = "number";

    // http://dublincore.org/documents/dces/
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

    const FACET_DISABLED = 0;
    const FACET_NO_LIMIT = -1;

    /**
     * @param Application $app
     * @param databox     $databox
     * @param array       $row
     */
    public function __construct(Application $app, databox $databox, array $row)
    {
        $this->app = $app;
        $this->set_databox($databox);
        $this->sbas_id = $databox->get_sbas_id();

        $this->loadFromRow($row);
    }

    /**
     * @param array $row
     */
    protected function loadFromRow(array $row)
    {
        $this->id = (int)$row['id'];
        $this->name = $row['name'];
        $this->original_src = $row['src'];
        $this->original_dces = $row['dces_element'];
        $this->tag = false;                  // lazy loaded on this->get_tag(), will become an object
        $this->dces_element = false;         // loazy loaded on this->get_dces_element(), will become an object or null
        $this->on_error = false;             // lazy calculated on this->is_on_error()
        $this->vocabulary_control = false;   // lazy loaded

        foreach (['en', 'fr', 'de', 'nl'] as $code) {
            $this->labels[$code] = $row['label_' . $code];
        }

        $this->indexable = (bool)$row['indexable'];
        $this->readonly = (bool)$row['readonly'];
        $this->required = (bool)$row['required'];
        $this->multi = (bool)$row['multi'];
        $this->Business = (bool)$row['business'];
        $this->report = (bool)$row['report'];
        $this->aggregable = (int)$row['aggregable'];
        $this->position = (int)$row['sorter'];
        $this->type = $row['type'] ?: self::TYPE_STRING;
        $this->tbranch = $row['tbranch'];
        $this->generate_cterms = (bool)$row['generate_cterms'];
        $this->gui_editable = (bool)$row['gui_editable'];
        $this->gui_visible = (bool)$row['gui_visible'];
        $this->printable = (bool)$row['printable'];
        $this->VocabularyType = $row['VocabularyControlType'];
        $this->VocabularyRestriction = (bool)$row['RestrictToVocabularyControl'];

        $this->separator = self::checkMultiSeparator($row['separator'], $this->multi);

        $this->thumbtitle = $row['thumbtitle'];
    }

    /**
     * @return ControlProviderInterface
     */
    public function getVocabularyControl()
    {
        return $this->vocabulary_control;
    }

    /**
     * @return boolean
     */
    public function isVocabularyRestricted()
    {
        // lazy load
        if($this->vocabulary_control === false) {
            $this->loadVocabulary();
        }

        return $this->vocabulary_control;
    }

    /**
     * @return boolean
     */
    public function isBusiness()
    {
        return $this->Business;
    }

    public function isAggregable()
    {
        return $this->aggregable !== self::FACET_DISABLED;
    }

    /**
     * A value of "0" means no facets (in that case, isAggregable() returns false too)
     * "-1" means all facet values should be returned (no limit)
     *
     * @return integer Maximum expected number of values on this facet
     */
    public function getFacetValuesLimit()
    {
        return $this->aggregable;
    }

    public function hydrate(Application $app)
    {
        $this->app = $app;
        $this->set_databox($this->app->findDataboxById($this->sbas_id));
        $this->loadVocabulary();
    }

    /**
     * @param databox $databox
     */
    public function set_databox(databox $databox)
    {
        $this->databox = $databox;
    }

    /**
     * @return Connection
     */
    public function get_connection()
    {
        return $this->databox->get_connection();
    }

    public function get_original_source()
    {
        return $this->original_src;
    }

    /**
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    public function delete()
    {
        caption_field::delete_all_metadatas($this->app, $this);

        $connbas = $this->get_connection();
        $sql = 'DELETE FROM metadatas_structure WHERE id = :id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':id' => $this->get_id()]);
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

        $this->dispatchEvent(RecordStructureEvents::FIELD_DELETED, new FieldDeletedEvent($this->databox, $this));

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
          `aggregable` = :aggregable,
          `report` = :report,
          `type` = :type,
          `tbranch` = :tbranch,
          `generate_cterms` = :generate_cterms,
          `gui_editable` = :gui_editable,
          `gui_visible` = :gui_visible,
          `printable` = :printable,
          `sorter` = :position,
          `thumbtitle` = :thumbtitle,
          `VocabularyControlType` = :VocabularyControlType,
          `RestrictToVocabularyControl` = :RestrictVocab,
          `label_en` = :label_en,
          `label_fr` = :label_fr,
          `label_de` = :label_de,
          `label_nl` = :label_nl
          WHERE id = :id';

        $params = [
            ':name'                  => $this->name,
            ':source'                => $this->get_tag()->getTagname(),
            ':indexable'             => $this->indexable ? '1' : '0',
            ':readonly'              => $this->readonly ? '1' : '0',
            ':required'              => $this->required ? '1' : '0',
            ':separator'             => $this->separator,
            ':multi'                 => $this->multi ? '1' : '0',
            ':business'              => $this->Business ? '1' : '0',
            ':aggregable'            => $this->aggregable,
            ':report'                => $this->report ? '1' : '0',
            ':type'                  => $this->type,
            ':tbranch'               => $this->tbranch,
            ':generate_cterms'        => $this->generate_cterms ? '1' : '0',
            ':gui_editable'          => $this->gui_editable ? '1' : '0',
            ':gui_visible'          => $this->gui_visible ? '1' : '0',
            ':printable'             => $this->printable ? '1' : '0',
            ':position'              => $this->position,
            ':thumbtitle'            => $this->thumbtitle,
            ':VocabularyControlType' => $this->getVocabularyControl() ? $this->getVocabularyControl()->getType() : null,
            ':RestrictVocab'         => $this->getVocabularyControl() ? ($this->VocabularyRestriction ? '1' : '0') : '0',
            ':id'                    => $this->id,
            ':label_en'              => isset($this->labels['en']) ? $this->labels['en'] : null,
            ':label_fr'              => isset($this->labels['fr']) ? $this->labels['fr'] : null,
            ':label_de'              => isset($this->labels['de']) ? $this->labels['de'] : null,
            ':label_nl'              => isset($this->labels['nl']) ? $this->labels['nl'] : null
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        if ($this->renamed) {
            caption_field::rename_all_metadatas($this->app, $this);
            $this->renamed = false;
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

            if ($this->name != $meta->nodeName) {
                $old_meta = $meta;
                $meta = $dom_struct->createElement($this->name);
                $nodes_parent = $xp_struct->query('/record/description');
                $nodes_parent->item(0)->replaceChild($meta, $old_meta);
            }
        }
        $meta->setAttribute('src', $this->get_tag()->getTagname());
        $meta->setAttribute('index', $this->indexable ? '1' : '0');
        $meta->setAttribute('readonly', $this->readonly ? '1' : '0');
        $meta->setAttribute('required', $this->required ? '1' : '0');
        $meta->setAttribute('multi', $this->multi ? '1' : '0');
        $meta->setAttribute('report', $this->report ? '1' : '0');
        $meta->setAttribute('business', $this->Business ? '1' : '0');
        $meta->setAttribute('aggregable', $this->aggregable);
        $meta->setAttribute('type', $this->type);
        $meta->setAttribute('tbranch', $this->tbranch);
        $meta->setAttribute('generate_cterms', $this->generate_cterms ? '1' : '0');
        $meta->setAttribute('gui_editable', $this->gui_editable ? '1' : '0');
        $meta->setAttribute('gui_visible', $this->gui_visible ? '1' : '0');
        $meta->setAttribute('printable', $this->printable ? '1' : '0');
        if ($this->multi) {
            $meta->setAttribute('separator', $this->separator);
        }
        $meta->setAttribute('thumbtitle', $this->thumbtitle);
        $meta->setAttribute('meta_id', $this->id);
        $meta->setAttribute('sorter', $this->position);

        $this->delete_data_from_cache();
        $this->databox->saveStructure($dom_struct);

        $this->dispatchEvent(RecordStructureEvents::FIELD_UPDATED, new FieldUpdatedEvent($this->databox, $this));

        return $this;
    }

    private function dispatchEvent($eventName, FieldEvent $event = null)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    /**
     * Sets a localized label for the field.
     *
     * @param string      $code
     * @param null|string $value
     *
     * @return \databox_field
     *
     * @throws InvalidArgumentException
     */
    public function set_label($code, $value)
    {
        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        $this->labels[$code] = $value;

        return $this;
    }

    /**
     * Gets a localized label for the field.
     *
     * @param string $code
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function get_label($code)
    {
        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        return isset($this->labels[$code]) && '' !== $this->labels[$code] ? $this->labels[$code] : $this->name;
    }

    /**
     * get all localized labels
     *
     * @return string[]
     */
    public function get_labels()
    {
        return $this->labels;
    }

    /**
     * @param string $name
     * @return databox_field
     *
     * @throws Exception_InvalidArgument
     */
    public function set_name($name)
    {
        $previous_name = $this->name;

        $name = self::generateName($name, $this->app['unicode']);

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
     * @param string $tagName
     * @param bool    $throwException
     * @return object Makes phpstorm hangs \PHPExiftool\Driver\TagInterface
     */
    public static function loadClassFromTagName($tagName, $throwException = true)
    {
        $tagName = str_ireplace('/rdf:rdf/rdf:description/', '', $tagName);

        if ('' === trim($tagName)) {
            return new NoSource();
        }

        try {
            return TagFactory::getFromTagname($tagName);
        } catch (TagUnknown $exception) {
            if ($throwException) {
                throw new NotFoundHttpException(sprintf("Tag %s not found", $tagName), $exception);
            }
        }

        return new NoSource($tagName);
    }

    /**
     * @param object $tag Actually \PHPExiftool\Driver\TagInterface but make PHPStorm hangs
     * @return $this
     */
    public function set_tag($tag = null)
    {
        if ($tag === null) {
            $tag = new NoSource();
        }

        $this->tag = $tag;

        return $this;
    }

    /**
     * @return object Avoid PHPStorm stucks on 16k classes... \PHPExiftool\Driver\TagInterface
     */
    public function get_tag()
    {
        // lazy loading
        if ($this->tag === false) {
            $this->tag = in_array($this->original_src, ['', 'Phraseanet:no-source'], true)
                ? new NoSource($this->name)
                : self::loadClassFromTagName($this->original_src, false);
        }

        return $this->tag;
    }

    /**
     * @return databox_Field_DCESAbstract
     */
    public function get_dces_element()
    {
        // lazy loading
        if ($this->dces_element === false) {
            if (array_key_exists($this->original_dces, self::$knownDCES)) {
                $class = self::$knownDCES[$this->original_dces];
                $this->dces_element = new $class();
            } else {
                $this->dces_element = null;
            }
        }

        return $this->dces_element;
    }

    public function set_dces_element(databox_Field_DCESAbstract $DCES_element = null)
    {
        $connection = $this->get_connection();

        if (null !== $DCES_element) {
            $connection->executeUpdate(
                'UPDATE metadatas_structure SET dces_element = null WHERE dces_element = :dces_element',
                [
                    'dces_element' => $DCES_element->get_label(),
                ]
            );
        }

        $connection->executeUpdate(
            'UPDATE metadatas_structure SET dces_element = :dces_element WHERE id = :id',
            [
                'dces_element' => $DCES_element ? $DCES_element->get_label() : null,
                'id' => $this->id,
            ]
        );

        $this->dces_element = $DCES_element;

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  boolean       $bool
     * @return databox_field
     */
    public function set_indexable($bool)
    {
        $this->indexable = (bool)$bool;

        return $this;
    }

    /**
     *
     * @param  boolean       $bool
     * @return databox_field
     */
    public function set_multi($bool)
    {
        $this->multi = (bool)$bool;
        $this->separator = self::checkMultiSeparator($this->separator, $this->multi);
        return $this;
    }

    /**
     * Set a vocabulary
     *
     * @param  ControlProviderInterface $vocabulary_control
     * @return \databox_field
     */
    public function setVocabularyControl(ControlProviderInterface $vocabulary_control = null)
    {
        $this->vocabulary_control = $vocabulary_control;

        return $this;
    }

    /**
     * Set whether or not the vocabulary is restricted to the provider
     *
     * @param  boolean        $boolean
     * @return \databox_field
     */
    public function setVocabularyRestricted($boolean)
    {
        $this->VocabularyRestriction = (bool)$boolean;

        return $this;
    }

    /**
     *
     * @param boolean $readonly
     *
     * @return databox_field
     */
    public function set_readonly($readonly)
    {
        $this->readonly = (bool)$readonly;

        return $this;
    }

    /**
     *
     * @param  boolean       $boolean
     * @return databox_field
     */
    public function set_business($boolean)
    {
        $this->Business = (bool)$boolean;

        if ($this->Business) {
            $this->thumbtitle = null;
        }

        return $this;
    }

    public function set_aggregable($int)
    {
        $this->aggregable = $int;


        return $this;
    }

    /**
     *
     * @param  boolean       $required
     * @return databox_field
     */
    public function set_required($required)
    {
        $this->required = (bool)$required;

        return $this;
    }

    /**
     *
     * @param  boolean       $report
     * @return databox_field
     */
    public function set_report($report)
    {
        $this->report = (bool)$report;

        return $this;
    }

    /**
     *
     * @param  string        $type
     * @return databox_field
     */
    public function set_type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     *
     * @param  string        $branch
     * @return databox_field
     */
    public function set_tbranch($branch)
    {
        $this->tbranch = $branch;

        return $this;
    }

    /**
     * @param  boolean       $generate_cterms
     * @return databox_field
     */
    public function set_generate_cterms($generate_cterms)
    {
        $this->generate_cterms = $generate_cterms;

        return $this;
    }

    /**
     * @param  boolean       $gui_editable
     * @return databox_field
     */
    public function set_gui_editable($gui_editable)
    {
        $this->gui_editable = $gui_editable;

        return $this;
    }

    /**
     * @param  boolean       $gui_visible
     * @return databox_field
     */
    public function set_gui_visible($gui_visible)
    {
        $this->gui_visible = $gui_visible;

        return $this;
    }

    /**
     * @param  boolean       $printable
     * @return databox_field
     */
    public function set_printable($printable)
    {
        $this->printable = $printable;

        return $this;
    }

    /**
     *
     * @param  string        $separator
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
     * @param  string  $separator
     * @param  boolean $multi
     * @return string
     */
    private static function checkMultiSeparator($separator, $multi)
    {
        if (! $multi) {
            return '';
        }

        if (strpos($separator, ';') === false) {
            $separator .= ';';
        }

        return $separator;
    }

    /**
     *
     * @param  string        $value
     * @return databox_field
     */
    public function set_thumbtitle($value)
    {
        $this->thumbtitle = $value;

        if (!$this->thumbtitle) {
            $this->Business = false;
        }

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
     * @return boolean
     */
    public function get_generate_cterms()
    {
        return $this->generate_cterms;
    }

    /**
     *
     * @return boolean
     */
    public function get_gui_editable()
    {
        return $this->gui_editable;
    }

    /**
     *
     * @return boolean
     */
    public function get_gui_visible()
    {
        return $this->gui_visible;
    }

    /**
     *
     * @return boolean
     */
    public function get_printable()
    {
        return $this->printable;
    }

    /**
     * @param Boolean $all If set to false, returns a one-char separator to use for serialiation
     *
     * @return string
     */
    public function get_separator($all = true)
    {
        if ($all) {
            return $this->separator;
        }

        return substr($this->separator, 0, 1);
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
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_position()
    {
        return $this->position;
    }

    /**
     *
     * @return string
     */
    public function set_position($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Return true is the field is unknown
     *
     * @return boolean
     */
    public function is_on_error()
    {
        return $this->original_src !== '' && $this->original_src !== $this->get_tag()->getTagname();
    }

    public function toArray()
    {
        return [
            'id'                    => $this->id,
            'sbas-id'               => $this->sbas_id,
            'labels'                => $this->labels,
            'name'                  => $this->name,
            'tag'                   => $this->get_tag()->getTagname(),
            'business'              => $this->Business,
            'aggregable'            => $this->aggregable,
            'type'                  => $this->type,
            'sorter'                => $this->position,
            'thumbtitle'            => $this->thumbtitle,
            'tbranch'               => $this->tbranch,
            'generate_cterms'        => $this->generate_cterms,
            'gui_editable'          => $this->gui_editable,
            'gui_visible'          => $this->gui_visible,
            'printable'             => $this->printable,
            'separator'             => $this->separator,
            'required'              => $this->required,
            'report'                => $this->report,
            'readonly'              => $this->readonly,
            'multi'                 => $this->multi,
            'indexable'             => $this->indexable,
            'dces-element'          => $this->get_dces_element() ? $this->get_dces_element()->get_label() : null,
            'vocabulary-type'       => $this->getVocabularyControl() ? $this->getVocabularyControl()->getType() : null,
            'vocabulary-restricted' => $this->VocabularyRestriction,
        ];
    }

    /**
     *
     * @param \Alchemy\Phrasea\Application $app
     * @param databox                      $databox
     * @param string                       $name
     * @param bool                         $multi
     *
     * @return self
     *
     * @throws \Exception_InvalidArgument
     */
    public static function create(Application $app, databox $databox, $name)
    {
        $sorter = 0;

        $sql = 'SELECT (MAX(sorter) + 1) as sorter FROM metadatas_structure';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $sorter = max(1, (int) $row['sorter']);
        }

        $sql = "INSERT INTO metadatas_structure
        (`id`, `name`, `src`, `readonly`, `gui_editable`,`gui_visible`, `printable`, `required`, `indexable`, `type`, `tbranch`, `generate_cterms`,
          `thumbtitle`, `multi`, `business`, `aggregable`,
          `report`, `sorter`, `separator`)
        VALUES (null, :name, '', 0, 1, 1, 1, 0, 1, 'string', '', 1,
          null, 0, 0, 0,
           1, :sorter, '')";

        $name = self::generateName($name, $app['unicode']);

        if ($name === '') {
            throw new \Exception_InvalidArgument();
        }

        $connection = $databox->get_connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute([':name'   => $name, ':sorter' => $sorter]);
        $id = $connection->lastInsertId();
        $stmt->closeCursor();

        $databox->delete_data_from_cache(databox::CACHE_META_STRUCT);

        return $databox->get_meta_structure()->get_element($id);
    }

    public static function generateName($name, unicode $unicode_processor)
    {
        $name = $unicode_processor->remove_nonazAZ09($name, false, false);

        return $unicode_processor->remove_first_digits($name);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = [];

        foreach ($this as $key => $value) {
            if (in_array($key, ['databox', 'app', 'vocabulary_control']))
                continue;
            $vars[] = $key;
        }

        return $vars;
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'field_' . $this->get_id() . ($option ? $option . '_' : '');
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string $option
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        return $this->databox->get_data_from_cache($this->get_cache_key($option));
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  mixed         $value
     * @param  string        $option
     * @param  int           $duration
     * @return caption_field
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Part of the cache_cacheableInterface
     *
     * @param  string        $option
     * @return caption_field
     */
    public function delete_data_from_cache($option = null)
    {
        $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }

    private function loadVocabulary()
    {
        if ($this->VocabularyType === '') {
            return;
        }

        try {
            $this->vocabulary_control = $this->app['vocabularies'][$this->VocabularyType];
        } catch (\InvalidArgumentException $e) {
            // Could not find Vocabulary
        }
    }

    public static function purge()
    {
        self::$_instance = [];
    }
}
