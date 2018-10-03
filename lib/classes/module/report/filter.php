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

class module_report_filter
{
    private $app;
    private $posting_filter = [];
    private $cor_query = [];
    private $active_column = [];

    public function __construct(Application $app, $current_filter, $correspondance)
    {
        $this->app = $app;
        $this->tab_filter = $current_filter;
        $this->cor_query = $correspondance;
    }

    private function checkSameFilter($field, $operator, $value)
    {
        $test = true;
        if (sizeof($this->tab_filter) > 0) {
            foreach ($this->tab_filter as $filters => $a_filter) {
                if (in_array($field, $a_filter) &&
                    in_array($operator, $a_filter) && in_array($value, $a_filter)) {
                    $test = false;
                    break;
                } elseif (in_array($field, $a_filter) &&
                    in_array($operator, $a_filter) && ! in_array($value, $a_filter)) {
                    $a_filter['v'] = $value;
                    $test = false;
                    break;
                }
            }
        }

        return $test;
    }

    public function addFilter($field, $operator, $value)
    {
        if ($this->checkSameFilter($field, $operator, $value))
            $this->tab_filter[] = ['f' => $field, 'o' => $operator, 'v' => $value];
    }

    public function getPostingFilter()
    {
        if (sizeof($this->tab_filter) > 0) {
            foreach ($this->tab_filter as $key => $filter) {
                if (empty($filter['v']))
                    $value = $this->app->trans('report:: non-renseigne');
                else
                    $value = $filter['v'];

                switch ($filter['f']) {
                    case 'user':
                        $field = $this->app->trans('phraseanet::utilisateurs');
                        break;
                    case 'ddate':
                        $field = $this->app->trans('report:: date');
                        break;
                    case 'ip':
                        $field = $this->app->trans('report:: IP');
                        break;
                    case 'appli':
                        $field = $this->app->trans('report:: modules');
                        break;
                    case 'fonction':
                        $field = $this->app->trans('report::fonction');
                        break;
                    case 'activite':
                        $field = $this->app->trans('report::activite');
                        break;
                    case 'pays':
                        $field = $this->app->trans('report::pays');
                        break;
                    case 'societe':
                        $field = $this->app->trans('report::societe');
                        break;
                    case 'record_id':
                        $field = $this->app->trans('report:: record id');
                        break;
                    case 'final':
                        $field = $this->app->trans('phraseanet:: sous definition');
                        break;
                    case 'coll_id':
                        $field = $this->app->trans('report:: collections');
                        break;
                    case 'comment':
                        $field = $this->app->trans('report:: commentaire');
                        break;
                    case 'search':
                        $field = $this->app->trans('report:: question');
                        break;
                    default:
                        $field = $filter['f'];
                        break;
                }

                if ($filter['f'] == 'appli') {
                    $value = implode(' ', phrasea::modulesName($this->app['translator'], @unserialize($value)));
                } elseif ($filter['f'] == "ddate") {
                    $value = $this->app['date-formatter']->getPrettyString(new DateTime($value));
                }

                $this->posting_filter[] = ['f' => $field, 'v' => $value];
            }
        }

        return $this->posting_filter;
    }

    public function removeFilter($field)
    {
        foreach ($this->tab_filter as $key => $value) {
            if ($value['f'] == $field)
                unset($this->tab_filter[$key]);
        }
    }

    public function getActiveColumn()
    {
        foreach ($this->tab_filter as $key => $value) {
            $this->active_column[] = $value['f'];
        }

        return $this->active_column;
    }

    public function getTabFilter()
    {
        return $this->tab_filter;
    }
}
