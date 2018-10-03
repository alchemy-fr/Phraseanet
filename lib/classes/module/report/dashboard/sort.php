<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class module_report_dashboard_sort implements module_report_dashboard_componentInterface
{
    public $arrayToSort = [];
    public $arraySorted = [];

    public function __construct(module_report_dashboard_merge $tridash)
    {
        if ($tridash->isValid()) {
            $this->arrayToSort = $tridash->getDash();
        }
        $this->process();
    }

    public function process()
    {
        foreach ($this->arrayToSort as $key => $value) {
            switch ($key) {
                case "top_ten_user_doc":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_user_prev":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_user_poiddoc":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_user_poidprev":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_dl_preview":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_dl_document":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_question":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "ask":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_site":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                case "top_ten_added":
                    $this->arraySorted[$key] = $this->array_orderby(
                        $this->arrayToSort[$key], 'nb', SORT_DESC
                    );
                    break;
                default;
                    break;
            }
        }
    }

    /**
     * @desc tri les tableaux  en fonction des parametres qu'on lui passe c'est la
     * fonction array_multisort qui est appelée
     * p1 : le tableau a trier, p2: la clef du tableau sur lequel on effectue le
     * tri, p3 SORT_DESC ou SORT_ASC,
     *
     * @return array
     */
    private function array_orderby()
    {
        //arguments
        $args = func_get_args();
        //data = first argument
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) { // = clef
                $tmp = [];
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = &$tmp;
            } else
                $args[$n] = &$field;
        }
        $args[] = &$data;

        call_user_func_array("array_multisort", $args);

        return array_pop($args);
    }

    public function isValid()
    {
        if (isset($this->arraySorted) && sizeof($this->arraySorted) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getDash()
    {
        return $this->arraySorted;
    }

    public function getTop($nbtop)
    {
        if ( ! is_int($nbtop)) {
            return [];
        }

        $tmp = [];

        if ($this->isValid()) {
            foreach ($this->arraySorted as $k => $v) {
                $i = 0;
                $tmp[$k] = [];
                foreach ($v as $a) {
                    if ($i < $nbtop)
                        array_push($tmp[$k], $a);
                    else
                        break;
                    $i ++;
                }
            }
        }

        return $tmp;
    }
}
