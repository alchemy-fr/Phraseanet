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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
    '__act'
    , '__class' // task class
    , '__tname'
    , '__tactive'
    , '__xml'
    , '__tid'
    , 'txtareaxml'
);

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
        <?php
        $task_manager = new task_manager($appbox);
        $ztask = $task_manager->getTask($parm['__tid']);
        switch ($parm['__act']) {
            case 'FORM2XML':
                if (method_exists($ztask, 'printInterfaceHTML')) {
                    if ($ztask->getGraphicForm()) {
                        $xml = p4string::MakeString($ztask->graphic2xml($parm['__xml']), "js");
                    } else {
                        $xml = p4string::MakeString($parm['__xml'], "js");
                    }
                    ?>
                    <script type="text/javascript">
                        var d = parent.document;

                        parent.jsTaskObj.oldXML = d.getElementById('txtareaxml').value = "<?php echo $xml ?>";
                        d.getElementById('divGraph').style.display = "none";
                        d.getElementById('divXml').style.display = "";
                        d.getElementById('linkviewxml').className = "tabFront";
                        d.getElementById('linkviewgraph').className = "tabBack";
                        parent.jsTaskObj.currentView = "XML";
                    </script>
            <?php
        }
        break;

    case 'XML2FORM':
        if (method_exists($ztask, 'printInterfaceHTML')) {
            if ((simplexml_load_string($parm['txtareaxml']))) {
                if ($ztask->getGraphicForm()) {
                    if (($msg = ($ztask->xml2graphic($parm['txtareaxml'], "parent.document.forms['graphicForm']"))) == "") {
                        ?>
                                <script type="text/javascript">
                                    var d = parent.document;
                                    d.getElementById('divGraph').style.display = "";
                                    d.getElementById('divXml').style.display = "none";
                                    d.getElementById('linkviewxml').className = "tabBack";
                                    d.getElementById('linkviewgraph').className = "tabFront";
                                    parent.jsTaskObj.currentView = "GRAPHIC";
                                </script>
                                <?php
                            } else {
                                ?>
                                <script type="text/javascript">
                                    alert("<?php echo p4string::MakeString($msg, 'js', '"') ?>");
                                </script>
                        <?php
                    }
                } else {
                    ?>
                            <script type="text/javascript">
                                var d = parent.document;
                                d.getElementById('divGraph').style.display = "";
                                d.getElementById('divXml').style.display = "none";
                                d.getElementById('linkviewxml').className = "tabBack";
                                d.getElementById('linkviewgraph').className = "tabFront";
                                parent.jsTaskObj.currentView = "GRAPHIC";
                            </script>
                            <?php
                        }
                    } else {
                        ?>
                        <script type="text/javascript">
                            if(confirm("<?php echo p4string::MakeString(_('admin::tasks: xml invalide, restaurer la version precedente ?'), 'js', '"') // xml invalide, restaurer la v. prec. ?    ?>"))
                            parent.document.forms['fxml'].txtareaxml.value = parent.jsTaskObj.oldXML;
                        </script>
                <?php
            }
        }
        break;

    case 'SAVE_GRAPHIC':
        $parm['txtareaxml'] = $ztask->graphic2xml($parm['__xml']);

    case 'SAVE_XML':
        if ((simplexml_load_string($parm['txtareaxml']))) {
            if (method_exists($ztask, 'checkXML')) {
                if ($ztask->checkXML($parm['txtareaxml']) != '') {
                    return;
                }
            }
            $task_manager = new task_manager($appbox);
            $tid = $parm['__tid'];
            $task = $task_manager->getTask($tid);

            $task->setActive($parm['__tactive']);
            $task->setTitle($parm['__tname']);
            $task->setSettings($parm['txtareaxml']);
            ?>
                    <script type="text/javascript">
                        parent.document.getElementById("taskid").innerHTML = "id : <?php echo $tid ?>";
                        if(o=parent.document.getElementById("__gtid"))
                            o.value = "<?php echo $tid ?>";
                        parent.document.forms['fxml'].__tid.value = "<?php echo $tid ?>";
                        //      parent.document.getElementById("saveButtons").style.display = "none";
                        //      parent.document.getElementById("returnButton").style.display = "";
                    </script>
                    <?php
                } else {
                    ?>
                    <script type="text/javascript">
                        if(confirm("<?php echo p4string::MakeString(_('admin::tasks: xml invalide, restaurer la version precedente ?'), 'js', '"') ?>"))
                        parent.document.forms['fxml'].txtareaxml.value = parent.jsTaskObj.oldXML;
                    </script>
                    <?php
                }
                break;

            case 'CANCEL_GRAPHIC':
                break;

            case 'CANCEL_XML':
                break;
        }
        ?>
    </head>
    <body>
    </body>
</html>


