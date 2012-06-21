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

$request = http_request::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", // base_id
                            "p1", // coll_id
                            "str" // si act=CHGSTRUCTURE, structure en xml
);

phrasea::headers();

$collname = phrasea::bas_names($parm["p1"]);

$sbas_id = phrasea::sbasFromBas($parm["p1"]);
$coll_id = phrasea::collFromBas($parm['p1']);

$databox = databox::get_instance($sbas_id);
$collection = collection::get_from_coll_id($databox, $coll_id);

if ($parm["act"] == "CHGSUGVAL") {
    if ($mdesc = DOMDocument::loadXML($parm["str"])) {
        $collection->set_prefs($mdesc);
    }
}

$curPrefs = $collection->get_prefs();
?>

<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
    </head>

    <link type="text/css" rel="stylesheet" href="/include/minify/f=include/jslibs/yui2.8/build/reset/reset.css,include/jslibs/jquery-ui-1.8.17/css/ui-lightness/jquery-ui-1.8.17.custom.css,include/jslibs/jquery-treeview/jquery.treeview.css,skins/common/main.css,skins/admin/admincolor.css,login/geonames.css,include/jslibs/jquery.contextmenu.css" />

    <style type="text/css">
        BODY
        {
            margin:10px;
        }
        .divTop
        {
            background-color : #ffffff;
            MARGIN-LEFT: 120px;
            OVERFLOW: hidden;
            WIDTH: 427px;
            WIDTH: 440px;
        }
        .tableTop
        {
            WIDTH: 720px;
        }
        .divCenter
        {
            background-color : #ffffff;
            RIGHT: 3px;
            OVERFLOW: auto;
            WIDTH: 443px;
            POSITION: relative;
            HEIGHT: 100%;

            text-align:left
        }
        .classdivtable
        {
            background-color : #FFFFFF;
            WIDTH: 566px;
            height:500px;
            text-align:left
        }
        .divLeft
        {
            background-color : #FFFFFF;
            DISPLAY: inline;
            FLOAT: left;
            OVERFLOW: hidden;
            WIDTH: 120px;
            HEIGHT: 118px;
            HEIGHT: 100%;
        }
        .tableLeft
        {
            table-layout:fixed;
            WIDTH: 120px;
        }
        .trLeft
        {
            HEIGHT: 25px;
            OVERFLOW: hidden;
        }
        .tdLeft
        {
            WIDTH: 120px;
            HEIGHT: 25px;
            OVERFLOW: hidden;
        }
        .divTdLeft
        {
            background-color:#ffffff;
            WIDTH: 117px;
            OVERFLOW: hidden;
            font-size:10px
        }
        .tableCenter
        {
            WIDTH: 720px;
            background-color:#ffffff;
            position:relative;
            top:0px;
            left:0px;

        }
        .tdTableCenter
        {
            OVERFLOW: hidden;
            WIDTH: 18px;
            HEIGHT: 25px;
            text-align:center;
        }
        .divTdTableCenter
        {
            OVERFLOW: hidden;
            WIDTH: 18px;
            text-align:center;

        }
        *
        {
            margin:0;
            padding:0;
        }
        #tableau table
        {
        }
        #tableau td, #tableau th
        {
            border-right:#CCCCCC 1px solid ;
        }
        .iptIdt
        {
            width:190px;
            border:#cccccc 1px solid;
            font-size:11px;
        }

        .desktopMenu
        {
            BORDER-RIGHT: 2px outset;
            BORDER-TOP: 2px outset;
            DISPLAY: none;
            FONT-SIZE: 8pt;
            Z-INDEX: 100;
            LEFT: 500px;
            VISIBILITY: visible;
            OVERFLOW: hidden;
            BORDER-LEFT: 2px outset;
            WIDTH: 150px;
            COLOR: #000000;
            BORDER-BOTTOM: 2px outset;
            POSITION: absolute;
            TOP: 300px;
            BACKGROUND-COLOR: #d4d0c9;
            TEXT-DECORATION: none
        }
        .desktopMenu A
        {
            DISPLAY: block;
            PADDING-LEFT: 5px;
            LEFT: 0px;
            WIDTH: 100%;
            COLOR: #000000;
            PADDING-TOP: 2px;
            WHITE-SPACE: nowrap;
            POSITION: relative;
            TOP: 0px;
            HEIGHT: 16px;
            BACKGROUND-COLOR: #d4d0c9;
            TEXT-DECORATION: none
        }
        .desktopMenu A:hover
        {
            COLOR: #ffffff;
            BACKGROUND-COLOR: #191970;
            TEXT-DECORATION: none
        }

    </style>
    <script type="text/javascript">

        var allgetID = new Array ;
        var total = 0;
        var statuscoll="";
        var changeInXml = false ;
        var avantModif="";
        function chgXml()
        {
            inxml = document.getElementById("txtareaxml").value;
            inxml2="";
            for (i=0; i<inxml.length;i++) {
                if(inxml.charCodeAt(i)!=13)
                    inxml2+=inxml.substr(i,1);

            }

            if( avantModif!= inxml2 )
                changeInXml = true ;

        }

        function tableScroll(theTable)
        {
            var TableId = theTable.id.replace("_center", "");
            var tableTop = document.getElementById(TableId+"_top");
            var tableLeft = document.getElementById(TableId+"_left");

            document.getElementById("imgtopinclin").style.width = "700px";

            tableTop.scrollLeft = theTable.scrollLeft;
            tableLeft.scrollTop = theTable.scrollTop;
        }
        function addEvent(obj, evType, fn, useCapture)
        {
            if (obj.addEventListener) {
                obj.addEventListener(evType, fn, useCapture);

                return true;
            } else {
                if (obj.attachEvent) {
                    var r = obj.attachEvent("on"+evType, fn);

                    return r;
                } else {
                    alert("Handler could not be attached");
                }
            }
        }
        /* Chargement de tt les elements dans un tableau pour un acces plus rapide */
        function scandom(node, depth)
        {
            var n;
            if(!node)

            return;
        if (node.id) {
            allgetID[node.id] = node;
            //node.style.visibility = "hidden";
            total++;
        }
        for (n=node.firstChild; n; n=n.nextSibling) {
            if(n.nodeType && n.nodeType == 1)
                scandom(n, depth+1);
        }
    }

    window.onload=function() {
        redrawme();
        scan();
        document.getElementById("iddivloading").style.visibility = "hidden";
    }
    public function scan()
    {
        if(document.all)
            scandom(document.documentElement, 0);
        else {
            allccuser = document.getElementsByName("ccuser");
            for (var i=0; i<allccuser.length;i++) {
                allgetID[allccuser[i].id] = allccuser[i];
                total++ ;
            }
        }

    }

    public function view(typeDiv)
    {

        switch (typeDiv) {
            case "RIGHTS":
                if ( document.getElementById( "divGraph") ) {
                    document.getElementById( "divGraph").style.visibility = "visible";
                    document.getElementById( "divGraph").style.display = "";
                }
                if( oo=returnElement("genecancel") )
                    oo.style.visibility = "visible" ;
                if( oo=returnElement("genevalid") )
                    oo.style.visibility = "visible" ;
                break;

        }
    }

    var pass=false;
    public function redrawme()
    {

        wb = document.getElementById("divref").offsetWidth;

        if(wb<150)
            wb= 150;

        document.getElementById("presentUser").style.width = (wb-10)+"px";

        /***************************************************/
        if (document.all) {
            if(document.documentElement.clientHeight)
                bodyH = document.documentElement.clientHeight - 5 ;
            else
                bodyH = document.body.clientHeight - 5 ;
            scrollLeft = null;
            if(document.documentElement.scrollLeft)
                scrollLeft = document.documentElement.scrollLeft;
            else
                scrollLeft = document.body.scrollLeft;
            scrolltop = null;
            if (document.documentElement.scrollTop) {
                scrolltop = document.documentElement.scrollTop;
                document.documentElement.scrollTop = 0 ;
            } else {
                scrolltop = document.body.scrollTop;
                document.body.scrollTop=0;

            }
        } else {

            bodyH =  parent.window.document.clientHeight;
            if(!bodyH)
                if(document.documentElement.clientHeight)
                    bodyH = document.documentElement.clientHeight - 5 ;
            else
                bodyH = document.body.clientHeight - 5 ;
            scrollLeft = null;
            if(document.documentElement.scrollLeft)
                scrollLeft = document.documentElement.scrollLeft;
            else
                scrollLeft = document.body.scrollLeft;
            scrolltop = null;
            if (document.documentElement.scrollTop) {
                scrolltop = document.documentElement.scrollTop;
                document.documentElement.scrollTop = 0;
            } else {
                scrolltop = document.body.scrollTop;
                document.body.scrollTop=0;
            }
        }
        /***************************************************/
        hauteur =  document.getElementById("spanref").offsetTop;
        hauteur =  bodyH-10;
        if (hauteur<10) {

            hauteur = document.getElementById("spanref").clientHeight;
        }
        if(hauteur<350)
            hauteur= 350;

        document.getElementById("divGraph").style.height = (hauteur-160)+"px";
        document.getElementById("tabsgv").style.height = (hauteur-160)+"px";
        document.getElementById("trajout").style.height = "50px";
        if( (hauteur-280)>10)
            document.getElementById("valsug2").style.height = (hauteur-280)+"px";
        else
            document.getElementById("valsug2").style.height = "10px";

        document.getElementById("txtareaxml").style.height = (hauteur-160)+"px";

        if (o = returnElement("iddivloading") ) {
            o.style.width = (wb-18)+"px";
            o.style.left = "10px";
            o.style.top = "95px";
            o.style.height = (hauteur-160)+"px";
        }
    }

    public function returnElement(unId)
    {
        if (! allgetID[unId] ) {
            if ( document.getElementById(unId) ) {
                allgetID[unId] = document.getElementById(unId);
            }
        }

        return allgetID[unId];
    }

    var pref = new Array(0);
    var lastpref=null;

    public function loaded()
    {
        self.focus();
        write_valsug();
        makeRestrict();
        maketextaffich();
        makeEmpty();
        redrawme();
        scan();
        document.getElementById("iddivloading").style.visibility = "hidden";
    }
    public function Roll(im, x)
    {
        var s=document[im].src;
        var d=s.substr(0,s.length-5)
        document[im].src = d + x + ".gif";
    }

    public function valeursPref(nomaff,valsug)
    {
        this.nomaff = nomaff;
        this.Type = "text";
        this.content = "none" ;
        this.empty = true;
        this.valsug = new Array(0);

        return(this);
    }
    public function savenomaff()
    {
        if ( (o = document.getElementById("namAff"))  != null  && lastpref!=null)
            pref[lastpref].nomaff = o.value ;
    }
    // ecrit le select des valsug
    public function write_valsug()
    {
        if ( (o = document.getElementById("nomchamPH"))  != null ) {
            if ((o2 = document.getElementById("valsug")) != null) {
                lastpref =  o.value;

                p = document.getElementById("valsug2");

                p.options.length = 0;
                for (i=0; i<pref[o.value].valsug.length; i++) {
                    if(pref[o.value].valsug[i])
                        x = p.options[p.options.length] = new Option(unescape(pref[o.value].valsug[i]), pref[o.value].valsug[i]);
                }
            }
        }
    }

    public function desactivall4VS()
    {
        activer_bout('bout_supp',true);
        activer_bout('bout_mont',true);
        activer_bout('bout_desc',true);
        if ((o2 = document.getElementById("valsug2")) != null) {
            o2.selectedIndex =-1;
        }
    }

    public function desactiv4VS()
    {
        if ((o2 = document.getElementById("valsug2")) != null) {
            if (o2.options.length >1) {

                if((o2.selectedIndex+1) != o2.options.length)
                    activer_bout('bout_desc',false);
                else
                    activer_bout('bout_desc',true);

                if(o2.selectedIndex!=0 && o2.options.length>1)
                    activer_bout('bout_mont',false);
                else
                    activer_bout('bout_mont',true);
            } else {
                activer_bout('bout_desc',true);
                activer_bout('bout_mont',true);
            }

        }
    }
    public function activ4VS()
    {
        if ((o2 = document.getElementById("valsug2")) != null) {
            if(o2.selectedIndex ==-1)

            return;
    }
    desactiv4VS();
    activer_bout('bout_supp',false);
  }

  // supprime une valsug
  function supprimer()
  {

    lastIdx = null;

    if ((o2 = document.getElementById("valsug2")) != null) {
        o = document.getElementById("nomchamPH");
        var ancienfocus = o2.selectedIndex;
        // pref[o.value].valsug.splice(o2.selectedIndex,1);
        var bb =0;
        lastIdx = (pref[o.value].valsug.length)-1;
        for ( aa in pref[o.value].valsug) {
            if (aa != o2.selectedIndex) {
                pref[o.value].valsug[bb]=pref[o.value].valsug[aa];
                if (aa+1 == pref[o.value].valsug.length) {
                    pref[o.value].valsug[bb]=null;
                }
                bb++;
            }
        }
        pref[o.value].valsug[bb]="";

        if (lastIdx!=null && lastIdx>=0) {
            delete(pref[o.value].valsug[lastIdx]);
            pref[o.value].valsug.length--;
        }

        write_valsug();
        o2 = document.getElementById("valsug2");

        var i = o2.options.length ;  // au depart i=1 et ancienfocus=1
        o2.selectedIndex = -1;
        while ( i>-1 ) {
            if (o2.options.length>= ancienfocus+1) {
                o2.selectedIndex = ancienfocus;
                i==0;
            } else {
                ancienfocus--;
            }
            i--;
        }
        if(o2.selectedIndex<0)
            activer_bout('bout_supp',true);
        desactiv4VS();

    }

  }

  // ajoute une valsug
  function ajouter()
  {
    o2 = document.getElementById("valajout");
    var test = false;
    for (var k=0; k<o2.value.length; k++) {
        if(o2.value.charAt(k)!=" ")
            test=true;
    }
    if (!test) {
        o2.value="";
        verifAndactiv();
        o2.focus();
    } else {
        if (o2.value!="" && o2.value!=null) {
            o = document.getElementById("nomchamPH");
            // pref[o.value].valsug.push( UtEncode(o2.value) );
            // pref[o.value].valsug[pref[o.value].valsug.length]= UtEncode(o2.value);
            pref[o.value].valsug[pref[o.value].valsug.length]= escape(o2.value);
            o2.value="";
        }
        write_valsug();
        if ( test && (o2 = document.getElementById("valsug2")) != null) {
            o2.selectedIndex = o2.options.length-1 ;
            activer_bout('bout_add',true);
            activ4VS();
            desactiv4VS();
        }
    }
  }
  function UtEncode(expr)
  {
    var expretour ="";
    for (i=0; i<expr.length; i++) {
        if( (expr.charCodeAt(i) >= 65 && expr.charCodeAt(i) <=90) || (expr.charCodeAt(i) >= 97 && expr.charCodeAt(i) <=122) )
            expretour += expr.substring(i,i+1);
        else
            expretour += "&#" + expr.charCodeAt(i)+";";
    }

    return (expretour);
  }
  function verifAndactiv()
  {
    if (o = document.getElementById("valajout") ) {
        if(o.value.length>0)
            activer_bout('bout_add',false);
        else
            activer_bout('bout_add',true);
    }
  }
  function desactiver()
  {
    // desactivation du bouton ajouter
    if (o = document.getElementById("valajout") ) {
        o.value = "";
        activer_bout("bout_add",true);
    }
  }

  function valid()
  {
    savenomaff();
    var lexmlstruct = '';

    if (document.getElementById('divGraph') && document.getElementById('divGraph').style.display=="") {
        lexmlstruct = getSruct();
    } elseif ( document.getElementById('divXml')  && document.getElementById('divXml').style.display=="" ) {
        lexmlstruct = document.getElementById('txtareaxml').value;
    }
    if (document.getElementById("idstr")) {
        document.getElementById("idstr").value = lexmlstruct ;

        document.forms["chgStructure"].act.value = "CHGSUGVAL";
        document.forms["chgStructure"].target = "";
        document.forms["chgStructure"].submit();
    }
  }
  function modifordre(bool)
  {
    //bool : true pour monter  -- false pour descendre
    if ((o2 = document.getElementById("valsug2")) != null) {
        if ( (o = document.getElementById("nomchamPH"))  != null ) {
            var ancienind = o2.selectedIndex;
            var tmp = pref[o.value].valsug[o2.selectedIndex];
            if (bool) {
                pref[o.value].valsug[o2.selectedIndex] = pref[o.value].valsug[o2.selectedIndex-1] ;
                pref[o.value].valsug[o2.selectedIndex-1] = tmp ;
                ancienind--;
            } else {
                pref[o.value].valsug[o2.selectedIndex] = pref[o.value].valsug[o2.selectedIndex+1] ;
                pref[o.value].valsug[o2.selectedIndex+1] = tmp ;
                ancienind++;
            }
            write_valsug();
            o2 = document.getElementById("valsug2");
            o2.selectedIndex = ancienind;
            desactiv4VS();
        }
    }

  }
  function trialph()
  {

    if ((o2 = document.getElementById("valsug2")) != null) {
        if ( (o = document.getElementById("nomchamPH"))  != null ) {
            pref[o.value].valsug.sort();
            write_valsug();
            desactiv4VS();
            desactivall4VS()

        }
    }
  }

  function maketextaffich()
  {
    if ( (o = document.getElementById("nomchamPH"))  != null ) {
        var tmp = "idtext";
        if(pref[o.value].Type != null )
            tmp = "id" + pref[o.value].Type;
        if ( (o3 = document.getElementById(tmp))  != null )
            o3.checked = true;
    }
  }
  function makeRestrict()
  {
    if ( (o = document.getElementById("nomchamPH"))  != null ) {
        var tmp = "none";
        if(pref[o.value].content!=null )
            tmp= pref[o.value].content;
        if ( (o3 = document.getElementById(tmp))  != null )
            o3.checked = true;
    }
  }
  function makeEmpty()
  {
    if ( (o = document.getElementById("nomchamPH"))  != null ) {
        var tmp = "empty";
        if(!pref[o.value].empty)
            tmp = "no"+tmp;
        if ( (o3 = document.getElementById(tmp))  != null )
            o3.checked = true;
    }
  }
  function chgEmpty(bool)
  {
    if ( (o = document.getElementById("nomchamPH"))  != null )
        pref[o.value].empty = bool;
  }
  function chgType(type)
  {
    if ( (o = document.getElementById("nomchamPH"))  != null )
        pref[o.value].Type = type;
  }

  function chgrestrict(nomRestrict)
  {
    if ( (o = document.getElementById("nomchamPH"))  != null )
        pref[o.value].content=nomRestrict;
  }

  function activer_bout(idBout,val)
  {
    if(o = document.getElementById(idBout) )
        o.disabled = val;
  }
  otherFields = "";
<?php
$structfields = null;
$databox = databox::get_instance($sbas_id);

foreach ($databox->get_meta_structure() as $meta) {
    if ($meta->is_readonly())
        continue;
    $ki = $meta->get_name();
    $structfields[$ki] = true;
    ?>
      pref["<?php echo $ki ?>"] = new valeursPref("<?php echo $ki ?>");
      pref["<?php echo $ki ?>"].nomaff="<?php echo $ki ?>";
      pref["<?php echo $ki ?>"].Type="text";
      pref["<?php echo $ki ?>"].content="none";
      pref["<?php echo $ki ?>"].empty=true;

    <?php
}

if (isset($curPrefs)) {
    if ($sxe = simplexml_load_string($curPrefs)) {
        $z = $sxe->xpath('/baseprefs/sugestedValues');
        if ($z && is_array($z)) {
            $f = 0;
            foreach ($z[0] as $ki => $vi) {
                if ($vi && isset($structfields[$ki])) {
                    foreach ($vi->value as $oneValue) {
                        ?>
                          pref["<?php echo p4string::JSstring($ki) ?>"].valsug[<?php echo $f ?>] = unescape("<?php echo p4string::JSstring($oneValue) ?>");
                        <?php
                        $f ++;
                    }
                }
            }
        }

        $z = $sxe->xpath('/baseprefs');
        if ($z && is_array($z)) {
            foreach ($z[0] as $ki => $vi) {

                if ($ki == "status") {
                    ?>
                      statuscoll ="<status><?php echo $vi ?></status>";
                    <?php
                } elseif ($ki != "sugestedValues") {
                    $lexml = $vi->asXML();
                    echo "\notherFields +=\"\\n\\t" . p4string::MakeString($lexml, "js") . "\";";
                }
            }
        }
    }
}
?>

  function getSruct()
  {
  var lexmlstruct = '<'+ '?xml version="1.0" encoding="UTF-8"?'+'>\n<baseprefs>\n';
  lexmlstruct += "\t" + statuscoll+"\n";
  if(otherFields!="")
      lexmlstruct += "\t" + otherFields+"\n";
  lexmlstruct +='\t<sugestedValues>\n';
  for (a in pref ) {
      lexmlstruct2 = "";
      for (b in pref[a].valsug ) {
          // rempl
          var reg=new RegExp("&", "g");
          var reg2=new RegExp("<", "g");
          var reg3=new RegExp(">", "g");
          pref[a].valsug[b] = unescape(pref[a].valsug[b]).replace(reg,"&amp;");
          pref[a].valsug[b] = pref[a].valsug[b].replace(reg2,"&lt;");
          pref[a].valsug[b] = pref[a].valsug[b].replace(reg3,"&gt;");

          if(pref[a].valsug[b]!="")
              lexmlstruct2 += '\t\t\t<value>'+ unescape(pref[a].valsug[b]).replace(reg,"&amp;") + '</value>\n';
      }
      if (lexmlstruct2!="") {
          lexmlstruct += '\t\t<' + a + '>\n';
          //lexmlstruct += lexmlstruct2;

          lexmlstruct += lexmlstruct2;
          lexmlstruct += '\t\t</' + a + '>\n';

      }
  }
  lexmlstruct += '\t</sugestedValues>\n';
  lexmlstruct += '</baseprefs>';

  return(lexmlstruct);

  }
  function view(type)
  {
  switch (type) {
      case 'XML':
          if(document.getElementById('divGraph') )
              document.getElementById('divGraph').style.display = "none";
          if(document.getElementById('divXml') )
              document.getElementById('divXml').style.display = "";

          if(document.getElementById('linkviewgraph') )
              document.getElementById('linkviewgraph').style.display = "";
          if(document.getElementById('linkviewxml') )
              document.getElementById('linkviewxml').style.display = "none";

          newStr=getSruct();

          if (document.getElementById('txtareaxml') && newStr!=null  ) {
              avantModif =  newStr;
              document.getElementById('txtareaxml').value = newStr;
          }

          break;
      case 'GRAPH':
          if ( !changeInXml || confirm("<?php echo p4string::MakeString(_('admin::sugval: Attention, passer en mode graphique implique la perte des modifications du xml si vous n\'appliquez pas les changements avant.\nContinuer quand meme ?'), 'JS') ?>")) {
              if(document.getElementById('divGraph') )
                  document.getElementById('divGraph').style.display = "";
              if(document.getElementById('divXml') )
                  document.getElementById('divXml').style.display = "none";

              if(document.getElementById('linkviewgraph') )
                  document.getElementById('linkviewgraph').style.display = "none";
              if(document.getElementById('linkviewxml') )
                  document.getElementById('linkviewxml').style.display = "";
          }
          break;
      }

  }

    </script>

    <body id="idBody"  onResize="redrawme();"  onLoad="loaded();" >

        <form method="post" action="/admin/users/search/" target="_self" style="visibility:hidden; display:none" >
            <input type="text" name="ord" value="" />
            <input type="text" name="srt" value="" />
            <input type="text" name="act" value="?" />
            <input type="text" name="p0" value="" />
            <input type="text" name="p1" value="" />
            <input type="text" name="p2" value="5" />
            <input type="text" name="p3" value="?" />
            <input type="submit">
        </form>
        <h1><?php echo _('Suggested values') ?></h1>
        <div id="iddivloading" style="background-image:url(./trans.gif);BACKGROUND-POSITION: top bottom; BACKGROUND-REPEAT: repeat; border:#ff0000 3px solid;position:absolute; width:94%;height:80%; top:95px; left:10px;z-index:99;text-align:center"><table style='width:100%;height:100%; text-align:center;valign:middle:; color:#FF0000; font-size:16px'><tr><td><br><div style='background-color:#FFFFFF'><b><?php echo _('phraseanet::chargement') ?></b></div><br></td></tr></table></div>

        <span id="spanref" style="position:absolute; bottom:0px; left:5px;  background-color:#0f00cc; visibility:hidden">
            <img src="./pixel.gif" name="test_longueur" width="1" height="100%" align="left">
        </span>

        <div id="divref" >&nbsp;</div>

        <table id="presentUser" style="table-layout:fixed; width:100%;" border="0" cellpadding="0" cellspacing="0">

            <tr style="height:30px; " >
                <td style="height:30px; width:20px;  font-size:12px; border:0px; BACKGROUND-POSITION: left top;BACKGROUND-REPEAT: no-repeat">

                </td>

                <td style="text-align:center;height:30px;font-size:12px; border:0px;BACKGROUND-POSITION:0px 0px;BACKGROUND-REPEAT: repeat-x" nowrap>
<?php echo _('admin::sugval: Valeurs suggerees/Preferences de la collection') . "<b>" . $collname . "</b>" ?>
                </td>

                <td style="height:30px;width:20px;font-size:12px;border:0px; text-align:right">

                </td>

            </tr>

            <tr style="background-color:#aaaaaa; border:#cccccc 1px solid;"  >
                <td colspan="3" style=" border:#aaaaaa 1px solid;padding-left:3p;text-align:right">
                    <a href="javascript:void();" id="linkviewxml" onClick="view('XML');return(false);" style="color:#000000"><?php echo _('boutton::vue xml') ?></a>
                    <a href="javascript:void();" id="linkviewgraph" onClick="view('GRAPH');return(false);" style="color:#000000;display:none"><?php echo _('boutton::vue graphique') ?></a>
                </td>

            </tr>

            <tr style="background-color:#c9c9c9; border:#cccccc 1px solid;"  >
                <td colspan="3" style=" border:#c9c9c9 1px solid;padding-left:3p;text-align:center">

                    <div id="divGraph" style="background-color:#c9c9c9; width:100%; height:100%;overflow:hidden;" >

                        <table border="0" id="tabsgv" style="width:100%; background-color:#c9c9c9;">
                            <tr>
                                <td valign="top" style="text-align:center;">
<?php echo _('admin::sugval: champs') ?>
                                    <select  name=usrbases id="nomchamPH" onKeyUp="javascript:write_valsug();" onChange="javascript:savenomaff();makeRestrict();maketextaffich();makeEmpty();write_valsug();"   onclick="javascript: makeRestrict();desactivall4VS(); write_valsug();" >
<?php
$databox = databox::get_instance($sbas_id);

foreach ($databox->get_meta_structure() as $meta) {
    if ($meta->is_readonly())
        continue;
    echo "\n<option value='" . $meta->get_name() . "' >" . $meta->get_name() . "";
}
?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" style="text-align:center;">
                                    <br>
                                    <input type="submit" value="<?php echo _('admin::base:collorder: monter') ?>" disabled onclick="javascript:modifordre(true);" id="bout_mont" name="bout_mont">
                                    &nbsp;
                                    <input type="submit" value="<?php echo _('admin::base:collorder: descendre') ?>"  disabled onclick="javascript:modifordre(false);" id="bout_desc" name="bout_desc">
                                    &nbsp;
                                    <input type="submit" value="<?php echo _('boutton::supprimer') ?>" disabled onclick="javascript:supprimer();" ondblclick="javascript:supprimer();" id="bout_supp" name="bout_supp">
                                    &nbsp;
                                    <input type="submit" value="<?php echo _('admin::base:collorder: reinitialiser en ordre alphabetique') ?>" onclick="javascript:trialph();" ondblclick="javascript:trialph();" id="bout_trialph" name="bout_trialph">

                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <span id="valsug">
                                        <select size="12" name=valsug2 id="valsug2" onFocus="activ4VS();" onClick="desactiver();activ4VS();" onChange="desactiv4VS();"  style="width:100%;font-size:11px">
                                        </select>
                                    </span>
                                </td>

                            </tr>
                            <tr id="trajout" style="height:50px">
                                <td valign="top" style="height:50px">
                                    <table style="width:100%;">
                                        <tr>
                                            <td style="width:100%"><input type="text" id="valajout" onKeyUp="verifAndactiv();" onclick="desactivall4VS();" style="width:100%"></td>
                                            <td style="width:100px" ><input type="submit" value="<< <?php echo _('boutton::ajouter') ?>" disabled style="width:100px" onclick="javascript:ajouter();" id="bout_add" name="bout_add"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="divXml" style="display:none">
                        <TEXTAREA nowrap id="txtareaxml" onchange="chgXml();" style=" width:99%;white-space:pre" ><?php echo $curPrefs ?></TEXTAREA>
                    </div>
                </td>
            </tr>

            <tr style="height:30px;" >
                <td style="height:30px;width:20px;BACKGROUND-POSITION: 0px 0px;BACKGROUND-REPEAT:no-repeat">

                </td>
                <td style="height:30px;  BACKGROUND-POSITION:  0px 0px;BACKGROUND-REPEAT: repeat-x; font-size:12px;text-align:center;">
                    <b><a href="javascript:void();" onclick="valid();return(false);"  id="genevalid" style="color:#000000;text-decoration:none"><?php echo _('boutton::valider') ?></a> </b>
                </td>
                <td style="height:30px;width:20px;font-size:12px; BACKGROUND-POSITION:left ;">
                </td>
            </tr>
        </table>
        <form method="post" name="chgStructure" action="./sugval.php" onsubmit="return(false);" target="???" style="visibility:hidden;">
            <input type="hidden" name="act" value="???" />
            <input type="hidden" name="p0" value="<?php echo $parm["p0"] ?>" />
            <input type="hidden" name="p1" value="<?php echo $parm["p1"] ?>" />
            <TEXTAREA nowrap style="visibility:hidden;white-space:pre\" name="str" id="idstr"><?php echo $parm["str"] ?></TEXTAREA>
        </form>

    </body>
</html>

