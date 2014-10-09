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

use Alchemy\Phrasea\Model\Entities\User;

class Prod extends Helper
{

    public function get_search_datas()
    {
        $search_datas = [
            'bases' => [],
            'dates' => [],
            'fields' => []
        ];

        $bases = $fields = $dates = [];

        if (! $this->app['authentication']->getUser() instanceof User) {
            return $search_datas;
        }

        $searchSet = json_decode($this->app['settings']->getUserSetting($this->app['authentication']->getUser(), 'search'), true);
        $saveSettings = $this->app['settings']->getUserSetting($this->app['authentication']->getUser(), 'advanced_search_reload');

        foreach ($this->app['acl']->get($this->app['authentication']->getUser())->get_granted_sbas() as $databox) {
            $sbas_id = $databox->get_sbas_id();

            $bases[$sbas_id] = [
                'thesaurus'   => (trim($databox->get_thesaurus()) != ""),
                'cterms'      => false,
                'collections' => [],
                'sbas_id' => $sbas_id
            ];

            foreach ($this->app['acl']->get($this->app['authentication']->getUser())->get_granted_base([], [$databox->get_sbas_id()]) as $coll) {
                $selected = $saveSettings ? ((isset($searchSet['bases']) && isset($searchSet['bases'][$sbas_id])) ? (in_array($coll->get_base_id(), $searchSet['bases'][$sbas_id])) : true) : true;
                $bases[$sbas_id]['collections'][] =
                    [
                        'selected' => $selected,
                        'base_id'  => $coll->get_base_id()
                ];
            }

            foreach ($databox->get_meta_structure() as $fieldMeta) {
                if (!$fieldMeta->is_indexable()) {
                    continue;
                $id = $meta->get_id();
                $name = $meta->get_name();
                if ($meta->get_type() == 'date') {
                    if (isset($dates[$id]))
                        $dates[$id]['sbas'][] = $sbas_id;
                    else
                        $dates[$id] = ['sbas' => [$sbas_id], 'fieldname' => $name];
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
                    $fields[$name] = [
                        'sbas' => [$sbas_id]
                        , 'fieldname' => $name
                        , 'type'      => $meta->get_type()
                        , 'id'        => $id
                    ];
                }
            }

            if (!$bases[$sbasId]['thesaurus']) {
                continue;
            }
            if ( ! $this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_sbas($sbas_id, 'bas_modif_th')) {
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
        return $this->app['random.low']->generateString(16);
    }
}
