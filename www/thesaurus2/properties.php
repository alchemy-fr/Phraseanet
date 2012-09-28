<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$app = new Application();
$appbox = $app['phraseanet.appbox'];
$registry = $app['phraseanet.registry'];
require($registry->get('GV_RootPath') . "www/thesaurus2/xmlhttp.php");

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "id"
    , "typ"
    , "dlg"
);


$lng = $app['locale'];

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <title><?php echo p4string::MakeString(_('thesaurus:: Proprietes')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
        <style type="text/css">
            a
            {
                text-decoration:none;
                font-size: 10px;
            }
            .path_separator
            {
                color:#ffff00;
            }
            .main_term
            {
                font-weight:900;
                xcolor:#ff0000;
            }
        </style>

        <script type="text/javascript" src="./win.js"></script>
        <script type="text/javascript" src="./xmlhttp.js"></script>
        <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery.sprintf.1.0.3.js"></script>
        <script type="text/javascript" src="./xmlhttp.js"></script>

        <script type="text/javascript">
            function loaded()
            {
                window.name="PROPERTIES";
                self.focus();
            }
        </script>
    </head>
    <body id="desktop" onload="loaded();" class="dialog">
        <div class="menu" id="flagsMenu" style="z-index:50">
<?php
// on liste tous les drapeaux
$tlng = User_Adapter::avLanguages();
foreach ($tlng as $lng_code => $lng) {
    print("<a id='flagMenu_$lng_code' href='javascript:void(0)' class=''><img src='/skins/lng/" . $lng_code . "_flag_18.gif' />$lng_code</a>");
}
?>
        </div>
        <div class="menu" id="syMenu" style="z-index:50">
            <a href="javascript:void(0)" id="delete_sy"><?php echo p4string::MakeString(_('thesaurus::menu: supprimer')) /* Supprimer... */ ?></a>
            <a href="javascript:void(0)" id="replace_sy" class="disabled"><?php echo p4string::MakeString(_('thesaurus:: remplacer')) /* Corriger... */ ?></a>
        </div>
<?php
$nsy = 0;

if ($parm["bid"] !== null) {
    $url = "thesaurus2/xmlhttp/getterm.x.php";
    $url .= "?bid=" . urlencode($parm["bid"]);
    $url .= "&piv=" . urlencode($parm["piv"]);
    $url .= "&sortsy=0";
    $url .= "&id=" . urlencode($parm["id"]);
    $url .= "&typ=" . urlencode($parm["typ"]);
    $url .= "&nots=1";

    // print($url. "<br/>\n");
    $dom = xmlhttp($url);
    printf("<div style='text-align:right'><b>id:</b>&nbsp;%s</div>\n", $parm["id"]);
    $fullpath = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
    print("<H4>" . $fullpath . "</H4><br/>\n");
    if ($parm["typ"] == "CT") {
//    printf("present dans %s fiche(s).<br/>\n", $dom->getElementsByTagName("hits")->item(0)->firstChild->nodeValue );
        print("<br/>\n");
    } elseif ($parm["typ"] == "TH") {
        echo p4string::MakeString(sprintf(_('thesaurus:: %s reponses retournees'), $dom->getElementsByTagName("allhits")->item(0)->firstChild->nodeValue));
        print("<br/>\n");
        print("<br/>\n");
    }
    ?>
            <div id="TSY" class="tableContainer" style="margin:10px; position:relative; top:0px; left:0px">
                <div>
                    <table border="0" cellpadding="0" cellspacing="0" style="width:100%; ">
                        <col style="width:40px;" />
                        <col style="width:30px;" />
                        <col style="width:auto;" />
                        <col style="width:40px;" />
                        <col style="width:140px;" />
                <!--        <col style="width:10px;" />    -->
                        <col style="width:14px;" />
                        <thead>
                            <tr>
    <?php
    if ($parm["typ"] == "TH") {
        ?>
                                    <th>&nbsp;</th>
    <?php
    } elseif ($parm["typ"] == "CT") {
        ?>
                                    <th>&nbsp;</th>
                                <?php } ?>
                                <th>&nbsp;</th>
                                <th><?php echo p4string::MakeString(_('thesaurus:: synonymes')) /* synonymes */ ?></th>
                                <th><?php echo p4string::MakeString(_('thesaurus:: hits')) /* hits */ ?></th>
                                <th><?php echo p4string::MakeString(_('thesaurus:: ids')) /* id */ ?></th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div style="position:relative; height:150px; overflow:scroll">
                    <div style="position:relative; height:150px; ">
                        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                            <col style="width:40px;" />
                            <col style="width:30px;" />
                            <col style="width:auto;" />
                            <col style="width:40px;" />
                            <col style="width:140px;" />
                  <!--          <col style="width:20px;" />    -->
                            <tbody id="LSY" style="">
    <?php
    $sy_list = $dom->getElementsByTagName("sy_list")->item(0);
    $nsy = 0;
    for ($n = $sy_list->firstChild; $n; $n = $n->nextSibling) {
        printf("\t\t\t\t\t\t<tr class='s_' id='SYN_%s' hits='%s'>\n", $id = $n->getAttribute("id"), $n->getAttribute("hits"));
        printf("\t\t\t\t\t\t\t<td style='text-align:center;'>\n");
        if ($nsy > 0)
            printf("\t\t\t\t\t\t\t\t<img id=\"BTNU_$id\" src=\"./images/up.gif\" />\n");
        if ($nsy > 0 && $n->nextSibling)
            print(" ");
        if ($n->nextSibling)
            printf("\t\t\t\t\t\t\t\t<img id=\"BTND_$id\" src=\"./images/down.gif\" /></td>\n");
        printf("\t\t\t\t\t\t\t</td>\n");
        if (($lng = $n->getAttribute("lng")))
            printf("\t\t\t\t\t\t\t<td id='FLG_%s'><img src='/skins/lng/%s_flag_18.gif' /></td>\n", $n->getAttribute("id"), $lng);
        else
            printf("\t\t\t\t\t\t\t<td id='FLG_%s'><img src='./images/noflag.gif' /></td>\n", $n->getAttribute("id"));
        printf("\t\t\t\t\t\t\t<td>%s</td>\n", $n->getAttribute("t"));
        printf("\t\t\t\t\t\t\t<td>%s</td>\n", $n->getAttribute("hits"));
        printf("\t\t\t\t\t\t\t<td>%s</td>\n", $id);
//      printf("<td></td>\n");
        print("\t\t\t\t\t\t</tr>\n");
        $nsy ++;
    }
//    if($parm["typ"]=="TH")
//    {
//      print("<tr><td colspan='4'><a href='javascript:void(0)' onclick='newsy();return(false);'>nouveau synonyme...</a></td></tr>\n");
//    }
    ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <center>
            <form onsubmit="return(false);">
                <input style="position:relative; z-index:2" type="button" id="close_button" value="<?php echo p4string::MakeString(_('boutton::fermer')) ?>" onclick="self.close();">
            </form>
        </center>
    <?php
    //indentXML($dom);
    // printf("<pre>%s</pre>\n", htmlentities($dom->saveXML()));
}
?>
</body>
<script type="text/javascript">
    // gui callback du menu des drapeaux
    var nsy = <?php echo $nsy ?>;

    function cbME_flags(action, cbParm, menuelem_id)
    {
        // cbParm = objet 'TR'
        // alert("cbParm.obj={obj:'" + cbParm.obj + "', id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");
        if(action != "SELECT" || !menuelem_id)

        return;      // pas d'option de menu : on est sorti du menu sans cliquer

    lng = menuelem_id.substr(9,2);    // id de l'option de menu : flagMenu_xx

    url = "xmlhttp/changesylng.x.php";
    parms  = "bid=<?php echo urlencode($parm["bid"]) ?>";
    // parms += "&id=<?php echo urlencode($parm["id"]) ?>";
    parms += "&typ=<?php echo urlencode($parm["typ"]) ?>";
    parms += "&piv=<?php echo urlencode($parm["piv"]) ?>";
    parms += "&id=" + cbParm.id.substr(4);
    //  parms += "&u=" + encodeURIComponent(cbParm.getAttribute("u"));
    parms += "&newlng=" + encodeURIComponent(lng);

    //  alert(url + "?" + parms);

    //  return;
    ret = loadXMLDoc(url, parms, true);
    sy_list = ret.getElementsByTagName("sy_list").item(0);

    refresh_sy(sy_list);

    refresh = ret.getElementsByTagName("refresh");
    for(i=0; i<refresh.length; i++)
    {
        switch(refresh.item(i).getAttribute("type"))
        {
            case "CT":
<?php echo $opener ?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
      break;
  case "TH":
<?php echo $opener ?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
      break;
  }
}
}

// gui callback du menu des synonymes
function cbME_synonym(action, cbParm, menuelem_id)
{

if(action == "INIT")
{
  if(nsy <= 1)
  {
      // pas d'action possible s'il ne reste qu'un seul synonyme
      // alert(nsy);
      document.getElementById("delete_sy").className = "disabled";
      // document.getElementById("reject_sy").className = "disabled";
  }
  else
  {
      if(cbParm && cbParm.hits > 0)
      {
          // y'a des hits, on peut pas supprimer
          // document.getElementById("reject_sy").className = "";
          document.getElementById("delete_sy").className = "";
      }
      else
      {
          // pas de hits : on peut supprimer
          // document.getElementById("reject_sy").className = "";
          document.getElementById("delete_sy").className = "";
      }
  }
  // si on ne connait pas encore le client mais que start est ouvert, on lui demande
  //    if(!opener.wClient && opener.opener.wClient)
  //      opener.wClient = opener.opener.wClient;
  // si on connait le client et qu'on peut s'en servir pour chercher, on active l'option dans le menu
  //    if(opener.wClient && opener.wClient.externQuery)
  //      document.getElementById("searchcli_sy").className = "";
  //    else
  //      document.getElementById("searchcli_sy").className = "disabled";
  return;
}

if(action != "SELECT" || !menuelem_id)

return;      // pas d'option de menu : on est sorti du menu sans cliquer
switch(menuelem_id)
{
case "delete_sy":    // cbParm = objet 'TR'
  url = "xmlhttp/getsy.x.php";
  url +=  "?bid=<?php echo urlencode($parm["bid"]) ?>";
  url += "&id=" + cbParm.id.substr(4);
  url += "&typ=<?php echo urlencode($parm["typ"]) ?>";
  ret = loadXMLDoc(url, null, true);
  // alert(ret);
  fullpath = ret.getElementsByTagName("fullpath").item(0).firstChild.nodeValue;
  //alert("delete : cbParm.obj={id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");

  url = "xmlhttp/delsy.x.php";
  parms  = "bid=<?php echo urlencode($parm["bid"]) ?>";
  parms += "&piv=<?php echo urlencode($parm["piv"]) ?>";
  parms += "&typ=<?php echo urlencode($parm["typ"]) ?>";
  parms += "&id=" + cbParm.id.substr(4);

  // alert(url + "?" + parms);

  if(confirm($.sprintf("<?php echo p4string::MakeString(_('thesaurus:: Confirmer la suppression du terme %s'), "js") ?>","\n\n"+fullpath+"\n\n")))
  {
      ret = loadXMLDoc(url, parms, true);

      sy_list = ret.getElementsByTagName("sy_list").item(0);
      refresh_sy(sy_list);

      refresh = ret.getElementsByTagName("refresh");
      for(i=0; i<refresh.length; i++)
      {
          switch(refresh.item(i).getAttribute("type"))
          {
              case "CT":
<?php echo $opener ?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
                  break;
              case "TH":
<?php echo $opener ?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
                  break;
              }
          }
      }
      break;
  case "searchcli_sy":    // cbParm = objet 'TR'
      url = "xmlhttp/getsy.x.php";
      url +=  "?bid=<?php echo urlencode($parm["bid"]) ?>";
      url += "&id=" + cbParm.id.substr(4);
      url += "&typ=<?php echo urlencode($parm["typ"]) ?>";
      ret = loadXMLDoc(url, null, true);
      // alert(ret);
      t = ret.getElementsByTagName("sy").item(0).getAttribute("t");
      //alert("delete : cbParm.obj={id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");

      if(opener.wClient && opener.wClient.externQuery)
      {
          opener.wClient.focus();
          opener.wClient.externQuery(opener.currentBaseId, t);
      }

      break;
  case "replace_sy":
      url  = "replace.php";
      url += "?bid=<?php echo $parm["bid"] ?>";
      url += "&piv=<?php echo $parm["piv"] ?>";
      url += "&pid=<?php echo $parm["id"] ?>"
      url += "&id=" + o.id.substr(4);
      url += "&typ=<?php echo urlencode($parm["typ"]) ?>";
      w = window.open(url, "REPLACE", "directories=no, height=300, width=500, location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, toolbar=no");
      break;
  }
}

function refresh_sy(sy_list)
{
  oldtbody = document.getElementById("LSY");
  tbody = document.createElement("tbody");
  tbody.setAttribute("id", "LSY");
  //  tr = tbody.appendChild(document.createElement("tr"));
  //  for(i=0; i<3; i++)
  //    tr.appendChild(document.createElement("td"));
  for(nsy=0, n=sy_list.firstChild; n; n=n.nextSibling, nsy++)
  {
      tr = tbody.appendChild(document.createElement("tr"));
      // tr.className = n.getAttribute("sel") ? "S_" : "s_";
      tr.className = "s_";
      tr.id = "SYN_" + (id=n.getAttribute("id"));

      td = tr.appendChild(document.createElement("td"));
      td.style.textAlign = "center";
      if(nsy > 0)
      {
          img = td.appendChild(document.createElement("img"));
          img.id = "BTNU_" + id;
          img.src = "./images/up.gif";
          if(n.nextSibling)
          {
              // td.appendChild(document.createEntityReference("nbsp"));
              td.appendChild(document.createTextNode(" "));
              // img.insertAdjacentHTML("afterEnd", "&nbsp;");
          }
      }
      if(n.nextSibling)
      {
          img = td.appendChild(document.createElement("img"));
          img.id = "BTND_" + id;
          img.src = "./images/down.gif";
      }

      td = tr.appendChild(document.createElement("td"));
      td.id = "FLG_"+(nsy+1);
      // td.innerText = n.getAttribute("lng");
      img = td.appendChild(document.createElement("img"));
      img.setAttribute("src", "/skins/lng/"+n.getAttribute("lng")+"_flag_18.gif");

      td = tr.appendChild(document.createElement("td"));
      // td.colSpan = "2";
      // td.setAttribute("colSpan", "3");          // attention au 'S' majuscule !!!
      td.innerHTML = n.getAttribute("t");

      td = tr.appendChild(document.createElement("td"));
      td.innerHTML = n.getAttribute("hits");

      td = tr.appendChild(document.createElement("td"));
      td.innerHTML = n.getAttribute("id");

      //td.innerText = " ";

      //    td = tr.appendChild(document.createElement("td"));
      //td.innerText = " ";

      if(n.getAttribute("sel"))
          myGUI.select(tr);
  }
<?php if ($parm["typ"] == "TH") {
    ?>
<?php } ?>
  newtbody = oldtbody.parentNode.replaceChild(tbody, oldtbody);
}


function cbDD_TSY(evt, type, eventObj)
{
  ret = true;
  switch(type)
  {
      case "RMOUSEDOWN":
          if(o = eventObj.Src0)
          {
              for(tr=o; tr && (tr.nodeName!="TR" || !tr.id || tr.id.substr(0, 4)!="SYN_"); tr=tr.parentNode)
                  ;
              if(tr)
                  myGUI.select(tr);
              switch(o.id.substr(0, 4))
              {
                  case "FLG_":    // le drapeau
                      // myGUI.select(o);
                      document.getElementById("flagsMenu").runAsMenu( evt, tr );
                      break;
                  case "SYN_":    // le synonyme
                      // myGUI.select(o);
                      //   document.getElementById("syMenu").runAsMenu( {id:o.id.substr(4)} );
                      document.getElementById("syMenu").runAsMenu( evt, tr );
                      break;
              }
          }
          break;
      case "MOUSEDOWN":
          if(o = eventObj.Src0)
          {
              for(tr=o; tr && (tr.nodeName!="TR" || !tr.id || tr.id.substr(0, 4)!="SYN_"); tr=tr.parentNode)
                  ;
              if(tr)
                  myGUI.select(tr);
              switch(o.id.substr(0, 5))
              {
                  case "BTNU_":
                      syChgPos(1);
                      break;
                  case "BTND_":
                      syChgPos(-1);
                      break;
              }
          }
          break;
      case "DBLCLICK":
          break;
  }

  return(ret);
}

function syChgPos(dir)
{
  if(!myGUI.selectedObject || myGUI.selectedObject.id.substr(0, 4)!="SYN_")

  return;
url = "xmlhttp/changesypos.x.php";
parms  = "bid=<?php echo urlencode($parm["bid"]) ?>";
parms += "&piv=<?php echo urlencode($parm["piv"]) ?>";
parms += "&typ=<?php echo urlencode($parm["typ"]) ?>";
parms += "&id=" + myGUI.selectedObject.id.substr(4);
parms += "&dir=" + dir;

//  alert(url + "?" + parms);
ret = loadXMLDoc(url, parms, true);
//  alert(ret);

sy_list = ret.getElementsByTagName("sy_list").item(0);
refresh_sy(sy_list);

refresh = ret.getElementsByTagName("refresh");
for(i=0; i<refresh.length; i++)
{
  switch(refresh.item(i).getAttribute("type"))
  {
      case "CT":
<?php echo $opener ?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
          break;
      case "TH":
<?php echo $opener ?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
<?php echo $opener ?>.myGUI.select(<?php echo $opener ?>.document.getElementById("THE_<?php echo $parm["id"] ?>"));
          break;
      }
  }
}

myGUI = new GUI("myGUI", "desktop", "FR");
myGUI.setClickable("TSY", cbDD_TSY);
myGUI.setAsMenu("flagsMenu", cbME_flags);
myGUI.setAsMenu("syMenu", cbME_synonym);

</script>
</html>
