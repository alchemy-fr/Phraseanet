<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Assert\Assertion;
use databox_descriptionStructure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchEngineOptions
{
    const RECORD_RECORD = 0;
    const RECORD_GROUPING = 1;
    const RECORD_STORY = 2;
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_FLASH = 'flash';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_ALL = '';
    const SORT_RELEVANCE = 'relevance';
    const SORT_CREATED_ON = 'created_on';
    const SORT_UPDATED_ON = 'updated_on';
    const SORT_RANDOM = 'random';
    const SORT_MODE_ASC = 'asc';
    const SORT_MODE_DESC = 'desc';

    /** @var CollectionReferenceRepository */
    private $collectionReferenceRepository;

    /** @var string */
    protected $record_type = self::TYPE_ALL;

    protected $search_type = self::RECORD_RECORD;

    /** @var null|int[]  bases ids where searching is done */
    private $basesIds = null;

    /** @var  null|CollectionReference[][] */
    private $collectionsReferencesByDatabox = null;

    /** @var null|\int[] */
    private $databoxesIds;

    /** @var \databox_field[] */

    protected $fields = [];
    protected $status = [];
    /** @var \DateTime */
    protected $date_min;
    /** @var \DateTime */
    protected $date_max;
    protected $date_fields = [];
    /** @var string */
    protected $i18n;
    /** @var bool */
    protected $stemming = true;
    /** @var bool */
    protected $use_truncation = false;
    /** @var string */
    protected $sort_by;

    /** @var string */
    protected $sort_ord = self::SORT_MODE_DESC;

    /** @var int[] */
    protected $business_fields = [];

    /** @var int */
    private $max_results = 10;

    /** @var bool */
    private $include_unset_field_facet = false;

    /** @var int */
    private $first_result = 0;

    private static $serializable_properties = [
        'record_type',
        'search_type',
        'basesIds',
        'fields',
        'status',
        'date_min',
        'date_max',
        'date_fields',
        'i18n',
        'stemming',
        'sort_by',
        'sort_ord',
        'business_fields',
        'max_results',
        'first_result',
        'use_truncation',
        'include_unset_field_facet',
    ];

    /**
     * Defines locale code to use for query
     *
     * @param string $locale An i18n locale code
     * @return $this
     */
    public function setLocale($locale)
    {
        if ($locale && !preg_match('/[a-z]{2,3}/', $locale)) {
            throw new \InvalidArgumentException('Locale must be a valid i18n code');
        }

        $this->i18n = $locale;

        return $this;
    }

    /**
     * Returns the locale value
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->i18n;
    }

    /**
     * @param  string $sort_by
     * @param  string $sort_ord
     * @return $this
     */
    public function setSort($sort_by, $sort_ord = self::SORT_MODE_DESC)
    {
        $this->sort_by = $sort_by;
        $this->sort_ord = $sort_ord;

        return $this;
    }

    /**
     * Allows business fields query on the given bases
     *
     * @param int[] $basesIds
     * @return $this
     */
    public function allowBusinessFieldsOn(array $basesIds)
    {
        $this->business_fields = $basesIds;

        return $this;
    }

    /**
     * Reset business fields settings
     *
     * @return $this
     */
    public function disallowBusinessFields()
    {
        $this->business_fields = [];

        return $this;
    }

    /**
     * Returns an array of bases ids on which business fields are allowed to
     * search on
     *
     * @return int[]
     */
    public function getBusinessFieldsOn()
    {
        return $this->business_fields;
    }

    /**
     * Returns the sort criteria
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Returns the sort order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sort_ord;
    }

    /**
     * Tells whether to use stemming or not
     *
     * @param  boolean             $boolean
     * @return $this
     */
    public function setStemming($boolean)
    {
        $this->stemming = !!$boolean;

        return $this;
    }

    /**
     * Tells whether to use truncation or not
     *
     * @param  boolean             $boolean
     * @return $this
     */
    public function setUseTruncation($boolean)
    {
        $this->use_truncation = !!$boolean;

        return $this;
    }

    /**
     * Return wheter the use of truncation is enabled or not
     *
     * @return boolean
     */
    public function useTruncation()
    {
        return $this->use_truncation;
    }

    /**
     * Return wheter the use of stemming is enabled or not
     *
     * @return boolean
     */
    public function isStemmed()
    {
        return $this->stemming;
    }

    public function setIncludeUnsetFieldFacet(bool $include_unset_field_facet)
    {
        $this->include_unset_field_facet = $include_unset_field_facet;
    }

    public function getIncludeUnsetFieldFacet()
    {
        return $this->include_unset_field_facet;
    }

    /**
     * Set document type to search for
     *
     * @param  int                 $search_type
     * @return $this
     */
    public function setSearchType($search_type)
    {
        switch ($search_type) {
            case self::RECORD_RECORD:
            default:
                $this->search_type = self::RECORD_RECORD;
                break;
            case self::RECORD_GROUPING:
            case self::RECORD_STORY:
                $this->search_type = self::RECORD_GROUPING;
                break;
        }

        return $this;
    }

    /**
     * Returns the type of documents type to search for
     *
     * @return int
     */
    public function getSearchType()
    {
        return $this->search_type;
    }

    /**
     * Set the bases where to search for
     *
     * @param  null|int[] $basesIds An array of ids
     * @return $this
     */
    public function onBasesIds($basesIds)
    {
        $this->basesIds = $basesIds;

        // Defer databox retrieval
        $this->databoxesIds = null;

        return $this;
    }

    /**
     * Returns the bases ids on which the search occurs
     *
     * @return int[]
     */
    public function getBasesIds()
    {
        if($this->basesIds === null) {
            throw new \LogicException('onBasesIds() must be called before getBasesIds()');
        }

        return $this->basesIds;
    }

    /**
     * @param \databox_field[] $fields An array of Databox fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param  array $status
     * @return $this
     */
    public function setStatus(array $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param  string $record_type
     * @return $this
     */
    public function setRecordType($record_type)
    {
        switch ($record_type) {
            case self::TYPE_ALL:
            default:
                $this->record_type = self::TYPE_ALL;
                break;
            case self::TYPE_AUDIO:
                $this->record_type = self::TYPE_AUDIO;
                break;
            case self::TYPE_VIDEO:
                $this->record_type = self::TYPE_VIDEO;
                break;
            case self::TYPE_DOCUMENT:
                $this->record_type = self::TYPE_DOCUMENT;
                break;
            case self::TYPE_FLASH:
                $this->record_type = self::TYPE_FLASH;
                break;
            case self::TYPE_IMAGE:
                $this->record_type = self::TYPE_IMAGE;
                break;
            case self::TYPE_UNKNOWN:
                $this->record_type = self::TYPE_UNKNOWN;
                break;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getRecordType()
    {
        return $this->record_type;
    }

    /**
     * @param \DateTime $min_date
     * @return $this
     */
    public function setMinDate(\DateTime $min_date = null)
    {
        if ($min_date && $this->date_max && $min_date > $this->date_max) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_min = $min_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMinDate()
    {
        return $this->date_min;
    }

    /**
     * @param \DateTime|string $max_date
     * @return $this
     */
    public function setMaxDate(\DateTime $max_date = null)
    {
        if ($max_date && $this->date_max && $max_date < $this->date_min) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_max = $max_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMaxDate()
    {
        return $this->date_max;
    }

    /**
     * @param \databox_field[] $fields
     * @return $this
     */
    public function setDateFields(array $fields)
    {
        $this->date_fields = $fields;

        return $this;
    }

    /** @return \databox_field[] */
    public function getDateFields()
    {
        return $this->date_fields;
    }

    public function __construct(CollectionReferenceRepository $collectionReferenceRepository)
    {
        $this->collectionReferenceRepository = $collectionReferenceRepository;
    }

    /**
     * @param $app
     * @return DisplaySettingService
     */
    static private function getSettings($app)
    {
        return $app['settings'];
    }

    /**
     * Creates options based on a Symfony Request object
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return static
     */
    public static function fromRequest(Application $app, Request $request)
    {
        /** @var Authenticator $authenticator */
        $authenticator = $app->getAuthenticator();
        $isAuthenticated = $authenticator->isAuthenticated();
        $user = $authenticator->getUser();

        $options = new static($app['repo.collection-references']);

        if($user) {
            $options->setIncludeUnsetFieldFacet((Boolean)(self::getSettings($app)->getUserSetting($user, 'show_unset_field_facet', false)));
        }
        else {
            $options->setIncludeUnsetFieldFacet(false);
        }

        $options->disallowBusinessFields();
        $options->setLocale($app['locale']);

        $options->setSearchType($request->get('search_type'));
        $options->setRecordType($request->get('record_type'));
        $options->setSort($request->get('sort'), $request->get('ord', SearchEngineOptions::SORT_MODE_DESC));
        $options->setStemming((Boolean) $request->get('stemme'));

        $min_date = $max_date = null;
        if ($request->get('date_min')) {
            $min_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->get('date_min') . ' 00:00:00');
        }
        if ($request->get('date_max')) {
            $max_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->get('date_max') . ' 23:59:59');
        }
        $options->setMinDate($min_date);
        $options->setMaxDate($max_date);

        $status = is_array($request->get('status')) ? $request->get('status') : [];
        $options->setStatus($status);

        /** @var ACLProvider $aclProvider */
        $aclProvider = $app['acl'];
        $acl = $isAuthenticated ? $aclProvider->get($authenticator->getUser()) : null;
        if ($acl) {
            $searchableBaseIds = $acl->getSearchableBasesIds();
            if (is_array($request->get('bases'))) {
                $selected_bases = array_map(function($bid){return (int)$bid;}, $request->get('bases'));
                $searchableBaseIds = array_values(array_intersect($searchableBaseIds, $selected_bases));
                if (empty($searchableBaseIds)) {
                    throw new BadRequestHttpException('No collections match your criteria');
                }
            }
            $options->onBasesIds($searchableBaseIds);
            if ($acl->has_right(\ACL::CANMODIFRECORD)) {
                /** @var int[] $bf */
                $bf = array_filter($searchableBaseIds, function ($baseId) use ($acl) {
                    return $acl->has_right_on_base($baseId, \ACL::CANMODIFRECORD);
                });
                $options->allowBusinessFieldsOn($bf);
            }
        }
        else {
            $options->onBasesIds([]);
        }

        /** @var \databox[] $databoxes */
        $databoxes = [];
        foreach($options->getCollectionsReferencesByDatabox() as $sbid=>$refs) {
            $databoxes[] = $app->findDataboxById($sbid);
        }

        $queryFields = is_array($request->get('fields')) ? $request->get('fields') : [];
        if (empty($queryFields)) {
            // Select all fields (business included)
            foreach ($databoxes as $databox) {
                foreach ($databox->get_meta_structure() as $field) {
                    $queryFields[] = $field->get_name();
                }
            }
        }
        $queryFields = array_unique($queryFields);

        $queryDateFields = array_unique(explode('|', $request->get('date_field')));

        $databoxFields = [];
        $databoxDateFields = [];

        foreach ($databoxes as $databox) {
            $metaStructure = $databox->get_meta_structure();

            foreach ($queryFields as $fieldName) {
                try {
                    if( ($databoxField = $metaStructure->get_element_by_name($fieldName, databox_descriptionStructure::STRICT_COMPARE)) ) {
                        $databoxFields[] = $databoxField;
                    }
                } catch (\Exception $e) {
                    // no-op
                }
            }

            foreach ($queryDateFields as $fieldName) {
                try {
                    if( ($databoxField = $metaStructure->get_element_by_name($fieldName, databox_descriptionStructure::STRICT_COMPARE)) ) {
                        $databoxDateFields[] = $databoxField;
                    }
                } catch (\Exception $e) {
                    // no-op
                }
            }
        }

        $options->setFields($databoxFields);
        $options->setDateFields($databoxDateFields);

        $options->setUseTruncation((Boolean) $request->get('truncation'));

        return $options;
    }

    public function getCollectionsReferencesByDatabox()
    {
        if($this->collectionsReferencesByDatabox === null) {
            $this->collectionsReferencesByDatabox = [];
            $refs = $this->collectionReferenceRepository->findMany($this->getBasesIds());
            foreach($refs as $ref) {
                $sbid = $ref->getDataboxId();
                if(!array_key_exists($sbid, $this->collectionsReferencesByDatabox)) {
                    $this->collectionsReferencesByDatabox[$sbid] = [];
                }
                $this->collectionsReferencesByDatabox[$sbid][] = $ref;
            }
        }

        return $this->collectionsReferencesByDatabox;
    }

    public function setMaxResults($max_results)
    {
        Assertion::greaterOrEqualThan($max_results, 0);

        $this->max_results = (int)$max_results;
    }

    public function getMaxResults()
    {
        return $this->max_results;
    }

    /**
     * @param int $first_result
     * @return void
     */
    public function setFirstResult($first_result)
    {
        Assertion::greaterOrEqualThan($first_result, 0);

        $this->first_result = (int)$first_result;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->first_result;
    }

    /**
     * @param Application $app
     * @return callable[]
     */
    private static function getHydrateMethods(Application $app)
    {
        $fieldNormalizer = function ($value) use ($app) {
            return array_map(function ($serialized) use ($app) {
                $data = explode('_', $serialized, 2);
                return $app->findDataboxById($data[0])->get_meta_structure()->get_element($data[1]);
            }, $value);
        };

        $optionSetter = function ($setter) {
            return function ($value, SearchEngineOptions $options) use ($setter) {
                $options->{$setter}($value);
            };
        };

        return [
            'record_type' => $optionSetter('setRecordType'),
            'search_type' => $optionSetter('setSearchType'),
            'status' => $optionSetter('setStatus'),
            'date_min' => function ($value, SearchEngineOptions $options) {
                $options->setMinDate($value ? \DateTime::createFromFormat(DATE_ATOM, $value) : null);
            },
            'date_max' => function ($value, SearchEngineOptions $options) {
                $options->setMaxDate($value ? \DateTime::createFromFormat(DATE_ATOM, $value) : null);
            },
            'i18n' => function ($value, SearchEngineOptions $options) {
                if ($value) {
                    $options->setLocale($value);
                }
            },
            'stemming' => $optionSetter('setStemming'),
            'use_truncation' => $optionSetter('setUseTruncation'),
            'date_fields' => function ($value, SearchEngineOptions $options) use ($fieldNormalizer) {
                $options->setDateFields($fieldNormalizer($value));
            },
            'fields' => function ($value, SearchEngineOptions $options) use ($fieldNormalizer) {
                $options->setFields($fieldNormalizer($value));
            },
            'basesIds' => function ($value, SearchEngineOptions $options) {
                $options->onBasesIds($value);
            },
            //'business_fields' => function ($value, SearchEngineOptions $options) use ($collectionNormalizer) {
            //    $options->allowBusinessFieldsOn($collectionNormalizer($value));
            //},
            'business_fields' => function ($value, SearchEngineOptions $options) {
                $options->allowBusinessFieldsOn($value);
            },
            'first_result' => $optionSetter('setFirstResult'),
            'max_results' => $optionSetter('setMaxResults'),
            'include_unset_field_facet' => $optionSetter('setIncludeUnsetFieldFacet'),
        ];
    }

    public function serialize()
    {
        $ret = [];
        foreach (self::$serializable_properties as $key) {
            $value = $this->{$key};
            if ($value instanceof \DateTime) {
                $value = $value->format(DATE_ATOM);
            }
            if (in_array($key, ['date_fields', 'fields'])) {
                $value = array_map(function (\databox_field $field) {
                    return $field->get_databox()->get_sbas_id() . '_' . $field->get_id();
                }, $value);
            }
            $ret[$key] = $value;
        }

        return \p4string::jsonencode($ret);
    }

    /**
     *
     * @param Application $app
     * @param string      $serialized
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function hydrate(Application $app, $serialized)
    {
        $serialized = json_decode($serialized, true);
        if (!is_array($serialized)) {
            throw new \InvalidArgumentException('SearchEngineOptions data are corrupted');
        }

        $options = new static($app['repo.collection-references']);
        $options->disallowBusinessFields();

        $methods = self::getHydrateMethods($app);

        $sort_by = null;
        $methods['sort_by'] = function ($value) use (&$sort_by) {
            $sort_by = $value;
        };

        $sort_ord = null;
        $methods['sort_ord'] = function ($value) use (&$sort_ord) {
            $sort_ord = $value;
        };

        foreach ($serialized as $key => $value) {
            if (!isset($methods[$key])) {
                throw new \RuntimeException(sprintf('Unable to handle key `%s`', $key));
            }
            if ($value instanceof \stdClass) {
                $value = (array)$value;
            }
            $callable = $methods[$key];
            $callable($value, $options);
        }

        if ($sort_by) {
            if ($sort_ord) {
                $options->setSort($sort_by, $sort_ord);
            } else {
                $options->setSort($sort_by);
            }
        }

        return $options;
    }

}
