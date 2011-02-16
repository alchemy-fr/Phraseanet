<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require(GV_RootPath."lib/adminUtils.php");
$session = session::getInstance();


$lng =  !isset($session->locale)?GV_default_lng:$session->locale;



if(isset($session->usr_id) && isset($session->ses_id))
{
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!isset($session->admin))
	{
		header("Location: /login/logout.php?app=admin");
		exit();	
	}
	elseif(!$session->admin)
	{
		phrasea::headers(403);
	}
	
}
else{
	header("Location: /login/admin/");
	exit();
}

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
		phrasea::headers(403);
}
user::updateClientInfos(3);
	
$conn = connection::getInstance();
if(!$conn)
		phrasea::headers(500);

		
phrasea::headers();


 
?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<title><?php echo GV_homeTitle?> Admin</title>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<link type="text/css" rel="stylesheet" href="/include/minify/f=include/jquery-treeview/jquery.treeview.css" />
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
<script type="text/javascript" src="/include/minify/g=admin"></script>
<script type="text/javascript">

var p4 = true;
var bodySize = {x:0,y:0};


var language = {<?php echo "serverName:'".GV_ServerName."',"?>
		<?php echo "serverError:'".html_entity_decode(addslashes(_('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique')))."',"?>
		<?php echo "serverTimeout:'".html_entity_decode(addslashes(_('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible')))."',"?>
		<?php echo "serverDisconnected:'".html_entity_decode(addslashes(_('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier')))."'"?>};

window.onbeforeunload = function() 
{ 
	var xhr_object = null;    
	if(window.XMLHttpRequest) // Firefox   
	   xhr_object = new XMLHttpRequest();   
	else if(window.ActiveXObject) // Internet Explorer   
	   xhr_object = new ActiveXObject("Microsoft.XMLHTTP");   
	else  // XMLHttpRequest non supporte par le navigateur   
	  return;   
	url= "/include/delses.php?app=3&t="+Math.random();
	xhr_object.open("GET", url, false);     
	xhr_object.send(null);
};

function sessionactive(){
	$.ajax({
		type: "POST",
		url: "/include/updses.php",
		dataType: 'json',
		data: {
			app : 3,
			usr : <?php echo $usr_id?>
		},
		error: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		timeout: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		success: function(data){
			if(data)
				manageSession(data);
			var t = 20000;
			if(data.apps && parseInt(data.apps)>1)
				t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 20000));
			window.setTimeout("sessionactive();", t);
			return;
		}
	})
};
function activeTree()
{
	$('#FNDR').treeview({
		collapsed: true,
		animated: "medium"
	});

	$.each($('#tree a[target=right]'),function(){
			var dest = $(this).attr('href');

			$(this).bind('click',function(){
					$('#right').attr('src',dest);
					$('#tree .selected').removeClass('selected');
					$(this).parent().addClass('selected');
					return false;
				});
			
			$(this).attr('href','#').removeAttr('target');
		});
	}
	$(document).ready(
		function(){
			resize();
			setTimeout('sessionactive();',15000);
			activeTree();
		}
	);
	function refreshBaskets()
	{
		return;
	}
	function resize()
	{
		$('#left, #right').height($(this).height()-$('#mainMenu').height());
		bodySize.y = $(window).height() - $('#mainMenu').outerHeight();
		bodySize.x = $(window).width();
	}
	
	$(window).bind('resize',function(){resize();});

	function reloadTree(position){
		$.ajax({
			type: "POST",
			url: "adminFeedback.php",
			data: {
				action : 'TREE',
				position : position
			},
			success: function(datas){
				$('#FNDR').empty().append(datas);
				activeTree();
				return;
			}
		})
	}

</script>
<style>

li.selected, div.selected{
	background-color:black;color:white;
}
li.selected a, div.selected a{
	background-color:black;color:white;
}
</style>
</head>
<body>
	<?php
	$twig = new supertwig();
	$twig->display('common/menubar.twig', array('module'=>'admin'));
	
	$request = httpRequest::getInstance();
	$parm = $request->get_parms('section');
	
	?>
	<div style="position:relative;height:100%;top:30px;">
		<div id="left" style="width:25%;position:relative;float:left;height:100%;border:none;">					
			<div id="FNDR">
				<?php echo getTree($usr_id,$ses_id,isset($parm['section']) ? $parm['section'] : false);?>
			</div>
		</div>
	<?php 
	
	
	$url = '/admin/sessionwhois.php';
	
	switch($parm['section'])
	{
		case 'registrations':
			$url = '/admin/demand.php?act=LISTUSERS';
			break;
		case 'users':
			$url = '/admin/users.php?act=LISTUSERS&p0=&p1=';
			break;
		case 'taskmanager':
			$url = '/admin/taskmanager.php';
			break;
	}
	
	?>
			<iframe src="<?php echo $url?>"  name="right" id="right" frameborder="1" border="0" framespacing="0" style="width:74%;position:relative;float:left;height:100%;border:none;"></iframe>
		</div>
		<div id="DIALOG" style="color:white;"></div>
		<?php
		if(trim(GV_googleAnalytics) != '')
		{
			?>
			<script type="text/javascript">
				var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
				document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
			</script>
			<script type="text/javascript">
				try {
				var pageTracker = _gat._getTracker("<?php echo GV_googleAnalytics?>");
				pageTracker._setDomainName("none");
				pageTracker._setAllowLinker(true);
				pageTracker._trackPageview();
				} catch(err) {}
			</script>
			<?php
		}
		?>
</body>
</html>