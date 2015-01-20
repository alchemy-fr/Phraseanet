<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class module_report_filter
{
    private $app;
    private $posting_filter = array();
    private $cor_query = array();
    private $active_column = array();

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
            $this->tab_filter[] = array('f' => $field, 'o' => $operator, 'v' => $value);
    }

    public function getPostingFilter()
    {
        $trans = array(
            'user'      => _('phraseanet::utilisateurs'),
            'ddate'     => _('report:: date'),
            'ip'        => _('report:: IP'),
            'appli'     => _('report:: modules'),
            'fonction'  => _('report::fonction'),
            'activite'  => _('report::activite'),
            'pays'      => _('report::pays'),
            'societe'   => _('report::societe'),
            'record_id' => _('report:: record id'),
            'final'     => _('phraseanet:: sous definition'),
            'coll_id'   => _('report:: collections'),
            'comment'   => _('report:: commentaire'),
            'search'    => _('report:: question'),
        );

        if (sizeof($this->tab_filter) > 0) {
            foreach ($this->tab_filter as $key => $filter) {
                if (empty($filter['v']))
                    $value = _('report:: non-renseigne');
                else
                    $value = $filter['v'];

                if (array_key_exists($filter['f'], $trans))
                    $field = _($trans[$filter['f']]);
                else
                    $field = $filter['f'];

                if ($filter['f'] == 'appli') {
                    $value = implode(' ', phrasea::modulesName(@unserialize($value)));
                } elseif ($filter['f'] == "ddate") {
                    $value = $this->app['date-formatter']->getPrettyString(new DateTime($value));
                }

                $this->posting_filter[] = array('f' => $field, 'v' => $value);
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
