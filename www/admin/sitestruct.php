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
$registry = $appbox->get_registry();

$usr_id = $session->get_usr_id();

phrasea::headers();


$request = http_request::getInstance();
$parm = $request->get_parms("act", "p0", "p1", 'flush_cache', 'sudo', 'admins', 'email');

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if ( ! $user->is_admin()) {
    phrasea::headers(403);
}


$cache_flushed = false;
if ($parm['flush_cache']) {
    $Core = \bootstrap::getCore();
    $Core['CacheService']->flushAll();
    $cache_flushed = true;
}
?>
        <style type="text/css">

            h1{
                position:relative;
                float:left;
                width:100%;
            }
            ul.setup {
                position:relative;
                float:left;
                width:360px;
                list-style-type:none;
                padding:0 0 0 0px;
                margin:5px 0 5px 40px;
                border:1px solid #404040;
            }
            ul.setup table{
                width:100%;
                table-layout: fixed;
            }
            .setup li{
                margin:0px 0;
                padding:2px 5px 2px 30px;
                background-image:url(/skins/icons/ok.png);
                background-repeat:no-repeat;
                background-position:5px center;
            }
            .setup li.non-blocker{
                background-image:url(/skins/icons/alert.png);
            }
            .setup li.blocker{
                background-image:url(/skins/icons/delete.png);
            }
            tr.even{
                background-color:#CCCCCC;
            }
            #flush_button {
                width:360px;
                margin: 5px 0 5px 40px;
            }
        </style>

        <style>
            .ui-autocomplete {
                max-height: 200px;
                overflow-y: auto;
                /* prevent horizontal scrollbar */
                overflow-x: hidden;
                /* add padding to account for vertical scrollbar */
                padding-right: 20px;
            }
            /* IE 6 doesn't support max-height
             * we use height instead, but this forces the menu to always be this tall
            */
            * html .ui-autocomplete {
                height: 200px;
            }
            .ui-autocomplete-loading { background: white url('/skins/icons/ui-anim_basic_16x16.gif') right center no-repeat; }
        </style>
        <script type="text/javascript">

            $(document).ready(function(){

                var container = $('#right-ajax');
                $( ".admin_adder", container ).autocomplete({
                    source: "/admin/users/typeahead/search/",
                    minLength: 2,
                    select: function( event, ui ) {
                        var form = $('#admin_adder');
                        $('input.new[name="admins[]"]', form).val(ui.item.id);
                        form.submit();
                    }
                }).data( "autocomplete" )._renderItem = function( ul, item ) {
                    var email = item.email ? '<br/>'+item.email : '';
                    var login = item.login != item.name ? " ("+ item.login +")" : '';

                    return $( "<li></li>" )
                    .data( "item.autocomplete", item )
                    .append( "<a>" + item.name + login + email + "</a>" )
                    .appendTo( ul );
                };

            });
        </script>
<?php
if ($parm['sudo']) {
    if ($parm['sudo'] == '1') {
        User_Adapter::reset_sys_admins_rights();
    }
}

if ($parm['admins']) {
    $admins = array();

    foreach ($parm['admins'] as $a) {
        if (trim($a) == '')
            continue;

        $admins[] = $a;
    }

    if ( ! in_array($session->get_usr_id(), $admins))
        $admins[] = $session->get_usr_id();

    if ($admins > 0) {
        User_Adapter::set_sys_admins($admins);
        User_Adapter::reset_sys_admins_rights();
    }
}

if ($cache_flushed) {
    ?>
            <div>
            <?php echo _('all caches services have been flushed'); ?>
            </div>
            <?php
        }
        ?>
        <div>
            <h1><?php echo _('setup:: administrateurs de l\'application') ?></h1>
            <form id="admin_adder" action="sitestruct.php" method="post">
            <?php
            $admins = User_Adapter::get_sys_admins();

            foreach ($admins as $usr_id => $usr_login) {
                ?>
                    <div><input name="admins[]" type="checkbox" value="<?php echo $usr_id ?>" id="adm_<?php echo $usr_id ?>" checked /><label for="adm_<?php echo $usr_id ?>"><?php echo $usr_login; ?></label></div>
    <?php
}
?>
                <div><?php echo _('setup:: ajouter un administrateur de l\'application') ?></div>

                <input class="admin_adder"/>
                <input type="hidden" class="new" name="admins[]"/>
                <input type="submit" value="<?php echo _('boutton::valider') ?>" />
            </form>
            <h1><?php echo _('setup:: Reinitialisation des droits admins') ?></h1>

            <form action="sitestruct.php" method="post" id="admin_reset">
                <input type="hidden" name="sudo" value="1" />
                <input type="submit" value="<?php echo _('boutton::reinitialiser') ?>" />
            </form>
        </div>
        <h1><?php echo _('setup:: Reglages generaux') ?></h1>
        <br>
        <h2><?php echo _('setup::Votre configuration') ?></h2>
        <div>
            <div style="position:relative;float:left;width:400px;">

            <h1><?php echo _('setup::Tests d\'envois d\'emails'); ?></h1>
            <form id="mail_checker" method="post" action="/admin/sitestruct.php" target="_self">
                <label>Email : </label><input name="email" type="text" />
                <input type="submit" value="<?php echo _('boutton::valider'); ?>"/>
            </form>
<?php

if ($parm['email']) {
    echo 'result : ';
    var_dump(mail::mail_test($parm['email']));
}
$php_constraints = setup::check_php_version();


foreach ($php_constraints as $php_constraint) {
    echo '<h1>' . $php_constraint->get_name() . '</h1>';
    echo '<ul class="setup">';
    ?>
                    <li class="<?php echo $php_constraint->is_ok() ? 'good-enough' : 'blocker'; ?>">
                    <?php echo $php_constraint->get_message(); ?>
                    </li>
                    <?php
                    echo '</ul>';
                }

                $php_constraints = setup::check_writability($registry);

                echo '<h1>' . _('setup::Filesystem configuration') . '</h1>';
                echo '<ul class="setup">';
                foreach ($php_constraints as $constraint) {
                    ?>
                    <li class="<?php echo ! $constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough'; ?>">
                    <?php echo $constraint->get_message(); ?>
                    </li>
                    <?php
                }
                echo '</ul>';


                $php_constraints = setup::check_binaries($registry);
                echo '<h1>' . _('setup::Executables') . '</h1>';
                echo '<ul class="setup">';
                foreach ($php_constraints as $constraint) {
                    ?>
                    <li class="<?php echo ! $constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough'; ?>">
                        <?php echo $constraint->get_message(); ?>
                    </li>
                    <?php
                }
                echo '</ul>';


                $php_constraints = setup::check_php_extension();
                echo '<h1>' . _('setup::PHP extensions') . '</h1>';
                echo '<ul class="setup">';
                foreach ($php_constraints as $constraint) {
                    ?>
                    <li class="<?php echo ! $constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough'; ?>">
                        <?php echo $constraint->get_message(); ?>
                    </li>
                    <?php
                }
                echo '</ul>';

                $php_constraints = setup::check_cache_server();
                echo '<h1>' . _('setup::Serveur de cache') . '</h1>';
                echo '<ul class="setup">';
                foreach ($php_constraints as $constraint) {
                    ?>
                    <li class="<?php echo ! $constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough'; ?>">
                    <?php echo $constraint->get_message(); ?>
                    </li>
                        <?php
                    }
                    echo '</ul>';
                    ?>
            </div>
            <div style="position:relative;float:left;width:400px;margin-left:25px;">
                <?php
                $php_constraints = setup::check_phrasea();
                echo '<h1>' . _('Phrasea Module') . '</h1>';
                echo '<ul class="setup">';
                foreach ($php_constraints as $constraint) {
                    ?>
                    <li class="<?php echo ! $constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough'; ?>">
                        <?php echo $constraint->get_message(); ?>
                    </li>
                    <?php
                }
                echo '</ul>';

                setup::check_apache();
                setup::check_mod_auth_token();
                setup::check_cache_opcode();
                setup::check_cache_memcache();

                if ($Core->getCache()->isServer()) {
                    ?>
                    <form id="cache_flusher" method="post" action="sitestruct.php">
                        <input type="hidden" name="flush_cache" value="1"/>
                        <input id="flush_button" type="submit" value="Flush All Caches" />
                    </form>
                    <?php
                }

                echo '<h1>' . _('OPCode cache') . '</h1>';
                echo '<ul class="setup">';
                if ($Core['OpcodeCache']->getName() == 'array') {
                    echo '<li class="non-blocker">' . _('Array opcode cache is activated, but phrasea strongly recommand the use of APC or Xcache in production') . '</li>';
                } else {
                    echo '<li>' . $Core['OpcodeCache']->getName() . '</li>';
                }
                echo '</ul>';

                setup::check_sphinx_search();
                setup::check_php_configuration();
                ?>
            </div>
        </div>
        