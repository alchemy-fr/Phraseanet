<html lang="<?php echo $session->usr_i18n;?>">
<body>
<div style='background-color:#e0e0e0'>
<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO



$request = httpRequest::getInstance();
$parm = $request->get_parms('ses_id', 'usr_id', 'upload_batch_id', 'index', 'file', 'debug');

	$fil = $parm['file'];
	
	$filesize   = filesize($fil);
	$filename   = basename($fil);
	$k          = file_get_contents($fil);
	$md5        = md5($k, false);
//	$crc32      = crc32($k);
	
	$url = dirname($_SERVER["HTTP_REFERER"]) . '/upload/upload.php';

	$post = array(
					'debug'=>$parm['debug'],    
					'file'=>'@'.realpath($fil),
					'ses_id'=>$parm['ses_id'],
					'usr_id'=>$parm['usr_id'],
					'p'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<parms action="UPLOAD">
	<upload_batch_id>'.htmlentities($parm['upload_batch_id']).'</upload_batch_id>
	<index>'.htmlentities($parm['index']).'</index>
	<filename>'.htmlentities($filename).'</filename>
	<filesize>'.htmlentities($filesize).'</filesize>
	<md5>'.htmlentities($md5).'</md5>
</parms>'
				);
?>

<b>post to :</b><?php echo htmlentities($url)?> <br/>
<pre>
<?php
	echo htmlentities(var_export($post, true));
				
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);    // same as <input type="file" name="file">
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	
	curl_close($ch);
?>
</pre>
</div>
<b>reponse : </b><br/>
<?php echo $response ?>
</body>
</html>