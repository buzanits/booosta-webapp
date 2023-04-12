<?php
namespace booosta\webapp;
\booosta\Framework::init_module('webapp');

const FEEDBACK = 'vendor/booosta/webapp/src/systpl/feedback.tpl';

class Webappbase extends \booosta\base\Module
{
  use moduletrait_webapp;

  protected $name, $supername, $subname, $subsubname;
  protected $superscript, $subscript;
  public $VAR, $maintpl, $subtpls, $action, $phpself, $self, $use_postfix, $postfix;
  protected $id, $super_id;
  protected $rowcount;
  
  public $tpldir = '';
  protected $toptpl;
  protected $extra_templates = [];
  protected $TPL;

  protected $includes, $preincludes, $htmlheader;
  protected $tablename, $classname, $listclassname, $sub_listclassname, $editclassname;
  protected $keyfilter, $fkeyfilter, $fields, $nfields;
  protected $default_clause, $default_order, $default_limit;
  protected $header, $condition, $autoheader;
  
  protected $sub_key, $sub_keyfilter, $sub_fkeyfilter, $sub_fields, $sub_nfields;
  protected $super_key;
  protected $sub_default_clause, $sub_default_order, $sub_default_limit;
  protected $sub_header, $sub_condition;

  protected $lightmode, $sub_lightmode;
  protected $use_datatable = false;
  protected $datatable_id;
  protected $formfields_disabled = false;

  // blank_fields: fields that will stay blank in forms and do not change when submitted blank
  // null_fields: fields that should be null in the DB when submitted blank
  protected $checkbox_fields, $blank_fields, $null_fields, $translate_fields, $sub_translate_fields, $boolfields, $sub_boolfields;
  protected $treat_0_as_null = false;
  public $moduleinfo;

  protected $idfield, $sub_idfield;
  protected $backpage, $backpagetpl, $goback;
  protected $editvars, $addvars, $neditvars, $naddvars;
  public $lang, $t, $translator_dir;
  protected $no_output;
  public $base_dir = '';

  protected $runs_on_cli;
  protected $use_form_token = true;
  protected $form_token_table = 'form_token';
  protected $form_token_time = 86400;
  protected $pass_vars_to_template = false;

  protected $encode, $decode;
  protected $error;
  protected $cancel_insert, $cancel_update, $cancel_delete;
  protected $foreign_keys, $sub_foreign_keys;
  protected $home_link;
  protected $reload_every;
  
  public $formelement_prefix;
  protected $use_subtablelink, $use_subsubtablelink;
  protected $edit_link;
  protected $dbobject, $old_obj;
  protected $datefield, $dateformat;

  protected $tablelister_table_class = 'table table-striped display responsive';
  protected $tablelister_td_field_class = ['edit' => 'tableeditclass', 'delete' => 'tabledeleteclass'];
  protected $datatable_omit_columns;
  protected $script_extension = '';
  protected $script_actionstr = '/';
  protected $script_divider = '/';


  public function __construct($name = null)
  {
    parent::__construct();

    if(is_array($files = $this->config('includefiles')))
      foreach($files as $file) if(is_readable($file)) include_once($file);

    // fill $this->VAR with cli parameters or post/get parameters
    $this->VAR = [];
    if(php_sapi_name() == 'cli'):
      global $argv;

      foreach($argv as $i=>$arg):
        if($i == 0) continue;
        list($var, $val) = explode('=', $arg);
        $this->VAR[$var] = $val;
      endforeach;

      $this->runs_on_cli = true;
    else:
      $this->VAR = $this->get_request_vars();
      $this->runs_on_cli = false;
    endif;

    if($name) $this->name = $name;

    if($this->subname === null) $this->subname = []; 
    elseif(is_string($this->subname)) $this->subname = [$this->subname];

    if($this->subsubname === null) $this->subsubname = []; 
    elseif(is_string($this->subsubname)) $this->subsubname = [[$this->subsubname]];

    if($this->subscript === null) $this->subscript = []; 
    elseif(is_string($this->subscript)) $this->subscript = [$this->subscript];

    if($this->sub_header === null) $this->sub_header = []; 
    elseif(is_string($this->sub_header)) $this->sub_header = [$this->sub_header];
    
    if($this->TPL === null) $this->TPL = ['_includes' => ''];
    if($site_name = $this->config('site_name')) $this->TPL['site_name'] = $site_name;
    if($site_logo = $this->config('site_logo')) $this->TPL['site_logo'] = $site_logo;
    if($this->maintpl === null) $this->maintpl = 'vendor/booosta/webapp/src/systpl/empty.tpl';
    
    if($this->toptpl === null) $this->toptpl = $this->get_toptpl();
    #\booosta\Framework::debug("toptpl: $this->toptpl");
    #\booosta\Framework::debug("toptplreadable: " . is_readable($this->toptpl));

    if($this->idfield === null) $this->idfield = 'id';
    $this->id = isset($this->VAR[$this->idfield]) ? intval($this->VAR[$this->idfield]) : 0;
    $this->id = isset($this->VAR['object_id']) ? intval($this->VAR['object_id']) : $this->id;       // object_id overrules idfield
    $this->action = isset($this->VAR['action']) ? $this->VAR['action'] : "";
    $this->phpself = $_SERVER['PHP_SELF'];
    $this->self = $this->phpself;
    $this->postfix = '';
    $this->keyfilter = '';
    $this->fkeyfilter = '';
    if($this->default_order === null) $this->default_order = '1';
    if($this->condition === null) $this->condition = [];
    $this->goback = true;
    if($this->no_output === null) $this->no_output = false;
    $this->cancel_insert = false;
    $this->cancel_update = false;
    $this->cancel_delete = false;

    if(is_string($this->sub_default_order)) $this->sub_default_order = [$this->sub_default_order];
    elseif($this->sub_default_order === null) $this->sub_default_order = ['1'];

    if($this->sub_idfield === null) $this->sub_idfield = [];
    elseif(is_string($this->sub_idfield)) $this->sub_idfield = [$this->sub_idfield];

    if($this->autoheader === null) $this->autoheader = true;

    if($this->foreign_keys === null) $this->foreign_keys = [];
    elseif(is_string($this->foreign_keys)) $this->foreign_keys = explode(',', $this->foreign_keys);

    #if($this->sub_foreign_keys === null) $this->sub_foreign_keys = [];
    #elseif(is_string($this->sub_foreign_keys)) $this->sub_foreign_keys[0] = [explode(',', $this->sub_foreign_keys)];

    if(is_string($this->sub_fields)) $this->sub_fields = [0 => $this->sub_fields];
    if(is_string($this->sub_key)) $this->sub_key = [0 => $this->sub_key];
    
    if($this->encode === null) $this->encode = [];
    if($this->decode === null) $this->decode = [];

    if(is_bool($flag = $this->config('use_form_token'))) $this->use_form_token = $flag;

    $this->TPL['base_dir'] = $this->base_dir;
    if($this->lang === null) $this->lang = 'en';    // $this->lang will be overwritten by module translator

    $this->simple_userfield = ($this->config('simple_userfield') === true);
    if($this->use_userfield === true) $this->use_userfield = 'user';

    if($home_link = $this->config('home_link')) $this->home_link = $home_link;
    else $this->home_link = '#';

    $this->classname = $name;
    if($this->use_subtablelink === null && is_bool($this->config('use_subtablelink'))) 
      $this->use_subtablelink = $this->config('use_subtablelink');

    if($this->use_subsubtablelink === null && is_bool($this->config('use_subsubtablelink'))) 
      $this->use_subsubtablelink = $this->config('use_subsubtablelink');

    if(!is_bool($this->use_subsubtablelink)) $this->use_subsubtablelink = $this->use_subtablelink;

    if($this->supername):
      $superfield = $this->find_super_fkfield();

      if($this->VAR[$superfield]) $this->super_id = $this->VAR[$superfield]; 
      else $this->super_id = $this->DB->query_value("select `$superfield` from `$name` where `$this->idfield`='$this->id'");
      #\booosta\debug("superid: $this->super_id");
    endif;

    if($this->formfields_disabled && $this->action == 'edit' && !$this->VAR['editmode'] && \booosta\module_exists('jquery'))
      $this->add_jquery_ready('booosta_disable_form();');
      
    if(is_string($this->datefield)) $this->datefield = explode(',', $this->datefield);

    if(is_string($this->editvars)) $this->editvars = explode(',', $this->editvars);
    if(is_string($this->neditvars)) $this->neditvars = explode(',', $this->neditvars);
    if(is_string($this->sub_edit_link)) $this->sub_edit_link = explode(',', $this->sub_edit_link);

    if($this->lightmode === null && $lightmode = $this->config('lightmode')) $this->lightmode = $lightmode;
    $this->TPL['page_title'] = $this->config('page_title');
    $this->TPL['page_title_short'] = $this->config('page_title_short');
    $this->TPL['page_copyright'] = $this->config('copyright');
    $this->TPL['page_version'] = $this->config('version');

    if($this->boolfields === null) $this->boolfields = $this->checkbox_fields;
 
    if(is_readable('incl/default.css')) $this->add_includes('<link rel="stylesheet" href="'.$this->base_dir.'incl/default.css">');

    $this->init();
    $this->apply_userfield('init');

    $methods = get_class_methods($this);
    foreach($methods as $method)
      if(substr($method, 0, 11) == 'webappinit_')
        call_user_func([$this, $method]);

    #\booosta\Framework::debug("script_extension: $this->script_extension");
    $this->TPL['script_extension'] = $this->script_extension;
    $this->TPL['script_actionstr'] = $this->script_actionstr;
    $this->TPL['script_divider'] = $this->script_divider;

    $this->post_init();
  }

  public function __invoke() { $this->run(); }

  public function __call($name, $args)
  {
    if($name == 'auth') return true;    // if there is no auth function we got from an other module, let it happen
    if($name == 'apply_userfield') return;   // if there is no userfield specific code from an other module, do nothing

    $this->raise_error("Call to undefined function: $name");
  }

  public function get_var($name) { return $this->VAR[$name]; }

  public function set_keyfilter($filter) { $this->keyfilter = $filter; }
  public function set_fkeyfilter($filter) { $this->fkeyfilter = $filter; }
  public function set_default_clause($clause) { $this->default_clause = $clause; }
  public function set_default_order($order) { $this->default_order = $order; }
  public function set_default_limit($limit) { $this->default_limit = $limit; }
  public function show_fields($fields) { $this->fields = $fields; }
  public function hide_fields($fields) { $this->nfields = $fields; }
  public function set_header($header) { $this->header = $header; }
  public function set_conditions($conditions) { $this->condition = $conditions; }
  public function add_condition($key, $condition) { $this->condition[$key] = $condition; }

  public function add_default_clause($clause)
  {
    if($this->default_clause == '') $this->default_clause = $clause;
    else $this->default_clause = "($this->default_clause) and ($clause)";
    #\booosta\debug("clause in add_default_clause: {$this->default_clause}");
  }

  public function get_TPL($name = null) 
  { 
    if($name) return $this->TPL[$name];
    return $this->TPL;
  }

  public function set_sub_keyfilter($filter, $index = 0) 
  { 
    if(is_array($filter)) $this->sub_keyfilter = $filter; 
    else $this->sub_keyfilter[$index] = $filter; 
  }

  public function set_sub_fkeyfilter($filter, $index = 0) 
  { 
    if(is_array($filter)) $this->sub_fkeyfilter = $filter; 
    else $this->sub_fkeyfilter[$index] = $filter; 
  }

  public function set_sub_default_clause($clause, $index = 0) 
  { 
    if(is_array($clause)) $this->sub_default_clause = $clause; 
    else $this->sub_default_clause[$index] = $clause; 
  }

  public function set_sub_default_order($order, $index = 0)
  { 
    if(is_array($order)) $this->sub_default_order = $order; 
    else $this->sub_default_order[$index] = $order; 
  }

  public function set_sub_default_limit($limit, $index = 0)
  {
    if(is_array($limit)) $this->sub_default_limit = $limit;
    else $this->sub_default_limit[$index] = $limit;
  }
  
  public function show_sub_fields($fields, $index = 0) 
  { 
    if(is_array($fields)) $this->sub_fields = $fields; 
    else $this->sub_fields[$index] = $fields; 
  }

  public function hide_sub_fields($fields, $index = 0) 
  { 
    if(is_array($fields)) $this->sub_nfields = $fields; 
    else $this->sub_nfields[$index] = $fields; 
  }

  public function set_sub_header($header, $index = 0) 
  { 
    if(is_array($header)) $this->sub_header = $header; 
    else $this->sub_header[$index] = $header; 
  }

  public function set_sub_conditions($conditions, $index = 0) 
  { 
    if($index === null) $this->sub_condition = $conditions; 
    else $this->sub_condition[$index] = $conditions; 
  }

  public function add_sub_condition($key, $condition, $index = 0) { $this->sub_condition[$index][$key] = $condition; }

  public function set_idfield($field) { $this->idfield = $field; }
  public function set_sub_idfield($idx, $field) { $this->sub_idfield[$idx] = $field; }
  public function set_checkbox_fields($fields) { $this->checkbox_fields = $fields; }
  public function set_blank_fields($fields) { $this->blank_fields = $fields; }
  public function set_lang($lang) { $this->lang = $lang; }
  ##public function get_lang() { return $this->lang; }
  public function set_extra_templates($templates) { if(is_array($templates)) $this->extra_templates = $templates; }
  public function pass_vars_to_template($value) { $this->pass_vars_to_template = $value; }
  public function set_foreign_keys($fk) { $this->foreign_keys = $fk; }  
  public function set_supername($name) { $this->supername = $name; }
  public function set_superscript($script) { $this->superscript = $script; }

  public function set_sub_foreign_keys($fk, $index = 0) { $this->sub_foreign_keys[$index] = $fk; }

  public function set_subname($name, $index = 0) 
  { 
    if(is_array($name)) $this->subname = $name; 
    elseif(strstr($name, ',')) $this->subname = explode(',', $name);
    else $this->subname[$index] = $name;
  }

  public function set_subsubname($name, $index = 0, $subindex = 0)
  {
    if(is_array($name)) $this->subsubname = $name;
    elseif(strstr($name, ',')) $this->subname[$index] = explode(',', $name);
    else $this->subsubname[$index][$subindex] = $name;
  }

  public function set_subscript($script, $index = 0) 
  { 
    if(is_array($script)) $this->subscript = $script; 
    elseif(strstr($script, ',')) $this->subscript = explode(',', $script);
    else $this->subscript[$index] = $script;
  }

  public function add_foreign_key($key, $value = null) 
  {
    if($value === null) $value = $key;
    $this->foreign_keys[$key] = $value;
  }

  public function add_sub_foreign_key($key, $value = null, $index = 0) 
  {
    if($value === null) $value = $key;
    $this->sub_foreign_keys[$index][$key] = $value;
  }
  
  public function add_encode($field, $func) { $this->encode[$field] = $func; }
  public function add_decode($field, $func) { $this->decode[$field] = $func; }

  protected function output($content) 
  { 
    #\booosta\debug($content);
    $garbage = ob_get_contents();
    
    if($garbage):
      $page = $_SERVER['PHP_SELF'];
      \booosta\Framework::debug("Garbage in $page: $garbage");
    endif;
  
    ob_end_clean();

    print $content; 
  }
  
  protected function init() {}
  protected function post_init() {}
  protected function get_request_vars() { return $_REQUEST; }
  
  protected function get_toptpl()
  {
    $template_module = $this->config('template_module') ?: 'bootstrap';
    $cfg_toptpl = $this->cfg_toptpl ?? $this->config('toptpl') ?? 'dashboard.html';

    $tpls = [$cfg_toptpl, 
             "vendor/booosta/$template_module/src/$cfg_toptpl",
             __DIR__ . "/../../$template_module/src/$cfg_toptpl",
             "vendor/booosta/$template_module/src/dashboard.html", 
             __DIR__ . "/../../$template_module/src/dashboard.html", 
             'tpl/dashboard.html',
             "vendor/booosta/bootstrap/src/dashboard.html",
             __DIR__ . "/../../bootstrap/src/dashboard.html"];

    foreach($tpls as $tpl) if(is_readable($tpl)) return $tpl;

    return null;
  }

  public function run()
  {
    #\booosta\debug("in run. name: $this->name, action: $this->action");
    #print_r($this->subname);
    if($this->subname == '') $this->use_subtablelink = false;
    if($this->subname == '') $this->use_subsubtablelink = false;

    if($this->tablename == '' && $this->name != ''):
      $this->tablename = $this->name;
      if($this->use_postfix && $this->name != '') $this->postfix = $this->name;   // activate when using XMLRPC or similar
    endif;
    $this->classname = $this->tablename;
    if($this->listclassname == '') $this->listclassname = $this->classname;
    if($this->editclassname == '') $this->editclassname = $this->classname;

    if($this->action) $actionfunction = "action_$this->action";
    else $actionfunction = 'action_default';

    try { $this->$actionfunction(); }
    catch(\booosta\Exception $e) { $this->output($e->getMessage()); return; }
    #\booosta\Framework::debug("run actionfunction: " . $actionfunction);
    #\booosta\Framework::debug("run backpage: " . $this->backpage);

    $methods = get_class_methods($this);
    foreach($methods as $method)
      if(substr($method, 0, 12) == 'beforeparse_')
        call_user_func([$this, $method]);

    $this->before_parse();
    if(!$this->no_output) $this->parse(); 
  }

  protected function before_parse() {}

  protected function parse()
  {
    $methods = get_class_methods($this);
    foreach($methods as $method)
      if(substr($method, 0, 9) == 'preparse_')
        call_user_func([$this, $method]);

    if($this->toptpl):
      $templates = ['MAIN'  =>  $this->maintpl];
      $tpl = $this->toptpl;
    else:
      $templates = null;
      $tpl = $this->maintpl;
    endif;

    if(sizeof($this->extra_templates)):
      if($templates == null) $templates = [];
      $templates = array_merge($templates, $this->extra_templates);
    endif;

    $this->TPL['home_link_'] = $this->home_link;
    $this->TPL['_goback'] = $this->goback ? 1 : 0;
    $backpage = $this->TPL['_backpage'] = $this->get_backpage();
    
    // avoid //myscript.php as backpage
    #if($this->TPL['base_dir'] == '/' && substr($backpage, 0, 1) == '/') $backpage = ltrim($backpage, '/');
    #$this->TPL['_backpage'] = $backpage;  

    #\booosta\debug($templates);
    #\booosta\debug($this->VAR);
    #\booosta\debug($_SESSION);
    #\booosta\Framework::debug("parse backpage: " . $this->get_backpage());

    $this->output_preincludes();
    $this->output_includes();
    $this->output_htmlheader();
    
    if($this->pass_vars_to_template === true):
      $this->TPL = array_merge($this->VAR, $this->TPL);
    elseif(is_string($this->pass_vars_to_template)):
      $vars = explode(',', $this->pass_vars_to_template);
      foreach($vars as $var):
        $var = trim($var);
        $this->TPL[$var] = $this->VAR[$var];
      endforeach;
    endif;

    #\booosta\Framework::debug('vtt: ' . $this->pass_vars_to_template);
    #\booosta\Framework::debug($this->TPL);
    $parser = $this->get_templateparser();
    if(isset($this->tpltags)) $parser->set_tags($this->tpltags);

    #\booosta\debug("tpl: $tpl");
    $this->output($parser->parse_template($tpl, $templates, $this->TPL));
  }

  protected function output_preincludes() { $this->TPL['_includes'] .= $this->preincludes; }
  protected function output_includes() { $this->TPL['_includes'] .= $this->includes; }
  protected function output_htmlheader() { $this->TPL['_htmlheader'] .= $this->htmlheader; }
  
  protected function vars2tpl($vars)
  {
    if(is_string($vars)) $vars = explode(',', $vars);
    foreach($vars as $var):
      $var = trim($var);
      if(isset($this->VAR[$var])) $this->TPL[$var] = $this->VAR[$var];
    endforeach;
  }

  public function get_templateparser() 
  { 
    $parser = $this->makeInstance('Templateparser', $this->lang);
    if($this->templateparser_tags) $parser->set_tags($this->templateparser_tags);
    return $parser;
  }

  protected function get_backpage() 
  { 
    #\booosta\Framework::debug("backpagetpl: $this->backpagetpl");
    if(strstr($this->backpagetpl, '{%')) return str_replace(['{%id}', '{%superid}'], [$this->id, $this->super_id], $this->backpagetpl);
    #\booosta\Framework::debug("backpage1: $this->backpage");
    if($this->backpage == '') return $this->self;
    #\booosta\Framework::debug("backpage2: $this->backpage");

    return $this->backpage; 
  }

  public function get_self() { return $this->self; }

  // function call to be changed if working with XMLRPC or similar technologies
  protected function call()
  {
    $args = func_get_args();
    $func = array_shift($args);
    return [true, call_user_func_array([$this, $func], $args)];
  }


  public function get_dbobject($id = null)
  {
    if($id === null) $id = $this->id;
    if($id == $this->id && is_object($this->dbobject)) return $this->dbobject;

    $classname = $this->classname;
    #\booosta\debug("classname: $classname, id: $id");
    $obj = $this->getDataobject($classname, $id);
    #\booosta\debug('class: ' . get_class($obj));
    #if(is_object($obj)) \booosta\debug($obj->get('id')); else \booosta\debug('else');

    if($id == $this->id) $this->dbobject = $obj;
    return $obj;
  }

  public function get_data($field = null, $id = null)
  {
    $obj = $this->get_dbobject($id);
    if(!is_object($obj)) return null;
    
    if($field === null) return $obj->get_data();
    return $obj->get($field);
  }
  
  public function old_data($field = null)
  {
    $obj = $this->old_obj;
    if(!is_object($obj)) return null;

    if($field === null) return $obj->get_data();
    return $obj->get($field);
  }


  protected function translate_list($list)
  {
    $result = [];
    if(is_array($list)):
      foreach($list as $key=>$val) $result[$key] = $this->translate_list($val);
      return $result;
    elseif(strstr($list, ',')):
      $tmp = explode(',', $list);
      $result = $this->translate_list($tmp);
      return implode(',', $result);
    else:
      return $this->t($list);
    endif;
  }
      

  protected function redirect($url) { header("Location: $url"); }


  public function raise_error($message, $backpage = null, $translate = true)
  {
    #\booosta\debug("backpage: $backpage");
    $this->maintpl = FEEDBACK;
    if(is_array($message) || is_object($message)) $message = print_r($message, true);
    $this->TPL['output'] = $translate ? $this->t($message) : $message;
    $this->goback = false;

    if($backpage !== null):
      $this->backpage = $backpage;
      $this->backpagetpl = null;
    endif;

    $this->parse();
    exit;
  }


  public function add_includes($code) { $this->includes .= $code; }
  public function add_htmlheader($code) { $this->htmlheader .= $code; }
  public function add_preincludes($code) { $this->preincludes .= $code; }

  public function add_javascript($js, $jstags = true)
  {
    if(is_string($js)):
      $code = $js;
    else:
      if(!is_object($js)) return false;
      if(!is_callable([$js, 'get_javascript'])) return false;
      $code = $js->get_javascript();
    endif;

    if($jstags) $this->includes .= "<script type='text/javascript'>\n";
    $this->includes .= $code;
    if($jstags) $this->includes .= "</script>\n";
  }

  public function add_javascriptfile($file) 
  {
    if(substr($file, 0, 4) == 'http') $f = $file; else $f = "$this->base_dir$file"; 
    $this->includes .= "<script type='text/javascript' src='$f'></script>"; 
  }
  
  protected function paramfilter($filter = null, $onlytrue = false)
  {
    $filterlen = strlen($filter);
    $result = array();

    foreach($this->VAR as $var=>$val)
      if($filter && substr($var, 0, $filterlen) == $filter && (!$onlytrue || $val))
        $result[substr($var, $filterlen)] = $val;

    return $result;
  }


  protected function generate_form_token()
  {
    $this->clear_form_tokens();

    $token = sha1(uniqid('', true));
    $obj = $this->makeDataobject($this->form_token_table);
    $obj->set('token', $token);
    $obj->set('created', time());
    $obj->insert(false);

    $this->TPL['form_token'] = $token;
    return $token;
  }


  protected function check_form_token($token = null)
  {
    if($token === null) $token = $this->DB->escape($this->VAR['form_token']);

    $expired = time() - $this->form_token_time;
    $obj = $this->getDataobject($this->form_token_table, "token='$token' and created>='$expired'");

    if(!is_object($obj) || !$obj->is_valid()):
      #\booosta\debug(date('Y-m-d H:i:s') . ' Token validation failed: ' . $token . ' ' . $_SERVER['REQUEST_URI']);   ###
      $this->raise_error('Token validation failed');
      return false;
    endif;

    $obj->delete(false);
    return true;
  }


  protected function clear_form_tokens()
  {
    $tokentable = $this->form_token_table;
    $expired = time() - $this->form_token_time;
    $this->DB->query("delete from $tokentable where created<'$expired'", false);
  }


  // default predefined functions, can be overriden
  protected function action_default()
  {
    if($this->table_sortable) $this->table_sort_ajaxurl = "$this->phpself_raw?action=sort" . $this->table_sort_ajaxurl_postfix;

    // Hook before_default_
    $this->before_action_default();

    if($this->name == '') return false;
    $this->apply_userfield('action default', $this);
    if($this->tpl_default) $this->maintpl = $this->tpl_default; else $this->maintpl = "tpl/$this->name".'_default.tpl';
    #\booosta\debug("clause: $this->default_clause");

    if($this->use_datatable === 'ajax') $result = [];
    else $result = $this->getall_data();

    $this->rowcount = sizeof($result);
    #\booosta\Framework::debug('result'); \booosta\Framework::debug($result);
    $list = $this->get_tablelist($result);
    #\booosta\debug($list);

    // Hook in_default_makelist
    $this->in_default_makelist($list);
    $this->TPL['liste'] = $list->get_html();
    #\booosta\Framework::debug($this->TPL['liste']);
    if($this->use_datatable && is_callable($list, 'get_html_includes')) $this->add_includes($list->get_html_includes());

    // Hook after_default_
    $this->after_action_default();
    return true;
  } 


  // default hook functions
  protected function before_default() {}
  protected function before_action_default() { $this->before_default(); }
  protected function after_default() {}
  protected function after_action_default() { $this->after_default(); }
  protected function in_default_makelist($list) {}
  protected function default_clause() { return $this->default_clause; }
  protected function default_order() { return $this->default_order; }
  protected function default_limit() { return $this->default_limit; }
  protected function sub_default_order($index) { return $this->sub_default_order[$index] ?: '1'; }


  protected function getall_data()
  {
    if($this->getallfunction) $func = $this->getallfunction; else $func = "getall_$this->postfix";

    #\booosta\debug("func: $func");
    #\booosta\debug($this->default_clause());
    list($ok, $result) = $this->call($func, $this->default_clause(), $this->default_order(), $this->default_limit());
    if(!$ok || (is_string($result) && strstr(print_r($result, true), 'ERROR'))) $this->raise_error("ERROR while calling $func: " . print_r($result, true));
    #\booosta\debug($result);

    if(is_array($this->datefield) && $this->dateformat)
      foreach($this->datefield as $datefield)
        foreach($result as $idx=>$record):
          $val = $result[$idx][$datefield];
          $result[$idx][$datefield] = ($val && $val != '0000-00-00') ? date($this->dateformat, strtotime(str_replace(' ', '', $val))) : '';
        endforeach;

    return $result;
  }


  protected function get_tablelist($data)
  {
    #\booosta\Framework::debug($data);
    // translate content defined in $this->translate_fields
    if(!is_array($this->translate_fields)) $this->translate_fields = array_filter(explode(',', $this->translate_fields));
    foreach($this->translate_fields as $trfield)
      foreach($data as $idx=>$dat) 
        $data[$idx][$trfield] = $this->t($data[$idx][$trfield]);
    #\booosta\debug($this->translate_fields);
    #\booosta\debug($this->fields);
    #\booosta\Framework::debug($data);

    // add additional fields, that are in show_fields, but not in data
    $fields = $this->fields;
    if(!is_array($fields)) $fields = explode(',', $fields);
    #\booosta\Framework::debug($fields);
    #\booosta\Framework::debug($data);

    foreach($data as $idx=>$dat)
      foreach($fields as $field):
        if($field == 'edit' || $field == 'delete') continue;
        if(!isset($dat[$field])) $data[$idx][$field] = '';
      endforeach;
    #\booosta\Framework::debug($data);

    $class = $this->table_sortable ? "\\booosta\\ui_sortable\\ui_sortable_table" : 'Tablelister';
    $list = $this->makeInstance($class, $data, true, $this->use_datatable);      // 3rd param: show_tabletags
    if($this->table_sort_ajaxurl) $list->set_ajaxurl($this->table_sort_ajaxurl);
    if(is_array($this->datatable_omit_columns)) $list->set_omit_columns($this->datatable_omit_columns);

    if($this->datatable_display_length) $list->set_datatable_display_length($this->datatable_display_length);
    $list->always_show_header(true);
    #$list->set_datatable_libpath($this->TPL['base_dir'] . 'vendor/booosta/datatable');
    $list->set_datatable_libpath(__DIR__ . '/../../datatable/src');
    $list->set_id($this->datatable_id ?? $this->name);

    if($this->tablelister_table_class) $list->set_table_class($this->tablelister_table_class);
    if($this->tablelister_td_field_class) $list->set_td_field_class($this->tablelister_td_field_class);
    
    if(isset($this->lightmode)) $list->set_lightmode($this->lightmode);

    if($this->use_subtablelink) $extrafields = ['subtables' => $this->linktext('subtables')]; else $extrafields = [];
    $extrafields = array_merge($extrafields, ['edit' => $this->linktext('edit'), 'delete' => $this->linktext('delete')]);
    $list->set_extrafields($extrafields);

    $list->set_conditions($this->condition);
    if($this->keyfilter) $list->set_keyfilter($this->keyfilter);

    if($this->use_datatable === 'ajax') $list->set_datatable_ajaxurl($this->datatable_ajaxurl ?? "$this->self_raw?action=datatable_ajaxload");

    // filter out serial fields from displayed list
    $ser_field_filter = '';
    
    $serial_field = $this->config('serial_field');
    if(is_array($serial_field))
      foreach($serial_field as $serfield)
        $ser_field_filter .= " && % != '$serfield'";

    #print 'fkeyfilter: ' . print_r($this->fkeyfilter);
    if($this->fkeyfilter) $list->set_fkeyfilter($this->fkeyfilter);
    else $list->set_fkeyfilter("% != '$this->idfield'$ser_field_filter");

    if($this->fields) $list->show_fields($this->fields);
    if($this->header) $list->set_header($this->translate_list($this->header));
    #\booosta\debug($this->translate_list($this->header));

    $nfields = is_array($this->nfields) ? $this->nfields : [$this->nfields];
    ##$nfields = array_merge($nfields, [$this->name]);   // disabled! WHY???

    if($nfields) $list->hide_fields($nfields);
    $script = $this->self;

    $subtable_script = $this->subtable_script ?: $script;
    $edit_script = $this->edit_script ?: $script;
    $delete_script = $this->delete_script ?: $script;

    $subtable_params = $this->subtable_params ?: '/subtables/';
    $edit_params = $this->edit_params?: '/edit/';
    $delete_params = $this->delete_params ?: '/delete/';

    if($this->use_subtablelink) $links = ['subtables' => "$subtable_script$subtable_params{$this->idfield}"];
    else $links = [];

    $links = array_merge($links, ['edit'=>"$edit_script$edit_params{"."$this->idfield}",
                                  'delete'=>"$delete_script$delete_params{"."$this->idfield}"]);
                                  
    if($this->edit_link) $links = array_merge($links, [$this->edit_link => "$edit_script$edit_params{"."$this->idfield}"]);         
    $list->set_links($links);

    $this->normalize_foreign_keys();
    foreach($this->foreign_keys as $fk=>$val)
      $list->set_foreignkey_db($fk, $val['table'], $val['idfield'], $val['showfield']);
    
    if($this->fk_links):
      if(is_string($this->fk_links)):
        $tables = explode(',', $this->fk_links);
        foreach($tables as $table)
          $fk_links[$table] = ['table' => $table, 'script' => "$table$this->script_extension", 'idfield' => 'id'];
      elseif(is_array($this->fk_links)):
        foreach($this->fk_links as $table=>$fk_link)
          $fk_links[$table] = array_merge(['table' => $table, 'script' => "$table$this->script_extension", 'idfield' => 'id'], $fk_link);
      endif;
      #\booosta\debug($fk_links);

      foreach($fk_links as $table=>$fk_link)
        $list->add_link($table, "{$fk_link['script']}$edit_params{{$fk_link['fk_field']}}");
    endif;

    if(is_string($this->boolfields)) $this->boolfields = [$this->boolfields];
    if(is_string($this->boolfields)) $this->boolfields = explode(',', str_replace(' ', '', $this->boolfields));

    if(is_array($this->boolfields))
      foreach($this->boolfields as $boolfield)
        if(substr($boolfield, 0, 1) == '!'):
          $boolfield = substr($boolfield, 1);
          $list->add_replaces($boolfield, [function($val) { return !$val ? '<i class="far fa-check-circle" style="color:green"></i>' : '<i class="far fa-times-circle" style="color:red"></i>'; }]);
        else:
          $list->add_replaces($boolfield, [function($val) { return $val ? '<i class="far fa-check-circle" style="color:green"></i>' : '<i class="far fa-times-circle" style="color:red"></i>'; }]);
        endif;

    #\booosta\Framework::debug($list);
    return $list;
  }


  protected function get_subtablelist($data, $index = 0)
  {
    // translate content defined in $this->sub_translate_fields
    if(!is_array($this->sub_translate_fields)):
       $tmp = array_filter(explode(',', $this->sub_translate_fields));
       $this->sub_translate_fields = [$tmp];
    endif;

    foreach($this->sub_translate_fields[$index] as $trfield)
      foreach($data as $idx=>$dat) 
        $data[$idx][$trfield] = $this->t($data[$idx][$trfield]);
    #\booosta\debug($this->sub_translate_fields);
    #\booosta\debug($data);

    $class = $this->subtable_sortable ? "\\booosta\\ui_sortable\\ui_sortable_table" : 'Tablelister';
    #\booosta\Framework::debug("use_datatable: $use_datatable");
    $list = $this->makeInstance($class, $data, true, $this->use_datatable);      // 3rd param: show_tabletags
    if($this->subtable_sort_ajaxurl) $list->set_ajaxurl($this->subtable_sort_ajaxurl);

    if($this->datatable_display_length) $list->set_datatable_display_length($this->datatable_display_length);
    $list->always_show_header(true);
    
    $list->set_datatable_libpath(__DIR__ . '/../../datatable/src');
    #$list->set_datatable_libpath($this->TPL['base_dir'] . 'vendor/booosta/datatable');
    $list->set_id($this->subname[$index] . $index);

    if($this->tablelister_table_class) $list->set_table_class($this->tablelister_table_class);
    if($this->tablelister_td_field_class) $list->set_td_field_class($this->tablelister_td_field_class);
    
    if(is_array($this->sub_lightmode) && isset($this->sub_lightmode[$index])) $list->set_lightmode($this->sub_lightmode[$index]);
    elseif(isset($this->sub_lightmode) && !is_array($this->sub_lightmode)) $list->set_lightmode($this->sub_lightmode);

    if($this->use_subsubtablelink && is_array($this->subsubname[$index]) && sizeof($this->subsubname[$index]))
      $extra = ['subtables' => $this->linktext('subtables')];
    else $extra = [];

    $extra = array_merge($extra, ['edit' => $this->linktext('edit'), 'delete' => $this->linktext('delete')]);
    $list->set_extrafields($extra);
    $list->set_conditions($this->sub_condition[$index]);

    if($this->sub_keyfilter) $list->set_keyfilter($this->sub_keyfilter);

    // filter out serial fields from displayed list
    $ser_field_filter = '';

    $serial_field = $this->config('serial_field');
    if(is_array($serial_field))
      foreach($serial_field as $serfield)
        $ser_field_filter .= " && % != '$serfield'";

    if($this->sub_fkeyfilter) $list->set_fkeyfilter($this->sub_fkeyfilter[$index]);
    else $list->set_fkeyfilter("% != '$this->idfield'$ser_field_filter");

    if($this->sub_fields) $list->show_fields($this->sub_fields[$index]);
    if($this->sub_header) $list->set_header($this->translate_list($this->sub_header[$index]));

    $nfields = is_array($this->nfields) ? $this->nfields : [$this->nfields];
    ##$nfields = array_merge($nfields, [$this->name]);   // disabled! WHY???
    if($nfields) $list->hide_fields($nfields);

    $script = $this->subscript[$index] ? $this->subscript[$index] : "{$this->subname[$index]}$this->script_extension";
    $sub_idfield = $this->sub_idfield[$index];
    if($sub_idfield == '') $sub_idfield = 'id';

    $subtable_params = $this->subtable_params ?: '/subtables/';
    $edit_params = $this->edit_params?: '/edit/';
    $delete_params = $this->delete_params ?: '/delete/';

    if($this->use_subsubtablelink && is_array($this->subsubname[$index]) && sizeof($this->subsubname[$index]))
      $links = ['subtables'=>"$script$subtable_params{{$sub_idfield}}"];
    else $links = [];

    $links = array_merge($links, ['edit'=>"$script$edit_params{{$sub_idfield}}",
                                  'delete'=>"$script$delete_params{{$sub_idfield}}"]);
    if($this->sub_edit_link[$index]) $links = array_merge($links, [$this->sub_edit_link[$index] => "$script$edit_params{{$sub_idfield}}"]);
    #\booosta\debug($links);
    $list->set_links($links);

    $this->normalize_foreign_keys($index);
    #\booosta\debug($this->sub_foreign_keys);

    foreach($this->sub_foreign_keys[$index] as $fk=>$val)
      $list->set_foreignkey_db($fk, $val['table'], $val['idfield'], $val['showfield']);
 
    if(is_string($this->sub_boolfields)) $this->sub_boolfields = [$this->sub_boolfields];
    if(is_string($this->sub_boolfields[$index])) $this->sub_boolfields[$index] = explode(',', str_replace(' ', '', $this->sub_boolfields[$index]));

    if(is_array($this->sub_boolfields[$index]))
      foreach($this->sub_boolfields[$index] as $boolfield)
        if(substr($boolfield, 0, 1) == '!'):
          $boolfield = substr($boolfield, 1);
          $list->add_replaces($boolfield, [function($val) { return !$val ? '<i class="far fa-check-circle" style="color:green"></i>' : '<i class="far fa-times-circle" style="color:red"></i>'; }]);
        else:
          $list->add_replaces($boolfield, [function($val) { return $val ? '<i class="far fa-check-circle" style="color:green"></i>' : '<i class="far fa-times-circle" style="color:red"></i>'; }]);
        endif;

    return $list;
  }


  protected function linktext($text) 
  { 
    if($picconf = $this->{"{$text}_pic_code"}) return $picconf;
    return $this->t($text); 
  }


  protected function normalize_foreign_keys($index = null)
  {
    if($index === null):  // foreign_keys
      if($this->foreign_keys === null):  // foreign_keys not defined
        $this->foreign_keys = [];
        return;
      elseif(is_string($this->foreign_keys)):  // foreign_keys = 'fieldname'
        $this->foreign_keys = [$this->foreign_keys];
      endif;

      $fk = $this->foreign_keys;
    else:  // sub_foreign_keys
      if($this->sub_foreign_keys === null):  // sub_foreign_keys not defined
        $this->sub_foreign_keys[$index] = [];
        return;
      elseif(is_string($this->sub_foreign_keys)):  // sub_foreign_keys = 'fieldname'
        $this->sub_foreign_keys = [$index => $this->sub_foreign_keys];
      elseif(is_string($this->sub_foreign_keys[$index])):  // sub_foreign_keys = ['fieldname']
        $this->sub_foreign_keys[$index] = [$this->sub_foreign_keys[$index]];
      endif;

      $fk = $this->sub_foreign_keys[$index];
    endif;
    #\booosta\debug("index: $index"); \booosta\debug($this->sub_foreign_keys);

    $fk1 = [];

    // split comma seperated list into array
    if(is_string($fk)) $fk = explode(',', $fk);

    // add table, idfield, showfield if not present
    foreach($fk as $key=>$val):
      if(is_numeric($key) && is_string($val)):
        $fk1[trim($val)] = ['table' => trim($val), 'idfield' => 'id', 'showfield' => 'name'];
      elseif(!is_array($val)): 
        $fk1[$key] = ['table' => $key, 'idfield' => 'id', 'showfield' => 'name'];
      else:
        if(!isset($val['table']) || $val['table'] == '') $val['table'] = $key;
        if(!isset($val['idfield']) || $val['idfield'] == '') $val['idfield'] = 'id';
        if(!isset($val['showfield']) || $val['showfield'] == '') $val['showfield'] = 'name';

        $fk1[$key] = $val;
      endif;
    endforeach;

    if($index === null) $this->foreign_keys = $fk1;
    else $this->sub_foreign_keys[$index] = $fk1;
  }


  protected function fk_referenced_table($column)
  {
    $this->normalize_foreign_keys();
    $result = $this->foreign_keys[$column]['table'];
    if($result) return $result;

    $fk = $this->makeInstance('Db_foreignkeys');
    return $fk->referenced_table($this->name, $column);
  }


  protected function fk_referenced_column($column)
  {
    $this->normalize_foreign_keys();
    $result = $this->foreign_keys[$column]['idfield'];
    if($result) return $result;

    $fk = $this->makeInstance('Db_foreignkeys');
    return $fk->referenced_column($this->name, $column);
  }


  protected function fk_local_column($referenced_table)
  {
    $this->normalize_foreign_keys();
    foreach($this->foreign_keys as $column=>$fk)
      if($fk['table'] == $referenced_table) return $column;

    $fk = $this->makeInstance('Db_foreignkeys');
    return $fk->local_column($this->name, $referenced_table);
  }


  protected function set_backpage($backpage, $action = null, $name = null)
  {
    if($backpage === null) $backpage = $_SERVER['REQUEST_URI'];
    if($name === null) $name = $this->name;

    $actions = explode(',', $action); 
    $names = explode(',', $name);
      
    foreach($actions as $actionstr):
      if($actionstr) $actionstr = "_$actionstr";

      foreach($names as $namestr):
        if($namestr) $namestr = "_$namestr";
        $_SESSION["backpage{$actionstr}{$namestr}"] = $backpage;
      endforeach;
    endforeach;

    #\booosta\debug("set backpage: _SESSION[backpage{$actionstr}_{$name}] = $backpage");
  }


  protected function check_backpage($action = null, $name = null)
  {
    if($action === null) $actionstr = ''; else $actionstr = "_$action";
    if($name === null) $name = $this->name;

    if(isset($_SESSION["backpage{$actionstr}_{$name}"])) $this->backpage = $_SESSION["backpage{$actionstr}_{$name}"];
    elseif(isset($_SESSION["backpage_$name"])) $this->backpage = $_SESSION["backpage_$name"];
    elseif(isset($_SESSION['backpage'])) $this->backpage = $_SESSION['backpage'];
    elseif($this->backpage == '') $this->backpage = $this->self;

    #\booosta\Framework::debug("check_backpage: $this->backpage - _SESSION[backpage{$actionstr}_{$name}] = " . $_SESSION["backpage{$actionstr}_{$name}"]);
    unset($_SESSION["backpage_$name"]);
    unset($_SESSION["backpage{$actionstr}_{$name}"]);
    #\booosta\Framework::debug($_SESSION);
  }


  protected function action_delete()
  {
    $this->apply_userfield('action delete');
    
    $this->before_action_delete();
    if($this->use_form_token) $token = '&form_token=' . $this->generate_form_token();

    $deleteyes_params = $this->deleteyes_params ?: '?action=deleteyes&object_id=';

    $yeslink = "$this->self$deleteyes_params$this->id$token";
    #\booosta\Framework::debug("yeslink: $yeslink");

    $tpl = $this->confirm_delete_text;
    if($tpl == '' && is_readable('vendor/booosta/webapp/src/systpl/confirm_delete_modal.tpl.' . $this->lang)) $tpl = 'vendor/booosta/webapp/src/systpl/confirm_delete_modal.tpl.' . $this->lang;
    if($tpl == '') $tpl = 'vendor/booosta/webapp/src/systpl/confirm_delete_modal.tpl';
    
    $vars = ['object' => $this->t($this->name) ?: $this->name ?: $this->t('object')];
    if(is_array($this->confirm_delete_vars)) $vars = array_merge($vars, $this->confirm_delete_vars);

    $modal = $this->makeInstance('ui_modal', 'delete');
    $modal->set_template($tpl, $vars);
    $modal->set_auto_open(true);

    #$steps = $this->delete_cancel_steps ?? 3;
    if($this->ui_modal_cancelpage) $cancelcode = "location.href='$this->ui_modal_cancelpage'";
    else $cancelcode = "location.href='$this->self'";
    #else $cancelcode = "history.go(-$steps);";

    $modal->on_cancellation($cancelcode);
    $modal->on_confirmation("location.href='$yeslink'");
    $this->TPL['modal'] = $modal->get_html();

    if($this->supername) $this->after_action_delete_sub();

    // Hook after_action_delete
    $this->after_action_delete();

    $this->maintpl = 'vendor/booosta/webapp/src/systpl/confirm_delete.tpl';

    return true;
  }


  protected function after_action_delete_sub()
  {
    if($this->backpagetpl):
      $this->TPL['nolink'] = $this->get_backpage();
    else:
      $superfield = $this->find_super_fkfield();
      $script = $this->superscript ? $this->superscript : "$this->supername$this->script_extension";
      $id = $this->DB->query_value("select `$superfield` from `$this->name` where $this->idfield='$this->id'");

      if($id):
        if($this->super_use_subtablelink):
          $this->TPL['nolink'] = $this->subtable_params ? "$script$this->subtable_params$id" : "$script?action=subtables&object_id=$id";
        else:
          $this->TPL['nolink'] = $this->edit_params ? "$script$this->edit_params$id" : "$script?action=edit&object_id=$id";
        endif;
      endif;
    endif;
  }

  // default hook functions
  protected function before_action_delete() {}
  protected function after_action_delete() {}


  protected function action_deleteyes()
  {
    $this->maintpl = FEEDBACK;
    $this->check_backpage('delete');
    if($this->use_form_token) $this->check_form_token();

    // Hook before_action_deleteyes
    $this->before_action_deleteyes();

    if($this->deletefunction) $func = $this->deletefunction; else $func = "delete_$this->postfix";

    list($ok, $result) = $this->call($func, intval($this->VAR['object_id'] ?? $this->id));
    #\booosta\debug("ok: $ok"); \booosta\debug($result);
    if(!$ok || strstr(print_r($result, true), 'ERROR') || $result === false):
      $errormsg = "ERROR while calling $func: " . print_r($result, true);
      if(strstr($errormsg, 'foreign key constraint fails')) $errormsg = "foreign key violation in $func";
      $this->raise_error($errormsg);
    endif;
 
    // Hook after_action_deleteyes
    $this->after_action_deleteyes();
    #\booosta\Framework::debug("action backpage: $this->backpage");
    return true;
  }


  // default hook functions
  protected function before_action_deleteyes() {}
  protected function after_action_deleteyes() {}


  protected function action_new() 
  {
    if($this->use_form_token) $this->generate_form_token();

    $debug = $this->before_action_new();
    if($this->tpl_new) $this->maintpl = $this->tpl_new; else $this->maintpl = "tpl/{$this->name}_new.tpl";

    $this->normalize_foreign_keys();

    foreach($this->foreign_keys as $fk=>$val):
      $fk_table = $val['table'];
      $fk_id = $val['idfield'];
      $fk_show = $val['showfield'];

      if($val['clause']):
        $parser = $this->makeInstance('Templateparser');
        $fk_clause = $parser->parse_template($val['clause'], null, $this->VAR);
      else:
        $fk_clause = null;
      endif;

      if($fk_table == $this->supername) $this->superfield = $fk;
      $selclass = $this->config('use_bootstrap_select') !== false ? 'ui_select' : "\\booosta\\formelements\\Select";
      $sel = $this->makeInstance($selclass, $fk, $this->get_opts_from_table($fk_table, $fk_show, $fk_id, $fk_clause, 'a'),
                                 $this->VAR[$fk]);

      if(method_exists($sel, 'set_caption')) $sel->set_caption(ucfirst($this->t($fk)));
      if(method_exists($sel, 'set_prefix')) $sel->set_prefix($this->select_prefix);
      if(method_exists($sel, 'set_postfix')) $sel->set_postfix($this->select_postfix);
      
      #\booosta\Framework::debug("select_class: $this->select_class");
      if($this->select_class) $sel->add_extra_attr("class='$this->select_class'");
      $this->TPL["list_$fk"] = $sel->get_html();
      $sel->add_top(['' => '']);
      $this->TPL["list0_$fk"] = $sel->get_html();
    endforeach;

    if($this->supername) $this->after_new_sub();
    $this->after_action_new();
  }


  protected function after_new_sub()
  {
    $superfield = $this->superfield ? $this->superfield : $this->supername;
    $this->TPL[$superfield] = $this->VAR[$superfield];
  }
    

  // default hook functions
  protected function before_action_new() {}
  protected function after_action_new() {}


  protected function action_newdo()
  {
    $this->maintpl = FEEDBACK;
    $this->check_backpage('new');
    if($this->use_form_token) $this->check_form_token();

    if($this->addfunction) $func = $this->addfunction; else $func = "add_$this->postfix";

    $this->before_action_newdo();

    $var = $this->VAR;
    if(is_array($this->encode))
      foreach($this->encode as $field=>$tfunc) $var[$field] = call_user_func_array($tfunc, [$var[$field]]);

    if($this->required_fields && is_string($this->required_fields)) $required_fields = explode(',', $this->required_fields);
    else $required_fields = $this->required_fields;

    if(is_array($required_fields))
      foreach($required_fields as $field)
        if($var[$field] == '') $this->raise_error("Missing required field: $field");

    if(is_array($this->datefield) && $this->dateformat)
      foreach($this->datefield as $datefield)
        if($var[$datefield] && $var[$datefield] != '0000-00-00') $var[$datefield] = date('Y-m-d', strtotime(str_replace(' ', '', $var[$datefield])));
        else $var[$datefield] = null;

    list($ok, $result) = $this->call($func, $var);
    if(!$ok || strstr(print_r($result, true), 'ERROR') || $result === false):
      $this->raise_error("ERROR while calling $func: " . print_r($result, true));
    elseif($result === false):
      $this->raise_error("Record $this->id not found");
    endif;

    if($this->supername):
      $script = $this->superscript ? $this->superscript : "$this->supername$this->script_extension";
      $superfield = $this->find_super_fkfield();

      if($this->VAR[$superfield] && $this->backpagetpl == ''):
        if($this->super_use_subtablelink):
          if($this->subtable_params) $this->backpage = "$script$this->subtable_params{$this->VAR[$superfield]}";
          else $this->backpage = "$script?action=subtables&object_id={$this->VAR[$superfield]}";;
        else:
          if($this->edit_params) $this->backpage = "$script$this->edit_params{$this->VAR[$superfield]}";
          else $this->backpage = "$script?action=edit&object_id={$this->VAR[$superfield]}";;
        endif;
      endif;

      $this->after_newdo_sub();
    endif;

    if($this->VAR['gotoedit']):
      $edit_params = $this->edit_params?: '?action=edit&object_id=';
      $this->backpage = "$this->self$edit_params$this->newid";
      $this->backpagetpl = null;
    elseif($this->VAR['gotonew']):
      $this->backpage = "$this->self?action=new&$this->supername={$this->VAR[$superfield]}";
      $this->backpagetpl = null;
    endif;
    
    $this->id = $this->newid;
    $this->after_action_newdo();
    return true;
  }

  protected function after_newdo_sub() {}
  protected function before_action_newdo() {}
  protected function after_action_newdo() {}

  // find field in current table that points to the supertable
  protected function find_super_fkfield()
  {
    $superfield = $this->fk_local_column($this->supername);
    if($superfield) return $superfield;

    return $this->supername;
  }

  protected function action_edit() 
  {
    if($this->use_form_token) $this->generate_form_token();

    if($this->tpl_edit) $this->maintpl = $this->tpl_edit; else $this->maintpl = "tpl/{$this->name}_edit.tpl";
    if($this->getfunction) $func = $this->getfunction; else $func = "get_$this->postfix";

    #if($this->table_sortable) $this->table_sort_ajaxurl = "?action=sort&object_id={$this->id}";
    if($this->subtable_sortable) $this->subtable_sort_ajaxurl = "?action=sort&object_id={$this->id}";

    // Hook before_action_edit
    $this->before_action_edit();
    $this->apply_userfield('action edit');

    list($ok, $result) = $this->call($func, $this->id, 'edit');
    if(!$ok || (is_string($result) && strstr(print_r($result, true), 'ERROR'))):
      $this->raise_error("ERROR while calling $func: " . print_r($result, true));
    elseif($result === false):
      $this->raise_error($this->t('No matching record found'));
    endif;

    #\booosta\debug($result);
    if($this->supername && !$this->simple_userfield) $this->apply_userfield('sub:action edit');
    else $this->apply_userfield('action edit');

    if(is_array($this->decode))
      foreach($this->decode as $field=>$tfunc)
        $result[$field] = call_user_func_array($tfunc, [$result[$field]]);

    if($this->blank_fields && is_string($this->blank_fields)) $blank_fields = explode(',', $this->blank_fields);
    else $blank_fields =$this->blank_fields;

    if(is_array($blank_fields))
      foreach($blank_fields as $field) $result[$field] = '';

    #\booosta\debug($result);
    if(is_array($this->datefield) && $this->dateformat)
      foreach($this->datefield as $datefield)
        if($result[$datefield] && $result[$datefield] != '0000-00-00') $result[$datefield] = date($this->dateformat, strtotime($result[$datefield]));
        else $result[$datefield] = null;
    #\booosta\debug($result);

    $this->TPL = array_merge($this->TPL, $result);
    #\booosta\debug($this->TPL);

    $this->normalize_foreign_keys();

    foreach($this->foreign_keys as $fk=>$val):
      $obj = $this->get_dbobject();
      if(!is_object($obj)) continue;

      $fk_table = $val['table'];
      $fk_id = $val['idfield'];
      $fk_show = $val['showfield'];
  
      if($val['clause']):
        $parser = $this->makeInstance('Templateparser');
        $fk_clause = $parser->parse_template($val['clause'], null, $this->VAR);
      else:
        $fk_clause = null;
      endif;
  
      $selclass = $this->config('use_bootstrap_select') !== false ? 'ui_select' : "\\booosta\\formelements\\Select";
      $sel = $this->makeInstance($selclass, $fk, $this->get_opts_from_table($fk_table, $fk_show, $fk_id, $fk_clause, 'a'), $obj->get($fk));

      if(method_exists($sel, 'set_caption')) $sel->set_caption(ucfirst($this->t($fk)));
      if(method_exists($sel, 'set_prefix')) $sel->set_prefix($this->select_prefix);
      if(method_exists($sel, 'set_postfix')) $sel->set_postfix($this->select_postfix);
      
      if($this->select_class) $sel->add_extra_attr("class='$this->select_class'");
      $this->TPL["list_$fk"] = $sel->get_html();
      $sel->add_top(['' => '']);
      $this->TPL["list0_$fk"] = $sel->get_html();
    endforeach;

    if(sizeof($this->subname) && !$this->use_subtablelink) $this->after_edit_super();
    $this->TPL['subtables_in_edit'] = !$this->use_subtablelink;

    // Hook after_action_edit
    $this->after_action_edit();

    return true;
  }

  protected function after_edit_super()
  {
    $this->handle_subtables();
  }

  protected function handle_subtables()
  {
    $func = 'getall_class';
    $dclause = "$this->name='$this->id'";

    if(is_string($this->sub_key) && $this->sub_key != '') $this->sub_key = [$this->sub_key];

    if(is_array($this->subname)):
      foreach($this->subname as $index=>$subname):
        if(isset($this->sub_key[$index])) $clause = $this->sub_key[$index] . "='$this->id'"; else $clause = $dclause;

        if(!is_array($this->sub_default_clause)) $this->sub_default_clause = [$this->sub_default_clause];
        if($this->sub_default_clause[$index]) $clause = "($clause) and ({$this->sub_default_clause[$index]})";
        #\booosta\debug($clause);
        
        if(is_string($this->sub_listclassname)) $this->sub_listclassname = [$this->sub_listclassname];
        if($this->sub_listclassname[$index]) $subname = $this->sub_listclassname[$index];
        #if($this->use_postfix) $func = "getall_class_$subname";

        list($ok, $result) = $this->call($func, $subname, $clause, $this->sub_default_order($index));
        #\booosta\debug("$func, $subname");
        if(!$ok || (is_string($result) && strstr(print_r($result, true), 'ERROR'))):
          $this->raise_error("ERROR while calling $func: " . print_r($result, true));
        endif;

        if($this->translate_data) $result = $this->translate_list($result);
        $list = $this->get_subtablelist($result, $index);

        if(is_array($this->datefield) && $this->dateformat)
          foreach($this->datefield as $datefield)
            $list->add_replaces($datefield, function($val) { return ($val && $val != '0000-00-00') ? date($this->dateformat, strtotime(str_replace(' ', '', $val))) : ''; });
            #$list->add_replaces($datefield, function($val) { return date($this->dateformat, strtotime(str_replace(' ', '', $val))) . ' ' . substr($val, 11, 5); });

        // Hook in_sub_makelist
        $this->in_sub_makelist($list, $index);

        if($index === 0) $this->TPL['subliste'] = $list->get_html();
        else $this->TPL["subliste_$index"] = $list->get_html();
      endforeach;
    endif;

    if($this->use_datatable && is_object($list)) $this->TPL['datatable_includes'] = $list->get_html_includes();
  }

  // default hook functions
  protected function before_action_edit() {}
  protected function after_action_edit() {}
  protected function in_sub_makelist($list, $index = 0) {}


  protected function action_editdo()
  {
    $this->maintpl = FEEDBACK;
    $this->check_backpage('edit');
    if($this->use_form_token) $this->check_form_token();

    if($this->editfunction) $func = $this->editfunction; else $func = "edit_$this->postfix";

    $var = $this->VAR;
    #\booosta\debug($var);
    #\booosta\debug("id: $this->id");

    $this->before_action_editdo();
    if($this->cancel_update):
      $this->DB->transaction_rollback();
      return null;
    endif;
    
    if($var['object_id']) $var['id'] = $var['object_id'];
    if($this->id) $var['id'] = $this->id;

    if($this->blank_fields && is_string($this->blank_fields)) $blank_fields = explode(',', $this->blank_fields);
    else $blank_fields =$this->blank_fields;

    if(is_array($blank_fields))
      foreach($blank_fields as $field) 
        if($var[$field] === '') unset($var[$field]);

    if($this->required_fields && is_string($this->required_fields)) $required_fields = explode(',', $this->required_fields);
    else $required_fields =$this->required_fields;

    if(is_array($required_fields))
      foreach($required_fields as $field)
        if($var[$field] == '' && !in_array($field, $blank_fields)) $this->raise_error("Missing required field: $field");

    if(is_array($this->encode))
      foreach($this->encode as $field=>$tfunc) 
        if(isset($var[$field])) $var[$field] = call_user_func_array($tfunc, [$var[$field]]);

    if(is_array($this->datefield) && $this->dateformat)
      foreach($this->datefield as $datefield)
        if($var[$datefield] && $var[$datefield] != '0000-00-00') $var[$datefield] = date('Y-m-d', strtotime(str_replace(' ', '', $var[$datefield])));
        #else $var[$datefield] = null;

    #\booosta\debug($var);

    list($ok, $result) = $this->call($func, $this->id, $var);
    if(!$ok || strstr(print_r($result, true), 'ERROR') || $result === false):
      $this->raise_error("ERROR while calling $func: " . print_r($result, true));
    endif;

    $this->after_action_editdo();
    return true;
  }


  protected function before_action_editdo() {}
  protected function after_action_editdo() {}


  protected function action_subtables()
  {
    $this->apply_userfield('action subtables');
    $this->before_action_subtables();

    if($this->tpl_subtables) $this->maintpl = $this->tpl_subtables; else $this->maintpl = "tpl/{$this->name}_subtables.tpl";
    $this->TPL[$this->idfield] = $this->id;
    if(sizeof($this->subname)) $this->handle_subtables();

    $this->after_action_subtables();
  }

  protected function before_action_subtables() {}
  protected function after_action_subtables() {}

  protected function delete_($id)
  {
    if(($err = $this->auth('delete')) !== true) return $err;
    if(!is_numeric($id)) return "ERROR: id not numeric: $id";
 
    $this->DB->transaction_start();

    if($this->supername && !$this->simple_userfield) $this->apply_userfield('sub:delete', $id);
    else $this->apply_userfield('delete', $id);
    
    $obj = $this->get_dbobject($id);
    if(!$obj->is_valid()) $this->raise_error("Object $id not found");
    $this->old_obj = clone $obj;


    if($this->supername) $this->before_delete_sub($id);
    $result_ = $this->before_delete_($id);

    if($this->cancel_delete):
      $this->before_rollback_delete();
      $this->DB->transaction_rollback();
      $this->after_rollback_delete();
      return null;
    endif;

    $obj->delete();
    $this->error .= $obj->get_error();
    #\booosta\debug("error: $this->error");
 
    $this->after_delete_($id);

    if($this->error):
      $this->before_rollback_delete();
      $this->DB->transaction_rollback();
      $this->after_rollback_delete();
      return 'ERROR: ' . $this->error;
    endif;
 
    $this->DB->transaction_commit();
    return true;
  }
 
  protected function before_delete_sub($id)
  {
    $script = $this->superscript ? $this->superscript : "$this->supername$this->script_extension";

    $superfield = $this->find_super_fkfield();
    $superid = $this->DB->query_value("select `$superfield` from `$this->name` where `$this->idfield`='$id'");

    if($superid && $this->backpagetpl == ''):
      if($this->super_use_subtablelink):
        if($this->subtable_params) $this->backpage = "$script$this->subtable_params$superid";
        else $this->backpage = "$script?action=subtables&object_id=$superid";;
      else:
        if($this->edit_params) $this->backpage = "$script$this->edit_params$superid";
        else $this->backpage = "$script?action=edit&object_id=$superid";;
      endif;
    endif;
  }
 
  protected function before_delete_($id) {}
  protected function after_delete_($id) {}
  protected function before_rollback_delete() {}
  protected function after_rollback_delete() {}

  public function set_editvars($vars)
  {
    if(!is_array($vars)) $vars = explode(',', str_replace(' ', '', $vars));
    $this->editvars = $vars;
  }

  public function set_neditvars($vars)
  {
    if(!is_array($vars)) $vars = explode(',', str_replace(' ', '', $vars));
    $this->neditvars = $vars;
  }
  
  protected function edit_($id, $data)
  {
    #\booosta\debug("id: $id"); \booosta\debug($data);
    if(($err = $this->auth('edit')) !== true) return $err;
    if(!is_numeric($id)) return "ERROR: id not numeric: $id";
  
    $this->DB->transaction_start();
  
    $obj = $this->get_dbobject($id);
    if(!is_object($obj)) $this->raise_error("Record with primary key $id not found.");
    $this->old_obj = clone $obj;
 
    if($this->checkbox_fields && is_string($this->checkbox_fields)) $checkbox_fields = explode(',', $this->checkbox_fields);
    else $checkbox_fields = $this->checkbox_fields;

    if(is_array($checkbox_fields))
      foreach($checkbox_fields as $field)
        if($data[$field]) $data[$field] = '1'; else $data[$field] = '0';

    if($this->null_fields && is_string($this->null_fields)) $null_fields = explode(',', $this->null_fields);
    else $null_fields = $this->null_fields;

    if(is_array($null_fields))
      foreach($null_fields as $field)
        if($data[$field] === '' || ($this->treat_0_as_null && ($data[$field] === '0' || $data[$field] === 0 || $data[$field] === ''))) 
          $data[$field] = null;

    foreach($data as $var=>$val)
      if(($this->editvars === null || in_array($var, $this->editvars)) && ($this->neditvars === null || !in_array($var, $this->neditvars))) $obj->set($var, $val);

    if($this->supername && !$this->simple_userfield) $this->apply_userfield('sub:edit', $obj);
    else $this->apply_userfield('edit', $obj);

    // Hook before_edit_
    $this->before_edit_($id, $data, $obj);
    if($this->cancel_update):
      $this->before_rollback_edit();
      $this->DB->transaction_rollback();
      $this->after_rollback_edit();
      return null;
    endif;

    $this->apply_userfield('edit', $obj);
    
    #\booosta\debug($obj);
    $result = $obj->update();
    $this->error .= $obj->get_error();

    if($this->error):
      $this->before_rollback_edit();
      $this->DB->transaction_rollback();
      $this->after_rollback_edit();
      $this->raise_error('ERROR: Update failed: ' . $this->error);
    endif;

    if($this->supername) $this->after_edit_sub($id);
    // Hook after_edit_
    $this->after_edit_($id, $data);
  
    if($this->error):
      $this->before_rollback_edit();
      $this->DB->transaction_rollback();
      $this->after_rollback_edit();
      $this->raise_error('ERROR: after_edit failed: ' . $this->error);
    endif;

    $this->DB->transaction_commit();
    return true;
  }
  
  protected function after_edit_sub($id)
  {
    $script = $this->superscript ? $this->superscript : "$this->supername$this->script_extension";
    $super_fkfield = $this->find_super_fkfield();  // which field points to the supertable?
    $superid = $this->DB->query_value("select `$super_fkfield` from `$this->name` where `$this->idfield`='$id'");

    if($superid && $this->backpagetpl == ''):
      if($this->super_use_subtablelink):
        if($this->subtable_params) $this->backpage = "$script$this->subtable_params$superid";
        else $this->backpage = "$script?action=subtables&object_id=$superid";;
      else:
        if($this->edit_params) $this->backpage = "$script$this->edit_params$superid";
        else $this->backpage = "$script?action=edit&object_id=$superid";;
      endif;
    endif;
  }

  // default hook functions
  protected function before_edit_($id, $data, $obj) {}
  protected function after_edit_($id, $data) {}
  protected function before_rollback_edit() {}
  protected function after_rollback_edit() {}
  

  public function set_addvars($vars)
  {
    if(!is_array($vars)) $vars = explode(',', str_replace(' ', '', $vars));
    $this->addvars = $vars;
  }


  protected function add_($data)
  {
    if(($err = $this->auth('create')) !== true) return $err;
    $this->DB->transaction_start();
  
    $obj = $this->makeDataobject($this->classname);
  
    if($this->checkbox_fields && is_string($this->checkbox_fields)) $checkbox_fields = explode(',', $this->checkbox_fields);
    else $checkbox_fields = $this->checkbox_fields;

    if(is_array($checkbox_fields))
      foreach($checkbox_fields as $field)
        if($data[$field]) $data[$field] = '1'; else $data[$field] = '0';
 
    if($this->null_fields && is_string($this->null_fields)) $null_fields = explode(',', $this->null_fields);
    else $null_fields = $this->null_fields;

    if(is_array($null_fields))
      foreach($null_fields as $field)
        if($data[$field] === '' || ($this->treat_0_as_null && ($data[$field] === '0' || $data[$field] === 0 || $data[$field] === ''))) 
          $data[$field] = null;

    foreach($data as $var=>$val)
      if($this->addvars === null || in_array($var, $this->addvars)) $obj->set($var, $val);

    ## removed redundant apply_userfield

    // Hook before_add_
    $this->before_add_($data, $obj);
    #\booosta\debug("cancel_insert: $this->cancel_insert");
    if($this->cancel_insert):
      $this->before_rollback_new();
      $this->DB->transaction_rollback();
      $this->after_rollback_new();
      return null;
    endif;

    if($this->supername && !$this->simple_userfield) $this->apply_userfield('sub:new', $obj);
    else $this->apply_userfield('new', $obj);
    
    $newid = $obj->insert();
    $this->error .= $obj->get_error();
    
    if($this->error):
      $this->before_rollback_new($newid);
      $this->DB->transaction_rollback();
      $this->after_rollback_new($newid);
      $this->raise_error('ERROR: Insert failed: ' . $this->error);
    endif;

    $this->id = $this->newid = $newid;

    // Hook after_add_
    $this->after_add_($data, $newid);
 
    if($this->error):
      $this->before_rollback_new($newid);
      $this->DB->transaction_rollback();
      $this->after_rollback_new($newid);
      $this->raise_error('ERROR: after_add failed: ' . $this->error);
    endif;

    $this->DB->transaction_commit();
    return $newid;
  }


  // default hook functions
  protected function before_add_($data, $obj) {}
  protected function after_add_($data, $newid) {}
  protected function before_rollback_new($newid = null) {}
  protected function after_rollback_new($newid = null) {}


  protected function getall_($clause, $order = '1', $limit = null) 
  { 
    return $this->getall_class($this->listclassname, $clause, $order, $limit); 
  }


  protected function getall_class($classname, $clause, $order = '1', $limit = null)
  {
    #\booosta\debug("classname: $classname, clause: $clause, order: $order, limit: $limit");
    #if(($err = $this->auth('view')) !== true) { \booosta\debug("err: $err"); return $err; }
    if(($err = $this->auth('view')) !== true) return $err;
    $result = [];
  
    if(is_numeric($clause)) $clause = "$this->idfield='$clause'";
    $objs = $this->getDataobjects($classname, $clause, $order, $limit);
    #\booosta\debug($objs);

    foreach($objs as $obj):
      $v = [];
      $vars = $obj->get_data();

      foreach($vars as $var=>$val) $v[$var] = $obj->get($var);
      $result[] = $v;
    endforeach;

    #\booosta\debug($result);
    return $result;
  }


  protected function get_($id, $mode = null)
  {
    if(($err = $this->auth('view')) !== true) return $err;
    if($id == '') return false;

    if($mode == 'edit') $arr = $this->getall_class($this->editclassname, $id);
    else $arr = $this->getall_($id);
    #\booosta\debug($id); \booosta\debug($arr);

    if(sizeof($arr)) return $arr[0];
    return false;
  }
  
  
  protected function chkerror($str, $args = [])
  {
    if(is_object($str) && is_callable([$str, 'get_error'])) $str = $str->get_error();
    if(is_object($str) && !is_callable([$str, 'get_error'])) $str = print_r($str, true);
    if(is_array($str)) $str = print_r($str, true);
    if(is_string($args)) $args = ['message' => $args];
    
    $args = array_merge(['errorstr' => 'ERROR', 'transaction' => false, 'message' => $str], $args);

    if(strstr(print_r($str, true), $args['errorstr'])):
      if($args['transaction']) $this->DB->transaction_rollback();
      $this->raise_error(str_replace('{error}', $str, $args['message']));
    endif;
  }

  
  protected function add_toggles($type, $names, $value = null)
  {
    if(!is_array($names)) $names = explode(',', $names);

    if($value !== null) foreach($names as $name) $this->add_jquery_ready("add_toggle_$type(\"$name\", \"$value\");");
    else foreach($names as $name) $this->add_jquery_ready("add_toggle_$type(\"$name\");");
  }
  ## removed get/set_settings and moved to module tools
}

// pseudo table class for catching the calls to the tablelister when using ajax data
class pseudo_tablelister extends \booosta\base\Base
{
  protected $replaces = [];
  protected $links = [];
  protected $extrafields = [];

  public function set_links($links) { $this->links = $links; }
  public function add_link($field, $link) { $this->links[$field] = $link; }
  public function set_extrafields($extrafields) { $this->extrafields = $extrafields; }
  public function add_extrafield($extrafield, $key = null) { if($key) $this->extrafields[$key] = $extrafield; else $this->extrafields[] = $extrafield; }
  public function set_replaces($replaces) { $this->replaces = $replaces; }

  public function add_replaces($key, $replaces)
  {
    if(is_array($key)):
       $this->replaces = array_merge($this->replaces, $key);
    else:
      if(is_array($replaces)) $replaces = array_shift($replaces);   // functions can only be passed in an array
      $this->replaces[$key] = $replaces;
    endif;
  }

  public function get_replaces() { return $this->replaces; }
  public function get_links() { return $this->links; }
  public function get_extrafields() { return $this->extrafields; }
}

if(is_readable('incl/webappclasses.php')) include_once('incl/webappclasses.php');
else include_once(__DIR__ . '/defaultclasses.incl.php');
