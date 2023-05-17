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

        $sort = [
            \databox_field::TYPE_STRING => [],
            \databox_field::TYPE_NUMBER => [],
            \databox_field::TYPE_DATE => []
        ];

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
                    'base_id'  => $coll->get_base_id(),
                    'name'     => $coll->get_name(),
                    'order'    => $coll->get_ord()
                );
            }

            /** @var DisplaySettingService $settings */
            $settings = $this->app['settings'];
            $userOrderSetting = $settings->getUserSetting($this->app->getAuthenticatedUser(), 'order_collection_by');

            // a temporary array to sort the collections
            $aName = [];
            list($ukey, $uorder) = ["order", SORT_ASC];     // default ORDER_BY_ADMIN
            switch ($userOrderSetting) {
                case $settings::ORDER_ALPHA_ASC :
                    list($ukey, $uorder) = ["name", SORT_ASC];
                    break;

                case $settings::ORDER_ALPHA_DESC :
                    list($ukey, $uorder) = ["name", SORT_DESC];
                    break;
            }
            foreach ($bases[$sbasId]['collections'] as $key => $row) {
                $aName[$key] = $row[$ukey];
            }
            // sort the collections
            array_multisort($aName, $uorder, SORT_REGULAR, $bases[$sbasId]['collections']);

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
                $label = $fieldMeta->get_label($this->app['locale']);

                $data = array(
                    'sbas' => array($sbasId),
                    'fieldname' => $name,
                    'type' => $type,
                    'label' => ($name === $label) ? [$label] : [$name . ' - ' .trim($label)],  // add the fieldname in the label
                    'id' => $id
                );

                if ($fieldMeta->get_type() === \databox_field::TYPE_DATE) {
                    if (!array_key_exists($name, $dates)) {
                        $dates[$name] = array('sbas' => array());
                    }
                    $dates[$name]['sbas'][] = $sbasId;

                    // add different label for the same field if exist
                    if (!isset($dates[$name]['label']) || !in_array(strtolower($label), array_map('strtolower', $dates[$name]['label']))) {
                        $dates[$name]['label'][] = trim($label);
                    }
                }

                if (array_key_exists($type, $sort)) {  // TYPE_STRING, TYPE_NUMBER or TYPE_DATE
                    if (!array_key_exists($name, $sort[$type])) {
                        $sort[$type][$name] = [
                            'sbas' => []
                        ];
                    }
                    $sort[$type][$name]['sbas'][] = $sbasId;
                }

                if (isset($fields[$name])) {
                    $fields[$name]['sbas'][] = $sbasId;

                    // add different label for the same field if exist
                    if (!in_array(strtolower($label), array_map('strtolower', $fields[$name]['label']))) {
                        $fields[$name]['label'][] = trim($label);
                    }
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

        $allSbasId = array_map(function ($db) {
            return $db->get_sbas_id();
        }, $acl->get_granted_sbas());

        // add default field date
        $dates['updated_on']['sbas'] = $allSbasId;
        $dates['updated_on']['label'][] = $this->app->trans('updated_on');
        $dates['created_on']['sbas'] = $allSbasId;
        $dates['created_on']['label'][] = $this->app->trans('created_on');

        // sort ASC by fieldname
        ksort($dates, SORT_STRING | SORT_FLAG_CASE);
        ksort($fields, SORT_STRING | SORT_FLAG_CASE);

        $searchData['fields'] = $fields;
        $searchData['dates'] = $dates;
        $searchData['bases'] = $bases;
        $searchData['sort'] = array_map(function($v){ksort($v, SORT_NATURAL);return $v;}, $sort); // sort by name of field
        $searchData['elasticSort'] = $elasticSort;

        return $searchData;
    }

    public function getRandom()
    {
        return md5(time() . mt_rand(100000, 999999));
    }
}
