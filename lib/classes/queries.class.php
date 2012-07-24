<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class queries
{

    public static function tree_topics()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        $out = '';

        $xmlTopics = null;
        $sxTopics = null;

        if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics_' . $session->get_I18n() . '.xml'))
            $xmlTopics = $registry->get('GV_RootPath') . 'config/topics/topics_' . $session->get_I18n() . '.xml';

        if ( ! $xmlTopics) {
            if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics.xml')) {
                $xmlTopics = $registry->get('GV_RootPath') . 'config/topics/topics.xml';
            }
        }

        $cssTopics = '';
        if ($xmlTopics && ($sxTopics = simplexml_load_file($xmlTopics))) {
            $cssTopics = (string) ($sxTopics->display->css);
        }

        $out .= '<style type="text/css">
            ' . $cssTopics . '
        </style>';

        $out .='<div class="searchZone" >
            <div class="linktopics1" >';

        if ($sxTopics) {
            $defaultview = mb_strtolower($sxTopics->display->defaultview);
            if ( ! $defaultview)
                $defaultview = 'static';
            $out .= ( "<ul id='TOPIC_UL' class='nobox'>\n");
            $out .= self::drawTopics($sxTopics->topics, 0, '', $defaultview);
            $out .= ( "\n</ul>\n");
        }

        $out .= '</div>
        </div>';

        return $out;
    }

    public static function topics_exists()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics_' . $session->get_I18n() . '.xml')) {
            return true;
        }

        if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics.xml')) {
            return true;
        }

        return false;
    }

    public static function dropdown_topics()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        $out = '';

        $xmlTopics = '';
        $sxTopics = null;

        if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics_' . $session->get_I18n() . '.xml'))
            $xmlTopics = $registry->get('GV_RootPath') . 'config/topics/topics_' . $session->get_I18n() . '.xml';

        if ($xmlTopics == '') {
            if (file_exists($registry->get('GV_RootPath') . 'config/topics/topics.xml')) {
                $xmlTopics = $registry->get('GV_RootPath') . 'config/topics/topics.xml';
            }
        }

        if ($xmlTopics == '') {
            return '';
        }

        $jsTopics = 'null';
        $maxdepth = 0;
        if (($sxTopics = simplexml_load_file($xmlTopics))) {
            $jsTopics = self::topicsAsJS($sxTopics->topics, 0, $maxdepth);
        }

        $out .= ' <script type="text/javascript">
                var maxdepth = ' . ($maxdepth + 1) . ';
                var topics = ' . $jsTopics . ';
                var current_popqry = "";

                function doSearchTopPop(qry)
                {
                    var qft = document.forms["pops"].qry.value;
                    if(qft != "" && current_popqry != "")
                        qry = "("+qft+") AND ("+current_popqry+")";
                    else
                        qry = qft+current_popqry;

                    if(qry=="")
                        qry = "all";

                    doSpecialSearch(qry,true);

                    return;
                }
                function chgPopTopic(ipop)
                {
                    if(ipop > ' . ($maxdepth + 1) . ')

                        return;
                    var i,j;
                    var _topics = topics;
                    var zpop;
                    current_popqry = "";
                    for (i=0; _topics && i<ipop; i++) {
                        zpop = document.forms["pops"]["popTopic_"+i];
                        if((j = zpop.selectedIndex) > 0)
                            current_popqry = zpop.options[j].value;
                        j--;
                        if(_topics[j] && _topics[j].topics)
                            _topics = _topics[j].topics;
                        else
                            _topics = null;
                    }
                    if(ipop == ' . ($maxdepth + 1) . ')

                        return;
                    zpop = document.forms["pops"]["popTopic_"+ipop];
                    if (_topics) {
                        while(zpop.options[0])
                            zpop.options[0] = null;
                        zpop.options[0] = new Option("All", "");
                        for(j=0; j<_topics.length; j++)
                            zpop.options[j+1] = new Option(_topics[j].label, _topics[j].query);
                        zpop.selectedIndex = 0;
                        document.getElementById("divTopic_"+ipop).style.display = "";
                    } else {
                        document.getElementById("divTopic_"+ipop).style.display = "none";
                    }
                    while (++ipop <= ' . $maxdepth . ') {
                        document.getElementById("divTopic_"+ipop).style.display = "none";
                    }
                }
                </script>';

        $out .= '<div class="searchZonePop" onload="chgPopTopic(0);">
                    <div class="linktopics1">
                        <form name="pops" onsubmit="return(false);" style="margin:0px; margin-left:5px; margin-right:5px">
                            <table>
                                <tr>
                                    <td colspan="2">' . _('boutton::chercher') . ' :
                                    <input style="width:180px" type="text" name="qry"></td>
                                </tr>
                            </table>
                            ' . _('client::recherche: dans les categories') . '<br/>';

        for ($i = 0; $i <= $maxdepth; $i ++ ) {
            $out .= '<p id="divTopic_' . $i . '" style="margin:0px;margin-bottom:5px;" >
                                <select style="width:100%;" id="popTopic_' . $i . '" name="popTopic_' . $i . '" onchange="chgPopTopic(' . ($i + 1) . ');">
                                </select>
                                </p>';
        }
        $out .= '<div style="text-align:right;">
                                <input type="submit" value="' . _('boutton::chercher') . '" onclick="doSearchTopPop();" />
                            </div>
                        </form>
                    </div>
                </div>
                <script>chgPopTopic(0);</script>';

        return $out;
    }

    public static function history()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $conn = $appbox->get_connection();

        $usr_id = $session->get_usr_id();

        $sql = "SELECT query from dsel where usr_id = :usr_id
            ORDER BY id DESC LIMIT 0,25";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':usr_id' => $usr_id));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $history = '<ul>';

        foreach ($rs as $row) {
            $longueur = strlen($row["query"]);

            $history .= '<li onclick="doSpecialSearch(\'' . str_replace(array("'", '"'), array("\'", '&quot;'), $row["query"]) . '\')">' . $row["query"] . '</li>';
        }

        $history .= '<ul>';

        return $history;
    }

    private static function hastopics(&$topics)
    {
        foreach ($topics->topics as $subtopic) {
            return true;
        }

        return false;
    }

    private static function topicsAsJS($topics, $depth, &$maxdepth)
    {
        if ($depth > $maxdepth)
            $maxdepth = $depth;
        $t = '';
        $tab = str_repeat("\t", $depth);
        foreach ($topics->topic as $subtopic) {
            $t .= $t ? "$tab, " : "$tab  ";
            $t .= '{ ';
            $t .= 'label:"' . p4string::MakeString(utf8_decode($subtopic->label), 'js') . '"';
            if ($q = $subtopic->query) {
                $q = str_replace(array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $subtopic->query);
                $t .= ", query:'" . $q . "'";
            } else {
                $t .= ', query:null';
            }
            if (self::hastopics($subtopic)) {
                $t .= ', topics:' . "\n" . self::topicsAsJS($subtopic->topics, $depth + 1, $maxdepth); //, $fullquery) ;
            } else {
                $t .= ', topics:null';
            }
            $t .= " }\n";
        }

        return("$tab" . "[\n" . $t . "\n$tab]");
    }

    private static function drawTopics($topics, $depth = 0, $triid = '', $defaultview)
    {
        $n = 0;
        $out = '';
        foreach ($topics->topic as $subtopic) {
            $tid = $triid . '_' . $n;
            $s = $subtopic->label;
            $l = p4string::MakeString($s, 'html');
            $l = '<span class=\'topic_' . $depth . '\'>' . $l . '</span>';
            if ($subtopic->query) {
                $q = str_replace(array("\\", "\"", "'", "\r", "\n"), array("\\\\", "&quot;", "\\'", "\\r", "\\n"), $subtopic->query);
                $q = '<a href="javascript:void();" onClick="doSpecialSearch(\'' . $q . '\',true);">' . $l . '</a>';
            } else {
                $q = $l;
            }
            if (self::hastopics($subtopic)) {
                $view = mb_strtolower($subtopic['view']);
                if ( ! $view)
                    $view = $defaultview;
                switch ($view) {
                    case 'opened':
                        $out .= ( '<li><a id=\'TOPIC_TRI' . $tid . '\' class="opened" href="javascript:void();" onclick="clktri(\'' . $tid . '\');return(false);"></a>&nbsp;' . $q . '</li>' . "\n");
                        $out .= ( "<ul id='TOPIC_UL$tid' class='opened'>\n");
                        $out .= self::drawTopics($subtopic->topics, $depth + 1, $tid, $defaultview);
                        $out .= ( "</ul>\n<div style='height:1px;'></div>\n");
                        break;
                    case 'closed':
                        $out .= ( '<li><a id=\'TOPIC_TRI' . $tid . '\' class="closed" href="javascript:void();" onclick="clktri(\'' . $tid . '\');return(false);"></a>&nbsp;' . $q . '</li>' . "\n");
                        $out .= ( "<ul id='TOPIC_UL$tid' class='closed'>\n");
                        $out .= self::drawTopics($subtopic->topics, $depth + 1, $tid, $defaultview);
                        $out .= ( "</ul>\n<div style='height:1px;'></div>\n");
                        break;
                    case 'static':
                    default:
                        $out .= ( '<li><span id=\'TOPIC_TRI' . $tid . '\' class="static">&nbsp</span>&nbsp;' . $q . '</li>' . "\n");
                        $out .= ( "<ul id='TOPIC_UL$tid' class='static'>\n");
                        $out .= self::drawTopics($subtopic->topics, $depth + 1, $tid, $defaultview);
                        $out .= ( "</ul>\n<div style='height:1px;'></div>\n");
                        break;
                }
            } else {
                $out .= ( '<li><span id=\'TOPIC_TRI' . $tid . '\' class="none">&nbsp</span>&nbsp;' . $q . '</li>' . "\n");
            }
            $n ++;
        }

        return $out;
    }
}
