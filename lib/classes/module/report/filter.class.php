<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_filter
{
    private $app;
    private $posting_filter = array();
    private $cor_query = array();
    private $active_column = array();
    private $trans = array(
        'user'      => 'phraseanet::utilisateurs',
        'ddate'     => 'report:: date',
        'ip'        => 'report:: IP',
        'appli'     => 'report:: modules',
        'fonction'  => 'report::fonction',
        'activite'  => 'report::activite',
        'pays'      => 'report::pays',
        'societe'   => 'report::societe',
        'record_id' => 'report:: record id',
        'final'     => 'phraseanet:: sous definition',
        'coll_id'   => 'report:: collections',
        'comment'   => 'report:: commentaire',
        'search'    => 'report:: question',
    );

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
        if (sizeof($this->tab_filter) > 0) {
            foreach ($this->tab_filter as $key => $filter) {
                if (empty($filter['v']))
                    $value = _('report:: non-renseigne');
                else
                    $value = $filter['v'];

                if (array_key_exists($filter['f'], $this->trans))
                    $field = _($this->trans[$filter['f']]);
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
