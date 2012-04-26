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
$Core = \bootstrap::getCore();
$appbox = appbox::get_instance($Core);

$user = $Core->getAuthenticatedUser();

$feeds = \Feed_Collection::load_all($appbox, $user);


$th_size = $user->getPrefs('images_size');

$core = \bootstrap::getCore();
$twig = $core->getTwig();
?>
<div style="height:50px;" class="homePubTitleBox">
    <div style="float:left;width:350px;"><h1 style="font-size:20px;margin-top:15px;">
            <h1><?php echo _('publications:: dernieres publications'); ?></h1>
    </div>
    <!--  <div style="float:right;width:160px;text-align:right;cursor:pointer;" class="subscribe_my_rss">
        <h1 style="font-size:17px;margin-top:19px;">
    <?php echo _('publications:: s\'abonner aux publications'); ?>
          <img style="border:none;" src="/skins/icons/rss16.png" />
        </h1>
      </div>-->
</div>

<?php
$feed = '';

foreach ($feeds->get_aggregate()->get_entries(0, 5)->get_entries() as $entry) {
    /* @var $entry \Feed_Entry_Adapter */

    $feed .= '<div class="boxPubli">' .
        '<div class="titlePubli">' .
        '<h2 class="htitlePubli">' .
        '<a class="homePubTitle" onclick="openCompare(\'' . $entry->get_id() . '\');">'
        . $entry->get_title() .
        '</a> </h2>' .
        '<span class="publiInfos">' .
        ' ' . \phraseadate::getPrettyString($entry->get_created_on()) .
        '  ';

    if ($entry->get_author_email())
        $feed .= '<a class="homePubLink" href="mailto:' . $entry->get_author_email() . '">';

    $feed .= $entry->get_author_name();

    if ($entry->get_author_email())
        $feed .= '</a>';

    if ($entry->get_updated_on() > $entry->get_created_on())
        $feed .= '<br/><span style="font-style:italic;">' . _('publications:: derniere mise a jour')
            . ' ' . \phraseadate::getPrettyString($entry->get_updated_on()) . '</span><br/><br/>';

    $feed .= '</span></div><div class="descPubli"><div style="margin:10px 0 10px 20px;width:80%;">';


    if (trim($entry->get_subtitle()) != '') {
        $feed .= '' . nl2br($entry->get_subtitle());
    }
    $feed .= '</div>';

    $feed .= '<div style="width:100%;position:relative;float:left;" id="PUBLICONT' . $entry->get_id() . '">';






    foreach ($entry->get_content() as $Feed_item) {
        /* @var $Feed_item \Feed_Entry_Item */
        $record = $Feed_item->get_record();

        $thumbnail = $record->get_thumbnail();

        $title = $record->get_title();
        $caption = $twig->render(
            'common/caption.html', array('view'   => 'internal_publi', 'record' => $record)
        );

        $preview = "<div tooltipsrc='/prod/tooltip/preview/" . $record->get_sbas_id() . "/" . $record->get_record_id() . "/' class=\"previewTips\"></div>&nbsp;";

        $docType = $record->get_type();
        $isVideo = ($docType == 'video');
        $isAudio = ($docType == 'audio');
        $isImage = ($docType == 'image');

        $duration = '';

        if ( ! $isVideo && ! $isAudio)
            $isImage = true;

        if ($isVideo) {
            $duration = $record->get_formated_duration();
            if ($duration != '')
                $duration = '<div class="duration">' . $duration . '</div>';
        }
        if ($isAudio) {
            $duration = $record->get_formated_duration();
            if ($duration != '')
                $duration = '<div class="duration">' . $duration . '</div>';
        }


        $ratio = $thumbnail->get_width() / $thumbnail->get_height();

        if ($ratio > 1) {
            $cw = min(((int) $th_size - 30), $thumbnail->get_width());
            $ch = $cw / $ratio;
            $pv = floor(($th_size - $ch) / 2);
            $ph = floor(($th_size - $cw) / 2);
            $imgStyle = 'width:' . $cw . 'px;xpadding:' . $pv . 'px ' . $ph . 'px;';
        } else {
            $ch = min(((int) $th_size - 30), $thumbnail->get_height());
            $cw = $ch * $ratio;

            $pv = floor(($th_size - $ch) / 2);
            $ph = floor(($th_size - $cw) / 2);

            $imgStyle = 'height:' . $ch . 'px;xpadding:' . $pv . 'px ' . $ph . 'px;';
        }

        $feed .= "<div style='width:" . ($th_size + 30) . "px;' sbas=\"" . $record->get_sbas_id() . "\"
      id='IMGT_" . $record->get_serialize_key() . "_PUB_" . $entry->get_id()
            . "' class='IMGT diapo'
        onclick=\"openPreview('FEED','" . $Feed_item->get_ord() . "','" . $entry->get_id() . "');\">";

        $feed .= '<div>';
        $feed .= "<div class=\"title\" style=\"height:40px;\">";

        $feed .= $title;

        $feed .= "</div>\n";

        $feed .= '</div>';

        $feed .= "<table class=\"thumb w160px h160px\" style=\"xheight:" . (int) $th_size . "px;\" cellspacing='0' cellpadding='0' valign='middle'>\n<tr><td>";

        $feed .= $duration . "<img title=\"" . str_replace('"', '&quot;', $caption) . "\" class=\" captionTips\" src=\"" . $thumbnail->get_url() . "\" style=\"" . $imgStyle . "\" />";

        $feed .= "</td></tr></table>";

        $feed .= '<div style="height: 25px;position:relative;">';
        $feed .= '<table class="bottom">';
        $feed .= '<tr>';
        $feed .= '<td>';

        $feed .= "</td>\n";

        $feed .= "<td style='text-align:right;' valign='bottom' nowrap>\n";

        $feed .= $preview;

        $feed .= "</td>";
        $feed .= "</tr>";
        $feed .= "</table>";
        $feed .= "</div>";


        $feed .= "</div>";
    }
    $feed .= '</div>' .
        '</div></div>';
}

echo '<div>' . $feed . '</div>';

