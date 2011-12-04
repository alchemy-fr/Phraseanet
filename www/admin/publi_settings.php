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
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
?>

<html lang="<?php echo $session->get_I18n(); ?>">
  <head>



  </head>

  <body>
    <form>
      <div>
                ajouter une preset de publication automatique :
        <select>
<?php
if (($dir = opendir($registry->get('GV_RootPath') . '/lib/classes/publi/')) !== false)
{
  while (($file = readdir($dir)) !== false)
  {
    $substr = substr($file, -10);
    if (is_file($file) && trim($substr) !== false)
    {
?><option value="<?php echo $substr; ?>"><?php echo $substr; ?></option><?php
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
