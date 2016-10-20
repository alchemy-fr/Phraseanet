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
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;

class record_exportElement extends record_adapter
{
    /**
     *
     * @var Array
     */
    protected $downloadable;

    /**
     *
     * @var Array
     */
    protected $orderable;

    /**
     *
     * @var Array
     */
    protected $size;

    /**
     *
     * @var int
     */
    protected $remain_hd;

    /**
     *
     * @var string
     */
    protected $directory;

    /**
     *
     * @param Application $app
     * @param integer     $sbas_id
     * @param integer     $record_id
     * @param string      $directory
     * @param integer     $remain_hd
     *
     * @return record_exportElement
     */
    public function __construct(Application $app, $sbas_id, $record_id, $directory = '', $remain_hd = false)
    {
        $this->directory = $directory;

        if ($this->directory) {
            $unicode = new \unicode();
            $this->directory = $unicode->remove_nonazAZ09($this->directory) . '/';
        }

        $this->remain_hd = $remain_hd;
        $this->size = [];
        parent::__construct($app, $sbas_id, $record_id);

        $this->get_actions($remain_hd);
    }

    /**
     *
     * @return record_exportElement
     */
    protected function get_actions()
    {
        $this->downloadable = $downloadable = [];
        $this->orderable = $orderable = [];

        $sd = $this->get_subdefs();

        $sbas_id = phrasea::sbasFromBas($this->app, $this->getBaseId());

        /** @var databox_subdef[] $subdefs */
        $subdefs = [];

        foreach ($this->app->findDataboxById($sbas_id)->get_subdef_structure() as $subdef_type => $subdefs_obj) {
            if ($subdef_type == $this->getType()) {
                $subdefs = $subdefs_obj;
                break;
            }
        }

        $go_dl = [
            'document'  => false,
            'preview'   => false,
            'thumbnail' => true
        ];

        if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($this->getBaseId(), \ACL::CANDWNLDHD)) {
            $go_dl['document'] = true;
        }
        if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($this->getBaseId(), \ACL::CANDWNLDPREVIEW)) {
            $go_dl['preview'] = true;
        }
        if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_hd_grant($this)) {
            $go_dl['document'] = true;
            $go_dl['preview'] = true;
        }
        if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_preview_grant($this)) {
            $go_dl['preview'] = true;
        }

        $query = $this->app['phraseanet.user-query'];

        $masters = $query->on_base_ids([$this->getBaseId()])
                ->who_have_right([\ACL::ORDER_MASTER])
                ->execute()->get_results();

        $go_cmd = (count($masters) > 0 && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($this->getBaseId(), 'cancmd'));

        $orderable['document'] = false;
        $downloadable['document'] = false;

        if (isset($sd['document']) && is_file($sd['document']->getRealPath())) {
            if ($go_dl['document'] === true) {
                if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->is_restricted_download($this->getBaseId())) {
                    $this->remain_hd --;
                    if ($this->remain_hd >= 0) {
                        $localizedLabel = $this->app->trans('document original');
                        $downloadable['document'] = [
                            'class' => 'document',
                            /** @Ignore */
                            'label' => $localizedLabel,
                        ];
                    }
                } else {
                    $localizedLabel = $this->app->trans('document original');
                    $downloadable['document'] = [
                        'class' => 'document',
                        /** @Ignore */
                        'label' => $localizedLabel
                    ];
                }
            }
            if ($go_cmd === true) {
                $orderable['document'] = true;
            }

            $this->add_count('document', $sd['document']->get_size());
        }

        foreach ($subdefs as $subdef) {
            $name = $subdef->get_name();
            $class = $subdef->get_class();

            $subdef_label = $name;
            foreach ($subdef->get_labels() as $lang => $label) {
                if (trim($label) == '')
                    continue;

                if ($lang == $this->app['locale']) {
                    $subdef_label = $label;
                    break;
                }
                $subdef_label = $label;
            }

            $downloadable[$name] = false;

            $downloadable_settings = $subdef->is_downloadable();

            if (! $downloadable_settings || $go_dl[$class] === false) {
                continue;
            }

            if ($go_dl[$class]) {
                if (isset($sd[$name]) && $sd[$name]->is_physically_present()) {
                    if ($class == 'document') {

                        if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->is_restricted_download($this->getBaseId())) {
                            $this->remain_hd --;
                            if ($this->remain_hd >= 0)
                                $downloadable[$name] = [
                                    'class'              => $class,
                                    /** @Ignore */
                                    'label'              => $subdef_label
                                ];
                        } else
                            $downloadable[$name] = [
                                'class' => $class,
                                /** @Ignore */
                                'label' => $subdef_label
                            ];
                    } else {
                        $downloadable[$name] = [
                            'class' => $class,
                            /** @Ignore */
                            'label' => $subdef_label
                        ];
                    }

                    $this->add_count($name, $sd[$name]->get_size());
                }
            }
        }

        $xml = $this->app['serializer.caption']->serialize($this->get_caption(), CaptionSerializer::SERIALIZE_XML);

        if ($xml) {
            $localizedLabel = $this->app->trans('caption XML');
            $downloadable['caption'] = [
                'class' => 'caption',
                /** @Ignore */
                'label' => $localizedLabel,
            ];
            $this->add_count('caption', strlen($xml));

            $localizedLabel = $this->app->trans('caption YAML');
            $downloadable['caption-yaml'] = [
                'class' => 'caption',
                /** @Ignore */
                'label' => $localizedLabel,
            ];
            $this->add_count('caption-yaml', strlen(strip_tags($xml)));
        }

        $this->downloadable = $downloadable;
        $this->orderable = $orderable;

        return $this;
    }

    /**
     *
     * @param  string               $name
     * @param  int                  $size
     * @return record_exportElement
     */
    private function add_count($name, $size)
    {
        if (! $this->size) {
            $objectsize = [];
        } else
            $objectsize = $this->size;

        $objectsize[$name] = $size;

        $this->size = $objectsize;

        return $this;
    }

    /**
     *
     * @param  string $name
     * @return mixed  content
     */
    public function get_size($name = false)
    {
        if ($name) {
            return $this->size[$name];
        } else {
            return $this->size;
        }
    }

    /**
     * @return array
     */
    public function get_orderable()
    {
        return $this->orderable;
    }

    /**
     * @return array
     */
    public function get_downloadable()
    {
        return $this->downloadable;
    }

    /**
     *
     * @return int
     */
    public function get_remain_hd()
    {
        return $this->remain_hd;
    }

    /**
     *
     * @return string
     */
    public function get_directory()
    {
        return $this->directory;
    }
}
