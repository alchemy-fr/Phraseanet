<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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
     * @param  int                  $base_id
     * @param  int                  $record_id
     * @param  string               $directory
     * @param  int                  $remain_hd
     * @return record_exportElement
     */
    public function __construct($sbas_id, $record_id, $directory = '', $remain_hd = false)
    {
        $this->directory = $directory;

        if ($this->directory) {
            $unicode = new \unicode();
            $this->directory = $unicode->remove_nonazAZ09($this->directory) . '/';
        }

        $this->remain_hd = $remain_hd;
        $this->size = array();
        parent::__construct($sbas_id, $record_id);

        $this->get_actions($remain_hd);

        return $this;
    }

    /**
     *
     * @return record_exportElement
     */
    protected function get_actions()
    {
        $this->downloadable = $downloadable = array();
        $this->orderable = $orderable = array();

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        $sd = $this->get_subdefs();

        $sbas_id = phrasea::sbasFromBas($this->base_id);

        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

        $subdefgroups = $appbox->get_databox($sbas_id)->get_subdef_structure();

        $subdefs = array();

        foreach ($subdefgroups as $subdef_type => $subdefs_obj) {
            if ($subdef_type == $this->get_type()) {
                $subdefs = $subdefs_obj;
                break;
            }
        }

        $go_dl = array(
            'document'  => false,
            'preview'   => false,
            'thumbnail' => true
        );

        if ($user->ACL()->has_right_on_base($this->get_base_id(), 'candwnldhd')) {
            $go_dl['document'] = true;
        }
        if ($user->ACL()->has_right_on_base($this->get_base_id(), 'candwnldpreview')) {
            $go_dl['preview'] = true;
        }
        if ($user->ACL()->has_hd_grant($this)) {
            $go_dl['document'] = true;
            $go_dl['preview'] = true;
        }
        if ($user->ACL()->has_preview_grant($this)) {
            $go_dl['preview'] = true;
        }

        $query = new User_Query($appbox);

        $masters = $query->on_base_ids(array($this->base_id))
                ->who_have_right(array('order_master'))
                ->execute()->get_results();

        $go_cmd = (count($masters) > 0 && $user->ACL()->has_right_on_base($this->base_id, 'cancmd'));

        $orderable['document'] = false;
        $downloadable['document'] = false;

        if (isset($sd['document']) && is_file($sd['document']->get_pathfile())) {
            if ($go_dl['document'] === true) {
                if ($user->ACL()->is_restricted_download($this->base_id)) {
                    $this->remain_hd --;
                    if ($this->remain_hd >= 0)
                        $downloadable['document'] = array(
                            'class'                   => 'document',
                            'label'                   => _('document original')
                        );
                } else
                    $downloadable['document'] = array(
                        'class' => 'document',
                        'label' => _('document original')
                    );
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

                if ($lang == $session->get_I18n()) {
                    $subdef_label = $label;
                    break;
                }
                $subdef_label = $label;
            }

            $downloadable[$name] = false;

            $downloadable_settings = $subdef->is_downloadable();

            if ( ! $downloadable_settings || $go_dl[$class] === false) {
                continue;
            }

            if ($go_dl[$class]) {
                if (isset($sd[$name]) && is_file($sd[$name]->get_pathfile())) {
                    if ($class == 'document') {

                        if ($user->ACL()->is_restricted_download($this->base_id)) {
                            $this->remain_hd --;
                            if ($this->remain_hd >= 0)
                                $downloadable[$name] = array(
                                    'class'              => $class,
                                    'label'              => $subdef_label
                                );
                        } else
                            $downloadable[$name] = array(
                                'class' => $class,
                                'label' => $subdef_label
                            );
                    } else {
                        $downloadable[$name] = array(
                            'class' => $class,
                            'label' => $subdef_label
                        );
                    }

                    $this->add_count($name, $sd[$name]->get_size());
                }
            }
        }

        $xml = $this->get_caption()->serialize(caption_record::SERIALIZE_XML);

        if ($xml) {
            $downloadable['caption'] = array(
                'class'                       => 'caption',
                'label'                       => _('caption XML')
            );
            $this->add_count('caption', strlen($xml));
            $downloadable['caption-yaml'] = array(
                'class' => 'caption',
                'label' => _('caption YAML')
            );
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
        if ( ! $this->size) {
            $objectsize = array();
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
