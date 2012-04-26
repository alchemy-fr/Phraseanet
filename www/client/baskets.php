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

$em = $Core->getEntityManager();

$Request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$nbNoview = 0;

$user = $Core->getAuthenticatedUser();
$ACL = $user->ACL();

$out = null;

if ($Request->get("act") == "DELIMG" && $Request->get("p0") != "") {
    $repository = $em->getRepository('\Entities\BasketElement');
    /* @var $repository \Repositories\BasketElementRepository */
    $basket_element = $repository->findUserElement($Request->get('p0'), $user);
    $em->remove($basket_element);
    $em->flush();
}

if ($Request->get('act') == "ADDIMG" && ($Request->get("p0") != "" && $Request->get("p0") != null)) {
    $repository = $em->getRepository('\Entities\Basket');
    /* @var $repository \Repositories\BasketRepository */
    $basket = $repository->findUserBasket($Request->get('courChuId'), $user, true);

    $sbas_id = $Request->get('sbas');
    $record = new record_adapter($sbas_id, $Request->get('p0'));

    $BasketElement = new \Entities\BasketElement();
    $BasketElement->setRecord($record);
    $BasketElement->setBasket($basket);
    $basket->addBasketElement($BasketElement);

    $em->persist($BasketElement);
    $em->merge($basket);

    $em->flush();
}

if ($Request->get('act') == "DELCHU" && ($Request->get("p0") != "" && $Request->get("p0") != null)) {
    $repository = $em->getRepository('\Entities\Basket');
    /* @var $repository \Repositories\BasketRepository */
    $basket = $repository->findUserBasket($Request->get('courChuId'), $user, true);

    $em->remove($basket);
    $em->flush();
    unset($basket);
}


$courChuId = $Request->get('courChuId');

if ($Request->get('act') == "NEWCHU" && ($Request->get("p0") != "" && $Request->get("p0") != null)) {
    $basket = new \Entities\Basket();
    $basket->setName($Request->get('p0'));
    $basket->setOwner($user);

    $em->persist($basket);
    $em->flush();

    $courChuId = $basket->getId();
}

$repository = $em->getRepository('\Entities\Basket');
/* @var $repository \Repositories\BasketRepository */
$baskets = $repository->findActiveByUser($user);

$out = "<table style='width:99%' class='baskIndicator' id='baskMainTable'><tr><td>";
$out .= '<select id="chutier_name" name="chutier_name" onChange="chg_chu();" style="width:120px;">';

$baskets_opt = $recepts_opt = '';

foreach ($baskets as $typeBask => $basket) {
    if ( ! $basket->getPusherId()) {
        $baskId = $basket->getId();
        $sltd = '';
        if (trim($courChuId) == '')
            $courChuId = $baskId;
        if ($courChuId == $baskId)
            $sltd = 'selected';
        $baskets_opt .= '<option class="chut_choice" ' . $sltd . ' value="' . $baskId . '">'
            . $basket->getName() . '</option>';
    }

    if ($basket->getPusherId()) {
        $baskId = $basket->getId();
        $sltd = '';
        if (trim($courChuId) == '')
            $courChuId = $baskId;
        if ($courChuId == $baskId)
            $sltd = 'selected';
        $recepts_opt .= '<option class="chut_choice" ' . $sltd . ' value="' . $baskId . '">'
            . $basket->getName() . '</option>';
    }
}

if ($baskets_opt) {
    $out .= '<optgroup label="' . _('paniers::categories: mes paniers') . '">'
        . $baskets_opt
        . '</optgroup>';
}
if ($recepts_opt) {
    $out .= '<optgroup label="' . _('paniers::categories: paniers recus') . '">'
        . $recepts_opt
        . '</optgroup>';
}


$out.='</optgroup>';
$out .= "</select>";
$out .= '</td><td style="width:40%">';



$em = $Core->getEntityManager();
$repository = $em->getRepository('\Entities\Basket');
/* @var $repository \Repositories\BasketRepository */
$basket = $repository->findUserBasket($courChuId, $user, true);

$jscriptnochu = $basket->getName() . " :  " . sprintf(_('paniers:: %d documents dans le panier'), $basket->getElements()->count());

$nbElems = $basket->getElements()->count();
?><div id="blocBask" class="bodyLeft" style="height:314px;bottom:0px;"><?php ?><div class="baskTitle"><?php ?><div id="flechenochu" class="flechenochu"></div><?php
$totSizeMega = $basket->getSize();
echo '<div class="baskName">' . sprintf(_('paniers:: paniers:: %d documents dans le panier'), $nbElems) .
 ($Core->getRegistry()->get('GV_viewSizeBaket') ? ' (' . $totSizeMega . ' Mo)' : '') . '</div>';
?></div><?php ?><div><?php
echo $out;
?><div class="baskDel" title="<?php echo _('action : supprimer') ?>" onclick="evt_chutier('DELSSEL');"/></div><?php ?><div class="baskCreate" title="<?php echo _('action:: nouveau panier') ?>" onclick="newBasket();"></div><?php ?><div style="float:right;position:relative;width:3px;height:16px;"></div><?php
if ($nbElems > 0 && ($ACL->has_right("candwnldhd") || $ACL->has_right("candwnldpreview") || $ACL->has_right("cancmd") > 0 )) {
    ?><div class="baskDownload" title="<?php echo _('action : exporter') ?>" onclick="evt_dwnl();"></div><?php
}
if ($nbElems > 0) {
    ?><div class="baskPrint" title="<?php echo _('action : print') ?>" onclick="evt_print();"></div><?php
}
$jsclick = '';
if (trim($courChuId) != '') {
    $jsclick = ' onclick=openCompare(\'' . $courChuId . '\') ';
}
?><div class="baskComparator" <?php echo $jsclick ?> title="<?php echo _('action : ouvrir dans le comparateur') ?>"></div><?php
?></td><?php
?></tr><?php
?></table><?php
?></div><?php
?><div class="divexterne" style="height:270px;overflow-x:hidden;overflow-y:auto;position:relative"><?php
    if ($basket->getPusher() instanceof user) {
    ?><div class="txtPushClient"><?php
    echo sprintf(_('paniers:: panier emis par %s'), $basket->getPusher()->get_display_name())
    ?></div><?php
}

foreach ($basket->getElements() as $basket_element) {
    $dim = $dim1 = $top = 0;

    $thumbnail = $basket_element->getRecord()->get_thumbnail();

    if ($thumbnail->get_width() > $thumbnail->get_height()) { // cas d'un format paysage
        if ($thumbnail->get_width() > 67) {
            $dim1 = 67;
            $top = ceil((67 - 67 * $thumbnail->get_height() / $thumbnail->get_width()) / 2);
        } else { // miniature
            $dim1 = $thumbnail->get_width();
            $top = ceil((67 - $thumbnail->get_height()) / 2);
        }
        $dim = "width:" . $dim1 . "px";
    } else { // cas d'un format portrait
        if ($thumbnail->get_height() > 55) {
            $dim1 = 55;
            $top = ceil((67 - 55) / 2);
        } else { // miniature
            $dim1 = $thumbnail->get_height();
            $top = ceil((67 - $thumbnail->get_height()) / 2);
        }
        $dim = "height:" . $dim1 . "px";
    }

    if ($thumbnail->get_height() > 42)
        $classSize = "hThumbnail";
    else
        $classSize = "vThumbnail";

    $tooltip = "";

    $record = $basket_element->getRecord();
    if ($Core->getRegistry()->get('GV_rollover_chu')) {
        $tooltip = 'tooltipsrc="/prod/tooltip/caption/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/basket/"';
    }
    ?><div class="diapochu"><?php
    ?><div class="image"><?php
    ?><img onclick="openPreview('BASK',<?php echo $basket_element->getRecord()->get_number() ?>,<?php echo $courChuId ?>); return(false);"
        <?php echo $tooltip ?> style="position:relative; top:<?php echo $top ?>px; <?php echo $dim ?>"
                     class="<?php echo $classSize ?> baskTips" src="<?php echo $thumbnail->get_url() ?>"><?php
        ?></div><?php ?><div class="tools"><?php ?><div class="baskOneDel" onclick="evt_del_in_chutier('<?php echo $basket_element->getId() ?>');"
                                                          title="<?php echo _('action : supprimer') ?>"></div><?php
    if ($user->ACL()->has_right_on_base($record->get_base_id(), 'candwnldhd') ||
        $user->ACL()->has_right_on_base($record->get_base_id(), 'candwnldpreview') ||
        $user->ACL()->has_right_on_base($record->get_base_id(), 'cancmd') ||
        $user->ACL()->has_preview_grant($record)) {
            ?><div class="baskOneDownload" onclick="evt_dwnl('<?php echo $record->get_sbas_id() ?>_<?php echo $record->get_record_id() ?>');" title="<?php echo _('action : exporter') ?>"></div><?php
    }
        ?></div><?php
        ?></div><?php
}
    ?></div></div><div id="blocNoBask" class="bodyLeft" style="height: 22px;display:none;bottom:0px;"><?php
    ?><div class="baskTitle"><?php
    ?><div id="flechechu" class="flechenochu"></div><?php
    ?><div id="viewtext" class="baskName"><?php echo $jscriptnochu ?><span style="width:16px;height:16px;position: absolute; right: 10px;background-position:center center;" class='baskIndicator'></span></div><?php ?></div><?php ?></div>
    <?php
    ?>
<script>
    var oldNoview = p4.nbNoview;
    p4.nbNoview = parseInt(<?php echo $nbNoview ?>);
    if(p4.nbNoview>oldNoview)
        alert('<?php echo _('paniers:: vous avez de nouveaux paniers non consultes'); ?>');
</script>
