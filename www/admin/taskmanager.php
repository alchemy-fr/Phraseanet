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
$parm = $request->get_parms("act", "tid");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

if ( ! $user->ACL()->has_right('taskmanager')) {
    phrasea::headers(403);
}

phrasea::headers();
$registry = $appbox->get_registry();

$task_manager = new task_manager($appbox);

$refresh_tasklist = false;
if ($parm["act"] == "DELETETASK") {
    try {
        $task = $task_manager->getTask($parm['tid']);
        $task->delete();
        $refresh_tasklist = true;
    } catch (Exception $e) {

    }
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>

        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
        <link rel="stylesheet" href="/include/minify/f=include/jslibs/jquery.contextmenu.css,include/jslibs/jquery-ui-1.8.17/css/ui-lightness/jquery-ui-1.8.17.custom.css" type="text/css" media="screen" />
        <style>
            .divTop
            {
                OVERFLOW: hidden;
                height:18px;
            }
            #redbox0 table{
                font-size:10px;
                text-align:center;
                table-layout: fixed;
            }
        </style>
        <link rel="stylesheet" href="/include/minify/f=include/jslibs/jquery.contextmenu.css,include/jslibs/jquery-ui-1.8.17/css/ui-lightness/jquery-ui-1.8.17.custom.css" type="text/css" media="screen" />
        <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js,include/jslibs/jquery.contextmenu.js"></script>
        <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.17/js/jquery-ui-1.8.17.custom.min.js"></script>
        <script type="text/javascript">

            var retPing = null;

            var newTaskMenu = null;

            var allgetID = new Array ;
            var total = 0;


            function resized()
            {
                $("#redbox0").width($("#tableau_center").width()-4);
                $("#redbox1").width = ($("#tableau_center").width()-4);
            }

            function newTask(tclass)
            {
                document.forms["task"].target = "";
                document.forms["task"].act.value = "NEWTASK";
                document.forms["task"].tcl.value = tclass;
                document.forms["task"].submit();
            }

            function doMenuSched(act)
            {
                switch(act)
                {
                    case "start":
                        lauchScheduler();
                        break;
                    case "stop":
                        setSchedStatus('tostop');
                        break;
                    case "log":
                        window.open("/admin/showlogtask.php?fil=scheduler&log=l", "scheduler.log");
                        break;
                    case "preferences":
                        preferencesScheduler();
                        break;
                }
            }

            function doMenuTask(context, act)
            {
                var tid = $(context).parent().attr('id').split('_').pop();

                switch(act)
                {
                    case "edit":
                        editTask(tid);
                        break;
                    case "start":
                        setTaskStatus(tid, 'tostart', null, true);  // null:no signal, true : reset crash counter
                        break;
                    case "stop":
                        setTaskStatus(tid, 'tostop', 15, false);  // 15 = SIGTERM
                        break;
                    case 'delete':
                        if(confirm("<?php echo p4string::MakeString(_('admin::tasks: supprimer la tache ?'), 'js', '"') ?>"))
                        {
                            document.forms["taskManager"].target = "";
                            document.forms["taskManager"].act.value = "DELETETASK";
                            document.forms["taskManager"].tid.value = tid;
                            document.forms["taskManager"].submit();
                        }
                        break;
                    case "log":
                        window.open("/admin/showlogtask.php?fil=task&id="+tid+"&log=l", "task_"+tid+".log");
                        break;
                    }
                }

                function preferencesScheduler()
                {
                    var buttons = {
                        '<?php echo _('Fermer') ?>':function(){$('#scheduler-preferences').dialog('close').dialog('destroy')},
                        '<?php echo _('Renouveller') ?>':function(){renew_scheduler_key();}
                    };
                    $('#scheduler-preferences').dialog({
                        width:400,
                        height:200,
                        modal:true,
                        resizable:false,
                        draggable:false,
                        buttons:buttons
                    });
                }

                function renew_scheduler_key()
                {
                    var datas = {action:'SCHEDULERKEY', renew:'1'};
                    $.post("/admin/adminFeedback.php"
                    , datas
                    , function(data){
                        $('#scheduler_key').val(data);

                        return;
                    });
                }

                $(document).ready(function(){
                    resized();

                    $(this).bind('resize',function(){resized();});

                    var allgetID = new Array ;
                    var total = 0;


                    var menuNewTask = [
<?php
// fill the 'new task' menu
$tasks = task_manager::getAvailableTasks();
$ntasks = count($tasks);
foreach ($tasks as $t) {
    printf("      {\n");
    printf("        '%s':\n", p4string::MakeString($t["name"], 'js'));
    printf("        {\n");
    printf("          disabled:%s,\n", $t["err"] ? 'true' : 'false');
    printf("          onclick:function(menuItem, menu) { newTask('%s'); },\n", p4string::MakeString($t["class"], 'js'));
    printf("          title:'%s'\n", p4string::MakeString($t["name"], 'js'));
    printf("        }\n");
    printf("      }%s\n",  -- $ntasks ? ',' : '');
}
?>
            ];


            $('#newTaskButton').contextMenu(
            menuNewTask,
            {
                //            theme:'vista'
            }
        );


            $('.dropdown.scheduler').contextMenu(
            [
                {
                    'Start':
                        {
                        onclick:function(menuItem,menu) { doMenuSched('start'); },
                        title:'Demarrer le TaskManager'
                    }
                },
                {
                    'Stop':
                        {
                        onclick:function(menuItem,menu) { doMenuSched('stop'); },
                        title:'Arreter le TaskManager'
                    }
                },
                $.contextMenu.separator,
                {
                    'Show log':
                        {
                        onclick:function(menuItem,menu) { doMenuSched('log'); },
                        title:'Afficher les logs'
                    }
                },
                {
                    'Preferences':
                        {
                        onclick:function(menuItem,menu) { doMenuSched('preferences'); },
                        title:'Scheduler preferences'
                    }
                }
            ]
            ,
            {
                // theme:'vista',
                optionsIdx:{'start':0, 'stop':1},
                beforeShow:function()
                {
                    if(!retPing)
                        return;
                    if(retPing.scheduler && retPing.scheduler.pid)
                    {
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['stop']+')').removeClass("context-menu-item-disabled");
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['start']+')').addClass("context-menu-item-disabled");
                    }
                    else
                    {
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['stop']+')').addClass("context-menu-item-disabled");
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['start']+')').removeClass("context-menu-item-disabled");
                    }
                }
            }
        );



            $('.task_manager .dropdown.task').contextMenu(
            [
                {
                    'Edit':
                        {
                        onclick:function(menuItem,menu) { doMenuTask($(this), 'edit'); },
                        title:'Modifier cette tache'
                    }
                },
                {
                    'Start':
                        {
                        onclick:function(menuItem,menu) { doMenuTask($(this), 'start'); },
                        title:'Demarrer cette tache'
                    }
                },
                {
                    'Stop':
                        {
                        onclick:function(menuItem,menu) { doMenuTask($(this), 'stop'); },
                        title:'Arreter cette tache'
                    }
                },
                {
                    'Delete':
                        {
                        onclick:function(menuItem,menu) { doMenuTask($(this), 'delete'); },
                        title:'Supprimer cette tache'
                    }
                },
                $.contextMenu.separator,
                {
                    'Show log':
                        {
                        onclick:function(menuItem,menu) { doMenuTask($(this), 'log'); },
                        title:'Afficher les logs'
                    }
                }
            ],
            {
                optionsIdx:{'edit':0, 'start':1, 'stop':2, 'delete':3, 'log':5},
                beforeShow:function()
                {
                    var tid = $($(this)[0].target).parent().attr('id').split('_').pop();

                    if(!retPing || !retPing.tasks[tid])
                        return;

                    if(retPing.tasks[tid].pid)
                    {
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['edit']+')').addClass("context-menu-item-disabled");
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['stop']+')').removeClass("context-menu-item-disabled");
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['start']+')').addClass("context-menu-item-disabled");
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['delete']+')').addClass("context-menu-item-disabled");
                    }
                    else
                    {
                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['edit']+')').removeClass("context-menu-item-disabled");

                        if(retPing.tasks[tid].status == 'started' || retPing.tasks[tid].status == 'torestart')
                            $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['stop']+')').removeClass("context-menu-item-disabled");
                        else
                            $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['stop']+')').addClass("context-menu-item-disabled");

                        $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['delete']+')').removeClass("context-menu-item-disabled");
                        if(retPing.scheduler && retPing.scheduler.pid)
                            $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['start']+')').removeClass("context-menu-item-disabled");
                        else
                            $(this.menu).find('.context-menu-item:eq('+this.optionsIdx['start']+')').addClass("context-menu-item-disabled");
                    }

                }
            }
        );


            self.setTimeout("pingScheduler(true);", 100); // true : loop forever each 2 sec
        })
        </script>
    </head>
    <body>

        <iframe id="zsched" src="about:blank" style="position:absolute; top:0px; left:0px; width:50px; height:50px; visibility:hidden"></iframe>

        <h1><?php echo _('admin::tasks: planificateur de taches') ?>
            <span style="font-size:12px;">
                <?php echo sprintf(_('Last update at %s.'), '<span id="pingTime"></span>'); ?>
            </span>
        </h1>

        <table class="admintable task_manager" cellpadding="0" cellSpacing="0">
            <thead>
                <tr>
                    <th style="width:20px;"></th>
                    <th style="text-align:center; width:40px;">ID</th>
                    <th style="text-align:center; width:30px;">!</th>
                    <th style="text-align:center; width:80px;"><?php echo _('admin::tasks: statut de la tache') ?></th>
                    <th style="text-align:center; width:60px;"><?php echo _('admin::tasks: process_id de la tache') ?></th>
                    <th style="text-align:center; width:120px;"><?php echo _('admin::tasks: etat de progression de la tache') ?></th>
                    <th style="width:auto;"><?php echo _('admin::tasks: nom de la tache') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr id="TR_SCHED" class="sched even">
                    <td class="dropdown scheduler">
                        <img src="/skins/admin/dropdown.png"/>
                    </td>
                    <td>&nbsp</td>
                    <td>&nbsp</td>
                    <td style="text-align:center" id="STATUS_SCHED"></td>
                    <td id="PID_SCHED" style="text-align:center;">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td style="font-weight:900" class="taskname">TaskManager</td>
                </tr>
                <?php
                $n = 0;
                foreach ($task_manager->getTasks($refresh_tasklist) as $task) {
                    $n ++;
                    $tid = $task->getID()
                    ?>
                    <tr id="TR_<?php echo $tid ?>" class="task <?php echo $n % 2 == 0 ? 'even' : 'odd' ?>">
                        <td class="dropdown task">
                            <img src="/skins/admin/dropdown.png"/>
                        </td>
                        <td style="text-align:center; font-weight:900"><?php echo $tid ?></td>
                        <td style="text-align:center"><img id="WARNING_<?php echo $tid ?>" src="/skins/icons/alert.png" title="" style="display:none;"/></td>
                        <td style="text-align:center" id="STATUS_<?php echo $tid ?>"></td>
                        <td style="text-align:center" id="PID_<?php echo $tid ?>">&nbsp;</td>
                        <td>
                            <div style="position:relative; top:0px; left:0px; right:0px;" >
                                <div id="COMPBOX_<?php echo $tid ?>" style="position:absolute; top:1px; left:3px; right:3px; height:5px; background-color:#787878">
                                    <div id="COMP_<?php echo $tid ?>" style="position:absolute; top:1px; left:0px; width:0%; height:3px; background-color:#FFFF80">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="taskname"><?php echo p4string::MakeString($task->getTitle()) ?> [<?php echo p4string::MakeString($task::getName()) ?>]</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="dropdown scheduler">
                        <img id="newTaskButton" src="/skins/admin/dropdown.png"/>
                    </td>
                    <td colspan="6">
                        <?php echo _('admin::tasks: Nouvelle tache') ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div id="db_processlist"></div>

        <script type="text/javascript">

                function editTask(tid)
                {
                    document.forms["task"].target = "";
                    document.forms["task"].act.value = "EDITTASK";
                    document.forms["task"].tid.value = tid;
                    document.forms["task"].submit();
                }

                function setTaskStatus(tid, status, signal, resetCrashCounter)
                {
                    if(resetCrashCounter)
                    {
                        $.ajax({
                            url: "/admin/adminFeedback.php",
                            data : { task_id:tid, action:"RESETTASKCRASHCOUNTER" },
                            dataType:'xml',
                            success: function(ret)
                            {
                            }
                        });
                    }

                    $.ajax({
                        url: "/admin/adminFeedback.php",
                        data : {task_id:tid, action:"SETTASKSTATUS", status:status, signal:signal},
                        dataType:'json',
                        success: function(ret)
                        {
                            pingScheduler(false); // false : just one time
                        }
                    });
                }


                function setSchedStatus(status)
                {
                    $.ajax({
                        url: "/admin/adminFeedback.php",
                        data : { action:"SETSCHEDSTATUS", status:status },
                        dataType:'json',
                        success: function(ret)
                        {
                            pingScheduler(false); // false : just one time
                        }
                    });
                }


                function deleteTask(tid)
                {
                    if(confirm("<?php echo p4string::MakeString(_('admin::tasks: supprimer la tache ?'), 'js', '"') ?>"))
                    {
                        document.forms["taskManager"].target = "";
                        document.forms["taskManager"].act.value = "DELETETASK";
                        document.forms["taskManager"].tid.value = tid;
                        document.forms["taskManager"].submit();
                    }
                }

                function pingScheduler(repeat)
                {
                    $.ajax({
                        url: '/admin/adminFeedback.php',
                        data:{action:'PINGSCHEDULER_JS', dbps:0},
                        dataType:'json',
                        success: function(ret)
                        {
                            retPing = ret;  // global
                            if(ret.time)
                                $("#pingTime").empty().append(ret.time);
                            if(ret.scheduler)
                            {
                                if(ret.scheduler.status)
                                    $("#STATUS_SCHED").html(ret.scheduler.status);
                                else
                                    $("#STATUS_SCHED").html('');
                                if(ret.scheduler.pid)
                                    $("#PID_SCHED").html(ret.scheduler.pid);
                                else
                                    $("#PID_SCHED").html('-');
                            }
                            else
                            {
                                $("#STATUS_SCHED").html('');
                                $("#PID_SCHED").html('-');
                            }

                            if(ret.tasks)
                            {
                                for(id in ret.tasks)
                                {
                                    if(ret.tasks[id].status)
                                        $("#STATUS_"+id).html(ret.tasks[id].status);
                                    else
                                        $("#STATUS_"+id).html('');

                                    if(ret.tasks[id].pid)
                                        $("#PID_"+id).html(ret.tasks[id].pid);
                                    else
                                        $("#PID_"+id).html('-');

                                    if(ret.tasks[id].crashed)
                                    {
                                        //                      $("#WARNING_"+id).show().setAttribute("src", "/skins/icons/alert.png");
                                        $("#WARNING_"+id).show().attr("title", "crashed "+ret.tasks[id].crashed+" times");
                                    }
                                    else
                                    {
                                        $("#WARNING_"+id).hide();
                                    }

                                    if(ret.tasks[id].completed && ret.tasks[id].completed>0 && ret.tasks[id].completed<=100)
                                    {
                                        $("#COMP_"+id).width(ret.tasks[id].completed + "%");
                                        $("#COMPBOX_"+id).show();
                                    }
                                    else
                                    {
                                        $("#COMPBOX_"+id).hide();
                                        $("#COMP_"+id).width('0px');
                                    }
                                }
                            }

                            if(ret.db_processlist)
                            {
                                var _table = document.createElement('table');
                                _table.setAttribute('class', 'db_processlist');
                                for(p in ret.db_processlist)
                                {
                                    if(p==0)
                                    {
                                        var _tr = _table.appendChild(document.createElement('tr'));
                                        for(c in ret.db_processlist[p])
                                            _tr.appendChild(document.createElement('th')).appendChild(document.createTextNode(c));
                                    }
                                    var _tr = _table.appendChild(document.createElement('tr'));
                                    for(c in ret.db_processlist[p])
                                        _tr.appendChild(document.createElement('td')).appendChild(document.createTextNode(ret.db_processlist[p][c]));
                                }
                                $("#db_processlist").html(_table);
                            }
                            if(repeat)
                                self.setTimeout("pingScheduler(true);", 1000);
                        }
                    });
                }

                function lauchScheduler()
                {
                    url  = "./runscheduler.php";
                    document.getElementById("zsched").src = url;
                    self.setTimeout('document.getElementById("zsched").src="about:blank";', 2000);
                }

        </script>

        <div id="scheduler-preferences" style="display:none;" title="<?php echo _('Preferences du TaskManager'); ?>">
            <div style="margin:5px 0;"><span><?php echo _('Cette URL vous permet de controler le sheduler depuis un manager comme cron') ?></span></div>
            <div style="margin:5px 0;"><input id="scheduler_key" style="width:100%;" type="text" readonly="readonly" value="<?php echo $registry->get('GV_ServerName') ?>admin/runscheduler.php?key=<?php echo phrasea::scheduler_key(); ?>" /></div>
        </div>

        <form method="post" name="taskManager" action="/admin/taskmanager.php" target="???" onsubmit="return(false);" >
            <input type="hidden" name="act" value="" />
            <input type="hidden" name="tid" value="" />
            <input type="hidden" name="att" value="" />
        </form>
        <form method="post"  name="task" action="/admin/task2.php" target="???" onsubmit="return(false);">
            <input type="hidden" name="act" value="???" />
            <input type="hidden" name="tid" value="" />
            <input type="hidden" name="tcl" value="" />
            <input type="hidden" name="view" value="GRAPHIC" />
        </form>


    </body>
</html>
