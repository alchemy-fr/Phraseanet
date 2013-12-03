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

        return $this;
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

        $sbas_id = phrasea::sbasFromBas($this->app, $this->base_id);

        $subdefgroups = $this->app['phraseanet.appbox']->get_databox($sbas_id)->get_subdef_structure();

        $subdefs = [];

        foreach ($subdefgroups as $subdef_type => $subdefs_obj) {
            if ($subdef_type == $this->get_type()) {
                $subdefs = $subdefs_obj;
                break;
            }
        }

        $go_dl = [
            'document'  => false,
            'preview'   => false,
            'thumbnail' => true
        ];

        if ($this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_base($this->get_base_id(), 'candwnldhd')) {
            $go_dl['document'] = true;
        }
        if ($this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_base($this->get_base_id(), 'candwnldpreview')) {
            $go_dl['preview'] = true;
        }
        if ($this->app['acl']->get($this->app['authentication']->getUser())->has_hd_grant($this)) {
            $go_dl['document'] = true;
            $go_dl['preview'] = true;
        }
        if ($this->app['acl']->get($this->app['authentication']->getUser())->has_preview_grant($this)) {
            $go_dl['preview'] = true;
        }

        $query = new User_Query($this->app);

        $masters = $query->on_base_ids([$this->base_id])
                ->who_have_right(['order_master'])
                ->execute()->get_results();

        $go_cmd = (count($masters) > 0 && $this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_base($this->base_id, 'cancmd'));

        $orderable['document'] = false;
        $downloadable['document'] = false;

        if (isset($sd['document']) && is_file($sd['document']->get_pathfile())) {
            if ($go_dl['document'] === true) {
                if ($this->app['acl']->get($this->app['authentication']->getUser())->is_restricted_download($this->base_id)) {
                    $this->remain_hd --;
                    if ($this->remain_hd >= 0)
                        $downloadable['document'] = [
                            'class' => 'document',
                            'label' => 'document original'
                        ];
                } else
                    $downloadable['document'] = [
                        'class' => 'document',
                        'label' => 'document original'
                    ];
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

                        if ($this->app['acl']->get($this->app['authentication']->getUser())->is_restricted_download($this->base_id)) {
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

        $xml = $this->get_caption()->serialize(caption_record::SERIALIZE_XML);

        if ($xml) {
            $downloadable['caption'] = [
                'class' => 'caption',
                'label' => 'caption XML'
            ];
            $this->add_count('caption', strlen($xml));
            $downloadable['caption-yaml'] = [
                'class' => 'caption',
                'label' => 'caption YAML'
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
     *
     * @return Array
     */
    public function get_orderable()
    {
        return $this->orderable;
    }

    /**
     *
     * @return Array
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
