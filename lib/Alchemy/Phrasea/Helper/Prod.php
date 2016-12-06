<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        $searchData = array('bases' => array(), 'dates' => array(), 'fields' => array(), 'sort' => array(), 'elasticSort' => array());

        $bases = $fields = $dates = $sort = $elasticSort = array();

        if (!$this->app->getAuthenticatedUser()) {
            return $searchData;
        }

        $searchSet = json_decode($this->app['settings']->getUserSetting($this->app->getAuthenticatedUser(), 'search', '{}'), true);
        $saveSettings = $this->app['settings']->getUserSetting($this->app->getAuthenticatedUser(), 'advanced_search_reload');
        $acl = $this->app->getAclForUser($this->app->getAuthenticatedUser());
        foreach ($acl->get_granted_sbas() as $databox) {
            $sbasId = $databox->get_sbas_id();

            $bases[$sbasId] = array(
                'thesaurus' => (trim($databox->get_thesaurus()) !== ""),
                'cterms' => false,
                'collections' => array(),
                'sbas_id' => $sbasId
            );

            foreach ($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([], [$databox->get_sbas_id()]) as $coll) {
                $selected = $saveSettings ? ((isset($searchSet['bases']) && isset($searchSet['bases'][$sbasId])) ? (in_array($coll->get_base_id(), $searchSet['bases'][$sbasId])) : true) : true;
                $bases[$sbasId]['collections'][] = array(
                    'selected' => $selected,
                    'base_id' => $coll->get_base_id()
                );
            }

            foreach ($databox->get_meta_structure() as $fieldMeta) {
                if (!$fieldMeta->is_indexable()) {
                    continue;
                }
                if($fieldMeta->isBusiness() && !$acl->can_see_business_fields($databox)) {
                    continue;
                }

                $id = $fieldMeta->get_id();
                $name = $fieldMeta->get_name();
                $type = $fieldMeta->get_type();

                $data = array(
                    'sbas' => array($sbasId),
                    'fieldname' => $name,
                    'type' => $type,
                    'id' => $id
                );

                if ($fieldMeta->get_type() === \databox_field::TYPE_DATE) {
                    if (!array_key_exists($name, $dates)) {
                        $dates[$name] = array('sbas' => array());
                    }
                    $dates[$name]['sbas'][] = $sbasId;
                }

                if ($fieldMeta->get_type() == \databox_field::TYPE_NUMBER || $fieldMeta->get_type() === \databox_field::TYPE_DATE) {
                    if (!array_key_exists($name, $sort)) {
                        $sort[$name] = array('sbas' => array());
                    }
                    $sort[$name]['sbas'][] = $sbasId;
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
            if (!$this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_sbas($sbasId, \ACL::BAS_MODIF_TH)) {
                continue;
            }

            if (false !== simplexml_load_string($databox->get_cterms())) {
                $bases[$sbasId]['cterms'] = true;
            }
        }

        if (isset($searchSet['elasticSort'])) {
            $elasticSort = $searchSet['elasticSort'];
        }

        $searchData['fields'] = $fields;
        $searchData['dates'] = $dates;
        $searchData['bases'] = $bases;
        $searchData['sort'] = $sort;
        $searchData['elasticSort'] = $elasticSort;

        return $searchData;
    }

    public function getRandom()
    {
        return md5(time() . mt_rand(100000, 999999));
    }
}
