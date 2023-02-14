function toggle_checkbox(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  if($('#' + name).prop('checked')) $(element).show();
  else $(element).hide();
}

function add_toggle_checkbox(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('#' + name).on('change', function(event) { toggle_checkbox(name, element); });
  toggle_checkbox(name, element);
}

function toggle_checkbox_not(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  if($('#' + name).prop('checked')) $(element).hide();
  else $(element).show();
}

function add_toggle_checkbox_not(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('#' + name).on('change', function(event) { toggle_checkbox_not(name, element); });
  toggle_checkbox_not(name, element);
}

function add_toggle_checkboxes(names)
{
  var arr = names.split(",");
  var len = arr.length;

  for (var i = 0; i < len; i++) {
    add_toggle_checkbox(arr[i]);
  }
}

function toggle_select(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  if($('#' + name).val() == showvalue) $(element).show();
  else $(element).hide();
}

function add_toggle_select(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('#' + name).on('change', function(event) { toggle_select(name, showvalue, element); });
  toggle_select(name, showvalue, element);
}

function toggle_select_not(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  if($('#' + name).val() != showvalue) $(element).show();
  else $(element).hide();
}

function add_toggle_select_not(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('#' + name).on('change', function(event) { toggle_select_not(name, showvalue, element); });
  toggle_select_not(name, showvalue, element);
}

// -------------------------------------------------------------------------------------

// like toggle_checkbox, but replaces [ and ]
function toggle_checkboxx(name, element)
{
  var divname = '#div_' + name.replace('[', '_').replace(']', '_');
  if (typeof element !== 'undefined') divname = element;

  if($('[name="' + name + '"]').prop('checked')) $(divname).show();
  else $(divname).hide();
}

function add_toggle_checkboxx(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('[name="' + name + '"]').on('change', function(event) { toggle_checkboxx(name, element); });
  toggle_checkboxx(name, element);
}

function toggle_selectt(name, showvalue, element)
{
  var divname = '#div_' + name.replace('[', '_').replace(']', '_');
  if (typeof element !== 'undefined') divname = element;

  
  if($('[name="' + name + '"]').val() == showvalue) $(divname).show();
  else $(divname).hide();
}

function add_toggle_selectt(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('[name="' + name + '"]').on('change', function(event) { toggle_selectt(name, showvalue, element); });
  toggle_selectt(name, showvalue, element);
}

function toggle_selectt_not(name, showvalue, element)
{
  var divname = '#div_' + name.replace('[', '_').replace(']', '_');
  if (typeof element !== 'undefined') divname = element;

  if($('[name="' + name + '"]').val() == showvalue) $(divname).hide();
  else $(divname).show();
}

function add_toggle_selectt_not(name, showvalue, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('[name="' + name + '"]').on('change', function(event) { toggle_selectt_not(name, showvalue, element); });
  toggle_selectt_not(name, showvalue, element);
}

function toggle_radio(name, element)
{
  var divname = '#div_' + name.replace('[', '_').replace(']', '_');
  if (typeof element !== 'undefined') divname = element;

  if($('input[name="' + name + '"]:checked').val() == '1') $(divname).show();
  else $(divname).hide();
}

function add_toggle_radio(name, element)
{
  if (typeof element === 'undefined') element = '#div_' + name;
  $('input[name="' + name + '"]').on('change', function(event) { toggle_radio(name, element); });
  toggle_radio(name, element);
}

function toggle_radio_not(name, element)
{
  var divname = '#div_' + name.replace('[', '_').replace(']', '_');
  if (typeof element !== 'undefined') divname = element;

  if($('input[name="' + name + '"]:checked').val() == '0') $(divname).show();
  else $(divname).hide();
}

function add_toggle_radio_not(name, element)
{
  $('input[name="' + name + '"]').on('change', function(event) { toggle_radio_not(name, element); });
  toggle_radio_not(name, element);
}

function request_merker_(code)
{
  url_merker = "merker.php?action=set&code=" + code;
}

// ----------------------------------------------------------------------------------------

function booosta_disable_form()
{
  $("form :input").prop("disabled", true);
  $("[id^=ui_select]").each(function() { if(this.selectize) this.selectize.disable(); });
}

function booosta_enable_form()
{
  $("form :input").prop("disabled", false);
  $("[id^=ui_select]").each(function() { if(this.selectize) this.selectize.enable(); });
}

