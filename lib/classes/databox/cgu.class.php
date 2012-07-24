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
class databox_cgu
{

    public function __construct(databox $databox, $locale)
    {
        return $this;
    }

    public static function askAgreement()
    {
        $terms = self::getUnvalidated();

        $out = '';

        foreach ($terms as $name => $term) {
            if (trim($term['terms']) == '') {
                continue;
            }
            $out .= '<div style="display:none;" class="cgu-dialog" title="' . str_replace('"', '&quot;', sprintf(_('cgus:: CGUs de la base %s'), $name)) . '">';

            $out .= '<blockquote>' . $term['terms'] . '</blockquote>';
            $out .= '<div>' . _('cgus:: Pour continuer a utiliser lapplication, vous devez accepter les conditions precedentes') . '
                <input id="terms_of_use_' . $term['sbas_id'] . '" type="button" date="' . $term['date'] . '" class="cgus-accept" value="' . _('cgus :: accepter') . '"/>
                <input id="sbas_' . $term['sbas_id'] . '" type="button" class="cgus-cancel" value="' . _('cgus :: refuser') . '"/>
                </div>';
            $out .= '</div>';
        }

        return $out;
    }

    private static function getUnvalidated($home = false)
    {
        $terms = array();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        if ( ! $home) {
            $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        }

        foreach ($appbox->get_databoxes() as $databox) {
            try {
                $cgus = $databox->get_cgus();

                if ( ! isset($cgus[Session_Handler::get_locale()]))
                    throw new Exception('No CGus for this locale');
                $name = $databox->get_viewname();

                $update = $cgus[Session_Handler::get_locale()]['updated_on'];
                $value = $cgus[Session_Handler::get_locale()]['value'];
                $userValidation = true;

                if ( ! $home) {
                    if ( ! $user->ACL()->has_access_to_sbas($databox->get_sbas_id())) {
                        continue;
                    }
                    $userValidation = ($user->getPrefs('terms_of_use_' . $databox->get_sbas_id()) !== $update && trim($value) !== '');
                }

                if ($userValidation)
                    $terms[$name] = array('sbas_id' => $databox->get_sbas_id(), 'terms'   => $value, 'date'    => $update);
            } catch (Exception $e) {

            }
        }

        return $terms;
    }

    public static function getHome()
    {
        $terms = self::getUnvalidated(true);

        $out = '';

        foreach ($terms as $name => $term) {
            if (trim($term['terms']) == '')
                continue;

            if ($out != '')
                $out .= '<hr/>';

            $out .= '<div><h1 style="text-align:center;">' . str_replace('"', '&quot;', sprintf(_('cgus:: CGUs de la base %s'), $name)) . '</h1>';

            $out .= '<blockquote>' . $term['terms'] . '</blockquote>';

            $out .= '</div>';
        }

        return $out;
    }
}
