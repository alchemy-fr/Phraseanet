<?php
$gv = dirname( __FILE__ ) . '/../../config/_GV.php';
if(is_file($gv))
	include_once $gv;
include_once dirname( __FILE__ ) . '/../../lib/classes/phrasea.class.php';
include_once dirname( __FILE__ ) . '/../../lib/version.inc';
include_once dirname( __FILE__ ) . '/../../lib/classes/session.class.php';
include_once dirname( __FILE__ ) . '/../../lib/classes/httpRequest.class.php';
phrasea::use_i18n();

$request = httpRequest::getInstance();

switch($request->get_code())
{
	case '204':
		$title = '204 NO Content';
		$desc = '<p>'._('error:204::Le contenu que vous demandez n\'existe pas ou a expire').'</p>';
		break;
	case '400':
		$title = '400 Bad Request';
		$desc = '<p>'._('error:400::La requete que vous faites ne peut etre traitee car les parametres necessaire a son traitement, sont mauvais ou manquants.').'</p>';
		break;
	case '403':
		$title = '403 Forbidden';
		$desc = '<p>'._('error:403::Vous avez demande une page a laquelle vous n\'avez pas acces.').'</p>
				<p>'._('error:403::Soit vous n\'avez pas les droits, soit vous avez ete deconnecte.').'</p>';
		break;
	case '404':
		$title = '404 Not Found';
		$desc = '<p>'._('error:404::Vous avez demande une page qui n\'existe pas ou plus').'</p>';
		break;
	case '500':
		$title = '500 Internal Server Error';
		$desc = '<p>'._('error:500::Erreur interne du serveur').'</p>
				<p>'._('error:500::Une erreur interne est survenue. Ceci se produit lorsque la connetion a la base de donnee a ete interrompue ou lorsqu\'un module rencontre un probleme.').'</p>
				<p>'._('error:500::Si ce probleme persiste, contactez l\'administrateur du serveur').'</p>';
		break;
	default:
		$title = 'Unknown Error';
		$desc = '';
		break;
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $title;?></title>
</head>
<style>
	body{
		background-color:#000000;
		color:#b1b1b1;
		font-family:Verdana, Arial, Sans-serif;
	}
	.title{
		font-size:28px;
		font-family:Helvetica, Arial, Sans-serif;
	}
	p{
		font-size:12px;
	}
	color:
</style>
<body>
	<h1><?php echo $title;?></h1>
	<?php echo $desc;?>

	<!-- 
	
	Cette page doit au moins peser 512 bytes, auquel cas IE l'affichera correctement.
	Du coup, lisons un petit coup de Ciceron en l'honneur de Microsoft :
	----------------------------------------------------------------------------------------------------------------------
	
	Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, 
	totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. 
	
	Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, 
	sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. 
	
	Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, 
	sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. 
	
	Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, 
	nisi ut aliquid ex ea commodi consequatur? 
	
	Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, 
	vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?
	
	----------------------------------------------------------------------------------------------------------------------
	
	-->
	 
	 
</body>
</html>