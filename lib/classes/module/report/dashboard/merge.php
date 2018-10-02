<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class module_report_dashboard_merge implements module_report_dashboard_componentInterface
{
    public $sort = [];
    public $sorted = [];
    private $currentkey;
    private $valid = false;

    /**
     * @desc tri le dashboard
     * @param dashboard_group $dash_organize
     */
    public function __construct(module_report_dashboard_group $dash_organize)
    {
        if ($dash_organize->isValid()) {
            $this->valid = true;
            $this->sort = $dash_organize->getDash();
        }
        $this->process();
    }

    /**
     * @des main function execute the process of triage
     * @return <void>
     */
    public function process()
    {
        if ($this->valid) {
            foreach ($this->sort as $key => $value) {
                $this->currentkey = $key;
                switch ($this->currentkey) {
                    case "nb_conn":
                        $this->sum();
                        break;
                    case "nb_dl":
                        $this->sum();
                        break;
                    case "activity":
                        $this->triActivity();
                        $this->toString();
                        break;
                    case "activity_day":
                        $this->triActivity();
                        $this->toString();
                        break;
                    case "activity_edited":
                        $this->triActivity();
                        $this->toString();
                        break;
                    case "activity_added":
                        $this->triActivity();
                        $this->toString();
                        break;
                    case "top_ten_user_doc":
                        $this->triTopTen();
                        break;
                    case "top_ten_user_prev":
                        $this->triTopTen();
                        break;
                    case "top_ten_user_poiddoc":
                        $this->triTopTen();
                        break;
                    case "top_ten_user_poidprev":
                        $this->triTopTen();
                        break;
                    case "top_dl_preview":
                        $this->triTopTen();
                        break;
                    case "top_dl_document":
                        $this->triTopTen();
                        break;
                    case "top_ten_question":
                        $this->triTopTen();
                        break;
                    case "ask":
                        $this->triTopTen();
                        break;
                    case "top_ten_site":
                        $this->triTopTen();
                        break;
                    case "top_ten_added":
                        $this->triTopTen();
                        break;
                    default:
                        break;
                }
            }
        }

        return;
    }

    /**
     * @return le     dashboard trie
     * @return <type>
     */
    public function getDash()
    {
        return $this->sorted;
    }

    /**
     * @desc check si les resultats sont valides
     * @return <bool>
     */
    public function isValid()
    {
        if (isset($this->sorted) && sizeof($this->sorted) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return <void>
     */
    private function sum()
    {
        $this->sorted[$this->currentkey] = 0;
        foreach ($this->sort[$this->currentkey] as $k => $v)
            $this->sorted[$this->currentkey] += $v;

        return;
    }

    /**
     * @desc Tri all result of activity type
     * @return <void>
     */
    private function triActivity()
    {
        foreach ($this->sort[$this->currentkey] as $sbas => $val) {
            foreach ($val as $key => $value) {

                isset($this->sorted[$this->currentkey][$key]) ?
                        $this->sorted[$this->currentkey][$key] += $value :
                        $this->sorted[$this->currentkey][$key] = $value;
            }
        }
    }

    /**
     * @desc force value to string values
     * this is the format to respect for displaying float results in
     * SVG google charts
     *
     * @return void;
     */
    private function toString()
    {
        foreach ($this->sorted[$this->currentkey] as $k => $v)
            $this->sorted[$this->currentkey][$k] =
                (string) number_format($v, 2, '.', '');

        return;
    }

    /**
     * @desc tri all result of top ten type
     * @return void
     */
    private function triTopTen()
    {
        foreach ($this->sort[$this->currentkey] as $sbas => $val) {
            foreach ($val as $id => $info) {
                foreach ($info as $k => $v) {
                    if (is_int($v) || is_float($v))
                        isset($this->sorted[$this->currentkey][$id][$k]) ?
                                $this->sorted[$this->currentkey][$id][$k] += $v :
                                $this->sorted[$this->currentkey][$id][$k] = $v;
                    elseif (is_string($v))
                        $this->sorted[$this->currentkey][$id][$k] = $v;
                    else
                        $this->sorted[$this->currentkey][$id][$k] = null;
                }
            }
        }

        return;
    }

    /**
     * @desc get only the number of result we wants
     * @return dashboard_order
     */
    public function top()
    {
        return new module_report_dashboard_sort($this);
    }
}
