<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms("act", "tid");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

if (!$user->ACL()->has_right('taskmanager'))
{
  phrasea::headers(403);
}

phrasea::headers();
$registry = $appbox->get_registry();
$task_manager = new task_manager($appbox);


?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>

    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
    <style>
      .divTop
      {
        OVERFLOW: hidden;
        height:18px;
      }
      #redbox0 table{
        font-size:10px;
        text-align:center;
      }

    </style>
    <link rel="stylesheet" href="/include/minify/f=include/jslibs/jquery.contextmenu.css,include/jslibs/jquery-ui-1.8.12/css/ui-lightness/jquery-ui-1.8.12.custom.css" type="text/css" media="screen" />
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js,include/jslibs/jquery-ui-1.8.12/development-bundle/ui/i18n/jquery-ui-i18n.js,include/jslibs/jquery.contextmenu.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript">
      var newTaskMenu = null;

      var allgetID = new Array ;
      var total = 0;



      var thbout_timer = null;
      var xMousePos = 0;
      var yMousePos = 0;

      function resized()
      {
        $("#redbox0").width($("#tableau_center").width()-4);
        $("#redbox1").width = ($("#tableau_center").width()-4);
      }

      var T_task = {};

      var menuTask = [
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
          'Fix':
            {
            onclick:function(menuItem,menu) { doMenuTask($(this), 'fix'); },
            title:'Reparer'
          }
        },
        $.contextMenu.separator,
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
      ];

      var T_mi_task = {edit:0, start:1, stop:2, fix:3, del:4, log:5};


      var menuSched = [
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
        {
          'Fix':
            {
            onclick:function(menuItem,menu) { doMenuSched('fix'); },
            title:'Reparer'
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
      ];

      var T_mi_sched = {start:0, stop:1, fix:2, log:4, preferences:5};

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
          case "preferences":
            preferencesScheduler();
            break;
          case "stop":
            setSchedStatus('tostop');
            break;
          case "fix":
            switch(T_task["SCHED"])
            {
              case "started_0":
              case "stopping_0":
              case "tostop_0":
                setSchedStatus('stopped');
                break;
              case "stopped_1":
              case "stopping_1":
              case "tostop_1":
                setSchedStatus('started');
                break;
            }
            break;
          case "log":
            window.open("/admin/showlogtask.php?fil=<?php echo urlencode('scheduler.log') ?>", "SCHEDLOG");
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
              setTaskStatus(tid, 'tostart');
              break;
            case "stop":
              setTaskStatus(tid, 'tostop');
              break;
            case "fix":
              switch(T_task[tid])
              {
                case "started_0":
                case "starting_0":
                case "stopping_0":
                case "tostart_0":
                case "tostop_0":
                case "manual_0":
                  setTaskStatus(tid, 'stopped');
                  break;

                case "stopped_1":
                case "starting_1":
                case "stopping_1":
                case "tostart_1":
                case "tostop_1":
                  //  case "manual_1":
                  //  case "torestart_1":
                  setTaskStatus(tid, 'started');
                  break;
              }
              break;
            case 'delete':
              switch(T_task[tid])
              {
                case "stopped_0":
                case "started_0":
                case "starting_0":
                case "stopping_0":
                case "tostart_0":
                case "tostop_0":
                case "manual_0":
                case "torestart_0":
                  if(confirm("<?php echo p4string::MakeString(_('admin::tasks: supprimer la tache ?'), 'js', '"') ?>"))
                  {
                    document.forms["taskManager"].target = "";
                    document.forms["taskManager"].act.value = "DELETETASK";
                    document.forms["taskManager"].tid.value = tid;
                    document.forms["taskManager"].submit();
                  }
                  break;
                }
                break;
              case "log":
                window.open("/admin/showlogtask.php?fil=<?php echo urlencode('task_t_') ?>"+tid+"%2Elog", "TASKLOG_"+tid);
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



              var thbout_timer = null;
              var xMousePos = 0;
              var yMousePos = 0;

              var menuNewTask = [
<?php
// fill the 'new task' menu
$tasks = task_manager::getAvailableTasks();
$ntasks = count($tasks);
foreach ($tasks as $t)
{
  printf("      {\n");
  printf("        '%s':\n", p4string::MakeString($t["name"], 'js'));
  printf("        {\n");
  printf("          disabled:%s,\n", $t["err"] ? 'true' : 'false');
  printf("          onclick:function(menuItem, menu) { newTask('%s'); },\n", p4string::MakeString($t["class"], 'js'));
  printf("          title:'%s'\n", p4string::MakeString($t["name"], 'js'));
  printf("        }\n");
  printf("      }%s\n", --$ntasks ? ',' : '');
}
?>
          ];


          $('#newTaskButton').contextMenu(
          menuNewTask,
          {
            theme:'vista'
          }
        );
          $('.task_manager .dropdown.task').contextMenu(
          menuTask,
          {
            theme:'vista',
            beforeShow:function()
            {
              var tid = $($(this)[0].target).parent().attr('id').split('_').pop();
              if(typeof(T_task[tid])=="undefined")
              {
                if(window.console)
                  console.log('No task like this');

                return(false);
              }

              switch(T_task[tid])
              {
                case "stopped_0":  // normal
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').removeClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
                  //                            setTaskStatus(tid, 'tostart');
                  break;
                case "started_0":
                case "starting_0":
                case "stopping_0":
                case "tostop_0":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').removeClass("context-menu-item-disabled");
                  break;
                  break;
                case "torestart_0":
                  break;

                case "started_1":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').removeClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
                  break;
                case "tostart_0":
                case "stopped_1":
                case "starting_1":
                case "stopping_1":
                case "tostart_1":
                case "tostop_1":
                case "manual_0":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').removeClass("context-menu-item-disabled");
                  break;
                case "manual_1":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.stop+')').removeClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_task.fix+')').addClass("context-menu-item-disabled");
                  break;
                case "torestart_1":
                  break;
              }
            }
          }
        );

          $('.dropdown.scheduler').contextMenu(
          menuSched,
          {
            theme:'vista',
            beforeShow:function()
            {
//              $("#TAB_CONTENT tr").removeClass('b');
//              $("#TR_SCHED").addClass('b');

              switch(T_task["SCHED"])
              {
                case "stopped_0":  // normal
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').removeClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').addClass("context-menu-item-disabled");
                  break;
                case "stopping_0":
                case "started_0":
                case "tostop_0":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').removeClass("context-menu-item-disabled");
                  break;
                  break;
                case "stopped_1":
                case "stopping_1":
                case "tostop_1":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').removeClass("context-menu-item-disabled");
                  break;
                case "started_1":
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.start+')').addClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.stop+')').removeClass("context-menu-item-disabled");
                  $(this.menu).find('.context-menu-item:eq('+T_mi_sched.fix+')').addClass("context-menu-item-disabled");
                  break;
              }
            }
          }
        );

          self.setTimeout("pingScheduler();", 100);
        })
    </script>
  </head>
  <body>


    <?php
    if ($parm["act"] == "DELETETASK")
    {
      try
      {
        $task = $task_manager->get_task($parm['tid']);
        $task->delete();
      }
      catch (Exception $e)
      {

      }
    }
    ?>
    <iframe id="zsched" src="about:blank" style="position:absolute; top:0px; left:0px; width:50px; height:50px; visibility:hidden"></iframe>

    <h1><?php echo _('admin::tasks: planificateur de taches') ?></h1>

    <form method="post" name="taskManager" action="/admin/taskmanager.php" target="???" onsubmit="return(false);" >
      <input type="hidden" name="act" value="" />
      <input type="hidden" name="tid" value="" />
      <input type="hidden" name="att" value="" />
    </form>
    <p>
      <?php echo sprintf(_('Last update at %s.'), '<span id="pingTime"></span>'); ?>
      <a id="newTaskButton" href="#"><?php echo _('admin::tasks: Nouvelle tache') ?></a>
    </p>
        <table class="admintable task_manager" cellpadding="0" cellSpacing="0">
          <thead>
            <tr>
              <th style="width:20px;"></th>
              <th style="width:40px;">ID</th>
              <th style="width:80px;"><?php echo _('admin::tasks: statut de la tache') ?></th>
              <th style="width:60px;"><?php echo _('admin::tasks: process_id de la tache') ?></th>
              <th style="width:120px;"><?php echo _('admin::tasks: etat de progression de la tache') ?></th>
              <th style="width:auto;"><?php echo _('admin::tasks: nom de la tache') ?></th>
            </tr>
          </thead>
          <tbody>
            <tr id="TR_SCHED" class="sched even">
              <td class="dropdown scheduler">
                <img src="/skins/admin/dropdown.png"/>
              </td>
              <td>&nbsp</td>

              <td style="text-align:center" id="STATUS_SCHED">
                <img id="STATUSIMG_SCHED" style="display:none;" />
                <img id='WARNING_SCHED' style='display:none' src="/skins/icons/alert.png" alt="" />
              </td>

              <td id="PID_SCHED" style="text-align:center;">&nbsp;</td>

              <td>
                &nbsp;
              </td>

              <td style="font-weight:900" class="taskname">TaskManager</td>
            </tr>
<?php
    $n = 0;
    foreach ($task_manager->get_tasks() as $task)
    {
      $n++;
      $tid = $task->get_task_id()
?>
            <tr id="TR_<?php echo $tid ?>" class="task <?php echo $n%2 == 0 ? 'even':'odd' ?>">
              <td class="dropdown task">
                <img src="/skins/admin/dropdown.png"/>
              </td>
              <td style="text-align:center; font-weight:900"><?php echo $tid ?></td>

              <td style="text-align:center" id="STATUS_<?php echo $tid ?>">
                <img id="STATUSIMG_<?php echo $tid ?>" style="display:none;" />
                <img id='WARNING_<?php echo $tid ?>' style='display:none' src="/skins/icons/alert.png" alt="" />
              </td>

              <td id="PID_<?php echo $tid ?>" style="text-align:center;width:60px;">&nbsp;</td>

              <td>
                <div id="COMPBOX_<?php echo $tid ?>" style="position:relative; top:1px; left:5px; width:110px; height:5px; background-color:#787878; visibility:hidden">
                  <div id="COMP_<?php echo $tid ?>" style="position:absolute; top:1px; left:0px; width:0%; height:3px; background-color:#FFFF80">
                  </div>
                </div>
              </td>

              <td style="width:auto" class="taskname"><?php echo p4string::MakeString($task->get_title()) ?> [<?php echo p4string::MakeString($task::getName()) ?>]</td>
            </tr>
<?php
//              $i = 1 - $i;
          }
?>          </tbody>
          </table>

<?php ?>
    <script type="text/javascript">

            function editTask(tid)
            {
              document.forms["task"].target = "";
              document.forms["task"].act.value = "EDITTASK";
              document.forms["task"].tid.value = tid;
              document.forms["task"].submit();
            }

            function setTaskStatus(tid, status)
            {
              var url  = "/admin/adminFeedback.php";
              url += "?action=SETTASKSTATUS&task_id=" + encodeURIComponent(tid);
              url += "&status=" + encodeURIComponent(status);

              $.ajax({
                url: url,
                dataType:'xml',
                success: function(ret)
                {

                }
              });
            }


            function setSchedStatus(status)
            {
              var url  = "/admin/adminFeedback.php";
              url += "?action=SETSCHEDSTATUS&status=" + encodeURIComponent(status);

              $.ajax({
                url: url,
                dataType:'xml',
                success: function(ret)
                {

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

                    function pingScheduler()
                    {
                      $.ajax({
                        url: '/admin/adminFeedback.php?action=PINGSCHEDULER',
                        dataType:'xml',
                        success: function(ret)
                        {


                          var ping= '', pingTime='', newStr='', statusping='', running='', qdelay;

                          status = ret.documentElement.getAttribute("status"); // +'_'+ret.documentElement.getAttribute("ping");
                          pingTime = ret.documentElement.getAttribute("time");
                          locked = ret.documentElement.getAttribute("locked");
                          qdelay = ret.documentElement.getAttribute("qdelay");
                          schedpid = ret.documentElement.getAttribute("pid");

                          T_task['SCHED'] = status+'_'+locked;

                          $("#PID_SCHED").empty().append(schedpid);
                          switch(status+'_'+locked)
                          {
                            case "stopped_0":  // normal

                              $("#STATUSIMG_SCHED").attr("src", "/skins/icons/stop-alt.png").show();
                              break;
                            case "stopping_0":  // not normal (scheduler killed ?)
                            case "started_0":
                            case "tostop_0":
                              $("#STATUSIMG_SCHED"+id).attr("src", "/skins/icons/alert.png").show();
                              break;
                            case "stopped_1":  // not normal (no database ?)

                              $("#STATUSIMG_SCHED"+id).attr("src", "/skins/icons/alert.png").show();

                              break;
                            case "stopping_1":  // normal, wait
                            case "tostop_1":

                              $("#STATUSIMG_SCHED").attr("src", "/skins/icons/indicator.gif").show();

                              break
                            case "started_1":

                              $("#STATUSIMG_SCHED").attr("src", "/skins/icons/up.png").show();

                              break;
                          }


                          $("#pingTime").empty().append(pingTime);


                          var t = ret.getElementsByTagName('task');
                          for(var i=0; i<t.length; i++)
                          {
                            var id = t.item(i).getAttribute('id');
                            var status  = t.item(i).getAttribute('status') + "_" + t.item(i).getAttribute('running');
                            var active  = t.item(i).getAttribute('active');
                            var pid     = t.item(i).getAttribute('pid');
                            var completed = 0 | (t.item(i).getAttribute('completed'));
                            var crashed   = 0 | (t.item(i).getAttribute('crashed'));
                            var runner = t.item(i).getAttribute('runner');

                            if(runner === 'manual')
                              status = 'manual'+ "_" + t.item(i).getAttribute('running');;

                            T_task[id] = status;
                            switch(status)
                            {
                              case "stopped_0":  // normal
                                $("#STATUSIMG_"+id).attr("src", "/skins/icons/stop-alt.png").show();
                                break;
                              case "started_0":
                              case "stopping_0":
                              case "manual_0":
                              case "tostop_0":
                              case "stopped_1":
                              case "starting_1":
                              case "tostart_1":
                                $("#STATUSIMG_"+id).attr("src", "/skins/icons/alert.png").show();
                                break;
                              case "tostart_0":
                              case "starting_0":
                              case "stopping_1":
                              case "tostop_1":
                              case "torestart_0":
                              case "torestart_1":
                                $("#STATUSIMG_"+id).attr("src", "/skins/icons/indicator.gif").show();
                                break;
                              case "manual_1":
                                $("#STATUSIMG_"+id).attr("src", "/skins/icons/public.png").show();
                                break;
                              case "started_1":
                                $("#STATUSIMG_"+id).attr("src", "/skins/icons/up.png").show();
                                break;
                            }



                            if(o = document.getElementById("COMP_"+id))
                            {
                              //if(completed >=0)
                              //  o.innerHTML = completed + "%";
                              //else
                              //  o.innerHTML = '&nbsp;';
                              if(completed >=0)
                              {
                                document.getElementById("COMP_"+id).style.width = completed + "%";
                                document.getElementById("COMPBOX_"+id).style.visibility = "visible";
                              }
                              else
                              {
                                document.getElementById("COMPBOX_"+id).style.visibility = "hidden";
                                document.getElementById("COMP_"+id).style.width = '0px';
                              }
                            }
                            if(o = document.getElementById("PID_"+id))
                            {
                              if(pid != "")
                                o.innerHTML = pid;
                              else
                                o.innerHTML = '&nbsp;';
                            }
                            if(o = document.getElementById("WARNING_"+id))
                            {
                              if(crashed > 0)
                              {
                                var str = "<?php echo p4string::MakeString(_('admin::tasks: Nombre de crashes : '), 'js', '"'); ?>"+crashed;
                                o.setAttribute('alt', str);
                              }
                              o.style.display = crashed > 0 ? '':'none';
                            }
                          }

                          self.setTimeout("pingScheduler();", 3000);
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

            <form method="post"  name="task" action="/admin/task2.php" target="???" onsubmit="return(false);">
      <input type="hidden" name="act" value="???" />
      <input type="hidden" name="tid" value="" />
      <input type="hidden" name="tcl" value="" />
      <input type="hidden" name="view" value="GRAPHIC" />
    </form>


  </body>
</html>
