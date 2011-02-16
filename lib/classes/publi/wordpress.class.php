<?php
class publi_wordpress extends p4publi
{	
	
	function getName()
	{
		return 'wordpress';
	}
	
	function __construct()
	{
		
	}
	
	function __destruct()
	{
		
	}
	
	function update($post_id,$datas,$ssel_id)
	{	
		$conn = connection::getInstance();
		
		$donnees = self::prepare($ssel_id);
		
		$titre = $donnees['titre'];
		$desc = $donnees['desc'];
		
		
		$ret = false;
		$domain = self::processDomain($datas['url']);
		
		$DOMAIN = $domain['domain'];
		$SUBDOMAIN = $domain['sub_domain'];
		$USER = $datas['login'];
		$PASSWORD = $datas['password'];
		 
		$cl = new xmlrpc_client ( $SUBDOMAIN."/xmlrpc.php", $DOMAIN, 80);
		 
		$req = new xmlrpcmsg('metaWeblog.editPost');
		
		$req->addParam ( new xmlrpcval ( $post_id, 'string')); 
		$req->addParam ( new xmlrpcval ( $USER, 'string' )); 
		$req->addParam ( new xmlrpcval ( $PASSWORD, 'string' )); 
		$struct = new xmlrpcval (
			array (
				"description" => new xmlrpcval ( $desc, 'string'), // description
			), "struct"
		);
		$req->addParam ( $struct );
		$req->addParam ( new xmlrpcval (1, 'int')); //publish
		 
		$ans = $cl->send($req);
		
		if($ans->faultCode() === 0 && $ans->faultString() === '')
		{
			return true;
		}
		return false;
		
	}
	
	function publish($datas,$ssel_id)
	{
		
		
		$conn = connection::getInstance();
		
		$post_id = false;
		
		$sql = 'SELECT * FROM published WHERE ssel_id = "'.$conn->escape_string($ssel_id).'" AND publi_id="'.$conn->escape_string($datas['publi_id']).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$post_id = $row['post_id'];
			$conn->free_result($rs);
		}
		
		if($post_id)
		{
			$updated = self::update($post_id,$datas,$ssel_id);
			if($updated)
				return $post_id;
		}
		$donnees = self::prepare($ssel_id);
		
		$titre = $donnees['titre'];
		$desc = $donnees['desc'];
		
		
		$ret = false;
		$domain = self::processDomain($datas['url']);
		
		$DOMAIN = $domain['domain'];
		$SUBDOMAIN = $domain['sub_domain'];
		$BLOGID = 0;
		$USER = $datas['login'];
		$PASSWORD = $datas['password'];
		 
		$cl = new xmlrpc_client ( $SUBDOMAIN."/xmlrpc.php", $DOMAIN, 80);
		 
		$req = new xmlrpcmsg('metaWeblog.newPost');
		
		$req->addParam ( new xmlrpcval ( $BLOGID, 'int')); 
		$req->addParam ( new xmlrpcval ( $USER, 'string' )); 
		$req->addParam ( new xmlrpcval ( $PASSWORD, 'string' )); 
		$struct = new xmlrpcval (
			array (
				"title" => new xmlrpcval ( $titre, 'string' ),
				"description" => new xmlrpcval ( $desc, 'string'), // description
			), "struct"
		);
		$req->addParam ( $struct );
		$req->addParam ( new xmlrpcval (1, 'int')); //publish
		 
		$ans = $cl->send($req);
		
		$id = false;
		if($ans->faultCode() === 0 && $ans->faultString() === '')
		{
			$id = $ans->value()->scalarval();
			
			self::save($ssel_id, $datas['publi_id'],$id);
		}
		return $id;
		
	}
	
	function requiredFields()
	{
		return array(
			'nom'=>_('publi externe:: nom'),'url'=>_('publi externe:: url'),'login'=>_('publi externe:: identifiant'),'password'=>_('publi externe:: password')
		);
	}
	
	function getOptions()
	{
		return array(
			array('name'=>'upload','label'=>'Uploader les fichiers sur le serveur','type'=>'checkbox')
		);
	}
	
	private function processDomain($domain)
	{
		$ret = array('domain'=>$domain,'sub_domain'=>'');
		
		$domain = preg_replace('/http:\/\//','',$domain,1);
		
		$domain= explode('/',p4string::delEndSlash($domain));
		
		$ret['domain'] = $domain[0];
		
		if(count($domain)>1)
		{
			unset($domain[0]);
			$ret['sub_domain'] = implode('/',$domain);
		}
		
		return $ret;
	}

	function test($datas)
	{ 
		
		$ret = false;
		$domain = self::processDomain($datas['url']);
		
		$DOMAIN = $domain['domain'];
		$SUBDOMAIN = $domain['sub_domain'];
		$BLOGID = 0;
		$USER = $datas['login'];
		$PASSWORD = $datas['password'];
		 
		$cl = new xmlrpc_client ( $SUBDOMAIN."/xmlrpc.php", $DOMAIN, 80);
		 
		$req = new xmlrpcmsg('metaWeblog.newPost');
		
		$req->addParam ( new xmlrpcval ( $BLOGID, 'int')); 
		$req->addParam ( new xmlrpcval ( $USER, 'string' )); 
		$req->addParam ( new xmlrpcval ( $PASSWORD, 'string' )); 
		$struct = new xmlrpcval (
			array (
				"title" => new xmlrpcval ( 'NEW Post', 'string' ),
				"description" => new xmlrpcval ( 'Test using metaWeblog newPost for creating a new post on our blog.', 'string'), // description
			), "struct"
		);
		$req->addParam ( $struct );
		$req->addParam ( new xmlrpcval (0, 'int')); //publish
		 
		$ans = $cl->send($req);
		
		if($ans->faultCode() === 0 && $ans->faultString() === '')
		{
			$id = $ans->value()->scalarval();
			
			$reqDel = new xmlrpcmsg('blogger.deletePost');
			
			$reqDel->addParam ( new xmlrpcval ( '', 'string'));//appkey deprecated 
			$reqDel->addParam ( new xmlrpcval ( $id, 'int')); 
			$reqDel->addParam ( new xmlrpcval ( $USER, 'string' )); 
			$reqDel->addParam ( new xmlrpcval ( $PASSWORD, 'string' )); 
			$reqDel->addParam ( new xmlrpcval (0, 'int')); //publish
			 
			$ansDel = $cl->send($reqDel);
			
			if($ansDel->faultCode() === 0 && $ansDel->faultString() === '')
				$ret = true;
		}
		
		
		return $ret;		
	}
	
	public function savePreset($datas)
	{
		
		$session = session::getInstance();
		$conn = connection::getInstance();
		$sql = 'INSERT INTO publi_settings (publi_id, usr_id, name, url, login, password, appkey, created_on, updated_on, type)
		 VALUES (null, "'.$conn->escape_string($session->usr_id).'","'.$conn->escape_string($datas['nom']).'","'.$conn->escape_string($datas['url']).'","'.$conn->escape_string($datas['login']).'","'.$conn->escape_string($datas['password']).'",null , NOW(), NOW(), "wordpress")';
		if($conn->query($sql))
			return true;
		return false;		
	}
	
	public static function unpublish($datas, $ssel_id)
	{
		
		$conn = connection::getInstance();
		
		$post_id = false;
		
		$sql = 'SELECT * FROM published WHERE ssel_id = "'.$conn->escape_string($ssel_id).'" AND publi_id="'.$conn->escape_string($datas['publi_id']).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$post_id = $row['post_id'];
			$conn->free_result($rs);
		}
		
		if(!$post_id)
		{
			return false;
		}
		
		
		$ret = false;
		$domain = self::processDomain($datas['url']);
		
		$DOMAIN = $domain['domain'];
		$SUBDOMAIN = $domain['sub_domain'];
		$BLOGID = 0;
		$USER = $datas['login'];
		$PASSWORD = $datas['password'];
		 
		$cl = new xmlrpc_client ( $SUBDOMAIN."/xmlrpc.php", $DOMAIN, 80);
		 
		$req = new xmlrpcmsg('metaWeblog.deletePost');
		
		$req->addParam ( new xmlrpcval ( '', 'string')); 
		$req->addParam ( new xmlrpcval ( $post_id, 'string')); 
		$req->addParam ( new xmlrpcval ( $USER, 'string' )); 
		$req->addParam ( new xmlrpcval ( $PASSWORD, 'string' )); 
		$req->addParam ( new xmlrpcval (0, 'int')); //publish
		 
		$ans = $cl->send($req);
		
		if($ans->faultCode() === 0 && $ans->faultString() === '')
		{
			return true;
		}
		return false;
	
	}
	
}