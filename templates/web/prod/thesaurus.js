function thesau_show()
{
  if(p4.thesau.currentWizard == "???")	// first show of thesaurus
    thesauShowWizard("wiz_0", false);
}

function thesauCancelWizard()
{
  thesauShowWizard("wiz_0", true);
}

function thesauShowWizard(wizard, refreshFilter)
{
  if(wizard != p4.thesau.currentWizard)
  {
    $("#THPD_WIZARDS DIV.wizard", p4.thesau.tabs).hide();
    $("#THPD_WIZARDS ." + wizard, p4.thesau.tabs).show();
    $("#THPD_T_treeBox", p4.thesau.tabs).css('top', $("#THPD_WIZARDS", p4.thesau.tabs).height());

    p4.thesau.currentWizard = wizard;

    if(refreshFilter)
      T_Gfilter_delayed($('#THPD_WIZARDS .gform', p4.thesau.tabs).eq(0).val(), 0);

    if(wizard == "wiz_0")	// browse
      $("#THPD_WIZARDS .th_cancel", p4.thesau.tabs).hide();
    else
      $("#THPD_WIZARDS .th_cancel", p4.thesau.tabs).show();

    if(wizard == "wiz_1")	// accept
      $("#THPD_WIZARDS .th_ok", p4.thesau.tabs).hide();
    else
      $("#THPD_WIZARDS .th_ok", p4.thesau.tabs).show();

    $("#THPD_WIZARDS FORM :text")[0].focus();
  }
}



// here when the 'filter' forms is submited with key <enter> or button <ok>
// force immediate search
function T_Gfilter(o)
{
  var f;
  if(o.nodeName=="FORM")
    f = $(o).children(":text").val();
  else if(o.nodeName=="INPUT")
    f = o.value;

  T_Gfilter_delayed(f, 0);

  switch(p4.thesau.currentWizard)
  {
    case "wiz_0":	// browse
      break;
    case "wiz_1":	// accept
      break;
    case "wiz_2":	// replace
      T_replaceBy2(f);
      break;
    default:
      break;
  }
}

// here when a key is pressed in the 'filter' form
function T_Gfilter_delayed(f, delay)
{
  switch(p4.thesau.currentWizard)
  {
    case "wiz_0":	// browse
      T_filter_delayed2(f, delay, "ALL");
      break;
    case "wiz_1":	// accept
      T_filter_delayed2(f, delay, "CANDIDATE");
      break;
    case "wiz_2":	// replace
      T_filter_delayed2(f, delay, "CANDIDATE");
      break;
    default:
      break;
  }
}


function T_replaceBy2(f)
{
  if(trees.C._selInfos.n != 1)
    return;
  var msg;
  var term = trees.C._selInfos.sel.eq(0).find("span span").html();

  var cid  = trees.C._selInfos.sel[0].getAttribute('id').split('.');
  cid.shift();
  var sbas = cid.shift();
  cid = cid.join('.');

  trees.C._toReplace = { 'sbas':sbas, 'cid':cid, 'replaceby':f };

  {% set message %}
   {% trans 'prod::thesaurusTab:dlg:Remplacement du candidat "%(from)s" par "%(to)s"' %}
  {% endset %}

  var msg = $.sprintf("{{ message | e('js') }}", {'from':term, 'to':f});

  var confirmBox = p4.Dialog.Create({
      size : 'Alert',
      closeOnEscape : true,
      cancelButton: true,
      buttons: {
          "Ok" : function() {
              confirmBox.Close();
              T_replaceCandidates_OK();
          }
      }
  });
  confirmBox.setContent(msg);
}


function T_filter_delayed2(f, delay, mode)
{
  if(this.timer)
  {
    window.clearTimeout(this.timer);
    this.timer = null;
    for(i in sbas)
    {
      if(sbas[i].seeker)
        sbas[i].seeker.abort();
    }
  }


  if(delay < 10)
    delay = 10;
  this.timer = window.setTimeout(
  function()
  {
    if(mode=='ALL')
    {
      // search in every base, everywhere
      for(i in sbas)
      {
        var zurl = "/xmlhttp/search_th_term_prod.j.php"
          + "?sbid=" + sbas[i].sbid
          + "&t=" + encodeURIComponent(f);

        sbas[i].seeker = $.ajax({
          url: zurl,
          type:'POST',
          data: [],
          dataType:'json',
          success: function(j)
          {
            var z = '#TX_P\\.' + j.parm['sbid'] + '\\.T';

            var o = $(z);
            var isLast = o.hasClass('last');

            o.replaceWith(j.html);

            if(isLast)
              $(z).addClass('last');
          },
          error:function(){

          },
          timeout:function(){

          }
        });
      }
    }
    else if(mode=='CANDIDATE')
    {
      // search only on the good base and the good branch(es)
      for(i in sbas)
      {
        var zurl = "/xmlhttp/search_th_term_prod.j.php"
          + "?sbid=" + sbas[i].sbid;

        if(sbas[i].sbid == trees.C._selInfos.sbas)
        {
          zurl += "&t=" + encodeURIComponent(f)
            + "&field=" + encodeURIComponent(trees.C._selInfos.field);
        }
        sbas[i].seeker = $.ajax({
          url: zurl,
          type:'POST',
          data: [],
          dataType:'json',
          success: function(j)
          {
            var z = '#TX_P\\.' + j.parm['sbid'] + '\\.T';

            var o = $(z);
            var isLast = o.hasClass('last');

            o.replaceWith(j.html);

            if(isLast)
              $(z).addClass('last');
          },
          error:function(){

          },
          timeout:function(){

          }
        });

      }
    }
  },
  delay
);
}


// ======================================================================================================

function T_replaceCandidates_OK()
{
  {% set replaceing_msg %}
    {% trans 'prod::thesaurusTab:dlg:Remplacement en cours.' %}
  {% endset %}

  var replacingBox = p4.Dialog.Create({
    size : 'Alert'
  });
  replacingBox.setContent("{{ replaceing_msg | e('js') }}");

  var parms = {
    url:	"/xmlhttp/replacecandidate.j.php",
    data:	{
      "id[]" : trees.C._toReplace.sbas + "." + trees.C._toReplace.cid
      , "t" : trees.C._toReplace.replaceby
      , "debug" : '0'
    },
    async:		false,
    cache:		false,
    dataType:	"json",
    timeout:	10*60*1000,	// 10 minutes !
    success:	function(result, textStatus)
    {
      trees.C._toReplace = null;
      thesauShowWizard("wiz_0", false);

      replacingBox.Close();

      if(result.msg != '')
      {
        var alert = p4.Dialog.Create({
          size : 'Alert',
          closeOnEscape : true,
          closeButton:true
        });
        alert.setContent(result.msg);
      }

      for(i in result.ctermsDeleted)
      {
          var cid = "#CX_P\\." + result.ctermsDeleted[i].replace(new RegExp("\\.", "g"), "\\.");	// escape les '.' pour jquery
          $(cid).remove();
      }

    },
    _ret: null	// private alchemy
  };

  $.ajax( parms );
}


function T_acceptCandidates_OK()
{
  {% set accepting_msg %}
    {% trans 'prod::thesaurusTab:dlg:Acceptation en cours.' %}
  {% endset %}

  var acceptingBox = p4.Dialog.Create({
    size : 'Alert'
  });
  acceptingBox.setContent("{{ accepting_msg | e('js') }}");

  var t_ids = [];
  var dst = trees.C._toAccept.dst.split('.');
  dst.shift();
  var sbid = dst.shift();
  dst = dst.join('.');
  same_sbas = true;
  // obviously the candidates and the target already complies (same sbas, good tbranch)
  trees.C._selInfos.sel.each(
    function()
    {
      var x = this.getAttribute('id').split('.');
      x.shift();
      if(x.shift() != sbid)
        same_sbas = false;
      t_ids.push(x.join('.'));
    }
  );

  if(!same_sbas)
    return;

  var parms = {
    url:	"/xmlhttp/acceptcandidates.j.php",
    data:	{
      // "debug": false,
      "sbid" : sbid,
      "tid"  : dst,
      "cid[]": t_ids,
      "typ"  : trees.C._toAccept.type,
      "piv"  : trees.C._toAccept.lng
    },
    async:	false,
    cache:	false,
    dataType: "json",
    timeout:	10*60*1000,	// 10 minutes !
    success:	function(result, textStatus)
    {
      for(i in result.refresh)
      {
        var zurl = "/xmlhttp/openbranch_prod.j.php"
          + "?type=" + result.refresh[i].type
          + "&sbid=" + result.refresh[i].sbid
          + "&id="   + encodeURIComponent(result.refresh[i].id);
        if(result.refresh[i].type=='T')
          zurl += "&sortsy=1" ;

        $.get(zurl
        , []
        , function(j)
        {
          var z = '#' + j.parm['type']
            + 'X_P\\.'
            + j.parm['sbid'] + '\\.'
            + j.parm['id'].replace(new RegExp("\\.", "g"), "\\.");	// escape les '.' pour jquery

          $(z).replaceWith(j.html);
        }
        , "json");
      }
      trees.C._toAccept = null;
      thesauShowWizard("wiz_0",false);
      acceptingBox.Close();
    },
    error:function(){acceptingBox.Close();},
    timeout:function(){acceptingBox.Close();},
    _ret: null	// private alchemy
  };

  $.ajax( parms );
}


function C_deleteCandidates_OK()
{
  {% set deleting_msg %}
    {% trans 'prod::thesaurusTab:dlg:Suppression en cours.' %}
  {% endset %}

  var deletingBox = p4.Dialog.Create({
    size : 'Alert'
  });
  deletingBox.setContent("{{ deleting_msg | e('js') }}");

  var t_ids = [];
  var lisel = trees.C.tree.find("LI .selected");
  trees.C.tree.find("LI .selected").each(
    function()
    {
      var x = this.getAttribute('id').split('.');
      x.shift();
      t_ids.push(x.join('.'));
    }
  );
  var parms = {
    url:"/xmlhttp/replacecandidate.j.php",
    data:{"id[]":t_ids},
    async:false,
    cache:false,
    dataType:"json",
    timeout:10*60*1000,	// 10 minutes !
    success: function(result, textStatus)
    {
      deletingBox.Close();

      if(result.msg != '')
      {
        var alert = p4.Dialog.Create({
          size : 'Alert',
          closeOnEscape : true,
          closeButton:true
        });
        alert.setContent(result.msg);
      }

      for(i in result.ctermsDeleted)
      {
        var cid = "#CX_P\\." + result.ctermsDeleted[i].replace(new RegExp("\\.", "g"), "\\.");	// escape les '.' pour jquery
        $(cid).remove();
      }
    },
    _ret: null
  };

  $.ajax( parms );
}


// menu option T:accept as...
function T_acceptCandidates(menuItem, menu, type)
{
  var lidst = trees.T.tree.find("LI .selected");
  if(lidst.length != 1)
    return;

  var lisel = trees.C.tree.find("LI .selected");
  if(lisel.length == 0)
    return;

  {% set messageOne %}
   {% trans 'prod::thesaurusTab:dlg:accepter le terme candidat "%s" ?' %}
  {% endset %}
  {% set messageMany %}
   {% trans 'prod::thesaurusTab:dlg:accepter les %d termes candidats ?' %}
  {% endset %}

  var msg;

  if(lisel.length == 1)
  {
    var term = lisel.eq(0).find("span span").html();
    msg = $.sprintf("{{ messageOne | e('js') }}", term);
  }
  else
  {
    msg = $.sprintf("{{ messageMany | e('js') }}", lisel.length);
  }

  trees.C._toAccept.type = type;
  trees.C._toAccept.dst = lidst.eq(0).attr("id");

  var confirmBox = p4.Dialog.Create({
      size : 'Alert',
      closeOnEscape : true,
      cancelButton: true,
      buttons: {
          "Ok" : function() {
              confirmBox.Close();
              T_acceptCandidates_OK();
          }
      }
  });
  confirmBox.setContent(msg);

}


// menu option T:search
function T_search(menuItem, menu, cmenu, e, label)
{
  if(!menu._li)
    return;
  var tcids = menu._li.attr("id").split(".");
  tcids.shift();
  var sbid = tcids.shift();
  var term = menu._li.find("span span").html();

  v = '*:"' + term.replace("(", "[").replace(")", "]") + '"';

  var nck = 0;
  $('#adv_search :checkbox[name=bas\[\]]').each(function(a)
  {
    bas2sbas["b"+this.value].ckobj = this;
    bas2sbas["b"+this.value].waschecked = this.checked;
    if(bas2sbas["b"+this.value].sbid == sbid)
    {
      if(this.checked)
        nck++;
    }
    else
    {
      this.checked = false;
    }
  }
);

  if(nck == 0)
  {
    var i;
    for(i in bas2sbas)
    {
      if(bas2sbas[i].sbid == sbid)
        bas2sbas[i].ckobj.checked = true;
    }
  }

  $('form[name="phrasea_query"] input[name="qry"]').val(v);
  checkFilters();
  newSearch();
}


function C_MenuOption(menuItem, menu, option, parm)
{
  if(!trees.C._selInfos)	// nothing selected in candidates ?
    return;

  trees.C._toAccept  = null;	// cancel previous 'accept' action anyway
  trees.C._toReplace = null;	// cancel previous 'replace' action anyway
  switch(option)
  {
    case 'ACCEPT':
      // glue selection to the tree
      trees.C._toAccept = { 'lng': parm['lng'] } ;

      // display helpful message into the thesaurus box...
      var msg;

      {% set messageOne %}
        {% trans 'prod::thesaurusTab:wizard:clic-droit / accepter le terme candidat "%s"' %}
      {% endset %}
      {% set messageMany %}
        {% trans "prod::thesaurusTab:wizard:clic-droit / accepter les %s termes candidats" %}
      {% endset %}

      if(trees.C._selInfos.n == 1)
      {
        msg = $.sprintf("{{ messageOne | e }}", menu._srcElement.find("span").html());
      }
      else
      {
        msg = $.sprintf("{{ messageMany | e }}", trees.C._selInfos.n);
      }

      // set the content of the wizard
      $("#THPD_WIZARDS .wiz_1 .txt").html(msg);
      // ... and switch to the thesaurus tab
      p4.thesau.tabs.tabs('select', 0);
      thesauShowWizard("wiz_1", true);

      break;

    case 'REPLACE':
      // display helpful message into the thesaurus box...
      var msg;

      {% set messageOne %}
        {% trans "prod::thesaurusTab:dlg:remplacer le terme "%s" des fiches par :" %}
      {% endset %}
      {% set messageMany %}
        {% trans "prod::thesaurusTab:dlg:remplacer les %d termes des fiches par :" %}
      {% endset %}

      if(trees.C._selInfos.n == 1)
      {
        var term = trees.C._selInfos.sel.eq(0).find("span span").html();
        msg = $.sprintf('{{ messageOne | e }}', term);
      }
      else
      {
        msg = $.sprintf('{{ messageMany |e }}', trees.C._selInfos.n);
      }

      p4.thesau.tabs.tabs('select', 0);

      // set the content of the wizard
      $("#THPD_WIZARDS .wiz_2 .txt").html(msg);
      // ... and switch to the thesaurus tab
      thesauShowWizard("wiz_2", true);

      break;

    case 'DELETE':
      $("#THPD_WIZARDS DIV", p4.thesau.tabs).hide();
      // display helpful message into the thesaurus box...

      {% set messageOne %}
        {% trans 'prod::thesaurusTab:dlg:supprimer le terme "%s" des fiches ?' %}
      {% endset %}
      {% set messageMany %}
        {% trans 'prod::thesaurusTab:dlg:supprimer les %d termes des fiches ?' %}
      {% endset %}

     var msg;
      if(trees.C._selInfos.n == 1)
      {
        var term = trees.C._selInfos.sel.eq(0).find("span span").html();
        msg = $.sprintf("{{ messageOne | e('js') }}", term);
      }
      else
      {
        msg = $.sprintf("{{ messageMany | e('js') }}", trees.C._selInfos.n);
      }

      var confirmBox = p4.Dialog.Create({
        size : 'Alert',
        closeOnEscape : true,
        cancelButton: true,
        buttons: {
            "Ok" : function() {
                confirmBox.Close();
                C_deleteCandidates_OK();
            }
        }
      });
      confirmBox.setContent(msg);

      break;
  }
}


function Xclick(e)
{
  var x = e.srcElement ? e.srcElement : e.target;
  switch(x.nodeName)
  {
    case "DIV":		// +/-
      var li = $(x).closest('li');
      var tids = li.attr('id').split('.');
      var tid  = tids.shift();
      var sbid = tids.shift();
      var type = tid.substr(0, 1);
      if((type=='T'||type=='C') && tid.substr(1, 4)=="X_P")	// TX_P ou CX_P
      {
        var ul = li.children('ul').eq(0);
        if(ul.css("display")=='none' || is_ctrl_key(e))
        {
          if(is_ctrl_key(e))
          {
            var oldField = ul.attr('field');
            ul.remove();
            if(oldField)
              li.append('<ul field="'+oldField+'">{% trans 'prod::thesaurusTab:tree:loading' %}</ul>');
            else
              li.append('<ul>{% trans 'prod::thesaurusTab:tree:loading' %}</ul>');
            li.removeAttr('loaded');
          }

          ul.show();

          if(!li.attr('loaded'))
          {
            var zurl = "/xmlhttp/openbranch_prod.j.php?type="+type+"&sbid="+sbid+"&id=" + encodeURIComponent(tids.join('.'));
            if(li.hasClass('last'))
              zurl += "&last=1";
            if(type=='T')
              zurl += "&sortsy=1";
            $.get(zurl, [], function(j)
            {
              li.replaceWith(j.html);
            }
            , "json");
          }
        }
        else
        {
          ul.hide();
        }
      }
      break;
    case "SPAN":
      var li = $(x).closest('li');
      var tids = li.attr('id').split('.');
      var type = tids[0].substr(0, 1);
      if((type=='T' && tids.length>2) || tids.length == 4) // && tids[0].substr(0, 1)=='C')
      {
        tids.pop();
        var tid3 = tids.join('.');
        if(!is_ctrl_key(e) && !is_shift_key(e))
        {
          $("LI", trees[type].tree).removeClass('selected');
          p4.thesau.lastClickedCandidate = null;
        }
        else
        {
          // if($("#THPD_C_treeBox")._lastClicked)
          if(p4.thesau.lastClickedCandidate != null)
          {
            if(p4.thesau.lastClickedCandidate.tid3 != tid3)
            {
              $("LI", trees[type].tree).removeClass('selected');
              p4.thesau.lastClickedCandidate = null;
            }
            else
            {
              if(e.shiftKey)
              {
                var lip = li.parent().children('li');
                var idx0 = lip.index(p4.thesau.lastClickedCandidate.item);
                var idx1 = lip.index(li);
                if(idx0 < idx1)
                  lip.filter(function(index){return(index >= idx0 && index <idx1); }).addClass('selected');
                else
                  lip.filter(function(index){return(index > idx1 && index <=idx0); }).addClass('selected');
              }
            }
          }
        }
        li.toggleClass('selected');
        if(type == 'C')
        {
          p4.thesau.lastClickedCandidate = { item:li, tid3:tid3 };
        }
      }
      break;
    default:
      break;
  }
}

function TXdblClick(e)
{
  var x = e.srcElement ? e.srcElement : e.target;
  switch(x.nodeName)
  {
    case "SPAN":		// term
      switch(p4.thesau.currentWizard)
      {
        case "wiz_0":				// simply browse
          var tid = $(x).closest('li').attr('id');
          if(tid.substr(0,5)=="TX_P.")
          {
            var sbid = tid.split(".")[1];
            var term = $(x).hasClass('separator') ? $(x).prev().text() : $(x).text();

            doThesSearch('T', sbid, term, null);
          }
          break;
        case "wiz_2":				// replace by
          var tid = $(x).closest('li').attr('id');
          if(tid.substr(0,5)=="TX_P.")
          {
            var term = $(x).text();
            $("#THPD_WIZARDS .wiz_2 :text").val(term);
            T_replaceBy2(term);
          }
          break;
      }
      break;
    default:
      break;
  }
}

function CXdblClick(e)
{
  var x = e.srcElement ? e.srcElement : e.target;
  switch(x.nodeName)
  {
    case "SPAN":		// term
      var li = $(x).closest('li');
      var field = li.closest('[field]').attr('field');
      if(typeof(field) != "undefined")
      {
        var tid = li.attr('id');
        if(tid.substr(0,5)=="CX_P.")
        {
          var sbid = tid.split(".")[1];
          var term = $(x).text();
          doThesSearch('C', sbid, term, field);
        }
      }
      break;
    default:
      break;
  }
}

function doThesSearch(type, sbid, term, field)
{
  var nck = 0;
  $('#adv_search input[name="bas[]"]').each(
  function(i,n)
  {
      var base_id = $(n).val();

      bas2sbas["b"+base_id].ckobj = this;
      bas2sbas["b"+base_id].waschecked = this.checked;
      if(bas2sbas["b"+base_id].sbid == sbid)
      {
        if(this.checked)
          nck++;
      }
      else
      {
        this.checked = false;
      }
    }
  );

  if(nck == 0 || type=='C')
  {
    var i;
    for(i in bas2sbas)
    {
      if(bas2sbas[i].sbid == sbid)
        bas2sbas[i].ckobj.checked = true;
    }
  }
  if(type=='T')
    v = '*:"' + term.replace("(", "[").replace(")", "]") + '"';
  else
    v = '"' + term + '" IN ' + field;
  $('form[name="phrasea_query"] input[name="qry"]').val(v);
  checkFilters();
  newSearch();
}


function thesau_clickThesaurus(event)	// onclick dans le thesaurus
{
  // on cherche ou on a clique
  for(e=event.srcElement ? event.srcElement : event.target; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
    ;
  if(e)
  {
    switch(e.id.substr(0,4))
    {
      case "TH_P":	// +/- de deploiement de mot
        js = "thesau_thesaurus_ow('"+e.id.substr(5)+"')";
        self.setTimeout(js, 10);
        break;
    }
  }
  return(false);
}

function thesau_dblclickThesaurus(event)	// onclick dans le thesaurus
{
  var err;
  try
  {
    p4.thesau.lastTextfocus.focus();
  }
  catch(err)
  {
    return;
  }

  // on cherche ou on a clique
  for(e=event.srcElement; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
    ;
  if(e)
  {
    switch(e.id.substr(0,4))
    {
      case "GL_W":	// double click sur le mot
        var t = e.id.split(".");
        t.shift();
        var sbid = t.shift();
        var thid = t.join(".");
        var url = "/xmlhttp/getsy_prod.x.php";
        var parms  = "bid=" + sbid + "&id=" + thid;

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("POST", url, false);
        xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        xmlhttp.send(parms);
        var ret = xmlhttp.responseXML;

        result = ret.getElementsByTagName("result");
        if(result.length==1)
        {
          val = result.item(0).getAttribute("t");
          replaceEditSel(val);
        }
        break;
    }
  }
  return(false);
}

function thesaurus_cw(id)	// on clique sur un mot de thesaurus
{
  return(false);
}

function thesau_thesaurus_ow(id)	// on ouvre ou ferme une branche de thesaurus
{
  var o = document.getElementById("TH_K."+id);
  if(o.className=="o")
  {
    // on ferme
    o.className = "c";
    document.getElementById("TH_P."+id).innerHTML = "+";
    document.getElementById("TH_K."+id).innerHTML = "{% trans 'prod::thesaurusTab:tree:loading' %}";
  }
  else if(o.className=="c" || o.className=="h")
  {
    // on ouvre
    o.className = "o";
    document.getElementById("TH_P."+id).innerHTML = "-";

    var t_id = id.split(".");
    var sbas_id = t_id[0];
    t_id.shift();
    var thid = t_id.join(".");
    var url = "/xmlhttp/getterm_prod.x.php";
    var parms  = "bid=" + sbas_id;
    parms += "&lng="+p4.lng;
    parms += "&sortsy=1";
    parms += "&id=" + thid;
    parms += "&typ=TH";

    p4.thesau.thlist['s'+sbas_id].openBranch(id, thid);
  }
  return(false);
}

function replaceEditSel(value)
{
  if(!p4.thesau.lastTextfocus || !p4.thesau.lastTextfocus.selectedTerm)
    return;

  p4.thesau.lastTextfocus.value = p4.thesau.lastTextfocus.value.substr(0, p4.thesau.lastTextfocus.selectedTerm.start) + value + p4.thesau.lastTextfocus.value.substr(p4.thesau.lastTextfocus.selectedTerm.end);
  if(typeof(document.selection) != 'undefined')
  {
    // explorer
    var range = p4.thesau.lastTextfocus.createTextRange();
    range.move('character', p4.thesau.lastTextfocus.selectedTerm.start + value.length);
    range.select();
  }
  else if(typeof(p4.thesau.lastTextfocus.selectionStart) != 'undefined')
  {
    // gecko (safari)
    p4.thesau.lastTextfocus.selectionStart = p4.thesau.lastTextfocus.selectionEnd = p4.thesau.lastTextfocus.selectedTerm.start + value.length;
  }
  cbEditing2(p4.thesau.lastTextfocus, "MOUSEUP");	// force le calcul de la nouvelle selection
  p4.thesau.lastTextfocus.focus();
  return;
}


function ThesauThesaurusSeeker(sbas_id)
{
  this.sbas_id = sbas_id;
  this._ctimer = null;
	this._xmlhttp = null;
  this.tObj = { 'TH_searching':null , 'TH_P':null , 'TH_K':null };
  this.search = function(txt) {
    if(this._ctimer)
      clearTimeout(this._ctimer);
    var js = "p4.thesau.thlist['s"+this.sbas_id+"'].search_delayed('"+txt.replace("'", "\\'")+"');" ;
    this._ctimer = setTimeout(js, 100);
  } ;
  this.search_delayed = function(txt) {
    var me = this;
    if($this._xmlttp.abort && typeof $this._xmlttp.abort == 'function')
    {
      this._xmlhttp.abort();
    }
    var url = "/xmlhttp/openbranches_prod.x.php";
		var parms  = {
      bid : this.sbas_id,
      t : txt,
      mod : "TREE"
    };

    this._xmlhttp = $.ajax({
      url: url,
      type:'POST',
      data: parms,
      success: function(ret)
      {
        me.xmlhttpstatechanged(ret);
      },
      error:function(){

      },
      timeout:function(){

      }
    });

    this._ctimer = null;
  } ;
  this.openBranch = function(id, thid) {
    var me = this;
    if($this._xmlttp.abort && typeof $this._xmlttp.abort == 'function')
    {
      this._xmlhttp.abort();
    }
    var url = "/xmlhttp/getterm_prod.x.php";
		var parms  = {
      bid : this.sbas_id,
      sortsy : 1,
      id : thid,
      typ : "TH"
    }

    this._xmlhttp = $.ajax({
      url: url,
      type:'POST',
      data: parms,
      success: function(ret)
      {
        me.xmlhttpstatechanged(ret, id);
      },
      error:function(){

      },
      timeout:function(){

      }
    });

  };
  this.xmlhttpstatechanged = function(ret, id) {
    try
    {
      if(!this.tObj["TH_searching"])
        this.tObj["TH_searching"] = document.getElementById("TH_searching");
      this.tObj["TH_searching"].src = "/skins/icons/ftp-loader-blank.gif";

      if(ret) // && (typeof(ret.parsed)=="undefined" || ret.parsed))
      {
        var htmlnodes = ret.getElementsByTagName("html");
        if(htmlnodes && htmlnodes.length==1 && (htmlnode=htmlnodes.item(0).firstChild))
        {
          if(typeof(id)=="undefined")
          {
            // called from search or 'auto' : full thesaurus search
            if(!this.tObj["TH_P"])
              this.tObj["TH_P"] = document.getElementById("TH_P."+this.sbas_id+".T");
            if(!this.tObj["TH_K"])
              this.tObj["TH_K"] = document.getElementById("TH_K."+this.sbas_id+".T");
            this.tObj["TH_P"].innerHTML = "...";
            this.tObj["TH_K"].className = "h";
            this.tObj["TH_K"].innerHTML = htmlnode.nodeValue;
          }
          else
          {
            // called from 'openBranch'
            //			var js = "document.getElementById('TH_K."+thid+"').innerHTML = \""+htmlnode.nodeValue+"\"";
            //			self.setTimeout(js, 10);
            document.getElementById("TH_K."+id).innerHTML = htmlnode.nodeValue;
          }
        }
      }
    }
    catch(err)
    {
      ;
    }
  };
}


function cbEditing2(textarea, act)
{
  var sbas_id = p4.edit.sbas_id;
  tmpCurField = 0;

  if(textarea.id=="idZTextArea")
  {
    tmpCurField = p4.edit.curField ;
  }
  else
  {
    if(textarea.id=="idZTextAreaReg")
      tmpCurField = p4.edit.curFieldReg;
  }

  p4.thesau.lastTextfocus = textarea;
  textarea.selectedTerm = null;
  var p0 = -1;
  var p1 = -1;
  if(typeof(document.selection) != 'undefined')
  {
    // ici si explorer
    var range = document.selection.createRange();
    var i;
    var oldrange = range.duplicate();
    for(i=0; i<200; i++, p0++)
    {
      pe = range.parentElement();
      if(pe != textarea)
        break;
      range.moveStart("character", -1);
    }
    range = oldrange.duplicate();
    for(i=0; i<200; i++, p1++)
    {
      pe = range.parentElement();
      if(pe != textarea)
        break;
      range.moveEnd("character", -1);
    }
  }
  else if(typeof(textarea.selectionStart) != "undefined")
  {
    // ici si gecko (safari)
    p0 = textarea.selectionStart;
    p1 = textarea.selectionEnd;
  }
  if(p0 != -1 && p1 != -1)
  {
    var c;
    // on etend les positions a tout le keyword (entre ';')
    t = textarea.value;
    l = t.length;
    for( ; p0 > 0; p0--)
    {
      c = t.charCodeAt(p0-1);
      if(c==59 || c==10 || c==13)	// 59==";"
        break;
    }
    for( ; p1 < l; p1++)
    {
      c = t.charCodeAt(p1);
      if(c==59 || c==10 || c==13)
        break;
    }
    // on copie le resultat dans le textarea
    textarea.selectedTerm = { start:p0, end:p1 };

    // on cherche le terme dans le thesaurus
    var zText = textarea.value.substr(p0, p1-p0);

    if(document.forms["formSearchTH"].formSearchTHck.checked)
    {
      if(zText && zText.length>2 && document.forms["formSearchTH"].formSearchTHfld.value != zText)
      {
        document.forms["formSearchTH"].formSearchTHfld.value = zText;

        document.getElementById("TH_searching").src = "/skins/icons/ftp-loader.gif";
        p4.thesau.thlist['s'+sbas_id].search(zText);
      }
    }
  }
  return(true);
}

function thesauSearchAll()
{
  var value = document.forms["formSearchTH"].formSearchTHfld.value;
  if(value == "")
  {
    loaded();
    self.setTimeout('document.forms["formSearchTH"].formSearchTHfld.focus()', 100);
  }
  else
  {
    var url, i, bid;
    document.getElementById("TH_searching").src = "/skins/icons/ftp-loader.gif";
    for(i in p4.thesau.thlist)
    {
      thlist[i].search(value);
    }
  }
}

function clkOnglet(onglet)
{
  switch(onglet)
  {
    case "FULL":
      document.getElementById("TH_Ofull").style.display = "block";
      document.getElementById("TH_Oclip").style.display = "none";
      document.getElementById("TH_Otabs_full").className = "actif";
      document.getElementById("TH_Otabs_clipboard").className = "inactif";
      break;
    case "PROP":
      document.getElementById("TH_Ofull").style.display = "none";
      document.getElementById("TH_Oclip").style.display = "none";
      document.getElementById("TH_Otabs_full").className = "inactif";
      document.getElementById("TH_Otabs_clipboard").className = "inactif";
      break;
    case "CLIP":
      document.getElementById("TH_Ofull").style.display = "none";
      document.getElementById("TH_Oclip").style.display = "block";
      document.getElementById("TH_Otabs_full").className = "inactif";
      document.getElementById("TH_Otabs_clipboard").className = "actif";
      break;
  }
}

function startThesaurus(){

  p4.thesau.thlist = {
    {% set first = 1 %}
    {% for base in module_prod.get_search_datas['bases'] %}
    {% if base['thesaurus'] %}
    {% if first == 0 %},{% endif %}
    "s{{base['sbas_id']}}": new ThesauThesaurusSeeker({{base['sbas_id']}})
    {% set first = 0 %}
    {% endif %}
    {% endfor %}
  };
  p4.thesau.currentWizard = "???";

  sbas     = {{thesau_json_sbas|raw}};
  bas2sbas = {{thesau_json_bas2sbas|raw}};

  p4.thesau.lastTextfocus = null;

  p4.thesau.lastClickedCandidate = null;

  p4.thesau.tabs = $("#THPD_tabs");
  p4.thesau.tabs.tabs();

  trees = {
    'T':{
      'tree'      : $("#THPD_T_tree", p4.thesau.tabs)
    },
    'C':{
      'tree'       : $("#THPD_C_tree", p4.thesau.tabs)
      , '_toAccept'  : null 			// may contain : {'type', 'dst', 'lng'}
      , '_toReplace' : null		//
      , '_selInfos'  : null				// may contain : {'sel':lisel, 'field':field, 'sbas':sbas, 'n':lisel.length}
    }
  };

  trees.T.tree.contextMenu(
  [
    {
      label:'{% trans 'boutton::chercher' %}',
      onclick:function(menuItem, menu, cmenu, e, label)
      {
        T_search(menuItem, menu, cmenu, e, label);
      }
    },
    {
      label:'{% trans 'prod::thesaurusTab:tmenu:Accepter comme terme specifique' %}',
      onclick:function(menuItem, menu)
      {
        T_acceptCandidates(menuItem, menu, 'TS');
      }
    },
    {
      label:'{% trans 'prod::thesaurusTab:tmenu:Accepter comme synonyme' %}',
      onclick:function(menuItem, menu)
      {
        T_acceptCandidates(menuItem, menu, 'SY');
      }
    }
  ]
  ,
  {
    className:"THPD_TMenu",
    beforeShow:function()
    {
      var menuOptions = $(this.menu).find(".context-menu-item");
      menuOptions.eq(1).addClass("context-menu-item-disabled");
      menuOptions.eq(2).addClass("context-menu-item-disabled");

      var x = this._showEvent.srcElement ? this._showEvent.srcElement : this._showEvent.target;
      var li  = $(x).closest("li");
      this._li = null;
      var tcids = li.attr("id").split(".");
      if(tcids.length > 2 && tcids[0] == "TX_P" && tcids[2] != 'T' && x.nodeName != "LI")
      {
        this._li = li;
        tcids.shift();
        var sbas = tcids.shift();

        // this._srcElement = li;		// private alchemy
        if(!li.hasClass('selected'))
        {
          // rclick OUTSIDE the selection : unselect all
          trees.T.tree.find("LI").removeClass('selected');

          $("li", trees.T.tree).removeClass('selected');
          li.addClass('selected');
        }

        if(trees.C._selInfos && trees.C._selInfos.sbas == sbas)
        {
          // whe check if the candidates can be validated here
          // aka does the tbranch of the field (of candidates) reaches the paste location ?
          var parms = {	url:"/xmlhttp/checkcandidatetarget.j.php"
              + "?sbid=" + sbas
              + "&acf=" + encodeURIComponent(trees.C._selInfos.field)
              + "&id=" + encodeURIComponent(tcids.join('.')) ,
            data:[],
            async:false,
            cache:false,
            dataType:"json",
            timeout:1000,
            success: function(result, textStatus)
            {
              this._ret = result ;
              if(result.acceptable)
              {
                menuOptions.eq(1).removeClass("context-menu-item-disabled");
                menuOptions.eq(2).removeClass("context-menu-item-disabled");
              }
            },
            _ret: null	// private alchemy
          };

          $.ajax( parms );

        }
      }
      return(true);
    }
  }
);

  trees.C.tree.contextMenu(
  [
    {% for lng_code, lng in thesau_languages %}
    {
      label:'{% trans %}prod::thesaurusTab:cmenu:Accepter en {{lng_code}}{% endtrans %}',
      onclick:function(menuItem, menu)
      {
        C_MenuOption(menuItem, menu, "ACCEPT", {'lng':'{{lng_code}}'});
      }
    }
    ,
    {% endfor %}
    {
      label:'{% trans 'prod::thesaurusTab:cmenu:Remplacer par...' %}',
//      disabled:true,
      onclick:function(menuItem, menu)
      {
        C_MenuOption(menuItem, menu, 'REPLACE', null);
      }
    },
    {
      label:'{% trans 'boutton::supprimer' %}',
//      disabled:true,
      onclick:function(menuItem, menu)
      {
        C_MenuOption(menuItem, menu, 'DELETE', null);
      }
    }
  ]
  ,
  {
    beforeShow:function()
    {
      var ret = false;

      var x = this._showEvent.srcElement ? this._showEvent.srcElement : this._showEvent.target;
      var li  = $(x).closest("li");

      if(!li.hasClass('selected'))
      {
        // rclick OUTSIDE the selection : unselect all
        // lisel.removeClass('selected');
        trees.C.tree.find("LI").removeClass('selected');
        p4.thesau.lastClickedCandidate = null;
      }
      var tcids = li.attr("id").split(".");
      if(tcids.length == 4 && tcids[0] == "CX_P" && x.nodeName != "LI")
      {
        // candidate context menu only clicking on final term
        if(!li.hasClass('selected'))
          li.addClass('selected');
        //				this._cutInfos = { sbid:tcids[1], field:li.parent().attr('field') };	// private alchemy
        this._srcElement = li;						// private alchemy

        // as selection changes, compute usefull info (field, sbas)
        var lisel = trees.C.tree.find("LI .selected");
        if(lisel.length > 0)
        {
          // lisel are all from the same candidate field, so check the first li
          var li0   = lisel.eq(0);
          var field = li0.parent().attr("field");
          var sbas  = li0.attr("id").split('.')[1];

          // glue selection info to the tree
          trees.C._selInfos = {'sel':lisel, 'field':field, 'sbas':sbas, 'n':lisel.length} ;

//             $(this.menu).find('.context-menu-item')[{{ thesau_languages|length }}].addClass('context-menu-item-disabled');
          if(lisel.length == 1)
          {
            $(this.menu).find('.context-menu-item').eq({{ thesau_languages|length }}).removeClass('context-menu-item-disabled');
          }
          else
          {
            $(this.menu).find('.context-menu-item').eq({{ thesau_languages|length }}).addClass('context-menu-item-disabled');
          }
        }
        else
        {
          trees.C._selInfos = null;
        }

        ret = true;
      }
      return(ret);
    }
  }
);

}
