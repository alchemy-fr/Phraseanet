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
require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers();
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
                "piv",
                "res",
                "dbg",
                "bid"
);

$dispdbg = $parm["dbg"] ? "" : " visibility:hidden; ";


$lng = Session_Handler::get_locale();

User_Adapter::updateClientInfos(5);
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <title><?php echo $appbox->get_registry()->get('GV_homeTitle'); ?> - <?php echo p4string::MakeString(_('phraseanet:: thesaurus')) ?></title>

    <style id="STYLES">
      DIV.glossaire DIV.r1_
      {
        display: none;
      }
    </style>

    <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
    <script type="text/javascript">
      var p4 = {};
    </script>

    <link rel="shortcut icon" type="image/x-icon" href="/thesaurus2/favicon.ico">
    <script type="text/javascript" src="/include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript" src="/include/minify/g=thesaurus"></script>
    <script type="text/javascript">

      var currentBaseName = "";
      var currentBaseId = null;
      var thesaurusChanged = false;

      // le lanceur nous passe la fenetre du client
      var wClient = null;
      function catchClient(w)
      {
        wClient = w;
      }

      // recharger tout
      function reload()
      {
        self.location.replace("thesaurus.php?piv=<?php echo $parm['piv'] ?>&bid=<?php echo $parm['bid'] ?>") ;
      }

      function test(div)
      {
        //  alert(deb);
        //  return;
        // alert("div");
        t = document.getElementById(div).innerHTML;
        t = t.replace(/&/g, "&amp;");
        t = t.replace(/</g, "&lt;");
        t = t.replace(/>/g, "&gt;");
        // t = escape(t);
        w = window.open("about:blank", div+"wSRC", "left=2, top=2, directories=yes, width=1000, height=800, location=yes, menubar=yes, toolbar=yes, help=yes, status=yes, resizable=yes, scrollbars=yes", true);
        w.document.write("<pre>"+t+"</pre>");
        w.document.close();
      }

      var o_thbox_bck = null;
      var o_TabT0 = null;
      var o_TabT0k = null;
      var o_TabT1 = null;
      var o_TabT1k = null;
      function loaded()
      {
        o_thbox_bck = document.getElementById("id_thbox_bck");
        o_TabT0     = document.getElementById("TabT0") ;
        o_TabT0k    = document.getElementById("TabT0k") ;
        o_TabT1     = document.getElementById("TabT1") ;
        o_TabT1k    = document.getElementById("TabT1k") ;

        f = document.forms["fBase"];

        // document.getElementById("T2").style.visibility = "hidden";

        document.getElementById("T0").innerHTML = document.getElementById("T1").innerHTML = "<?php echo p4string::MakeString(_('phraseanet::chargement'), "js") ?>";

        f.target = "IFR0";
        f.submit();
        //  loadForm("?");
      }

      function chgCkShowRejected()
      {
        // document.styleSheets("STYLES", 0).rules[0].style.display  = document.forms["fTh"].ckShowRejected.checked ? "" : "none";
        var rules = document.styleSheets[0].cssRules ? document.styleSheets[0].cssRules : document.styleSheets[0].rules;
        rules[0].style.display  = document.forms["fTh"].ckShowRejected.checked ? "" : "none";

        return(true);
      }


      var timer_scrolling = null;
      function evtScrollBody()
      {
        if(timer_scrolling)
        {
          window.clearTimeout(timer_scrolling);
          timer_scrolling = null;
        }
        timer_scrolling = window.setTimeout("scrollEnd(0);", 50);
      }

      function scrollEnd(n)
      {
        document.getElementById("desktop").scrollTop = 0;
        /*
      if(n==0)
        window.setTimeout("scrollEnd(1);", 500);
      else
      {
      //  alert('zerzerzer');
    //    o_thbox_bck.style.width  = (0)+"px";
    //    o_thbox_bck.style.height = (0)+"px";
        // o_thbox_bck.style.visibility = "hidden";
    //    o_thbox_bck.style.visibility = "visible";

    //    window.setTimeout("resizeEnd();", 25);
        // window.setTimeout("o_thbox_bck.style.visibility = \"visible\";", 25);
        evtResize();
      }
         */
      }
      var xhr_object;
      function sessionactive(){
        $.ajax({
          type: "POST",
          url: "/include/updses.php",
          dataType: 'json',
          data: {
            app : 5,
            usr : <?php echo $session->get_usr_id() ?>
          },
          error: function(){
            window.setTimeout("sessionactive();", 10000);
          },
          timeout: function(){
            window.setTimeout("sessionactive();", 10000);
          },
          success: function(data){
            //if(manageSession(data))
            var t = 120000;
            if(data.apps && parseInt(data.apps)>1)
              t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 120000));
            window.setTimeout("sessionactive();", t);

            return;
          }
        })
      };
      window.onbeforeunload = function()
      {
        xhr_object = null;
        if(window.XMLHttpRequest) // Firefox
          xhr_object = new XMLHttpRequest();
        else if(window.ActiveXObject) // Internet Explorer
          xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
        else  // XMLHttpRequest non supporte par le navigateur

        return;
      url= "/include/delses.php?app=5&t="+Math.random();
      xhr_object.open("GET", url, false);
      xhr_object.send(null);

    };

    sessionactive();
    </script>

  </head>

  <body id="desktop" style="background-color:#808080; overflow:hidden" onload="loaded();" onmousewheel="return(false);" onscroll="evtScrollBody();" >

    <div class="menu" id="flagsMenu" style="z-index:50">
<?php
// on liste tous les drapeaux
$jsFlags = "";
$tlng = User_Adapter::avLanguages();
foreach ($tlng as $lng_code => $lng)
{
  if (file_exists("/skins/lng/" . $lng_code . "_flag_18.gif") && ($s = getimagesize("/skins/lng/" . $lng_code . "_flag_18.gif") ))
  {
    printf("\t<img id='flagMenu_%s' src='/skins/lng/%s_flag_18.gif' />\n", $lng_code, $lng_code);

    $jsFlags .= ( $jsFlags ? ', ' : '') . $lng_code . ":{ w:" . $s[0] . ", h:" . $s[1] . " }";
  }
}
$jsFlags = '{ ' . $jsFlags . ' }';
?>
    </div>
    <div class="menu" id="kctermMenu" style="z-index:50; width:240px;">
      <a href="javascript:void(0)" class=""         id="kcterm_properties" style="font-weight:700"><?php echo p4string::MakeString(_('thesaurus::menu: proprietes')) /* Proprietes... */ ?></a>
      <a href="javascript:void(0)" class=""         id="kcterm_reject"><?php echo p4string::MakeString(_('thesaurus::menu: refuser')) /* refuser... */ ?></a>
      <a href="javascript:void(0)" class="disabled" id="kcterm_accept"><?php echo p4string::MakeString(_('thesaurus::menu: accepter')) /* Retablir comme candidat... */ ?></a>
      <div class="line"></div>
      <a href="javascript:void(0)" class=""         id="kcterm_delete"><?php echo p4string::MakeString(_('thesaurus::menu: supprimer')) /* Supprimer... */ ?></a>
      <a href="javascript:void(0)" class=""         id="kcterm_delete0hits"><?php echo p4string::MakeString(_('thesaurus::menu: supprimer les candidats a 0 hits')) /* Supprimer... */ ?></a>
      <!--
    <a href="javascript:void(0)" class="disabled" id="kcterm_replace"><?php echo p4string::MakeString(_('thesaurus::menu: remplacer')) /* Remplacer par... */ ?></a>
    <a href="javascript:void(0)" class="" id="kcterm_candidate">candidat</a>
      -->
      <div class="line"></div>
      <a href="javascript:void(0)" class=""         id="kcterm_search"><?php echo p4string::MakeString(_('thesaurus::menu: chercher')) /* Chercher... */ ?></a>
      <a href="javascript:void(0)" class=""         id="kcterm_export"><?php echo p4string::MakeString(_('thesaurus::menu: exporter')) /* Exporter... */ ?></a>
      <!--
    <div class="line"></div>
    <a href="javascript:void(0)" class="disabled" id="kcterm_rescan"><?php echo p4string::MakeString(_('thesaurus::menu: relire les candidats')) /* Relire les candidats... */ ?></a>
    <div class="line"></div>
      -->
    </div>

    <div class="menu" id="kThMenu" style="z-index:50; width:200px;">
      <a href="javascript:void(0)" class="" id="kth_import"><?php echo p4string::MakeString(_('thesaurus::menu: importer')) /* Importer... */ ?></a>
    </div>

    <div class="menu" id="ktermMenu" style="z-index:50; width:200px;">
      <a href="javascript:void(0)" class="" id="kterm_properties" style="font-weight:700"><?php echo p4string::MakeString(_('thesaurus::menu: proprietes')) /* Proprietes... */ ?></a>
      <a href="javascript:void(0)" class="" id="kterm_newts"><?php echo p4string::MakeString(_('thesaurus::menu: Nouveau terme')) /* Nouveau terme specifique... */ ?></a>
      <a href="javascript:void(0)" class="" id="kterm_newsy"><?php echo p4string::MakeString(_('thesaurus::menu: Nouveau synonyme')) /* Nouveau synonyme... */ ?></a>
    <!--  <a href="javascript:void(0)" class="" id="kterm_replace"><?php echo p4string::MakeString(_('thesaurus::menu: remplacer')) /* remplacer par... */ ?></a>  -->
      <a href="javascript:void(0)" class="" id="kterm_delete"><?php echo p4string::MakeString(_('thesaurus::menu: supprimer')) /* Supprimer... */ ?></a>
      <div class="line"></div>
      <a href="javascript:void(0)" class="" id="kterm_search"><?php echo p4string::MakeString(_('thesaurus::menu: chercher')) /* Chercher... */ ?></a>
      <a href="javascript:void(0)" class="" id="kterm_export"><?php echo p4string::MakeString(_('thesaurus::menu: exporter')) /* Exporter... */ ?></a>
      <a href="javascript:void(0)" class="" id="kterm_topics"><?php echo p4string::MakeString(_('thesaurus::menu: export topics')) /* Exporter comme topics... */ ?></a>
      <div class="line"></div>
      <a href="javascript:void(0)" class="" id="kterm_link"><?php echo p4string::MakeString(_('thesaurus::menu: lier au champ')) /* Lier au champ... */ ?></a>
    </div>

    <!--
    <div class="menu" id="ktsMenu" style="z-index:50; width:200px;">
    <a href="javascript:void(0)" id="kcterm_reject">refuser</a>
    </div>
    -->
    <form name="fBase" action="./loadth.php" method="post" target="?">
      <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>" />
      <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>" />
      <input type="hidden" name="repair" value="" />
    </form>

    <form name="fSave" action="./savethesaurus1.php" method="post">
      <input type="hidden" name="bid" value="?" />
      <input type="hidden" name="th" value="?" />
      <input type="hidden" name="ch" value="?" />
    </form>

    <form name="fTh" style="position:absolute; top:0px; left:0px; right:0px; bottom:0px;">

      <br/>
      <br/>
      <div id="id_thbox_bck" class="thbox" style="position:absolute; top:28px; left:8px; right:8px; bottom:8px; background-color:#f4f4f4; xoverflow:hidden">

        <div class="onglet" style="background-color:#f0f0f0; border-bottom:1px solid #f4f4f4">
          <span id="baseName"><?php echo p4string::MakeString(_('phraseanet:: thesaurus')) ?></span>
          <a href="javascript:void();" onclick="fixTh();return(false);" style="<?php echo $dispdbg ?>">X</a>
        </div>


        <div id="TabT0" style="position:absolute; top:28px; left:0px; bottom:0px; width:40%;">

          <div class="thbox" style="position:absolute; top:0px; bottom:8px; left:6px; right:3px;">
            <div class="onglet"><?php echo p4string::MakeString(_('thesaurus:: onglet stock')) /* Stock */ ?>
              &nbsp;
              <a href="javascript:void();" onclick="test('T0');return(false);" style="<?php echo $dispdbg ?>">X</a>
            </div>
            <div style="width:100%; overflow:hidden">
              <input type="checkbox" name="ckShowRejected" onClick="return(chgCkShowRejected());" /><span style="white-space:nowrap; overflow:hidden"><?php echo p4string::MakeString(_('thesaurus:: afficher les termes refuses')) /* Afficher les termes refuses */ ?></span>
            </div>
            <div id="TabT0k" style="position:absolute; top:20px; bottom:0px; left:0px; right:0px; overflow:scroll; border:0px #000000 none">
              <div id="T0" style="position:absolute; top:0px; left:0px;">
              </div>
            </div>
          </div>
        </div>

        <div id="TabT1" style="position:absolute; top:28px; right:0px; bottom:0px; width:60%;">
          <!--
                <input type="text" name="textT1" value="" onkeyup="evt_kup_T1();return(true);" /> <span id="WT1" style="visibility:hidden">searching...</span>
          -->
          <div class="thbox"  style="position:absolute; top:0px; bottom:8px; left:3px; right:6px;">
            <div class="onglet"><span id='TabT1Title' style="cursor:pointer"><?php echo p4string::MakeString(_('thesaurus:: onglet thesaurus')) /* Thesaurus */ ?></span>
              &nbsp;
              <a href="javascript:void();" onclick="test('T1');return(false);" style="<?php echo $dispdbg ?>">X</a>
            </div>
            <div id="TabT1k" style="position:absolute; top:0px; bottom:0px; left:0px; right:0px; overflow:scroll; border:0px #000000 none">
              <div id="T1" style="position:absolute; top:0px; left:0px;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <br/>

    </form>

    <div id="clipboard" style="position:absolute; top:0px; left:0px; z-index:99">&nbsp;</div>


    <iframe src="about:blank" name="IFRsave" id="IFRsave" style="<?php echo $dispdbg ?> ; position:absolute; top:<?php echo $parm["dbg"] ? 600 : 0 ?>px; left:5px;   height:<?php echo $parm["dbg"] ? 150 : 50 ?>px; width:<?php echo $parm["dbg"] ? 340 : 50 ?>px; overflow:scroll"></iframe>
    <iframe src="about:blank" name="IFR0"    id="IFR0"    style="<?php echo $dispdbg ?> ; position:absolute; top:<?php echo $parm["dbg"] ? 600 : 0 ?>px; left:400px; height:<?php echo $parm["dbg"] ? 150 : 50 ?>px; width:<?php echo $parm["dbg"] ? 340 : 50 ?>px; overflow:scroll"></iframe>

    <script type="text/javascript">

    document.body.oncontextmenu = function(){
      return false;
    }

    tFlags = <?php echo $jsFlags ?> ;

    myGUI = new GUI("myGUI", "desktop", "FR");

    var selectedObject = null;        // l'object selectionné dans l'interface

    var selectedThesaurusItem = null;    // le thesaurus item en cours d'édition

    function buidTermBalloon(xmlobj)
    {
      var html = "";
      var syl = xmlobj.getElementsByTagName("sy_list");
      if(syl.length==1)
      {
        html = "<table>";
        for(var sy=syl.item(0).firstChild; sy; sy=sy.nextSibling )
        {
          var lng = sy.getAttribute("lng");
          html += "<tr>";
          if(lng)
            if(tFlags[lng])
              html += "<td><img width='"+tFlags[lng].w+"' height='"+tFlags[lng].h+"' src='/skins/lng/"+lng+"_flag_18.gif'></td>";
          else
            html += "<td><span style='background-color:#cccccc'>&nbsp;"+lng+"&nbsp;</span></td>";
          else
            html += "<td><span style='background-color:#cccccc'>&nbsp;?&nbsp;</span></td>";

          html += "<td>&nbsp;"+sy.getAttribute("v")+"</td>";

          var hits = 0+sy.getAttribute("hits");
          if(hits == 1)
            html += "<td>&nbsp;&nbsp;<i>"+sy.getAttribute("hits")+" hit</i></td></tr>";
          else
            html += "<td>&nbsp;&nbsp;<i>"+sy.getAttribute("hits")+" hits</i></td></tr>";
        }
        html += "</table>";
      }

      return(html);
    }


    // ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //     T0
    //
    // ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // gui callback du menu contextuel sur terme candidat
    function cbME_kcterm(action, cbParm, menuelem_id)
    {
      // alert("id=" + cbParm.id + "menuelem_id='" + menuelem_id + "'");
      // alert("cbParm.obj={obj:'" + cbParm.obj + "', id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");
      var o = null;
      var om;
      //  o = document.getElementById(cbParm);
      o = cbParm;
      switch(action)
      {
        case "INIT":
          // last chance to change menu content
          switch(o.id.substr(0, 4))
          {
            case "TCE_":    // racine (STOCK) ou premier niveau (champ ou [trash])
              //       alert(o.id);
              if(o.id == "TCE_C")
              {
                // racine
                if(om=document.getElementById("kcterm_delete0hits"))
                  om.className = "disabled";
              }
              else
              {
                // premier niveau
                if(om=document.getElementById("kcterm_delete0hits"))
                  om.className = "";
              }
              if(om=document.getElementById("kcterm_reject"))
                om.className = "disabled";
              if(om=document.getElementById("kcterm_accept"))
                om.className = "disabled";
              if(om=document.getElementById("kcterm_delete"))
                om.className = "disabled";
              if(om=document.getElementById("kcterm_properties"))
                om.className = "disabled";
              // if(om=document.getElementById("kcterm_candidate"))
              //  om.className = "disabled";
              if(om=document.getElementById("kcterm_search"))
                om.className = "";
              if(om=document.getElementById("kcterm_rescan"))
                om.className = "disabled";
              break;
            case "THE_":    // terme candidat
              //          alert("id: "+o.id+" ; p: "+o.parentNode.id);
              if(om=document.getElementById("kcterm_delete0hits"))
                om.className = "disabled";
              if(om=document.getElementById("kcterm_rescan"))
                om.className = "disabled";
              if(o.id.substr(4, 1)=="R")
              {
                if(om=document.getElementById("kcterm_reject"))
                  om.className = "disabled";
                if(om=document.getElementById("kcterm_delete"))
                  om.className = "";
                if(o.parentNode.id.indexOf(".") == -1)
                {
                  // terme de premier niveau sous un champ : on peut retablir
                  if(om=document.getElementById("kcterm_accept"))
                    om.className = "";
                }
                else
                {
                  // terme 'profond' (dans deleted) : on peut pas retablir
                  if(om=document.getElementById("kcterm_accept"))
                    om.className = "disabled";
                }
              }
              else
              {
                if(om=document.getElementById("kcterm_accept"))
                  om.className = "disabled";
                if(om=document.getElementById("kcterm_delete"))
                  om.className = "";
                if(o.parentNode.id.indexOf(".") == -1)
                {
                  // terme de premier niveau sous un champ : on peut refuser
                  if(om=document.getElementById("kcterm_reject"))
                    om.className = "";
                }
                else
                {
                  // terme 'profond' (dans deleted) : on peut pas refuser
                  if(om=document.getElementById("kcterm_reject"))
                    om.className = "disabled";
                }
              }
              if(om=document.getElementById("kcterm_properties"))
                om.className = "";

              // document.getElementById("kcterm_replace").className = "";
              if(om = document.getElementById("kcterm_replace"))
                om.className = ""; // "disabled";

              if(o.firstChild.className == "nots")  // carre en face du terme (+- ; balise 'U') == grise ?
              {
                if(om = document.getElementById("kcterm_search"))
                  om.className = "disabled";  // carre grise
              }
              else
              {
                if(om=document.getElementById("kcterm_search"))
                  om.className = "";
              }
              break;
          }
          // alert(o.id);
          break;
        case "SELECT":
          switch(menuelem_id)
          {
            case "kcterm_delete":
              url  = "xmlhttp/getterm.x.php";
              url += "?bid=<?php echo urlencode($parm["bid"]) ?>";
              url += "&id=" + o.id.substr(4);
              url += "&typ=CT";

              // alert(url);

              ret = loadXMLDoc(url, null, true);
              // alert(ret);
              allhits = ret.getElementsByTagName("allhits").item(0).firstChild.nodeValue;

              if(allhits==0)
                msg = "<?php echo p4string::MakeString(_('thesaurus:: Supprimer cette branche ?&#10;(les termes concernes remonteront en candidats a la prochaine indexation)'), "js") /* Supprimer cette branche ?\\n(les termes concernes remonteront en candidats e la prochaine indexation) */ ?>";
              else
                msg = "<?php echo p4string::MakeString(_('thesaurus:: Des reponses sont retournees par cette branche. &#10;Supprimer quand meme ?&#10;(les termes concernes remonteront en candidats a la prochaine indexation)'), "js") /* cette branche retourne %s reponses.... */ ?>";

              if(confirm(msg))
              {
                var myObj = { "win":window };
                url  = "./xmlhttp/killterm.x.php";
                url += "?bid=<?php echo $parm["bid"] ?>";
                url += "&piv=<?php echo $parm["piv"] ?>";
                url += "&id=" + o.id.substr(4);
                // url += "&typ=CT";

                // alert(url);

                ret = loadXMLDoc(url, parms, true);

                refresh = ret.getElementsByTagName("refresh");
                for(i=0; i<refresh.length; i++)
                {
                  switch(refresh.item(i).getAttribute("type"))
                  {
                    case "CT":
                      reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                      break;
                    case "TH":
                      reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                      break;
                  }
                }
              }
              break;
            case "kcterm_delete0hits":
              url  = "xmlhttp/searchnohits.x.php";
              url += "?bid=<?php echo urlencode($parm["bid"]) ?>";
              url += "&id=" + o.id.substr(4);
              url += "&typ=CT";

              ret = loadXMLDoc(url, null, true);
              // alert(ret);
              n_nohits = ret.documentElement.getAttribute("n_nohits");

              if(n_nohits==0)
              {
                alert("<?php echo p4string::MakeString(_('thesaurus:: Tous les termes ont des hits'), "js") /* Cette branche ne contient aucun terme 'e 0 hits' */ ?>");

                return;
              }
              else
              {
                if(confirm("<?php echo p4string::MakeString(_('thesaurus:: Des termes de cette branche ne renvoient pas de hits. Les supprimer ?'), "js") ?>"));
                {
                  url  = "xmlhttp/deletenohits.x.php";
                  url += "?bid=<?php echo urlencode($parm["bid"]) ?>";
                  url += "&id=" + o.id.substr(4);
                  url += "&typ=CT";

                  ret = loadXMLDoc(url, null, true);

                  reloadCtermsBranch(o.id.substr(4));
                }
              }
              break;

            case "kcterm_reject":
              var myObj = { "win":window };
              url  = "./xmlhttp/reject.x.php";
              url += "?bid=<?php echo $parm["bid"] ?>";
              url += "&piv=<?php echo $parm["piv"] ?>";
              url += "&id=" + o.id.substr(4);
              // url += "&typ=CT";

              // alert(url);

              ret = loadXMLDoc(url, parms, true);

              refresh = ret.getElementsByTagName("refresh");
              for(i=0; i<refresh.length; i++)
              {
                switch(refresh.item(i).getAttribute("type"))
                {
                  case "CT":
                    reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                    break;
                  case "TH":
                    reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                    break;
                }
              }
              break;
            case "kcterm_accept":
              var myObj = { "win":window };
              url  = "./xmlhttp/accept.x.php";
              url += "?bid=<?php echo $parm["bid"] ?>";
              url += "&piv=<?php echo $parm["piv"] ?>";
              url += "&id=" + o.id.substr(4);
              // url += "&typ=CT";

              //    alert(url);

              ret = loadXMLDoc(url, parms, true);

              refresh = ret.getElementsByTagName("refresh");
              for(i=0; i<refresh.length; i++)
              {
                switch(refresh.item(i).getAttribute("type"))
                {
                  case "CT":
                    reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                    break;
                  case "TH":
                    reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                    break;
                }
              }
              break;
            case "kcterm_candidate":
              if( o )
              {
                o.className = o.className.replace("R_", "r_");
                if(!o.oldid)
                {
                  //    o.oldid = o.id.substr(4);
                  o.setAttribute("oldid", o.id.substr(4) );
                }
                o.id = "TCE_C" + o.id.substr(5);
              }
              break;
            case "kcterm_properties":
              var myObj = { "win":window };
              url  = "properties.php";
              url += "?bid=<?php echo $parm["bid"] ?>";
              url += "&piv=<?php echo $parm["piv"] ?>";
              url += "&id=" + o.id.substr(4);
              url += "&typ=CT";
              url += "&dlg=1";
              ret = window.showModalDialog(url, myObj, "dialogHeight:340px; dialogWidth:500px; center:yes; help:no; resizable:no; scroll:no; status:no; unadorned:yes");
              break;
            case "kcterm_rescan":
              // if(confirm("<?php echo p4string::MakeString(_('thesaurus:: Supprimer tous les candidats et placer toutes les fiches en reindexation-thesaurus ?'), "js") /* Supprimer tous les candidats et placer toutes les fiches en reindexation-thesaurus ? */ ?>"))
              // {
              var myObj = { "win":window };
              url  = "rescan_dlg.php";
              url += "?bid=<?php echo $parm["bid"] ?>";
              url += "&piv=<?php echo $parm["piv"] ?>";
              w = window.open(url, "RESCAN", "directories=no, height=300, width=500, location=no, menubar=no, resizable=yes, scrollbars=no, status=no, toolbar=no");
              // }
              break;
            case "kcterm_search":
              ret = window.showModalDialog("search.php?dlg=1", null, "dialogHeight:240px; dialogWidth:300px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
              if(ret && ret.t != "")
              {
                url = "./xmlhttp/openbranches.x.php";
                parms  = "bid=<?php echo $parm["bid"] ?>";
                parms += "&id=" + cbParm.id.substr(4);
                parms += "&typ=CT";
                parms += "&method=" + ret.method;
                parms += "&t=" + encodeURIComponent(ret.t);
                //alert(url + "?" + parms);

                ret = loadXMLDoc(url, parms, true);
                // alert(ret);
                thb = document.getElementById("THB_" + cbParm.id.substr(4));

                ts = ret.getElementsByTagName("html");
                if(ts.length==1)
                {
                  replaceContent(thb, ts.item(0));
                  thb.className = "hb";
                  document.getElementById("THP_" + cbParm.id.substr(4)).innerText="...";
                }
              }
              break;
            case "kcterm_export":
              var myObj = { "win":window };
              url  = "export_text_dlg.php";
              url += "?bid=<?php echo $parm["bid"] ?>";
              url += "&piv=<?php echo $parm["piv"] ?>";
              url += "&id=" + o.id.substr(4);
              url += "&typ=CT";
              w = window.open(url, "EXPORT", "directories=no, height=300, width=700, location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, toolbar=no");
              break;
              break;
            }
            break;
          }
        }

        function cbDD_T0(event, type, eventObj)
        {
          ret = true;
          switch(type)
          {
            case "RMOUSEDOWN":
              if(o = eventObj.Src0)
              {
                // alert(o.id.substr(0, 4));
                switch(o.id.substr(0, 4))
                {
                  case "TCE_":    // une branche (champ) de candidats
                    myGUI.select(o);
                    document.getElementById("kctermMenu").runAsMenu( event, o );
                    break;
                  case "THE_":    // le terme candidat
                    myGUI.select(o);
                    document.getElementById("kctermMenu").runAsMenu( event, o );
                    break;
                }
              }
              break;
            /*
        case "CONTEXTMENU":
          if(o = eventObj.Src0)
          {
            // alert(o.id.substr(0, 4));
            switch(o.id.substr(0, 4))
            {
              case "TCE_":    // une branche (champ) de candidats
              case "THE_":    // le terme candidat
                myGUI.select(o);
                // document.getElementById("kctermMenu").runAsMenu( event, o );
                self.setTimeout('document.getElementById("kctermMenu").runAsMenu( event, o.id );', 3000);
                break;
            }
          }
          break;
             */
          case "DBLCLICK":
            if(o = eventObj.Src0)
            {
              switch(o.id.substr(0, 4))
              {
                case "THE_":    // terme candidat
                  if(o.id.indexOf(".") != -1)
                  {
                    var myObj = { "win":window };
                    url  = "properties.php";
                    url += "?bid=<?php echo $parm["bid"] ?>";
                    url += "&piv=<?php echo $parm["piv"] ?>";
                    url += "&id=" + o.id.substr(4);
                    url += "&typ=CT";
                    url += "&dlg=1";
                    ret = window.showModalDialog(url, myObj, "dialogHeight:340px; dialogWidth:500px; center:yes; help:no; resizable:no; scroll:no; status:no; unadorned:yes");
                  }
                  break;
                }
              }
              break;
            case "MOUSEDOWN":
              if(o = eventObj.Src0)
              {
                // alert(o.id);
                switch(o.id.substr(0, 4))
                {
                  case "THP_":  // + ou - devant un terme
                    thid = o.id.substr(4);
                    if(tce = document.getElementById("TCE_"+thid))
                      myGUI.select(tce);
                    else
                      if(the = document.getElementById("THE_"+thid))
                        myGUI.select(the);
                    if(o.className=="" && (thb = document.getElementById("THB_"+thid)))
                    {
                      // alert(thb.className);
                      // alert(thb.className + " " + thb.className.indexOf("OB"));
                      /*
                  if(thb.className.indexOf("OB") != -1)
                  {
                    eventObj.Src0.innerHTML = "+";

                    // thb.className = "ob";
                    thb.className = thb.className.replace(/OB/, "ob");
                  }
                  else
                  {
                    new_thb = reloadbranch(thb, thid, "CT");

                    eventObj.Src0.innerHTML = "-";
                    new_thb.className = new_thb.className.replace(/ob/, "OB");
                    //new_thb.className = "OB";
                    //alert(new_thb.className);
                  }
                       */
                      if(thb.className == "ob")
                      {
                        new_thb = reloadbranch(thb, thid, "CT");

                        eventObj.Src0.innerHTML = "-";
                        new_thb.className = "OB";
                      }
                      else
                      {
                        eventObj.Src0.innerHTML = "+";

                        // thb.className = "ob";
                        thb.className = "ob";
                      }
                      // document.getElementById("THE_").style.display = "none";
                      // document.getElementById("THE_").style.display = "";
                    }
                    ret = false;  // empêchera le drag/drop à partir du +-
                    break;
                  case "THE_":    // le terme candidat
                    myGUI.select(o);
                    if(o.id.substr(4, 1) != "C")  // on ne peut pas draguer que les candidats, pas les refuses ---
                    {
                      ret = false;
                    }
                    break;
                  case "TCE_":    // le nom de champ
                    myGUI.select(o);
                    // if(o.id.substr(4, 1) != "C")  // on ne peut pas draguer que les candidats, pas les refuses ---
                    //{
                    ret = false;
                    // }
                    break;
                }
              }
              break;
            case "DRAGSTART":
              if(o = eventObj.Src0)
              {
                if(o.id.substr(0, 4)=="THE_")
                {
                  myGUI.select(null);
                  cp = document.getElementById("clipboard");
                  //          if(cp = document.getElementById("clipboard"))
                  //          {

                  // o.style.position = "absolute";
                  //    cp.style.pixelLeft = eventObj.X+10;
                  //    cp.style.pixelTop = eventObj.Y+10;

                  o2 = o.cloneNode(true);
                  //o2.style.position = "absolute";
                  //o2.style.pixelLeft = 0;
                  //o2.style.pixelTop = 0;
                  o2.style.backgroundColor = "#ffff00";
                  //o2.style.zIndex = 2;

                  //    o.style.display = "none";
                  if(pp = document.getElementById("clipboard"))
                  {
                    pp.replaceChild(o2, pp.firstChild);
                    pp.style.visibility = "visible";
                    myGUI.setDragObj(pp);
                  }
                  //          }
                }
                else
                {
                  ret = false;
                }
              }
              break;
            case "BALLOON":
              if(o = eventObj.Src0)
              {
                if(o.id.substr(0, 4)=="THE_") // && o.id.substr(4)!="T")
                {
                  var url   = "xmlhttp/getterm.x.php";
                  var parms = "bid=<?php echo $parm["bid"] ?>";
                  parms    += "&piv=<?php echo $parm["piv"] ?>";
                  parms    += "&sortsy=0";
                  parms    += "&id=" + o.id.substr(4);
                  parms    += "&typ=CT";
                  parms    += "&nots=1";
                  // alert(url);

                  var ret = loadXMLDoc(url, parms, true);
                  var html = buidTermBalloon(ret);
                  if(html)
                    myGUI.showBalloon(html);
                }
              }
              break;
            }

            return(ret);
          }


          /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          //
          //     T1
          //
          /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          function reloadbranch(thb, thid, typ)
          {
            // alert(thb.id);
            new_thb = null;
            url = "xmlhttp/getterm.x.php";
            parms  = "bid=<?php echo $parm["bid"] ?>";
            parms += "&piv=<?php echo $parm["piv"] ?>";
            parms += "&sortsy=1";
            parms += "&id=" + thid;
            parms += "&typ=" + typ;
            ret = loadXMLDoc(url, parms, true);
            ts = ret.getElementsByTagName("ts_list");
            // alert(url+"?"+parms);
            if(ts.length==1)
            {
              new_thb = document.createElement("DIV");
              new_thb.setAttribute("id", "THB_" + thid);
              nts = 0;
              for(n=ts.item(0).firstChild; n; n=n.nextSibling)
              {
                div = new_thb.appendChild(document.createElement("div"));
                id = n.getAttribute("id");
                if(id.substr(0,1) == "R")
                  div.className = "s_ r1_";
                else
                  div.className = "s_ r0_";
                div.setAttribute("id", "THE_" + n.getAttribute("id"));

                u = div.appendChild(document.createElement("u"));
                u.setAttribute("id", "THP_" + n.getAttribute("id"));
                if(n.firstChild)
                  txt = div.appendChild(document.createTextNode(n.firstChild.nodeValue));
                else
                  txt = div.appendChild(document.createTextNode(""));
                if(n.getAttribute("nts") > 0)
                {
                  u.appendChild(document.createTextNode("+"));
                }
                else
                {
                  u.className = "nots";
                  u.appendChild(document.createTextNode(""));
                }
                new_thb2 = new_thb.appendChild(document.createElement("DIV"));
                //  new_thb2.className = "ob r0_";
                new_thb2.className = "ob";
                new_thb2.setAttribute("id", "THB_" + n.getAttribute("id"));
                nts++;
              }
              thb.parentNode.replaceChild(new_thb, thb);
              thp = document.getElementById("THP_"+thid);

              if(nts > 0)
              {
                //       alert("thb.className = "+thb.className);
                new_thb.className = thb.className;
                if(thid.substr(0,1) == "R")
                {
                  // new_thb.className = "ob r1_";
                  new_thb.className = "ob";
                }
                else
                {
                  // new_thb.className = "ob r0_";
                }
                // alert(thid + " " + new_thb.className);
                thp.className = "";
                thp.innerText = thb.className=="ob"?"+":"-";
              }
              else
              {
                //      new_thb.className = "ob r1_";
                new_thb.className = "ob";
                thp.className = "nots";
                thp.innerText = "";
              }
            }

            return(new_thb);
            // alert(ret.nodeFromID("tsid"));  // marche pas sur safari
            // alert(ret.selectSingleNode("//te"));  // marche pas sur safari
          }
          function reloadCtermsBranch(cid)
          {
            // alert('reloadCtermsBranch('+cid+')');
            return(reloadbranch(document.getElementById("THB_"+cid), cid, "CT"))
          }
          function reloadThesaurusBranch(tid)
          {
            if(tid=='')
              tid='T';

            return(reloadbranch(document.getElementById("THB_"+tid), tid, "TH"))
          }


          // gui callback du menu contextuel sur terme
          function cbME_kterm(action, cbParm, menuelem_id)
          {
            // alert("id=" + cbParm.id + "menuelem_id='" + menuelem_id + "'");
            // alert("cbParm.obj={obj:'" + cbParm.obj + "', id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");
            var o = null;
            var om;
            var ret;
            if(cbParm.id)
              o = document.getElementById(cbParm.id);
            switch(action)
            {
              case "INIT":
                // last chance to change menu content
                if(o.id.substr(4) == "T")
                {
                  // menu contextuel e la racine du thesaurus
                  // document.getElementById("kterm_replace").style.display = "none";
                  if(om=document.getElementById("kterm_link"))
                    om.className = "disabled";
                  if(om=document.getElementById("kterm_delete"))
                    om.className = "disabled";
                  if(om=document.getElementById("kterm_newsy"))
                    om.className = "disabled";
                  if(om=document.getElementById("kterm_properties"))
                    om.className = "disabled";
                }
                else
                {
                  // menu contextuel sur un terme du thesaurus
                  // document.getElementById("kterm_replace").style.display = "";
                  if(om=document.getElementById("kterm_link"))
                    om.className = "";
                  if(om=document.getElementById("kterm_delete"))
                    om.className = "";
                  if(om=document.getElementById("kterm_newsy"))
                    om.className = "";
                  if(om=document.getElementById("kterm_properties"))
                    om.className = "";
                }
                break;
              case "SELECT":
                switch(menuelem_id)
                {
                  case "kterm_newts": // nouveau terme specifique
                    var myObj = { "win":window };
                    url  = "newsy_dlg.php?piv=<?php echo $parm["piv"] ?>&typ=TS";

                    ret = window.showModalDialog(url, myObj, "dialogHeight:200px; dialogWidth:400px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");

                    if(ret && ret.t)
                    {
                      var myObj = { "win":window };
                      url  = "newterm.php";
                      url += "?bid=<?php echo $parm["bid"] ?>";
                      url += "&piv=<?php echo $parm["piv"] ?>";
                      url += "&pid=" + o.id.substr(4);
                      //        url += "&t=" + escape(newts);        // PAS avec un prompt UTF8
                      url += "&t=" + encodeURIComponent(ret.t);
                      url += "&typ=TS";
                      url += "&sylng=" + encodeURIComponent(ret.lng);
                      url += "&dlg=1";
                      ret = window.showModalDialog(url, myObj, "dialogHeight:290px; dialogWidth:490px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                    }
                    break;
                  case "kterm_newsy":
                    var myObj = { "win":window };
                    url  = "newsy_dlg.php?piv=<?php echo $parm["piv"] ?>&typ=SY";
                    ret = window.showModalDialog(url, myObj, "dialogHeight:200px; dialogWidth:400px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                    if(ret && ret.t)
                    {
                      var myObj = { "win":window };
                      url  = "newterm.php";
                      url += "?bid=<?php echo $parm["bid"] ?>";
                      url += "&piv=<?php echo $parm["piv"] ?>";
                      url += "&pid=" + o.id.substr(4);
                      //        url += "&t=" + escape(newts);        // PAS avec un prompt UTF8
                      url += "&t=" + encodeURIComponent(ret.t);
                      url += "&typ=SY";
                      url += "&sylng=" + encodeURIComponent(ret.lng);
                      url += "&dlg=1";
                      ret = window.showModalDialog(url, myObj, "dialogHeight:290px; dialogWidth:490px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                    }
                    break;
                  case "kterm_delete":
                    tid = o.id.substr(4);
                    url = "./xmlhttp/getterm.x.php";
                    url +=  "?bid=<?php echo $parm["bid"] ?>";
                    url +=  "&piv=<?php echo $parm["piv"] ?>";
                    url += "&id=" + tid;
                    url += "&typ=TH";
                    ret = loadXMLDoc(url, null, true);
                    // alert(ret);
                    fullpath = ret.getElementsByTagName("fullpath").item(0).firstChild.nodeValue;

                    url = "xmlhttp/delts.x.php";
                    parms  = "bid=<?php echo $parm["bid"] ?>";
                    parms += "&piv=<?php echo $parm["piv"] ?>";
                    parms += "&id=" + tid;

                    if(confirm("<?php echo p4string::MakeString(_('thesaurus:: deplacer le terme dans la corbeille ?'), "js") ?>"+"\n\n"+fullpath+"\n\n"))
                    {
                      // xmlhttp/delts.x.php?bid=15&id=T1.629&debug=1
                      // alert(url+"?"+parms);
                      ret = loadXMLDoc(url, parms, true);
                      // alert(ret);

                      refresh = ret.getElementsByTagName("refresh");
                      for(i=0; i<refresh.length; i++)
                      {
                        switch(refresh.item(i).getAttribute("type"))
                        {
                          case "CT":
                            reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                            break;
                          case "TH":
                            reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                            break;
                        }
                      }
                    }
                    break;
                  case "kterm_replace":
                    alert("todo...");
                    break;
                  case "kterm_link":
                    var myObj = { "win":window };
                    url = "linkfield.php";
                    url +=  "?bid=<?php echo $parm["bid"] ?>";
                    url +=  "&piv=<?php echo $parm["piv"] ?>";
                    url += "&tid=" + o.id.substr(4);
                    url += "&dlg=1";
                    ret = window.showModalDialog(url, myObj, "dialogHeight:340px; dialogWidth:500px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                    break;
                  case "kterm_properties":
                    var myObj = { "win":window };
                    url  = "properties.php";
                    url += "?bid=<?php echo $parm["bid"] ?>";
                    url += "&piv=<?php echo $parm["piv"] ?>";
                    url += "&id=" + o.id.substr(4);
                    url += "&typ=TH";

                    w = window.open(url, "PROPERTIES", "directories=no, height=300, width=500, location=no, menubar=no, resizable=yes, scrollbars=no, status=no, toolbar=no");
                    break;
                  case "kterm_search":
                    ret = window.showModalDialog("search.php?dlg=1", myObj, "dialogHeight:240px; dialogWidth:300px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                    if(ret && ret.t != "")
                    {
                      url = "./xmlhttp/openbranches.x.php";
                      parms  = "bid=<?php echo $parm["bid"] ?>";
                      parms += "&id=" + cbParm.id.substr(4);
                      parms += "&typ=TH";
                      parms += "&method=" + ret.method;
                      parms += "&t=" + encodeURIComponent(ret.t);
                      // alert(url + "?" + parms);

                      ret = loadXMLDoc(url, parms, true);
                      // alert(ret);

                      thb = document.getElementById("THB_" + cbParm.id.substr(4));

                      ts = ret.getElementsByTagName("html");
                      if(ts.length==1)
                      {
                        replaceContent(thb, ts.item(0));
                        thb.className = "hb";
                        document.getElementById("THP_" + cbParm.id.substr(4)).innerText="...";
                      }
                    }
                    break;
                  case "kterm_export":
                    var myObj = { "win":window };
                    url  = "export_text_dlg.php";
                    url += "?bid=<?php echo $parm["bid"] ?>";
                    url += "&piv=<?php echo $parm["piv"] ?>";
                    url += "&id=" + o.id.substr(4);
                    url += "&typ=TH";
                    w = window.open(url, "EXPORT", "directories=no, height=300, width=700, location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, toolbar=no");
                    break;
                  case "kterm_topics":
                    var myObj = { "win":window };
                    url  = "export_topics_dlg.php";
                    url += "?bid=<?php echo $parm["bid"] ?>";
                    url += "&piv=<?php echo $parm["piv"] ?>";
                    url += "&id=" + o.id.substr(4);
                    url += "&typ=TH";
                    url += "&obr=" + list_opened_branches(o.parentNode);
                    w = window.open(url, "EXPORT", "directories=no, height=400, width=550, location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, toolbar=no");
                    break;
                  }
                  break;
                }
              }

              function list_opened_branches(o, depth)
              {
                l = "";
                if(o.nodeType==1)  // element
                {
                  if(o.id.substr(0,4)=="THB_" && o.className=="OB")
                    l += o.id.substr(4) + ";";
                  for(var oo=o.firstChild; oo; oo=oo.nextSibling)
                    l += list_opened_branches(oo);
                }

                return(l);
              }

              function replaceContent(dstNode, newContent)
              {
                var n;
                if(document.importNode)    // safari ?
                {
                  dstNode.innerHTML = "";
                  for(n=newContent.firstChild; n; n=n.nextSibling)
                    docImport(n, dstNode);  // assez rapide sous safari, tres tres lent sous explorer
                }
                else
                {
                  var t = "";
                  for(n=newContent.firstChild; n; n=n.nextSibling)
                    t += n.xml;    // marche pas sous safari...
                  // IE fails to recognize <tag/> as a closed tag when using innerHTML, so replace it with <tag></tag>
                  // except for br, img, and hr elements.
                  var expr = new RegExp("<(?:(?!br|img|hr)([a-zA-Z]+))([^>]*)/>", "ig");
                  t = t.replace(expr, "<$1$2></$1>");

                  dstNode.innerHTML = t;  // rapide sous explorer, tres tres lent sous safari
                }
              }

              function cbDD_T1(event, type, eventObj)
              {
                ret = true;
                switch(type)
                {
                  case "RMOUSEDOWN":
                    if(o = eventObj.Src0)
                    {
                      switch(o.id.substr(0, 4))
                      {
                        case "THE_":    // le terme
                          myGUI.select(o);
                          document.getElementById("ktermMenu").runAsMenu( event, {id:o.id} );
                          ret = false;
                          break;
                      }
                    }
                    break;
                  case "MOUSEDOWN":
                    if(o = eventObj.Src0)
                    {
                      switch(o.id.substr(0, 4))
                      {
                        case "THP_":  // + ou - devant un terme
                          thid = o.id.substr(4);
                          if(the = document.getElementById("THE_"+thid))
                            myGUI.select(the);
                          if(o.className=="" && (thb = document.getElementById("THB_"+thid)))
                          {
                            if(thb.className == "ob")
                            {
                              new_thb = reloadbranch(thb, thid, "TH");
                              eventObj.Src0.innerHTML = "-";
                              new_thb.className = "OB";
                            }
                            else
                            {
                              eventObj.Src0.innerHTML = "+";
                              thb.className = "ob";
                            }
                          }
                          ret = false;  // empêchera le drag/drop à partir du +-
                          break;
                        case "THE_":  // terme
                          myGUI.select(o);
                          break;
                      }
                    }
                    break;
                  case "DBLCLICK":
                    if(o = eventObj.Src0)
                    {
                      if(o.id.substr(0, 4)=="THE_" && o.id.substr(4)!="T")
                      {
                        var myObj = { "win":window };
                        url  = "properties.php";
                        url += "?bid=<?php echo $parm["bid"] ?>";
                        url += "&piv=<?php echo $parm["piv"] ?>";
                        url += "&id=" + o.id.substr(4);
                        url += "&typ=TH";
                        url += "&dlg=1";
                        ret = window.showModalDialog(url, myObj, "dialogHeight:340px; dialogWidth:500px; center:yes; help:no; resizable:no; scroll:no; status:no; unadorned:yes");
                      }
                    }
                    break;
                  case "BALLOON":
                    if(o = eventObj.Src0)
                    {
                      if(o.id.substr(0, 4)=="THE_") // && o.id.substr(4)!="T")
                      {
                        var url   = "xmlhttp/getterm.x.php";
                        var parms = "bid=<?php echo $parm["bid"] ?>";
                        parms    += "&piv=<?php echo $parm["piv"] ?>";
                        parms    += "&sortsy=0";
                        parms    += "&id=" + o.id.substr(4);
                        parms    += "&typ=TH";
                        parms    += "&nots=1";
                        // alert(url);

                        var ret = loadXMLDoc(url, parms, true);
                        var syl = ret.getElementsByTagName("sy_list");
                        var ret = loadXMLDoc(url, parms, true);
                        var html = buidTermBalloon(ret);
                        if(html)
                          myGUI.showBalloon(html);
                      }
                    }
                    break;
                  case "DRAGLEAVE":
                    if(lo = eventObj.lastTarget0)
                      lo.style.backgroundColor = ""; // eventObj.lastTarget0Style;
                    break;
                  case "DRAGOVER":
                    //  if(cp = document.getElementById("clipboard"))
                    {
                      // o.style.position = "absolute";
                      //    cp.style.pixelLeft = eventObj.X+10;
                      //    cp.style.pixelTop = eventObj.Y+10;
                      // o.style.backgroundColor = "#ffff00";
                      // o.style.zIndex = 2;
                      if(o = eventObj.Target0)
                      {
                        if(lo = eventObj.lastTarget0)
                        {
                          lo.style.backgroundColor = ""; // eventObj.lastTarget0Style;
                          // lo.style.border = "1px none #ffff00"; // eventObj.lastTarget0Style;
                          //            lo.style.color="#ff00ff";
                        }
                        if(o.id.substr(0, 4) == "THE_") // && o.id != "THE_T")  // pas de drop e la racine du thesaurus
                        {
                          // myGUI.select(o);
                          //eventObj.lastTarget0Style = o.style.borderBottom;
                          o.style.backgroundColor =  "#99a2d0";
                          // o.style.border = "1px solid #99a2d0";
                          //          o.style.color="#000000";
                          eventObj.lastTarget0 = o;
                        }
                      }
                    }
                    break;
                  case "DROP":
                    if(lo = eventObj.lastTarget0)
                      lo.style.backgroundColor = "";

                    if(cp = document.getElementById("clipboard").firstChild)
                    {
                      if(tgt0 = eventObj.Target0)
                      {
                        var myObj = { "win":window };
                        url  = "accept.php";
                        url += "?bid=<?php echo $parm["bid"] ?>";
                        url += "&piv=<?php echo $parm["piv"] ?>";
                        url += "&src=" + eventObj.Src0.id.substr(4);
                        url += "&tgt=" + tgt0.id.substr(4);
                        w = window.open(url, "ACCEPT", "directories=no, height=300, width=500, location=no, menubar=no, resizable=yes, scrollbars=no, status=no, toolbar=no");
                      }
                    }
                    break;
                  }

                  return(ret);
                }

                function cbDD_TabT1(event, type, eventObj)
                {
                  ret = true;
                  switch(type)
                  {
                    case "RMOUSEDOWN":
                      document.getElementById("kThMenu").runAsMenu( event, null );
                      break;
                    case "MOUSEDOWN":
                      break;
                    case "DBLCLICK":
                      break;
                    case "DRAGLEAVE":
                      break;
                    case "DRAGOVER":
                      break;
                    case "DROP":
                      break;
                  }

                  return(ret);
                }



                // gui callback du menu contextuel sur l'onglet 'thesaurus'
                function cbME_kTh(action, cbParm, menuelem_id)
                {
                  // alert("id=" + cbParm.id + "menuelem_id='" + menuelem_id + "'");
                  // alert("cbParm.obj={obj:'" + cbParm.obj + "', id:'" + cbParm.id + "'} ; menuelem_id='" + menuelem_id + "'");
                  var om;
                  switch(action)
                  {
                    case "INIT":
                      // last chance to change menu content
                      break;
                    case "SELECT":
                      switch(menuelem_id)
                      {
                        case "kth_import": // importer
                          var myObj = { "win":window };
                          url  = "import_dlg.php?piv=<?php echo $parm["piv"] ?>&bid=<?php echo $parm["bid"] ?>&id=&dlg=1";
                          window.showModalDialog(url, myObj, "dialogHeight:400px; dialogWidth:600px; center:yes; help:no; resizable:yes; scroll:no; status:no; unadorned:yes");
                          //          url  = "import_dlg.php?piv=<?php echo $parm["piv"] ?>&bid=<?php echo $parm["bid"] ?>&id=";
                          //          w = window.open(url, "IMPORT", "directories=no, height=300, width=500, location=no, menubar=no, resizable=yes, scrollbars=no, status=no, toolbar=no");
                          break;
                        }
                        break;
                      }
                    }

                    function docImport(src, dst)
                    {
                      var n, nn;
                      // alert(src.nodeType);
                      switch(src.nodeType)
                      {
                        case 1:  // element
                          // alert(src.nodeName);
                          nn = dst.appendChild(document.createElement(src.nodeName))
                          if(v = src.getAttribute("id"))
                            nn.id = v;
                          if(v = src.getAttribute("name"))
                            nn.name =  v;
                          if(v = src.getAttribute("class"))
                            nn.className =  v;
                          if(v = src.getAttribute("style"))
                            nn.setAttribute("style", v);
                          for(n=src.firstChild; n; n=n.nextSibling)
                            docImport(n, nn);
                          break;
                        case 3: // text
                          // alert(src.nodeValue);
                          nn = dst.appendChild(document.createTextNode(src.nodeValue))
                          break;
                      }
                    }


                    function reloadbranch2(thb, thid, typ)
                    {
                      new_thb = null;
                      url = "xmlhttp/gethtmlbranch.x.php";
                      parms  = "bid=<?php echo $parm["bid"] ?>";
                      parms += "&id=" + thid;
                      parms += "&typ=" + typ;

                      alert(url + "?" + parms);

                      ret = loadXMLDoc(url, parms, true);

                      alert(ret);

                      ts = ret.getElementsByTagName("html");
                      if(ts.length==1)
                      {
                        /*
thb.innerHTML = ts.item(0).firstChild.nodeValue;
                         */
                        if(document.importNode)    // safari ?
                        {
                          thb.innerHTML = "";
                          for(n=ts.item(0).firstChild; n; n=n.nextSibling)
                            docImport(n, thb);

                        }
                        else
                        {
                          t = "";
                          for(n=ts.item(0).firstChild; n; n=n.nextSibling)
                            t += n.xml;    // marche pas sous safari...
                          // IE fails to recognize <tag/> as a closed tag when using innerHTML, so replace it with <tag></tag>
                          // except for br, img, and hr elements.
                          var expr = new RegExp("<(?:(?!br|img|hr)([a-zA-Z]+))([^>]*)/>", "ig");
                          t = t.replace(expr, "<$1$2></$1>");

                          // alert(t);

                          thb.innerHTML = t;

                        }
                      }
                    }

                    myGUI.setClickable("T0", cbDD_T0);

                    myGUI.setDraggable("T0", cbDD_T0);


                    myGUI.setClickable("TabT1Title", cbDD_TabT1);



                    myGUI.setClickable("T1", cbDD_T1);

                    myGUI.setDroppable("T1", cbDD_T1);

                    // ttttttttttttttttttttttttttttttttttttttt
                    // myGUI.setAsMenu("flagsMenu", cbME_flags);

                    myGUI.setAsMenu("kctermMenu", cbME_kcterm);

                    myGUI.setAsMenu("ktermMenu", cbME_kterm);

                    myGUI.setAsMenu("kThMenu", cbME_kTh);
                    /*
                     */

    </script>

  </body>

</html>


