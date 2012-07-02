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
require($registry->get('GV_RootPath') . "lib/classes/deprecated/countries.php");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

require(__DIR__ . "/../../lib/conf.d/_GV_template.inc");

$request = http_request::getInstance();

if ($request->has_post_datas()) {
    if (setup::create_global_values($appbox->get_registry(), $request->get_post_datas())) {
        return phrasea::redirect('/admin/global_values.php');
    }
}

function getFormTimezone($props = array(), $selected = false)
{
    $form = '<select ';

    foreach ($props as $k => $v)
        $form .= $k . '="' . $v . '" ';
    $form .='>';

    $list = DateTimeZone::listAbbreviations();

    $times = array();

    foreach ($list as $k => $v) {
        foreach ($v as $v2)
            if (($timezone = trim($v2['timezone_id'])) !== '')
                $times[] = $timezone;
    }

    $times = array_unique($times);
    asort($times);

    foreach ($times as $time)
        $form .= '<option ' . ($selected == $time ? "selected" : "") . ' value="' . $time . '">' . $time . '</option>';

    $form .= '</select>';

    return $form;
}
phrasea::start($Core);
?>
        <style type="text/css">

            /*
            #GV_form div{
                position:relative;
                float:left;
                width:100%;
            }
            #GV_form ul{
                position:relative;
                float:left;
                width:100%;
                list-style-type:none;
                width:100%;
            }
            #GV_form li{
                position:relative;
                float:left;
                width:100%;
            }
            #GV_form li div.input
            {
                width:200px;
            }
            #GV_form li div.input input,
            #GV_form li div.input textarea,
            #GV_form li div.input select
            {
                width:180px;
            }
            #GV_form li div.input input.checkbox
            {
                width:auto;
            }
            #GV_form li div.label
            {
                width:350px;
            }
            */
        </style>

        <form class="form-horizontal" id="GV_form_head">
            <div class="control-group">
                <label class="control-label">Adresse : </label>
                <div class="controls">
                    <input type="text" class="input-xlarge" readonly="readonly" value="<?php echo $registry->get('GV_ServerName'); ?>"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Installation : </label>
                <div class="controls">
                    <input type="text" class="input-xlarge" readonly="readonly" value="<?php echo $registry->get('GV_RootPath'); ?>"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Maintenance : </label>
                <div class="controls">
                    <input type="checkbox" readonly="readonly" disabled="disabled" <?php echo $registry->get('GV_maintenance') == true ? "checked='checked'" : ''; ?>/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Debug : </label>
                <div class="controls">
                    <input type="checkbox" readonly="readonly" disabled="disabled" <?php echo $registry->get('GV_debug') == true ? "checked='checked'" : ''; ?>/>
                </div>
            </div>
        </form>
<?php
$rules = array();

echo '<form id="GV_form" class="form-horizontal" method="post" action = "global_values.php">';

foreach ($GV as $section) {
    echo '<div class="section" style="">';
    echo '<h1>' . $section['section'] . '</h1>';
    foreach ($section['vars'] as $value) {
        $readonly = false;
        if (isset($value['readonly']) && $value['readonly'] === true)
            $readonly = true;

        $input = '';

        $currentValue = null;
        if ($registry->is_set($value['name']))
            $currentValue = $registry->get($value['name']);
        elseif (isset($value['default']))
            $currentValue = $value['default'];

        switch ($value['type']) {

            case 'boolean':
                $input = '
                    <label class="radio inline" for="id_' . $value['name'] . '_no"><input ' . ($readonly ? 'readonly="readonly"' : '') . ' ' . ( $currentValue == '0' ? 'checked="selected"' : '' ) . ' type="radio"  name="' . $value['name'] . '" value="False" id="id_' . $value['name'] . '_no" />False</label>
                    <label class="radio inline" for="id_' . $value['name'] . '_yes"><input ' . ($readonly ? 'readonly="readonly"' : '') . ' ' . ( $currentValue == '1' ? 'checked="checked"' : '' ) . ' type="radio"  name="' . $value['name'] . '" value="True" id="id_' . $value['name'] . '_yes" />True</label>
                    ';
                break;
            case 'string':
                $input = '<input ' . ($readonly ? 'readonly="readonly"' : '') . ' name="' . $value['name'] . '" id="id_' . $value['name'] . '" type="text" value="' . str_replace('"', '&quot;', $currentValue) . '"/>';
                break;
            case 'text':
                $input = '<textarea ' . ($readonly ? 'readonly="readonly"' : '') . ' name="' . $value['name'] . '" id="id_' . $value['name'] . '">' . str_replace('"', '&quot;', $currentValue) . '</textarea>';
                break;
            case 'enum':
                $input = '<select ' . ($readonly ? 'readonly="readonly"' : '') . ' name="' . $value['name'] . '" id="id_' . $value['name'] . '">';
                if (isset($value['available']) && is_array($value['available'])) {
                    foreach ($value['available'] as $k => $v)
                        $input .= '<option value="' . $k . '" ' . ( $currentValue === $k ? 'selected="selected"' : '' ) . '>' . $v . '</option>';
                } else {
                    echo '<p style="color:red;">erreur avec la valeur ' . $value['name'] . '</p>';
                }
                $input .= '</select>';
                break;
            case 'enum_multi':
                if (isset($value['available']) && is_array($value['available'])) {
                    foreach ($value['available'] as $k => $v)
                        $input .= '<label class="checkbox"><input type="checkbox" name="' . $value['name'] . '[]" ' . ($readonly ? 'readonly="readonly"' : '') . ' value="' . $k . '" ' . ( ( ! is_array($currentValue) || in_array($k, $currentValue)) ? 'checked="checked"' : '' ) . '/>' . $v . '</label>';
                } else {
                    echo '<p class="error">erreur avec la valeur ' . $value['name'] . '</p>';
                }
                break;
            case 'list':

                break;
            case 'integer':
                $input .= '<input ' . ($readonly ? 'readonly="readonly"' : '') . ' name="' . $value['name'] . '" id="id_' . $value['name'] . '" type="text" value="' . $currentValue . '"/>';
                break;
            case 'password':
                $input .= '<input ' . ($readonly ? 'readonly="readonly"' : '') . ' name="' . $value['name'] . '" id="id_' . $value['name'] . '" type="password" value="' . str_replace('"', '\"', stripslashes($currentValue)) . '"/>';
                break;
            case 'timezone':
                if (trim($currentValue) === '') {
                    $datetime = new DateTime();
                    $currentValue = $datetime->getTimezone()->getName();
                }
                $input .= getFormTimezone(array('name' => $value['name'], 'id'   => 'id_' . $value['name']), $currentValue);
                break;
            default:
                break;
        }

        $isnew = $registry->is_set($value['name']);
        echo '  <div class="control-group">
                    <div class="controls">' . $input . '</div>
                    <label class="control-label" for="id_' . $value['name'] . '"><span class="NEW">' . ($isnew === false ? 'NEW' : '') . '</span>' . $value['comment'] . '</label>
                </div>';
        if (isset($value['required'])) {
            $rules[$value['name']] = array('required'                => $value['required']);
            $messages[$value['name']] = array('required' => 'Ce champ est requis !');
        }
    }

    if (isset($section['javascript'])) {
        echo "<div><input type='button' onclick='" . $section['javascript'] . "(this);' value='Tester'/></div>";
    }

    echo '</div>';
}


$JS = '$(document).ready(function() {
    // validate signup form on keyup and submit
    $("#GV_form").validate({
        rules: ' . p4string::jsonencode($rules) . ',
        messages: ' . p4string::jsonencode($messages) . ',
        errorPlacement: function(error, element) {
        error.prependTo( element.parent().next() );
        }
    });
    });
';
?>
        <input type="submit" class="btn input-medium" style="margin-bottom: 10px;" value="<?php echo _('boutton::valider') ?>"/>
    </form>
    <script type='text/javascript'>
        <?php echo $JS ?>
    </script>

