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
require_once __DIR__ . "/../../lib/bootstrap.php";

$registry = registry::get_instance();
$request = http_request::getInstance();
$parms = $request->get_parms('charset_tables', 'libstemmer');
if (is_array($parms['charset_tables'])) {
    $registry->set('sphinx_charset_tables', $parms['charset_tables'], registry::TYPE_ARRAY);
}
if (is_array($parms['libstemmer'])) {
    $registry->set('sphinx_user_stemmer', $parms['libstemmer'], registry::TYPE_ARRAY);
}

$sphinx_conf = new sphinx_configuration();

$selected_charsets = $registry->get('sphinx_charset_tables');
$selected_libstemmer = $registry->get('sphinx_user_stemmer');

$options = array(
    'charset_tables' => ( ! is_array($selected_charsets) ? array() : $selected_charsets)
    , 'libstemmer' => ( ! is_array($selected_libstemmer) ? array() : $selected_libstemmer)
);
?>
<form>
    <select name="charset_tables[]" multiple="multiple">
<?php
foreach ($sphinx_conf->get_available_charsets() as $charset => $charset_obj) {
    echo "<option value='" . $charset . "' " . (in_array($charset, $selected_charsets) ? "selected='selected'" : "") . ">" . $charset_obj->get_name() . "</option>";
}
?>
    </select>
    <select name="libstemmer[]" multiple="multiple">
        <?php
        foreach ($sphinx_conf->get_available_libstemmer() as $stemme) {
            echo "<option value='" . $stemme . "' " . (in_array($stemme, $selected_libstemmer) ? "selected='selected'" : "") . ">" . $stemme . "</option>";
        }
        ?>
    </select>



    <button type="submit">valide</button>

</form>

<textarea style="width:100%;height:70%">
<?php
echo $sphinx_conf->get_configuration($options);
?>
</textarea>
