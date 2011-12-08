function initializeEdit()
{
  p4.edit = {};
  p4.edit.curField = "?";
  p4.edit.editBox = $('#idFrameE');
  p4.edit.textareaIsDirty = false;
  p4.edit.fieldLastValue = "";
  p4.edit.lastClickId = null;
  p4.edit.sbas_id = false;
  p4.edit.what = false;
  p4.edit.regbasprid = false;
  p4.edit.newrepresent = false;
  p4.edit.ssel = false;
}

$(document).ready(function(){
  $(window).bind('resize',function(){
    setPreviewEdit();
    setSizeLimits();
  });
});

function setSizeLimits()
{
  if(!$('#EDITWINDOW').is(':visible'))
    return;

  $('#EDIT_TOP').resizable('option','maxHeight', ($('#EDIT_ALL').height() - $('#buttonEditing').height() - 10 - 160));
  $('#divS_wrapper').resizable('option','maxWidth', ($('#EDIT_MID_L').width() - 270));
  $('#EDIT_MID_R').resizable('option','maxWidth', ($('#EDIT_MID_R').width() + $('#idEditZone').width() - 240));
}

function edit_kdwn(evt, src)
{  
  cancelKey = false;

  switch(evt.keyCode)
  {
    case 13:
    case 10:
      if(p4.edit.T_fields[p4.edit.curField].type == "date")
        cancelKey = true;
  }

  if(cancelKey)
  {
    evt.cancelBubble = true;
    if(evt.stopPropagation)
      evt.stopPropagation();
    return(false);
  }
  return(true);
}

// ----------------------------------------------------------------------------------------------
// des events sur le textarea pour tracker la selection (chercher dans le thesaurus...)
// ----------------------------------------------------------------------------------------------
function edit_mdwn_ta(evt)
{ 
  evt.cancelBubble = true;
  return(true);
}

// mouse up textarea
function edit_mup_ta(evt, obj)
{ 
	
  if(p4.edit.T_fields[p4.edit.curField].tbranch)
  {
    if(obj.value != "")
      ETHSeeker.search(obj.value);
  }
  return(true);
}

// key up textarea
function edit_kup_ta(evt, obj)
{
	
  var cancelKey = false;
  var o;
  switch(evt.keyCode)
  {
    case 27:	// esc : on restore la valeur avant editing
      //			$("#btn_cancel", p4.edit.editBox).parent().css("backgroundColor", "#000000");
      edit_validField(evt, "cancel");
      //			self.setTimeout("document.getElementById('btn_cancel').parentNode.style.backgroundColor = '';", 100);
      cancelKey = true;
      break;
  }
	
  if(cancelKey)
  {
    evt.cancelBubble = true;
    if(evt.stopPropagation)
      evt.stopPropagation();
    return(false);
  }
  if(!p4.edit.textareaIsDirty && ($("#idEditZTextArea", p4.edit.editBox).val() != p4.edit.fieldLastValue))
  {
    p4.edit.textareaIsDirty = true;
  }

  var s = obj.value;
  if(p4.edit.T_fields[p4.edit.curField].tbranch)
  {
    if(s != "")
      ETHSeeker.search(s);
  }
  return(true);
}

// ---------------------------------------------------------------------------
// on a clique sur le peudo champ 'status'
// ---------------------------------------------------------------------------
function edit_mdwn_status(evt)
{
  if(!p4.edit.textareaIsDirty || edit_validField(evt, "ask_ok")==true)
    editStatus(evt);
  evt.cancelBubble = true;
  if(evt.stopPropagation)
    evt.stopPropagation();
  return(false);
}

// ---------------------------------------------------------------------------
// on a clique sur un champ 
// ---------------------------------------------------------------------------
function edit_mdwn_fld(evt, meta_struct_id, fieldname)
{
  if(!p4.edit.textareaIsDirty || edit_validField(evt, "ask_ok")==true)
    editField(evt, meta_struct_id);
}

// ---------------------------------------------------------------------------
// change de champ (avec les fleches autour du nom champ)
// ---------------------------------------------------------------------------
function edit_chgFld(evt, dir)
{
  var current_field = $('#divS .edit_field.active');
  if(current_field.length == 0)
  {
    current_field = $('#divS .edit_field:first');
    current_field.trigger('click');
  }
  else
  {
    if(dir >= 0)
    {
      current_field.next().trigger('click');
    }
    else
    {
      current_field.prev().trigger('click');
    }
  }
}

// ---------------------------------------------------------------------------
// on active le pseudo champ 'status'
// ---------------------------------------------------------------------------
function editStatus(evt)
{
  $(".editDiaButtons", p4.edit.editBox).hide();

  document.getElementById('idEditZTextArea').blur();
  document.getElementById('EditTextMultiValued').blur();
	
  $("#idFieldNameEdit", p4.edit.editBox).html("[STATUS]") ;
  $("#idExplain", p4.edit.editBox).html("&nbsp;");

  $("#ZTextMultiValued", p4.edit.editBox).hide();
  $("#ZTextMonoValued", p4.edit.editBox).hide();
  $("#ZTextStatus", p4.edit.editBox).show();
	
  $("#idEditZone", p4.edit.editBox).show();
	
  document.getElementById("editFakefocus").focus();
  p4.edit.curField = -1;
  activeField();
}

function activeField()
{
  var meta_struct_id = parseInt(p4.edit.curField);

  meta_struct_id = (isNaN(meta_struct_id) || meta_struct_id <0)?'status':meta_struct_id;
	
  $('#divS div.active, #divS div.hover').removeClass('active hover');
  $('#EditFieldBox_'+meta_struct_id).addClass('active');

  var cont = $('#divS');
  var calc = $('#EditFieldBox_'+meta_struct_id).offset().top - cont.offset().top;// hauteur relative par rapport au visible

  if(calc > cont.height() || calc <0)
  {
    cont.scrollTop(calc + cont.scrollTop());
  }
}

//// ---------------------------------------------------------------------------
//// on change de champ courant
//// ---------------------------------------------------------------------------

function editField(evt, meta_struct_id)
{
  document.getElementById('idEditZTextArea').blur();
  document.getElementById('EditTextMultiValued').blur();
  
  $('#idEditZTextArea, #EditTextMultiValued').unbind('keyup.maxLength');
	
  p4.edit.curField = meta_struct_id;
  if(meta_struct_id >= 0)
  {
    var name = p4.edit.T_fields[meta_struct_id].name + (p4.edit.T_fields[meta_struct_id].required ? '<span style="font-weight:bold;font-size:16px;"> * </span>' : '');
    $("#idFieldNameEdit", p4.edit.editBox).html(name) ;

    if(p4.edit.T_fields[meta_struct_id].explain ||
      p4.edit.T_fields[meta_struct_id].maxLength > 0)
      {
      var idexplain = $("#idExplain");
      idexplain.html('');
      
      $('#idEditZTextArea, #EditTextMultiValued').bind('keyup.maxLength', function(){
        var remaining = Math.max((p4.edit.T_fields[meta_struct_id].maxLength - $(this).val().length), 0);
        idexplain.html("<span class='metadatas_restrictionsTips' tooltipsrc='/prod/tooltip/metas/restrictionsInfos/"+p4.edit.sbas_id+"/"+meta_struct_id+"/'><img src='/skins/icons/help32.png' /><!--<img src='/skins/icons/alert.png' />--> Caracteres restants : "+(remaining)+"</span>");
        $('.metadatas_restrictionsTips', idexplain).tooltip();
      }).trigger('keyup.maxLength');      
    }
    else
    {
      $("#idExplain").html("");
    }

    if(!p4.edit.T_fields[meta_struct_id].multi)
    {
      // champ monovalue : textarea
      $(".editDiaButtons", p4.edit.editBox).hide();
			
      if(p4.edit.T_fields[meta_struct_id].type == "date")
      {
        $("#idEditZTextArea", p4.edit.editBox).css("height", "16px");
        $("#idEditDateZone", p4.edit.editBox).show();
      }
      else
      {
        $("#idEditDateZone", p4.edit.editBox).hide();
        $("#idEditZTextArea", p4.edit.editBox).css("height", "100%");
      }
			
      $("#ZTextStatus", p4.edit.editBox).hide();
      $("#ZTextMultiValued", p4.edit.editBox).hide();
      $("#ZTextMonoValued", p4.edit.editBox).show();
			
      if(p4.edit.T_fields[meta_struct_id]._status == 2)
      {
        // heterogene
        $("#idEditZTextArea", p4.edit.editBox).val(p4.edit.fieldLastValue = "") ;
        $("#idEditZTextArea", p4.edit.editBox).addClass("hetero");
        $("#idDivButtons", p4.edit.editBox).show();	// valeurs h�t�rog�nes : les 3 boutons remplacer/ajouter/annuler
      }
      else
      {
        // homogene
        $("#idEditZTextArea", p4.edit.editBox).val(p4.edit.fieldLastValue = p4.edit.T_fields[meta_struct_id]._value);
        $("#idEditZTextArea", p4.edit.editBox).removeClass("hetero");
				
        $("#idDivButtons", p4.edit.editBox).hide();	// valeurs homog�nes
        if(p4.edit.T_fields[meta_struct_id].type == "date")
        {
          var v = p4.edit.T_fields[meta_struct_id]._value.split(' ');
          d = v[0].split('/');
          var dateObj = new Date();
          if(d.length == 3)
          {
            dateObj.setYear(d[0]);
            dateObj.setMonth((d[1]-1));
            dateObj.setDate(d[2]);
          }
          $("#idEditDateZone", p4.edit.editBox).datepicker('setDate', dateObj);
        }
      }
      p4.edit.textareaIsDirty = false;
	
      $("#idEditZone", p4.edit.editBox).show();
      
      $('#idEditZTextArea').trigger('keyup.maxLength');
      
      self.setTimeout("document.getElementById('idEditZTextArea').focus();", 50);
    }
    else
    {
      // champ multivalue : liste
      $("#ZTextStatus", p4.edit.editBox).hide();
      $("#ZTextMonoValued", p4.edit.editBox).hide();
      $("#ZTextMultiValued", p4.edit.editBox).show();
			
      $("#idDivButtons", p4.edit.editBox).hide();	// valeurs homogenes

      // on compare toutes les valeurs de chaque fiche selectionnee
      p4.edit.T_mval = [];			// tab des mots, pour trier
      var a = [];		// key : mot ; val : nbr d'occurences distinctes
      var n = 0;					// le nbr de records selectionnes

      for(var r=0; r<p4.edit.T_records.length; r++)
      {
        if(!p4.edit.T_records[r]._selected)
          continue;

        if(p4.edit.T_records[r].fields[meta_struct_id])
        {
          // le champ est present dans le record
          for(var f=0; f<p4.edit.T_records[r].fields[meta_struct_id].value.length; f++)
          {
            var v = p4.edit.T_records[r].fields[meta_struct_id].value[f];		// le mot
							
            if(typeof(a['%'+v]) == 'undefined')
            {
              a['%'+v] = {
                'n':0,
                'f':new Array()
              };	// n:nbr d'occurences DISTINCTES du mot ; f:flag presence mot dans r
              p4.edit.T_mval.push(v);
            }
						
            if(!a['%'+v].f[r])
              a['%'+v].n++;		// premiere apparition du mot dans le record r
            a['%'+v].f[r] = true;	// on ne recomptera pas le mot s'il apparait a nouveau dans le meme record
          }
        }
        n++;
      }
      p4.edit.T_mval.sort(SortCompareStrings);
			
      var t = "";
      for(var i in p4.edit.T_mval)	// pour lire le tableau 'a' dans l'ordre trie par 'p4.edit.T_mval'
      {
        v = p4.edit.T_mval[i];
        if(i>0 && p4.edit.T_mval[i-1]==p4.edit.T_mval[i])
          continue;	// on n'accepte pas les doublons
        if(a['%'+v].n == n)
        {
          // le mot etait present dans tous les records selectionnes
          t += "<div multi=\"0\" onclick=\"edit_clkmval(this, "+i+")\">" + v + "</div>";
        }
        else
        {
          // le mot n'etait pas present dans tous les records
          t += "<div multi=\"1\" class=\"hetero\" onclick=\"edit_clkmval(this, "+i+")\">" + v + "</div>";
        }
      }
      $('#ZTextMultiValued_values', p4.edit.editBox).html(t);
      $('#EditTextMultiValued', p4.edit.editBox).val("");
      $('#idEditZone', p4.edit.editBox).show();
      
      $('#EditTextMultiValued').trigger('keyup.maxLength');
      
      self.setTimeout("document.getElementById('EditTextMultiValued').focus();", 50);

      reveal_mval();
    }
  }
  else
  {
    // pas de champ, masquer la zone du textarea
    $("#idEditZone", p4.edit.editBox).hide();
    $(".editDiaButtons", p4.edit.editBox).hide();

  }
  activeField();
}

// ---------------------------------------------------------------------------
// on a clique sur une des multi-valeurs dans la liste
// ---------------------------------------------------------------------------
function edit_clkmval(mvaldiv, ival)
{
  $('#EditTextMultiValued', p4.edit.editBox).val(p4.edit.T_mval[ival]);
  $('#EditTextMultiValued').trigger('keyup.maxLength');
  reveal_mval();		// on highlight la liste sur la valeur saisie
}


// ---------------------------------------------------------------------------
// highlight la valeur en cours de saisie dans la liste des multi-valeurs
// appele par le onkeyup
// ---------------------------------------------------------------------------
function reveal_mval()
{
  var button_del = false;
  var button_add = true;
  var textZone = document.getElementById('EditTextMultiValued');
  var v = textZone.value;

  var vu = v.toUpperCase();

  if(p4.edit.T_fields[p4.edit.curField].tbranch)
  {
    if(v != "")
      ETHSeeker.search(v);
  }

  var l = v.length;
  $("#ZTextMultiValued_values > div").each(
    function(i)
    {
      with($(this))
      {
        var k  = html();
        var k = k.replace(new RegExp("</?I>", "gi"), "");
        var x  = k.substr(0,l);
        var k2 = k;
        if(x.toUpperCase() == vu)
          k2 = "<I>" + x + "</I>" + k.substr(l);
        html(k2);

        if(k == v)
        {
          button_del = true;		// on peut supprimer un mot meme s'il n'est pas dans tous les records
          addClass("hilighted");
          if(attr("multi")=="0")
            button_add = false;		// pas la peine d'ajouter un mot s'il est deja dans tous les records
        }
        else
        {
          removeClass("hilighted");
        }
        }
    }
    );

  if(v != "")
  {
    //		var nsel = 0;
    var tot_records = p4.edit.T_records.length;
		
    for(var r=0; r<tot_records; r++)
    {
      //			if(p4.edit.T_records[r]._selected)
      //				nsel++;
			
      isin = false;
      if(p4.edit.T_records[r].fields[p4.edit.curField])
      {
        // le champ est present dans le record
        var tot_fields = p4.edit.T_records[r].fields[p4.edit.curField].value.length;
        for(var f=0; !isin && f<tot_fields; f++)
        {
          if(p4.edit.T_records[r].fields[p4.edit.curField].value[f] == v)
          {
            isin = true;
            break;
          }
        }
      }
      if(isin)
      {
        $("#idEditDiaButtonsP_"+r).hide();
        var talt = $.sprintf(language.editDelSimple,v);
        $("#idEditDiaButtonsM_"+r).show().attr('alt', talt).attr('Title', talt);
      }
      else
      {
        $("#idEditDiaButtonsM_"+r).hide();
        $("#idEditDiaButtonsP_"+r).show();
        var talt = $.sprintf(language.editAddSimple,v);
        $("#idEditDiaButtonsP_"+r).show().attr('alt', talt).attr('Title', talt);
      }
    }
    $(".editDiaButtons", p4.edit.editBox).show();
  }
  else
  {
    button_del = button_add = false;	// on ne peut rien faire s'il n'y a rien de saisi
    $(".editDiaButtons", p4.edit.editBox).hide();
  }

  var talt;
  talt = $.sprintf(language.editAddMulti,v);
  $("#EditButAddMultiValued", p4.edit.editBox).css("visibility", button_add ? "visible" : "hidden").attr('alt', talt).attr('Title', talt);
  talt = $.sprintf(language.editDelMulti,v);
  $("#EditButDelMultiValued", p4.edit.editBox).css("visibility", button_del ? "visible" : "hidden").attr('alt', talt).attr('Title', talt);
	
  textZone.focus();
  return(true);
}

function edit_diabutton(irec, act)
{
  var meta_struct_id = p4.edit.curField;		// le champ en cours d'editing
  var v = $('#EditTextMultiValued', p4.edit.editBox).val();

  // on ajoute/supprime le mot dans le record
  if(p4.edit.T_records[irec].fields[meta_struct_id])
  {
    // le champ est present dans le record
    var found = false;
    for(var f=0; !found && f<p4.edit.T_records[irec].fields[meta_struct_id].value.length; f++)
    {
      if(p4.edit.T_records[irec].fields[meta_struct_id].value[f] == v)
      {
        found = true;	// le mot existe dans le champ
        if(act == 'del')
        {
          // on le supprime
          p4.edit.T_records[irec].fields[meta_struct_id].value.splice(f,1);
        }
      }
    }
    if(!found && act=='add')
    {
      // la valeur n'existait pas, on l'ajoute
      p4.edit.T_records[irec].fields[meta_struct_id].value.push(v);
      // on trie
      p4.edit.T_records[irec].fields[meta_struct_id].value = p4.edit.T_records[irec].fields[meta_struct_id].value.sort(SortCompareStrings);
      p4.edit.T_records[irec].fields[meta_struct_id].dirty = true;
      p4.edit.T_records[irec].fields[meta_struct_id].meta_struct_id = meta_struct_id;
    }
  }
  else
  {
    if(act=='add')
    {
      // le champ n'existe pas dans le record, on le cree
      p4.edit.T_records[irec].fields[meta_struct_id] = {
        'dirty': true,
        'value': [ v ],
        'meta_struct_id':meta_struct_id
      };
    }
  }

  p4.edit.T_mval = [];			// tab des mots, pour trier
  var a = new Array();		// key : mot ; val : nbr d'occurences distinctes
  var n = 0;					// le nbr de records selectionnes
  for(var r=0; r<p4.edit.T_records.length; r++)
  {
    if(!p4.edit.T_records[r]._selected)
      continue;

    if(p4.edit.T_records[r].fields[meta_struct_id])
    {
      // le champ est present dans le record
      for(var f=0; f<p4.edit.T_records[r].fields[meta_struct_id].value.length; f++)
      {
        var v = p4.edit.T_records[r].fields[meta_struct_id].value[f];		// le mot
					
        if(typeof(a['%'+v]) == 'undefined')
        {
          a['%'+v] = {
            'n':0,
            'f':new Array()
          };	// n:nbr d'occurences DISTINCTES du mot ; f:flag presence mot dans r
          p4.edit.T_mval.push(v);
        }
				
        if(!a['%'+v].f[r])
          a['%'+v].n++;		// premiere apparition du mot dans le record r
        a['%'+v].f[r] = true;	// on ne recomptera pas le mot s'il apparait a nouveau dans le meme record
      }
    }
    n++;
  }
	
  p4.edit.T_mval.sort(SortCompareStrings);
	
  var t = "";
  for(var i in p4.edit.T_mval)	// pour lire le tableau 'a' dans l'ordre trie par 'p4.edit.T_mval'
  {
    v = p4.edit.T_mval[i];
    if(i>0 && p4.edit.T_mval[i-1]==p4.edit.T_mval[i])
      continue;	// on n'accepte pas les doublons
    if(a['%'+v].n == n)
    {
      // le mot etait present dans tous les records selectionnes
      t += "<div multi=\"0\" onclick=\"edit_clkmval(this, "+i+")\">" + v + "</div>";
    }
    else
    {
      // le mot n'etait pas present dans tous les records
      t += "<div multi=\"1\" class=\"hetero\" onclick=\"edit_clkmval(this, "+i+")\">" + v + "</div>";
    }
  }
  $("#ZTextMultiValued_values", p4.edit.editBox).html(t);
  $("#idEditZone", p4.edit.editBox).show();

  self.setTimeout("document.getElementById('EditTextMultiValued').focus();", 50);
  reveal_mval();
}

// ---------------------------------------------------------------------------
// on a clique sur le bouton 'ajouter' un mot dans le multi-val
// ---------------------------------------------------------------------------
function edit_addmval()
{
  var meta_struct_id = p4.edit.curField;		// le champ en cours d'editing
  var v = $('#EditTextMultiValued', p4.edit.editBox).val();
  // on ajoute le mot dans tous les records selectionnes
  for(var r=0; r<p4.edit.T_records.length; r++)
  {
    if(!p4.edit.T_records[r]._selected)
      continue;

    if(p4.edit.T_records[r].fields[meta_struct_id])
    {
      // le champ est present dans le record
      var found = false;
      for(var f=0; !found && f<p4.edit.T_records[r].fields[meta_struct_id].value.length; f++)
      {
        if(p4.edit.T_records[r].fields[meta_struct_id].value[f] == v)
          found = true;	// le mot existe deja dans le champ pour ce record
      }
      if(!found)
      {
        // la valeur n'existait pas, on l'ajoute
        p4.edit.T_records[r].fields[meta_struct_id].value.push(v);
        // on trie
        p4.edit.T_records[r].fields[meta_struct_id].value = p4.edit.T_records[r].fields[meta_struct_id].value.sort(SortCompareStrings);
        p4.edit.T_records[r].fields[meta_struct_id].dirty = true;
        p4.edit.T_records[r].fields[meta_struct_id].meta_struct_id = meta_struct_id;
      }
    }
    else
    {
      // le champ n'existe pas dans le record, on le cree
      p4.edit.T_records[r].fields[meta_struct_id] = {
        'dirty': true,
        'value': [ v ],
        'meta_struct_id' : meta_struct_id
      };
    }
  }
	
  updateEditSelectedRecords(null);
}

// ---------------------------------------------------------------------------
// on a clique sur le bouton 'supprimer' un mot dans le multi-val
// ---------------------------------------------------------------------------
function edit_delmval()
{
  var meta_struct_id = p4.edit.curField;		// le champ en cours d'editing
  var v = $('#EditTextMultiValued', p4.edit.editBox).val();
  // on ajoute le mot dans tous les records selectionnes
  for(var r=0; r<p4.edit.T_records.length; r++)
  {
    if(!p4.edit.T_records[r]._selected)
      continue;

    if(p4.edit.T_records[r].fields[meta_struct_id])
    {
      // le champ est present dans le record
      var t=0;		// to
      for(var f=0; f < p4.edit.T_records[r].fields[meta_struct_id].value.length; f++ )
      {
        if(p4.edit.T_records[r].fields[meta_struct_id].value[f] != v)
          p4.edit.T_records[r].fields[meta_struct_id].value[t++] = p4.edit.T_records[r].fields[meta_struct_id].value[f];
      }
      p4.edit.T_records[r].fields[meta_struct_id].value.length = t;
      p4.edit.T_records[r].fields[meta_struct_id].dirty = true;
      p4.edit.T_records[r].fields[meta_struct_id].meta_struct_id = meta_struct_id;
    }
  }
	
  updateEditSelectedRecords(null);
}

// ---------------------------------------------------------------------------------------------------------
// en mode textarea, on clique sur ok, cancel ou fusion
// appele egalement quand on essaye de changer de champ ou d'image : si ret=false on interdit le changement
// ---------------------------------------------------------------------------------------------------------
function edit_validField(evt, action)
{
  // action : 'ok', 'fusion' ou 'cancel'
  if(p4.edit.curField == "?")
    return(true);

  if(action == "cancel")
  {
    // on restore le contenu du champ
    $("#idEditZTextArea", p4.edit.editBox).val(p4.edit.fieldLastValue) ;
    $('#idEditZTextArea').trigger('keyup.maxLength');
    p4.edit.textareaIsDirty = false;
    return(true);
  }

  if(action=="ask_ok" && p4.edit.textareaIsDirty && p4.edit.T_fields[p4.edit.curField]._status == 2)
  {
    alert(language.edit_hetero);
    return(false);
  }
  var o, newvalue;
  if(o = document.getElementById("idEditField_"+p4.edit.curField))
  {
    t = $("#idEditZTextArea", p4.edit.editBox).val();
		
    status = 0;
    firstvalue = "";
    for(i=0; i<p4.edit.T_records.length; i++)
    {
      if(!p4.edit.T_records[i]._selected)
        continue;			// on ne modifie pas les fiches non selectionnees

      if(action == "ok" || action == "ask_ok")
      {
        // remplace tout le contenu
        if(typeof(p4.edit.T_records[i].fields[p4.edit.curField]) == "undefined")
          p4.edit.T_records[i].fields[p4.edit.curField] = {
            'dirty': null,
            'value': null
          };		// si le champ n'existait pas, on le cree
        newvalue = t;
      }
      else if(action == "fusion" || action == "ask_fusion")
      {
        // ajoute a la fin
        if(typeof(p4.edit.T_records[i].fields[p4.edit.curField]) == "undefined")
        {
          // si le champ n'existait pas, on le cree
          p4.edit.T_records[i].fields[p4.edit.curField] = {
            'dirty': null,
            'value': null
          };
          newvalue = t;
        }
        else
        {
          if(p4.edit.T_fields[p4.edit.curField].multi)
            oldvalue = getConcatMulti(p4.edit.T_records[i].fields[p4.edit.curField].value);
          else
            oldvalue = p4.edit.T_records[i].fields[p4.edit.curField].value;
          if(oldvalue == "")
            newvalue = t;
          else
            newvalue = oldvalue + (p4.edit.T_fields[p4.edit.curField].multi ? " ; " : " ") + t;
        }
      }
			
      // on compare les valeurs du champ sur toutes les fiches selectionnees
      if(status == 0)
      {
        // premiere fiche
        status = 1;			// meme valeur partout
        firstvalue = newvalue;
      }
      else if(status == 1)
      {
        if(newvalue != firstvalue)
          status = 2;		// mixed
      }
			
      if(p4.edit.T_fields[p4.edit.curField].multi)
        newvalue = getSplitMulti(newvalue);

      p4.edit.T_records[i].fields[p4.edit.curField].dirty = true;
      p4.edit.T_records[i].fields[p4.edit.curField].value = newvalue;
      p4.edit.T_records[i].fields[p4.edit.curField].meta_struct_id = p4.edit.curField;
			
      check_required(i, p4.edit.curField);
    }
		
    p4.edit.T_fields[p4.edit.curField]._status = status;
    if(status == 2)
    {
      p4.edit.T_fields[p4.edit.curField]._value = "";
      o.innerHTML = "<span class='hetero'>xxxxx</span>";
    }
    else
    {
      p4.edit.T_fields[p4.edit.curField]._value = firstvalue;
      // l'objet 'o' est un span avec white-space:normal pour wrapper.
      // pour afficher les cr/lf du text, on adapte un peu pour html
      o.innerHTML = cleanTags(firstvalue).replace(/\n/gm, "<span style='color:#0080ff'>&para;</span><br/>");
    }
  }

  p4.edit.textareaIsDirty = false;
	
	
  editField(evt, p4.edit.curField);
  return(true);
}

function skipImage(evt, step)
{
  var cache = $('#EDIT_FILM2');
  var first = $('.diapo.selected:first', cache);
  var last = $('.diapo.selected:last', cache);
  var sel = $('.diapo.selected', cache);

  sel.removeClass('selected');
	
  var i = step==1 ? (parseInt(last.attr('pos'))+1) : (parseInt(first.attr('pos'))-1);

  if(i < 0)
    i = parseInt($('.diapo:last', cache).attr('pos'));
  else
  if(i >= $('.diapo',cache).length)
    i = 0;

  edit_clk_editimg(evt, i);
}

function edit_select_all()
{
  $('#EDIT_FILM2 .diapo', p4.edit.editBox).addClass('selected');
	
  for(i in p4.edit.T_records)
    p4.edit.T_records[i]._selected = true;

  p4.edit.lastClickId = 1 ;
	
  updateEditSelectedRecords(null);		// null : no evt available
}

// ---------------------------------------------------------------------------
// on a clique sur une thumbnail
// ---------------------------------------------------------------------------
function edit_clk_editimg(evt, i)
{
  if(p4.edit.curField >= 0)
  {
    if(p4.edit.textareaIsDirty && edit_validField(evt, "ask_ok")==false)
      return;
  }

  // guideline : si on mousedown sur une selection, c'est qu'on risque de draguer, donc on ne desectionne pas
  if(evt && evt.type=="mousedown" && p4.edit.T_records[i]._selected)
    return;

  if( evt && is_shift_key(evt) && p4.edit.lastClickId != null )
  {
    // shift donc on sel du p4.edit.lastClickId a ici
    var pos_from = p4.edit.T_pos[p4.edit.lastClickId];
    var pos_to   = p4.edit.T_pos[i];
    if( pos_from > pos_to )
    {
      var tmp  = pos_from;
      pos_from = pos_to;
      pos_to   = tmp;
    }

    var pos;
    for(pos=pos_from; pos<=pos_to; pos++ )
    {
      var id = p4.edit.T_id[pos];
      if(!p4.edit.T_records[id]._selected)	// toutes les fiches selectionnees
      {
        p4.edit.T_records[id]._selected = true;
        $("#idEditDiapo_"+id, p4.edit.editBox).addClass('selected');
      }
    }
  }
  else
  {
    if( !evt || !is_ctrl_key(evt)  )
    {
      // on deselectionne tout avant
      var id;
      for(id in p4.edit.T_records)
      {
        if(p4.edit.T_records[id]._selected)	// toutes les fiches selectionnees
        {
          p4.edit.T_records[id]._selected = false;
          $("#idEditDiapo_"+id, p4.edit.editBox).removeClass('selected');
        }
      }
    }
    if(i >= 0)
    {
      p4.edit.T_records[i]._selected = !p4.edit.T_records[i]._selected;
      if(p4.edit.T_records[i]._selected)
        $("#idEditDiapo_"+i, p4.edit.editBox).addClass('selected');
      else
        $("#idEditDiapo_"+i, p4.edit.editBox).removeClass('selected');
    }
  }
	
  $('#TH_Opreview .PNB10').empty();
	
  var selected = $('#EDIT_FILM2 .diapo.selected');
  if(selected.length == 1)
  {

    var r = selected.attr('id').split('_').pop();
    previewEdit(r);
  }

  p4.edit.lastClickId = i ;
  updateEditSelectedRecords(evt);
}

// ---------------------------------------------------------------------------
// on a clique sur une checkbow de status
// ---------------------------------------------------------------------------
function edit_clkstatus(evt, bit, val)
{
  var ck0 = $("#idCheckboxStatbit0_"+bit);
  var ck1 = $("#idCheckboxStatbit1_"+bit);
  switch(val)
  {
    case 0:
      ck0.attr('class', "gui_ckbox_1");
      ck1.attr('class', "gui_ckbox_0");
      break;
    case 1:
      ck0.attr('class', "gui_ckbox_0");
      ck1.attr('class', "gui_ckbox_1");
      break;
  }
  var id;
  for(id in p4.edit.T_records)
  {
    if(p4.edit.T_records[id]._selected)	// toutes les fiches selectionnees
    {
      if($('#idEditDiapo_'+id).hasClass('nostatus'))
        continue;
			
      p4.edit.T_records[id].statbits[bit].value = val;
      p4.edit.T_records[id].statbits[bit].dirty = true;
    }
  }
}

function updateEditSelectedRecords(evt)
{
  for(n in p4.edit.T_statbits)	// tous les statusbits de la base
  {
    p4.edit.T_statbits[n]._value = "-1";			// val unknown
    for(i in p4.edit.T_records)
    {
      if(!p4.edit.T_records[i]._selected)
        continue;
      if(p4.edit.T_records[i].statbits.length === 0)
        continue;

      if(p4.edit.T_statbits[n]._value == "-1")
        p4.edit.T_statbits[n]._value = p4.edit.T_records[i].statbits[n].value;
      else
      if(p4.edit.T_statbits[n]._value != p4.edit.T_records[i].statbits[n].value)
        p4.edit.T_statbits[n]._value = "2";
    }
    var ck0 = $("#idCheckboxStatbit0_"+n);
    var ck1 = $("#idCheckboxStatbit1_"+n);

    switch(p4.edit.T_statbits[n]._value)
    {
      case "0":
      case 0:
        ck0.removeClass('gui_ckbox_0 gui_ckbox_2').addClass("gui_ckbox_1");
        ck1.removeClass('gui_ckbox_1 gui_ckbox_2').addClass("gui_ckbox_0");
        break;
      case "1":
      case 1:
        ck0.removeClass('gui_ckbox_1 gui_ckbox_2').addClass("gui_ckbox_0");
        ck1.removeClass('gui_ckbox_0 gui_ckbox_2').addClass("gui_ckbox_1");
        break;
      case "2":
        ck0.removeClass('gui_ckbox_0 gui_ckbox_1').addClass("gui_ckbox_2");
        ck1.removeClass('gui_ckbox_0 gui_ckbox_1').addClass("gui_ckbox_2");
        break;
    }
  }


  var nostatus = $('.diapo.selected.nostatus', p4.edit.editBox).length;
  var status_box = $('#ZTextStatus');
  $('.nostatus, .somestatus, .displaystatus', status_box).hide();
	
  if(nostatus == 0)
  {
    $('.displaystatus', status_box).show();
  }
  else
  {
    var yesstatus = $('.diapo.selected', p4.edit.editBox).length;
    if(nostatus == yesstatus)
    {
      $('.nostatus', status_box).show();
    }
    else
    {
      $('.somestatus, .displaystatus', status_box).show();
    }
  }
	
  // calcul des valeurs suggerees COMMUNES aux records (collections) selectionnes //
  for(f in p4.edit.T_fields)	// tous les champs de la base
    p4.edit.T_fields[f]._sgval = [];
  var t_lsgval = {};
  var t_selcol = {};		// les bases (coll) dont au - une thumb est selectionnee
  var ncolsel = 0;
  var nrecsel = 0;
  for(i in p4.edit.T_records)
  {
    if(!p4.edit.T_records[i]._selected)
      continue;
    nrecsel++;
			
    var bid = "b"+p4.edit.T_records[i].bid;
    if(t_selcol[bid])
      continue;
			
    t_selcol[bid] = 1;
    ncolsel++;
    for(f in p4.edit.T_sgval[bid])
    {
      if(!t_lsgval[f])
        t_lsgval[f] = {};
      for(ivs in p4.edit.T_sgval[bid][f])
      {
        vs = p4.edit.T_sgval[bid][f][ivs];
        if(!t_lsgval[f][vs])
          t_lsgval[f][vs] = 0;
        t_lsgval[f][vs]++;
      }
    }
  }
  var t_sgval = {};
  for(f in t_lsgval)
  {
    for(sv in t_lsgval[f])
    {
      if(t_lsgval[f][sv] == ncolsel)
      {
        p4.edit.T_fields[f]._sgval.push( {
          label:sv,
          onclick: function(menuItem, menu, e, label)
          {
            if(p4.edit.T_fields[p4.edit.curField].multi)
            {
              $("#EditTextMultiValued", p4.edit.editBox).val(label);
              $('#EditTextMultiValued').trigger('keyup.maxLength');
              edit_addmval();
            }
            else
            {
              if(is_ctrl_key(e))
              {
                var t = $("#idEditZTextArea", p4.edit.editBox).val();
                $("#idEditZTextArea", p4.edit.editBox).val(t + (t?" ; ":"") + label);
              }
              else
              {
                $("#idEditZTextArea", p4.edit.editBox).val(label);
              }
              $('#idEditZTextArea').trigger('keyup.maxLength');
              p4.edit.textareaIsDirty = true;
              if(p4.edit.T_fields[p4.edit.curField]._status != 2)
                edit_validField(evt, "ask_ok");
            }
          }
        }
        );
      }
    }
    if(p4.edit.T_fields[f]._sgval.length > 0)
    {
      $("#editSGtri_"+f, p4.edit.editBox).css("visibility", "visible");
      $("#editSGtri_"+f, p4.edit.editBox).unbind();
      $("#editSGtri_"+f, p4.edit.editBox).contextMenu(
        p4.edit.T_fields[f]._sgval,
        {
          theme:'vista',
          openEvt:"click",
          beforeShow:function(a,b,c,d)
          {
            var fid = this.target.getAttribute('id').substr(10);
            if(!p4.edit.textareaIsDirty || edit_validField(null, "ask_ok")==true)
            {
              editField(null, fid);
              return(true);
            }
            else
            {
              return(false);
            }
          }
        }
        );
    }
    else
    {
      $("#editSGtri_"+f, p4.edit.editBox).css("visibility", "hidden");
    }
  }
	
  $('#idFrameE .ww_status', p4.edit.editBox).html( nrecsel + " record(s) selected for editing");

  for(f in p4.edit.T_fields)	// tous les champs de la base
  {
    p4.edit.T_fields[f]._status = 0;			// val unknown
    for(i in p4.edit.T_records)
    {
      if(!p4.edit.T_records[i]._selected)
        continue;
				
      if(typeof(p4.edit.T_records[i].fields[f])=="undefined")
      {
        // le champ n'existe pas dans ce record, on le considere comme 'vide'
        v = "";
      }
      else
      {
        // le champ existe dans la fiche
        if(p4.edit.T_fields[f].multi)
        {
          // champ multi : on compare la concat des valeurs
          v = getConcatMulti(p4.edit.T_records[i].fields[f].value);
        }
        else
        {
          v = p4.edit.T_records[i].fields[f].value;
        }
      }
			
      if(p4.edit.T_fields[f]._status == 0)
      {
        p4.edit.T_fields[f]._value  = v;
        p4.edit.T_fields[f]._status = 1;
      }
      else if(p4.edit.T_fields[f]._status == 1 && p4.edit.T_fields[f]._value != v)
      {
        p4.edit.T_fields[f]._value  = "*****";
        p4.edit.T_fields[f]._status = 2;
        break;	// plus la peine de verifier le champ sur les autres records
      }
    }
    if(o = document.getElementById("idEditField_"+f))
    {
      if(p4.edit.T_fields[f]._status == 2)	// mixed
        o.innerHTML = "<span class='hetero'>xxxxx</span>";
      else
        o.innerHTML = cleanTags(p4.edit.T_fields[f]._value).replace(/\n/gm, "<span style='color:#0080ff'>&para;</span><br/>");
    }
  }

  if(p4.edit.curField == -1)
    editStatus(evt);
  else
    editField(evt, p4.edit.curField);
}

function SortCompareStrings(a, b)
{
  if(typeof(a) != 'string')
    return(-1);
  if(typeof(b) != 'string')
    return(1);
  var na = a.toUpperCase();
  var nb = b.toUpperCase();
  if(na == nb)
    return(0);
  return(na < nb ? -1 : 1);
}

// ---------------------------------------------------------------------
// retourne une valeur concatenee pour comparer des champs multi-val
// ---------------------------------------------------------------------
function getConcatMulti(a)
{
  var v = "";
  var i;
  if(typeof(a)=="string")
    return(a);
  a = a.sort();
  for(i in a)
  {
    if(a[i]=="")
      continue;	// on n'accepte pas les mots vides
    if(i==0 || a[i]!=a[i-1])	// on supprime les doublons
      v += (v ? " ; ":"") + a[i];
  }
  return(v);
}

// ---------------------------------------------------------------------
// retourne une valeur decoupee en multi-val
// ---------------------------------------------------------------------
function getSplitMulti(s)
{
  var v = new RegExp("\b*;\b*", "g");
  return(s.split(v));
}

//---------------------------------------------------------------------
//nettoie
//---------------------------------------------------------------------
function cleanTags(string)
{
  var chars2replace = [ {
    f:"&",
    t:"&amp;"
  }, {
    'f':"<",
    't':"&lt;"
  }, {
    'f':">",
    't':"&gt;"
  },  ];
  for(c in chars2replace)
    string = string.replace(RegExp(chars2replace[c].f,"g") ,chars2replace[c].t);
  return string;
}

function check_required(id_r, id_f)
{
  var required_fields = false;

  if(typeof id_r == 'undefined')
    id_r = false;
  if(typeof id_f == 'undefined')
    id_f = false;
	
  for(f in p4.edit.T_fields)
  {
    if(id_f !== false && f != id_f)
      continue;
		
    var name = p4.edit.T_fields[f].name;
		
    if(!p4.edit.T_fields[f].required)
      continue;
		
    for(r in p4.edit.T_records)
    {
      if(id_r !== false && r != id_r)
        continue;
			
      var elem = $('#idEditDiapo_'+r+' .require_alert');
			
      elem.hide();
			
      if(!p4.edit.T_records[r].fields[f])
      {
        elem.show();
        required_fields = true;
      }
      else
      {
        var values = p4.edit.T_fields[f].multi ? (p4.edit.T_records[r].fields[f].value.length > 0 ? p4.edit.T_records[r].fields[f].value : ['']) : [ p4.edit.T_records[r].fields[f].value ];
	
        var check_required = $.trim(values.join(''));
        if(check_required == '')
        {
          elem.show();
          required_fields = true;
        }
      }
    }
		
  }
  return required_fields;
}

// ----------------------------------------------------------------------------------
// on a clique sur le 'ok' general : save
// ----------------------------------------------------------------------------------
function edit_applyMultiDesc(evt)
{
  var sendorder = "";
  var sendChuOrder = "";
  	
  var t = [];

  if(p4.edit.textareaIsDirty && edit_validField(evt, "ask_ok")==false)
    return(false);
  	
  var required_fields = check_required();

  if(required_fields)
  {
    alert(language.some_required_fields);
    return;
  }

  $("#EDIT_ALL", p4.edit.editBox).hide();

  $("#EDIT_WORKING", p4.edit.editBox).show();
	
  for(r in p4.edit.T_records)
  {
    var record_datas = {
      record_id : p4.edit.T_records[r].rid,
      metadatas : [],
      edit : 0,
      status : null
    };

    var editDirty = false;

    for(f in p4.edit.T_records[r].fields)
    {
      var name    = p4.edit.T_fields[f].name;
      var meta_id = '';
      if(typeof p4.edit.T_records[r].fields[f].meta_id != 'undefined')
        var meta_id = p4.edit.T_records[r].fields[f].meta_id;
      var type    = p4.edit.T_fields[f].type;
      var values  = p4.edit.T_fields[f].multi ? (p4.edit.T_records[r].fields[f].value.length > 0 ? p4.edit.T_records[r].fields[f].value : ['']) : [ p4.edit.T_records[r].fields[f].value ];
			
      if(!p4.edit.T_records[r].fields[f].dirty)
        continue;
      
      editDirty = true;
      record_datas.edit = 1;

      var temp = {
        meta_id : meta_id,
        meta_struct_id : p4.edit.T_records[r].fields[f].meta_struct_id,
        value  : []
      };

      for(v in values)
      {
        temp.value.push(cleanTags(values[v]));
      }
      record_datas.metadatas.push(temp);
    }
    // les statbits
    var tsb  = [];
    for(var n=0; n<64; n++)
      tsb[n] = 'x';
    sb_dirty = false;
    for(var n in p4.edit.T_records[r].statbits)
    {
      if(p4.edit.T_records[r].statbits[n].dirty)
      {
        tsb[63-n] = p4.edit.T_records[r].statbits[n].value;
        sb_dirty = true;
      }
    }

    if(sb_dirty || editDirty)
    {
      if(sb_dirty === true)
        record_datas.status = tsb.join("");

      t.push(record_datas);
    }
  }
	
  var options = {
    mds:t,
    sbid : p4.edit.sbas_id,
    act:'WORK',
    lst:$('#edit_lst').val(),
    act_option:'SAVE'+p4.edit.what,
    regbasprid:p4.edit.regbasprid,
    // newrepresent:p4.edit.newrepresent,
    ssel:p4.edit.ssel
  };
  if(p4.edit.newrepresent != false)
    options.newrepresent = p4.edit.newrepresent;
	
  //  options.mds = t;
	
	
  $.ajax({
    url :"/prod/records/edit/apply/"
    ,
    data : options
    //    ,dataType:'json'
    ,
    type:'POST'
    ,
    success : function(data){
      if(p4.edit.what == 'GRP' || p4.edit.what == 'SSEL')
        refreshBaskets('current');
      $("#Edit_copyPreset_dlg").remove();
      $('#EDITWINDOW').hide();
      hideOverlay(2);
      if(p4.preview.open)
        reloadPreview();
      return;
    }
  });
	
}

function edit_cancelMultiDesc(evt)
{
  
  
  var dirty = false;
  
  evt.cancelBubble = true;
  if(evt.stopPropagation)
    evt.stopPropagation();
	
  if(p4.edit.curField >= 0)
  {
    if(p4.edit.textareaIsDirty && edit_validField(evt, "ask_ok")==false)
      return;
  }
	
  for(r in p4.edit.T_records)
  {
    for(f in p4.edit.T_records[r].fields)
    {
      if( (dirty |= p4.edit.T_records[r].fields[f].dirty) )
        break;
    }
    for(var n in p4.edit.T_records[r].statbits)
    {
      if( (dirty |= p4.edit.T_records[r].statbits[n].dirty) )
        break;
    }
  }
  if(!dirty || confirm(language.confirm_abandon))
  {
    $("#Edit_copyPreset_dlg").remove();
    $('#idFrameE .ww_content', p4.edit.editBox).empty();

    // on reaffiche tous les thesaurus
    for(i in p4.thesau.thlist)	// tous les thesaurus
    {
      var bid = p4.thesau.thlist[i].sbas_id;
      var e = document.getElementById('TH_T.'+bid+'.T');
      if(e)
        e.style.display = "";
    }
    self.setTimeout("$('#EDITWINDOW').fadeOut();hideOverlay(2);", 100);
		
  }
}

// ======================================================
// ================ gestion du thesaurus ================
// ======================================================

function edit_clickThesaurus(event)	// onclick dans le thesaurus
{
  // on cherche ou on a clique
  for(e=event.srcElement ? event.srcElement : event.target; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
  ;
  if(e)
  {
    switch(e.id.substr(0,4))
    {
      case "TH_P":	// +/- de deploiement de mot
        js = "edit_thesaurus_ow('"+e.id.substr(5)+"')";
        self.setTimeout(js, 10);
        break;
    }
  }
  return(false);
}

function edit_dblclickThesaurus(event)	// ondblclick dans le thesaurus
{
  for(e=event.srcElement ? event.srcElement : event.target; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
  ;
  if(e)
  {
    switch(e.id.substr(0,4))
    {
      case "TH_W":
        if(p4.edit.curField >= 0)
        {
          var w = e.innerHTML;
          if(p4.edit.T_fields[p4.edit.curField].multi)
          {
            $("#EditTextMultiValued", p4.edit.editBox).val(w);
            $('#EditTextMultiValued').trigger('keyup.maxLength');
            edit_addmval();
          }
          else
          {
            $("#idEditZTextArea", p4.edit.editBox).val(w);
            $('#idEditZTextArea').trigger('keyup.maxLength');
            p4.edit.textareaIsDirty = true;
          }
        }
        break;
    }
  }
  return(false);
}

function edit_thesaurus_ow(id)	// on ouvre ou ferme une branche de thesaurus
{
  var o = document.getElementById("TH_K."+id);
  if(o.className=="o")
  {
    // on ferme
    o.className = "c";
    document.getElementById("TH_P."+id).innerHTML = "+";
    document.getElementById("TH_K."+id).innerHTML = language.loading;
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

    ETHSeeker.openBranch(id, thid);
  }
  return(false);
}

function EditThesaurusSeeker(sbas_id)
{
  this.jq = null;
	
  this.sbas_id = sbas_id;
	
  var zid = (""+sbas_id).replace(new RegExp("\\.", "g"), "\\.") + "\\.T";
	
  this.TH_P_node = $("#TH_P\\." + zid, p4.edit.editBox);
  this.TH_K_node = $("#TH_K\\." + zid, p4.edit.editBox);
	
  this._ctimer = null;

  this.search = function(txt) {
    if(this._ctimer)
      clearTimeout(this._ctimer);
    var js = "ETHSeeker.search_delayed('"+txt.replace("'", "\\'")+"');" ;
    this._ctimer = setTimeout(js, 125);
  };
	
  this.search_delayed = function(txt) {
    if(this.jq && typeof this.jq.abort == "function")
    {
      this.jq.abort();
      this.jq = null;
    }
    txt = txt.replace("'", "\\'");
    var url = "/xmlhttp/openbranches_prod.h.php";
    var parms  = {
      bid:this.sbas_id,
      lng:p4.lng,
      t:txt,
      mod:"TREE",
      u:Math.random()
    };

    var me = this;
		
    this.jq = $.ajax({
      url: url,
      data: parms,
      type:'POST',
      success: function(ret)
      {
        me.TH_P_node.html("...");
        me.TH_K_node.attr("class", "h").html(ret);
        me.jq = null;
      },
      error:function(){

      },
      timeout:function(){

      }
    });
  };

  this.openBranch = function(id, thid) {
    if(this.jq)
    {
      this.jq.abort();
      this.jq = null;
    }
    var url = "/xmlhttp/getterm_prod.h.php";
    var parms  = {
      bid:this.sbas_id,
      lng:p4.lng,
      sortsy:1,
      id:thid,
      typ:"TH"
    } ;
    var me = this;


    this.jq = $.ajax({
      url: url,
      data: parms,
      success: function(ret)
      {
        var zid = "#TH_K\\." + id.replace(new RegExp("\\.", "g"), "\\.");	// escape les '.' pour jquery
        $(zid, p4.edit.editBox).html(ret);
        me.jq = null;
      },
      error:function(){

      },
      timeout:function(){

      }
    });
  };
}


























function replace()
{
	
	
  var field   = $("#EditSRField", p4.edit.editBox).val();
  var search  = $("#EditSearch",  p4.edit.editBox).val();
  var replace = $("#EditReplace", p4.edit.editBox).val();
	
  var where  = $("[name=EditSR_Where]:checked", p4.edit.editBox).val();
  var commut  = "";
  var rgxp    = $("#EditSROptionRX", p4.edit.editBox).attr('checked') ? true : false;
	
  var r_search;
  if(rgxp)
  {
    r_search = search;
    commut  = ($("#EditSR_RXG", p4.edit.editBox).attr('checked') ? "g" : "")
    + ($("#EditSR_RXI", p4.edit.editBox).attr('checked') ? "i" : "") ;
  }
  else
  {
    commut  = $("#EditSR_case", p4.edit.editBox).attr('checked') ? "g" : "gi";
    r_search = "";
    for(i=0; i<search.length; i++)
    {
      var c = search.charAt(i);
      if( ("^$[]()|.*+?\\").indexOf(c) != -1 )
        r_search += "\\";
      r_search += c;
    }
    if(where == "exact")
      r_search = "^" + r_search + "$";
  }
	
  search = new RegExp(r_search, commut);
	
  var r, f, v, oldv, oldlist;
  for(r in p4.edit.T_records)
  {
    if(!p4.edit.T_records[r]._selected)
      continue;
    for(f in p4.edit.T_records[r].fields)
    {
      if(field == '' || field==f)
      {
        if(typeof(p4.edit.T_records[r].fields[f].value) == "string")
        {
          var oldv = p4.edit.T_records[r].fields[f].value;
          var newv = oldv.replace(search, replace);
          if(newv != oldv)
          {
            p4.edit.T_records[r].fields[f].value = newv;
            p4.edit.T_records[r].fields[f].dirty = true;
            p4.edit.T_records[r].fields[f].meta_struct_id = f;
          }
        }
        else
        {
          for(v in p4.edit.T_records[r].fields[f].value)
          {
            var oldv = p4.edit.T_records[r].fields[f].value[v];
            var newv = oldv.replace(search, replace);
            if(newv != oldv)
            {
              p4.edit.T_records[r].fields[f].value[v] = newv;
              p4.edit.T_records[r].fields[f].dirty = true;
              p4.edit.T_records[r].fields[f].meta_struct_id = f;
            }
          }
          // no duplicates in multi-valued please
          var oldlist = p4.edit.T_records[r].fields[f].value;
          p4.edit.T_records[r].fields[f].value = [];
          for(var i in oldlist)
          {
            var found = false;
            for(v in p4.edit.T_records[r].fields[f].value)
            {
              if(p4.edit.T_records[r].fields[f].value[v] == oldlist[i])
              {
                found = true;
                break;
              }
            }
            if(!found)
              p4.edit.T_records[r].fields[f].value.push(oldlist[i]);
          }
          p4.edit.T_records[r].fields[f].value.sort(SortCompareStrings);
        }
      }
    }
  }
  updateEditSelectedRecords(null);
}

function changeReplaceMode(ckRegExp)
{
	
	
  if(ckRegExp.checked)
  {
    $("#EditSR_TX", p4.edit.editBox).hide();
    $("#EditSR_RX", p4.edit.editBox).show();
  }
  else
  {
    $("#EditSR_RX", p4.edit.editBox).hide();
    $("#EditSR_TX", p4.edit.editBox).show();
  }
}

function preset_copy()
{
  var html = "";
  for(i in p4.edit.T_fields)
  {
    if(p4.edit.T_fields[i]._status == 1)
    {
      var c = p4.edit.T_fields[i]._value == "" ? "" : "checked=\"1\"";
      var v = p4.edit.T_fields[i]._value;
      html += "<div><input type=\"checkbox\" class=\"checkbox\" id=\"new_preset_"+p4.edit.T_fields[i].name+"\" value=\""+i+"\" "+c+"/><label for=\"new_preset_"+p4.edit.T_fields[i].name+"\">" +"<b>" + p4.edit.T_fields[i].name + " : </b></label> ";
      html += cleanTags(p4.edit.T_fields[i]._value) + "</div>";
    }
  }
  $("#Edit_copyPreset_dlg FORM DIV").html(html);
  $("#Edit_copyPreset_dlg").dialog('open');
}

function preset_paint(data)
{
  $(".EDIT_presets_list", p4.edit.editBox).html(data.html);
  $(".EDIT_presets_list A.triangle").click(
    function()
    {
      $(this).parent().parent().toggleClass("opened");
      return false;
    }
    );
	
  $(".EDIT_presets_list A.title").dblclick(
    function()
    {
      var preset_id = $(this).parent().parent().attr("id");
      if(preset_id.substr(0, 12)=="EDIT_PRESET_")
        preset_load(preset_id.substr(12));
      return false;
    }
    );
	
  $(".EDIT_presets_list A.delete").click(
    function()
    {
      var li = $(this).closest("LI");
      var preset_id = li.attr("id");
      var title = $(this).parent().children(".title").html();
      if(preset_id.substr(0, 12)=="EDIT_PRESET_" && confirm("supprimer le preset '" + title + "' ?"))
      {
        preset_delete(preset_id.substr(12), li);
      }
      return false;
    }
    );
}

function preset_delete(preset_id, li)
{
  var p = {
    "act":"DELETE",
    "presetid":preset_id
  };
  $.getJSON(
    "/xmlhttp/editing_presets.j.php",
    p,
    function(data, textStatus)
    {
      li.remove();
    }
    );
}

function preset_load(preset_id)
{
  var p = {
    "act":"LOAD",
    "presetid":preset_id
  };
  $.getJSON(
    "/xmlhttp/editing_presets.j.php",
    p,
    function(data, textStatus)
    {
      $("#Edit_copyPreset_dlg").dialog("close");
      
      for(i in p4.edit.T_fields)
      {
        p4.edit.T_fields[i].preset = null;
        if(typeof(data.fields[p4.edit.T_fields[i].name]) != "undefined")
        {
          p4.edit.T_fields[i].preset = data.fields[p4.edit.T_fields[i].name];
        }
      }
      for(var r=0; r<p4.edit.T_records.length; r++)
      {
        if(!p4.edit.T_records[r]._selected)
          continue;

        for(i in p4.edit.T_fields)
        {
          if(p4.edit.T_fields[i].preset != null)
          {
            if(p4.edit.T_fields[i].multi)
            {
              p4.edit.T_records[r].fields[""+i] = {
                value:[], 
                dirty:true
              };// = {
              var n = 0;
              for(val in p4.edit.T_fields[i].preset)
              {
                p4.edit.T_records[r].fields[""+i].value[n] = p4.edit.T_fields[i].preset[val];
                n++;
              }
            }
            else
            {
              p4.edit.T_records[r].fields[""+i] = {
                "value":p4.edit.T_fields[i].preset[0],
                "dirty":true
              };
            }
          }
        }
      }
      updateEditSelectedRecords();
    }
    );
}













function hsplit1()
{
  var el = $('#EDIT_TOP');
  if(el.length == 0)
    return;
  var h = $(el).outerHeight();
  $(el).height(h);
  var t = $(el).offset().top + h;

  $("#EDIT_MID", p4.edit.editBox).css("top", (t)+"px");
}
function vsplit1()
{
  $('#divS_wrapper').height('auto');
	
  var el = $('#divS_wrapper');
  if(el.length == 0)
    return;
  var a = $(el).width();
  el.width(a);

  $("#idEditZone", p4.edit.editBox).css("left", (a+20 ) );
}
function vsplit2()
{
  var el = $('#EDIT_MID_R');
  if(el.length == 0)
    return;
  var a = $(el).width();
  el.width(a);
  var v = $('#EDIT_ALL').width() -a -20;

  $("#EDIT_MID_L", p4.edit.editBox).width(v);
}

function setPreviewEdit()
{
  if(!$('#TH_Opreview').is(':visible'))
    return false;
	
  var selected = $('#EDIT_FILM2 .diapo.selected');
	
  if(selected.length != 1)
  {
    return;
  }

  var id = selected.attr('id').split('_').pop();
	
  var container = $('#TH_Opreview');
  var zoomable = $('img.record.zoomable', container);
	
  if(zoomable.length > 0 && zoomable.hasClass('zoomed'))
    return;
	
  //  var datas = p4.edit.T_records[id].preview;
	
  var h = parseInt($('input[name=height]', container).val());
  var w = parseInt($('input[name=width]', container).val());
	
  //  if(datas.doctype == 'video')
  //  {
  //    var h = parseInt(datas.height);
  //    var w = parseInt(datas.width);
  //  }
  var t=0;
  var de = 0;

  var margX = 0;
  var margY = 0;
	
  if($('img.record.record_audio', container).length > 0)
  {
    var margY = 100;
    de = 60;
  }

  var display_box = $('#TH_Opreview .PNB10');
  var dwidth = display_box.width();
  var dheight = display_box.height();
	
	
  //  if(datas.doctype != 'flash')
  //  {
  var ratioP = w / h;
  var ratioD = dwidth / dheight;
		
  if (ratioD > ratioP) {
    //je regle la hauteur d'abord
    if ((parseInt(h) + margY) > dheight) {
      h = Math.round(dheight - margY);
      w = Math.round(h * ratioP);
    }
  }
  else {
    if ((parseInt(w) + margX) > dwidth) {
      w = Math.round(dwidth - margX);
      h = Math.round(w / ratioP);
    }
  }
  //  }
  //  else
  //  {
  //
  //    h = Math.round(dheight - margY);
  //    w = Math.round(dwidth - margX);
  //  }
  t = Math.round((dheight - h - de) / 2);
  var l = Math.round((dwidth - w) / 2);
  $('.record',container).css({
    width: w,
    height: h,
    top: t,
    left: l
  }).attr('width',w).attr('height',h);
	
}

function previewEdit(r)
{
	
  $('#TH_Opreview .PNB10').empty().append(p4.edit.T_records[r].preview);
	
  //  var data = p4.edit.T_records[r].preview;

  //  if ((data.doctype == 'video' || data.doctype == 'audio' || data.doctype == 'flash')) {
  //    if(data.doctype != 'video' && data.flashcontent.url)
  //    {
  //      var flashvars = false;
  //      var params = {
  //        menu: "false",
  //        flashvars: data.flashcontent.flashVars,
  //        movie: data.flashcontent.url,
  //        allowFullScreen :"true",
  //        wmode: "transparent"
  //      };
  //      var attributes = false;
  //      if (data.doctype != 'audio') {
  //        attributes = {
  //          styleclass: "PREVIEW_PIC"
  //        };
  //      }
  //      swfobject.embedSWF(data.flashcontent.url, "FLASHPREVIEW", data.flashcontent.width, data.flashcontent.height, "9.0.0", false, flashvars, params, attributes);
  //    }
  //    else
  //    {
  //      flowplayer("FLASHPREVIEW", '/include/flowplayer/flowplayer-3.2.2.swf',{
  //        clip: {
  //          autoPlay: true,
  //          autoBuffering:true,
  //          provider: 'h264streaming',
  //          metadata: false,
  //          scaling:'fit',
  //          url: data.flashcontent.flv
  //        },
  //        onError:function(code,message){
  //          getNewVideoToken(p4.edit.T_records[r].sbas_id, p4.edit.T_records[r].rid, this);
  //        },
  //        plugins: {
  //          h264streaming: {
  //            url: '/include/flowplayer/flowplayer.pseudostreaming-3.2.2.swf'
  //          }
  //        }
  //      });
  //    }
  //  }
	
  if($('img.PREVIEW_PIC.zoomable').length > 0)
  {
    $('img.PREVIEW_PIC.zoomable').draggable();
  }
  setPreviewEdit();
}

function startThisEditing(sbas_id,what,regbasprid,ssel)
{

  p4.edit.sbas_id = sbas_id;
  p4.edit.what = what;
  p4.edit.regbasprid = regbasprid;
  p4.edit.ssel = ssel;
	
  $('#EDIT_MID_R .tabs').tabs();
	
  $('#divS div.edit_field:odd').addClass('odd');
  $('#divS div').bind('mouseover',function(){
    $(this).addClass('hover');
  }).bind('mouseout',function(){
    $(this).removeClass('hover');
  });

  $('#editcontextwrap').remove();
	
  if($('#editcontextwrap').length == 0)
    $('body').append('<div id="editcontextwrap"></div>');
	
  self.setTimeout("edit_select_all();", 100);

  $('.previewTips, .DCESTips, .fieldTips', p4.edit.editBox).tooltip({
    fixable:true, 
    fixableIndex:1200
  });
  $('.infoTips', p4.edit.editBox).tooltip();
	
  if(p4.edit.what == 'GRP')
  {
    $('#EDIT_FILM2 .reg_opts').show();
		
    $.each($('#EDIT_FILM2 .contextMenuTrigger'),function(){

      var id = $(this).attr('id').split('_').slice(1,3).join('_');
      $(this).contextMenu('#editContext_'+id+'',{
        appendTo:'#editcontextwrap',
        openEvt:'click',
        dropDown:true,
        theme:'vista',
        dropDown:true,
        showTransition:'slideDown',
        hideTransition:'hide',
        shadow:false
      });
    });
  }
	
  $('#EDIT_TOP', p4.edit.editBox).resizable({
    handles : 's',
    minHeight:100,
    resize:function(){
      hsplit1();
      setPreviewEdit();
    },
    stop:function(){
      hsplit1();
      setPref('editing_top_box', Math.floor($('#EDIT_TOP').height() * 100 / $('#EDIT_ALL').height()) + '%');
      setSizeLimits();
    }
  });
	
  $('#divS_wrapper', p4.edit.editBox).resizable({
    handles : 'e',
    minWidth:200,
    resize:function(){
      vsplit1();
      setPreviewEdit();
    },
    stop:function(){
      setPref('editing_right_box', Math.floor($('#divS').width() * 100 / $('#EDIT_MID_L').width()) + '%');
      vsplit1();
      setSizeLimits();
    }
  });
	
  $('#EDIT_MID_R', p4.edit.editBox).resizable({
    handles : 'w',
    minWidth:200,
    resize:function(){
      vsplit2();
      setPreviewEdit();
    },
    stop:function(){
      setPref('editing_left_box', Math.floor($('#EDIT_MID_R').width() * 100 / $('#EDIT_MID').width()) + '%');
      vsplit2();
      setSizeLimits();
    }
  });
	
  $('#EDIT_ZOOMSLIDER', p4.edit.editBox).slider({
    min:60,
    max:300,
    value:p4.edit.diapoSize,
    slide:function(event,ui)
    {
      var v = $(ui.value)[0];
      $('#EDIT_FILM2 .diapo', p4.edit.editBox).width(v).height(v);
    },
    change:function(event,ui)
    {
      p4.edit.diapoSize = $(ui.value)[0];
      setPref("editing_images_size", p4.edit.diapoSize);
    }
  });
	
  var buttons = {};
  buttons[language.valider] = function(e)
  {
    $(this).dialog("close");
    edit_applyMultiDesc(e);
  };
  buttons[language.annuler] = function(e)
  {
    $(this).dialog("close");
    edit_cancelMultiDesc(e);
  };

  $("#EDIT_CLOSEDIALOG", p4.edit.editBox).dialog({
    autoOpen: false,
    closeOnEscape:true,
    resizable:false,
    draggable:false,
    modal:true,
    buttons: buttons
  });
	
  var buttons = {};
  buttons[language.valider] = function()
  {
    var form = $("#Edit_copyPreset_dlg FORM");
    var jtitle = $(".EDIT_presetTitle", form);
    if(jtitle.val() == '')
    {
      alert(language.needTitle);
      jtitle[0].focus();
      return;
    }
			
    var p = {
      "act":"SAVE",
      "sbas":p4.edit.sbas_id,
      "title":jtitle.val(),
      "f":{}
    };
    var f = {};
    var x = "<fields>";
    $(":checkbox", form).each(
      function(idx, elem)
      {
        if(elem.checked)
        {
          var i = 0|elem.value;
          var f;
          if(p4.edit.T_fields[i].multi)
            f = p4.edit.T_fields[i]._value.split(";");
          else
            f = [ p4.edit.T_fields[i]._value ];
          for(j in f)
          {
            x += "<"+p4.edit.T_fields[i].name+">"
            +  cleanTags(f[j])
            + "</"+p4.edit.T_fields[i].name+">";
          }
        }
      }
      );

    x += "</fields>";
    p["f"] = x;

    $.getJSON(
      "/xmlhttp/editing_presets.j.php",
      p,
      function(data, textStatus)
      {
        preset_paint(data);
        $("#Edit_copyPreset_dlg").dialog("close");
      }
      );
  };
  buttons[language.annuler] = function()
  {
    $(this).dialog("close");
		
  };
	
  $("#Edit_copyPreset_dlg", p4.edit.editBox).dialog( {
    zIndex:5000,
    stack:true,
    closeOnEscape:true,
    resizable:false,
    draggable:false,
    autoOpen:false,
    modal:true,
    width:600,
    title:language.newPreset,
    open:function(event, ui)
    {
      $(".EDIT_presetTitle")[0].focus();
    },
    buttons:buttons
  });
	
  $('#idEditDateZone', p4.edit.editBox).datepicker({
    changeYear: true,
    changeMonth:true,
    dateFormat: 'yy/mm/dd',
    onSelect: function(dateText, inst)
    {
				
				
      var lval = $('#idEditZTextArea', p4.edit.editBox).val();
      if(lval != dateText)
      {
        fieldLastValue = lval;
        $('#idEditZTextArea', p4.edit.editBox).val(dateText);
        $('#idEditZTextArea').trigger('keyup.maxLength');
        textareaIsDirty = true;
        edit_validField(null, 'ok');
      }
    }
  });
	
  $('input.input-button').hover(
    function(){
      $(this).addClass('hover');
    },
    function(){
      $(this).removeClass('hover');
    }
    );
		
  ETHSeeker = new EditThesaurusSeeker(p4.edit.sbas_id);

  hsplit1();
  vsplit2();
  vsplit1();

  setSizeLimits();

  var p = {
    "act":"LIST",
    "sbas":p4.edit.sbas_id
  };
  $.getJSON(
    "/xmlhttp/editing_presets.j.php",
    p,
    function(data, textStatus)
    {
      preset_paint(data);
    }
  );

  check_required();

  $('#TH_Opresets button.adder').button().bind('click', function(){preset_copy();});
 
  try{
    $('#divS .edit_field:first').trigger('mousedown');
  }
  catch(err)
  {

  }
}



function setRegDefault(n,record_id)
{
  p4.edit.newrepresent = record_id;

  var src = $('#idEditDiapo_'+n).find('img.edit_IMGT').attr('src');
  var style = $('#idEditDiapo_'+n).find('img.edit_IMGT').attr('style');
	
  $('#EDIT_GRPDIAPO .edit_IMGT').attr('src',src).attr('style',style);
}