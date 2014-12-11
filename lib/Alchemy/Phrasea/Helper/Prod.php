<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Prod extends Helper
{

    public function get_search_datas()
    {
        $searchData = array('bases' => array(), 'dates' => array(), 'fields' => array(), 'sort' => array(),);

        $bases = $fields = $dates = $sort = array();

        if (!$this->app['authentication']->getUser()) {
            return $searchData;
        }

        $searchSet = json_decode($this->app['settings']->getUserSetting($this->app['authentication']->getUser(), 'search'), true);
        $saveSettings = $this->app['settings']->getUserSetting($this->app['authentication']->getUser(), 'advanced_search_reload');

        foreach ($this->app['acl']->get($this->app['authentication']->getUser())->get_granted_sbas() as $databox) {
            $sbasId = $databox->get_sbas_id();

            $bases[$sbasId] = array('thesaurus' => (trim($databox->get_thesaurus()) !== ""), 'cterms' => false, 'collections' => array(), 'sbas_id' => $sbasId);

            foreach ($this->app['acl']->get($this->app['authentication']->getUser())->get_granted_base([], [$databox->get_sbas_id()]) as $coll) {
                $selected = $saveSettings ? ((isset($searchSet['bases']) && isset($searchSet['bases'][$sbasId])) ? (in_array($coll->get_base_id(), $searchSet['bases'][$sbasId])) : true) : true;
                $bases[$sbasId]['collections'][] = array('selected' => $selected, 'base_id' => $coll->get_base_id());
            }

            foreach ($databox->get_meta_structure() as $fieldMeta) {
                if (!$fieldMeta->is_indexable()) {
                    continue;
                }
                $id = $fieldMeta->get_id();
                $name = $fieldMeta->get_name();
                $type = $fieldMeta->get_type();

                $data = array('sbas' => array($sbasId), 'fieldname' => $name, 'type' => $type, 'id' => $id);

                if ($fieldMeta->get_type() === \databox_field::TYPE_DATE) {
                    if (isset($dates[$id])) {
                        $dates[$id]['sbas'][] = $sbasId;
                    } else {
                        $dates[$id] = $data;
                    }
                }

                if ($fieldMeta->get_type() == \databox_field::TYPE_NUMBER || $fieldMeta->get_type() === \databox_field::TYPE_DATE) {
                    if (isset($sort[$id])) {
                        $sort[$id]['sbas'][] = $sbasId;
                    } else {
                        $sort[$id] = $data;
                    }
                }

                if (isset($fields[$name])) {
                    $fields[$name]['sbas'][] = $sbasId;
                } else {
                    $fields[$name] = $data;
                }
            }

            if (!$bases[$sbasId]['thesaurus']) {
                continue;
            }
            if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_sbas($sbasId, 'bas_modif_th')) {
                continue;
            }

            if (false !== simplexml_load_string($databox->get_cterms())) {
                $bases[$sbasId]['cterms'] = true;
            }
        }

        $searchData['fields'] = $fields;
        $searchData['dates'] = $dates;
        $searchData['bases'] = $bases;
        $searchData['sort'] = $sort;

        return $searchData;
    }

    public function getRandom()
    {
        return md5(time() . mt_rand(100000, 999999));
    }
}