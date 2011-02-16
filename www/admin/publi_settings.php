<?php
$session = session::getInstance();

?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	
	
	
	</head>
	
	<body>
		<form>
			<div>
				ajouter une preset de publication automatique : 
				<select>
				<?php 
				
				if(($dir = opendir(GV_RootPath . '/lib/classes/publi/')) !== false)
				{
					while(($file=readdir($dir)) !== false)
					{
						$substr = substr($file,-10);
						if(is_file($file) && trim($substr) !== false)
						{
							?><option value="<?php echo $substr;?>"><?php echo $substr;?></option><?php 
						}
					}
					
				}
				?>
				</select>
				<input type="text" value=""/>
			</div>
		
		
		
		</form>
	
	
	</body>
</html>