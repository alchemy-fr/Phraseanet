<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;

class SearchEngineRequest
{
    private $options;

    public function __construct(SearchEngineOptions $options)
    {
        $this->options = $options;
    }
    
    public function getOptions()
    {
        return $this->options;
    }
    
    public static function fromRequest(Application $app, Request $request)
    {
        $options = new SearchEngineOptions();
        
        $options->disallowBusinessFields();
        
        $bas = $app['phraseanet.user']->ACL()->get_granted_base();

        if (is_array($request->request->get('bases'))) {
            $bas = array_map(function($base_id) use ($app) {
                    return \collection::get_from_base_id($app, $base_id);
                }, $request->request->get('bases'));
        }

        $databoxes = array();

        foreach ($bas as $collection) {
            if (!isset($databoxes[$collection->get_sbas_id()])) {
                $databoxes[$collection->get_sbas_id()] = $collection->get_databox();
            }
        }

        if ($app['phraseanet.user']->ACL()->has_right('modifyrecord')) {
            $BF = array_filter($bas, function($collection) use ($app) {
                    return $app['phraseanet.user']->ACL()->has_right_on_base($collection->get_base_id(), 'canmodifrecord');
                });

            $options->allowBusinessFieldsOn($BF);
        }

        $status = is_array($request->request->get('status')) ? $request->request->get('status') : array();
        $fields = is_array($request->request->get('fields')) ? $request->request->get('fields') : array();

        $databoxFields = array();

        foreach ($databoxes as $databox) {
            foreach ($fields as $field) {
                try {
                    $databoxField = $databox->get_meta_structure()->get_element_by_name($field);
                } catch (\Exception $e) {
                    continue;
                }
                if ($databoxField) {
                    $databoxFields[] = $databoxField;
                }
            }
        }

        $options->setFields($databoxFields);
        $options->setStatus($status);
        $options->onCollections($bas);

        $options->setSearchType($request->request->get('search_type'));
        $options->setRecordType($request->request->get('record_type'));

        $min_date = $max_date = null;
        if ($request->request->get('date_min')) {
            $min_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->request->get('date_min') . ' 00:00:00');
        }
        if ($request->request->get('date_max')) {
            $max_date = \DateTime::createFromFormat('Y/m/d H:i:s', $request->request->get('date_max') . ' 23:59:59');
        }

        $options->setMinDate($min_date);
        $options->setMaxDate($max_date);

        $databoxDateFields = array();

        foreach ($databoxes as $databox) {
            foreach (explode('|', $request->request->get('date_field')) as $field) {
                try {
                    $databoxField = $databox->get_meta_structure()->get_element_by_name($field);
                } catch (\Exception $e) {
                    continue;
                }
                if ($databoxField) {
                    $databoxDateFields[] = $databoxField;
                }
            }
        }

        $options->setDateFields($databoxDateFields);
        $options->setSort($request->request->get('sort'), $request->request->get('ord', SearchEngineOptions::SORT_MODE_DESC));
        $options->useStemming((Boolean) $request->request->get('stemme'));

        return new static($options);
    }
}
