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
$usr_id = $session->get_usr_id();
$registry = $appbox->get_registry();
$request = http_request::getInstance();
$user_obj = User_Adapter::getInstance($usr_id, $appbox);

$createBase = $mountBase = false;
$error = array();

$Core = bootstrap::getCore();

?>
        <style type="text/css">
            blockquote{
                margin:0 15px;
                padding:5px 10px;
                border:3px dotted black;
                background-color:#DEDEDE;
            }
        </style>
        <?php
        if ($request->has_post_datas() && $user_obj->is_admin() === true) {
            $parm = $request->get_parms('upgrade');
            if ( ! is_null($parm['upgrade'])) {
                $checks = phrasea::is_scheduler_started();
                if ($checks !== true) {
                    $appbox = appbox::get_instance(\bootstrap::getCore());
                    try {
                        $upgrader = new Setup_Upgrade($appbox);
                        $advices = $appbox->forceUpgrade($upgrader);

                        $code = '';
                        foreach ($advices as $advice) {
                            $code .= $advice['sql'] . '<br/>';
                        }

                        $recommendations = $upgrader->getRecommendations();

                        if($code) {
                            $code = _('Propositions de modifications des tables')
                                . '<blockquote>' . $code . '</blockquote>';
                        ?>
                        <pre>
                            <?php echo $code; ?>
                        </pre>
                        <?php
                        }
                        if ($recommendations) {
                            foreach($recommendations as $recommendation) {
                                list($message, $command) = $recommendation;
                                ?>
                                <p><?php echo $message; ?></p>
                                <pre>
                                    <blockquote><?php echo $command; ?></blockquote>
                                </pre>
                                <?php
                            }
                        }

                        ?>
                        <div style="color:black;font-weight:bold;background-color:green;">
                            <?php echo _('N\'oubliez pas de redemarrer le planificateur de taches'); ?>
                        </div>
                        <?php
                    } catch (\Exception_Setup_UpgradeAlreadyStarted $e) {
                        ?>
                        <div style="margin-top:10px;color:black;font-weight:bold;background-color:yellow;">
                            <?php echo _('The upgrade is already started'); ?>
                        </div>
                        <?php
                    }catch(\Exception_Setup_FixBadEmailAddresses $e){
                        ?>
                        <div style="margin-top:10px;color:black;font-weight:bold;background-color:yellow;">
                            <?php echo _('Please fix the database before starting'); ?>
                        </div>
                        <?php
                    }catch(\Exception $e){
                        ?>
                        <div style="margin-top:10px;color:black;font-weight:bold;background-color:yellow;">
                            <?php echo _('An error occured'); ?>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div style="color:black;font-weight:bold;background-color:red;"><?php echo _('Veuillez arreter le planificateur avant la mise a jour'); ?></div>
                    <?php
                }
            }
            $parm = $request->get_parms('mount_base', 'new_settings', 'new_dbname', 'new_data_template', 'new_hostname', 'new_port', 'new_user', 'new_user', 'new_password', 'new_dbname', 'new_data_template');
            if ( ! $parm['mount_base']) {
                if ( ! $parm['new_settings'] && $parm['new_dbname'] && $parm['new_data_template']) {
                    if (p4string::hasAccent($parm['new_dbname'])) {
                        $error[] = _('Database name can not contains special characters');
                    }

                    if (count($error) === 0) {
                        try {
                            $configuration = \Alchemy\Phrasea\Core\Configuration::build();

                            $choosenConnexion = $configuration->getPhraseanet()->get('database');

                            $connexion = $configuration->getConnexion($choosenConnexion);

                            $hostname = $connexion->get('host');
                            $port = $connexion->get('port');
                            $user = $connexion->get('user');
                            $password = $connexion->get('password');

                            $data_template = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $parm['new_data_template'] . '.xml');

                            $connbas = new connection_pdo('databox_creation', $hostname, $port, $user, $password, $parm['new_dbname'], array(), $appbox->get_registry());

                            try {
                                $base = databox::create($appbox, $connbas, $data_template, $registry);
                                $base->registerAdmin($user_obj);
                                $createBase = $sbas_id = $base->get_sbas_id();
                            } catch (Exception $e) {
                                $error[] = $e->getMessage();
                            }
                        } catch (Exception $e) {
                            $error[] = _('Database does not exists or can not be accessed');
                        }
                    }
                } elseif ($parm['new_settings'] && $parm['new_hostname'] && $parm['new_port']
                    && $parm['new_user'] && $parm['new_password']
                    && $parm['new_dbname'] && $parm['new_data_template']) {

                    if (p4string::hasAccent($parm['new_dbname'])) {
                        $error[] = _('Database name can not contains special characters');
                    }

                    if (count($error) === 0) {

                        try {
                            $data_template = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $parm['new_data_template'] . '.xml');
                            $connbas = new connection_pdo('databox_creation', $parm['new_hostname'], $parm['new_port'], $parm['new_user'], $parm['new_password'], $parm['new_dbname'], array(), $appbox->get_registry());
                            $base = databox::create($appbox, $connbas, $data_template, $registry);
                            $base->registerAdmin($user_obj);
                            $createBase = $sbas_id = $base->get_sbas_id();
                        } catch (Exception $e) {
                            $error[] = $e->getMessage();
                        }
                    }
                }
            } elseif ($parm['mount_base']) {
                if ( ! $parm['new_settings'] && $parm['new_dbname']) {

                    if (p4string::hasAccent($parm['new_dbname']))
                        $error[] = _('Database name can not contains special characters');

                    if (count($error) === 0) {
                        try {
                            $configuration = \Alchemy\Phrasea\Core\Configuration::build();

                            $connexion = $configuration->getConnexion();

                            $hostname = $connexion->get('host');
                            $port = $connexion->get('port');
                            $user = $connexion->get('user');
                            $password = $connexion->get('password');

                            $appbox->get_connection()->beginTransaction();
                            $base = databox::mount($appbox, $hostname, $port, $user, $password, $parm['new_dbname'], $registry);
                            $base->registerAdmin($user_obj);
                            $mountBase = true;
                            $appbox->get_connection()->commit();
                        } catch (Exception $e) {
                            $appbox->get_connection()->rollBack();
                            $error[] = $e->getMessage();
                        }
                    }
                } elseif ($parm['new_settings'] && $parm['new_hostname'] && $parm['new_port'] && $parm['new_user']
                    && $parm['new_password'] && $parm['new_dbname']) {

                    if (p4string::hasAccent($parm['new_dbname']))
                        $error[] = 'No special chars in dbname';

                    if (count($error) === 0) {
                        try {
                            $appbox->get_connection()->beginTransaction();
                            $base = databox::mount($appbox, $parm['new_hostname'], $parm['new_port'], $parm['new_user'], $parm['new_password'], $parm['new_dbname'], $registry);
                            $base->registerAdmin($user_obj);
                            $appbox->get_connection()->commit();
                        } catch (Exception $e) {
                            $appbox->get_connection()->rollBack();
                            $error[] = $e->getMessage() . '@' . $e->getFile() . $e->getLine();
                        }
                    }
                }
            }
        }


        $upgrade_available = false;

        if ($appbox->upgradeavailable())
            $upgrade_available = true;

        $sbas_ids = array_merge(
            array_keys($user_obj->ACL()->get_granted_sbas(array('bas_manage')))
            , array_keys($user_obj->ACL()->get_granted_sbas(array('bas_modify_struct')))
        );

        $hasRightsMountDB = count($sbas_ids) > 0;

        $sbas = array();
        foreach ($sbas_ids as $sbas_id) {
            $version = 'unknown';
            $sbas[$sbas_id] = '<img src="/skins/icons/db-remove.png"/> ' . ' (Unreachable server)';
            try {
                $databox = databox::get_instance($sbas_id);
                $version = $databox->get_version();
                if ($databox->upgradeavailable())
                    $upgrade_available = true;
                $sbas[$sbas_id] = '<img src="/skins/icons/foldph20close_0.gif">' . phrasea::sbas_names($sbas_id)
                    . ' (version ' . $version . ') MySQL ' . $databox->get_connection()->server_info();
            } catch (Exception $e) {

            }
        }
        ?>
        <script type="text/javascript">
<?php
if ($createBase || $mountBase) {
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    $user->ACL()->delete_data_from_cache();
    ?>
            parent.reloadTree('bases:bases');
    <?php
    if ($createBase) {
        ?>
                    document.location.replace('/admin/newcoll.php?act=GETNAME&p0=<?php echo $createBase; ?>');
        <?php
    } else {
        phrasea::redirect('/admin/databases.php');
    }
}
?>

        </script>
        <?php
        foreach ($error as $e) {
            ?>
            <span style="background-color:red;color:white;padding:3px"><?php echo $e; ?></span>
            <?php
        }
        ?>
        <div style="position:relative;float:left;width:100%;">
            <h2>Bases actuelles :</h2>
            <ul>
                <?php
                if (count($sbas) > 0) {
                    foreach ($sbas as $k => $v) {
                        ?>
                        <li>
                            <a href='database.php?p0=<?php echo $k ?>' target='_self'>
                                <span><?php echo $v ?></span>
                            </a>
                        </li>
                        <?php
                    }
                } else {
                    ?>
                    <li>None</li>
                    <?php
                }
                ?>

            </ul>
        </div>
        <?php
        if ($user_obj->is_admin() === true) {
            ?>

            <div style="position:relative;float:left;width:100%;">
                <h2><?php echo _('admin::base: Version') ?></h2>
                <?php
                if ($upgrade_available) {
                    ?>
                    <div><?php echo _('update::Votre application necessite une mise a jour vers : '), ' ', $Core->getVersion()->getNumber() ?></div>
                    <?php
                } else {
                    ?>
                    <div><?php echo _('update::Votre version est a jour : '), ' ', $Core->getVersion()->getNumber() ?></div>
                    <?php
                }
                ?>
                <form action="databases.php" method="post" >
                    <input type="hidden" value="" name="upgrade" />
                    <input type="submit" value="<?php echo _('update::Verifier els tables') ?>"/>
                </form>
            </div>

            <div style="position:relative;float:left;width:100%;">
                <h2><?php echo _('admin::base: creer une base') ?></h2>
                <div id="create_base">
                    <form method="post" action="databases.php">
                        <div>
                            <input type="checkbox" name="new_settings" onchange="if(this.checked == true)$('#server_opts').slideDown();else $('#server_opts').slideUp();"/><label><?php echo _('phraseanet:: Creer une base sur un serveur different de l\'application box'); ?></label>
                        </div>
                        <div id="server_opts" style="display:none;">
                            <div>
                                <label><?php echo _('phraseanet:: hostname'); ?></label><input name="new_hostname" value="" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: port'); ?></label><input name="new_port" value="3306" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: user'); ?></label><input name="new_user" value="" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: password'); ?></label><input name="new_password" value="" type="password"/>
                            </div>
                        </div>
                        <div>
                            <label><?php echo _('phraseanet:: dbname'); ?></label><input name="new_dbname" value="" type="text"/>
                        </div>
                        <div>
                            <label><?php echo _('phraseanet:: Modele de donnees'); ?></label>
                            <select name="new_data_template">
                                <?php
                                if ($handle = opendir($registry->get('GV_RootPath') . 'lib/conf.d/data_templates')) {
                                    while (false !== ($file = readdir($handle))) {
                                        if (is_file($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $file)) {
                                            $file = substr($file, 0, (strlen($file) - 4));
                                            ?>
                                            <option value="<?php echo $file; ?>"><?php echo $file; ?></option>
                                            <?php
                                        }
                                    }

                                    closedir($handle);
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <input value="<?php echo _('boutton::creer'); ?>" type="submit"/>
                        </div>
                    </form>
                </div>
            </div>
            <div style="position:relative;float:left;width:100%;">
                <h2><?php echo _('admin::base: Monter une base') ?></h2>
                <div id="mount_base">
                    <form method="post" action="databases.php">
                        <div>
                            <input type="checkbox" name="new_settings" onchange="if(this.checked == true)$('#servermount_opts').slideDown();else $('#servermount_opts').slideUp();"/><label><?php echo _('phraseanet:: Monter une base provenant d\'un serveur different de l\'application box'); ?></label>
                        </div>
                        <div id="servermount_opts" style="display:none;">
                            <div>
                                <label><?php echo _('phraseanet:: hostname'); ?></label><input name="new_hostname" value="" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: port'); ?></label><input name="new_port" value="3306" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: user'); ?></label><input name="new_user" value="" type="text"/>
                            </div>
                            <div>
                                <label><?php echo _('phraseanet:: password'); ?></label><input name="new_password" value="" type="password"/>
                            </div>
                        </div>
                        <div>
                            <label><?php echo _('phraseanet:: dbname'); ?></label><input name="new_dbname" value="" type="text"/>
                        </div>
                        <div>
                            <input type="hidden" name="mount_base" value="yes"/>
                            <input value="<?php echo _('boutton::monter') ?>" type="submit"/>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
