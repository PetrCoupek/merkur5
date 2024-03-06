<?php
/** mlib.php - Merkur5 core library
 * 
 * There are some default actions when the core starts (executable part).
 * This file contains the framework's core essentials. 
 * The abstract class M5_core is the startpoint for client-server functionality. 
 * The class M5 could be used directly or could be override with the real one. 
 * The set of basic global functions simplifies and shorted the basic functionality
 *  
 * @author Petr Čoupek
 * @package Merkur5
 * @version 0.47-050324
 */
 /* compatability  */
if (!defined('PHP_VERSION_ID')) {
  $_version = explode('.', PHP_VERSION);
  define('PHP_VERSION_ID', ($_version[0] * 10000 + $_version[1] * 100 + $_version[2]));
}
if (!defined('__DIR__')){
  define('__DIR__',dirname(__FILE__));
}
/* executable part */
ini_set('default_charset','utf-8');
set_time_limit(0);
M5_core::iniset();
spl_autoload_register("m5_autoload"); /* $errcontext=null pro PHP8 */
set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext=null) {
    /* error was suppressed with the @-operator */
    /* $errcontext is attached object with all the details - do not print_r it ! */
    if (0 === error_reporting()) {
        return false;
    }
    M5::set('errors',M5::get('errors')."$errstr, $errno, $errfile, $errline ".gettype($errcontext)."\n");
});

/* the attempt to load global parametres stored in $GLOBALS or defined constants ..*/
if (file_exists('ini.php')) include_once 'ini.php';
/* done.. */

/** Abstract class with the core functionality */

abstract class M5_core{
  
  public static $ent=array();
  
  /** initialize of internal variables - the registry pattern 
   *  It is called in the mlib library itself
  */
  static function iniset(){
    self::set('debug',false);  /* debug status */
    self::set('errors','');    /* erors area for debug mode */
    //tick('start');
    self::set('header','');    /* header text */
    self::set('htfr','');      /* frontend content - scripts and styles */
    self::set('htpr','');      /* output text/html buffer */
    self::set('http_lan','C'); /* initial language */
    self::set('immediate',false); /* in CLI, you can output immediatelly, no to wait when script ends */ 
    self::set('path_current',str_replace('\\','/',dirname(dirname( __FILE__ )))); /* it requires to be called from subdir lib*/
    self::set('path_relative',str_replace($_SERVER['DOCUMENT_ROOT'],'',self::get('path_current')));
    self::set('routes',[]);    /* initial route rules */
    self::set('sapi_name',php_sapi_name()); /* when =='cli' command line script is running */
    self::set('title',''); /* title text */
    self::set('version','(c) SmallM 2022'); /* version text */
    self::set('DATA',self::getparm());
  }
     
  /** see global function ta() 
   * @param string $tagname
   * @param string $content
   * @return string 
   */ 
  static function ta($tagname,$content=''){
    //if ($content ==''){
    //  return '<'.$tagname.'/>';
    //}
    if ($content=='noslash'){
      return '<'.$tagname.'>';
    }
    return '<'.$tagname.'>'.$content.'</'.$tagname.'>'."\n";
  }

  /** see global function tg() 
   * 
  */
  static function tg($tagname,$params='',$content='',$nopack=true){
    if ($params!='') $params=' '.$params;
    if ($content=='noslash' && !$nopack){
      return '<'.$tagname.$params.'>';
    }
    return '<'.$tagname.$params.'>'.$content.'</'.$tagname.'>'."\n";
  }
  
  /** Skeleton method
   * 
   * This method provide main execution lopp / entry point for an PHP script. 
   * Late binding functionality since PHP 5.3 is essential.
   * Skeleton provide controller functionality by calling route method. 
   * @param string $path
   * @return void
   * */ 

  static function skeleton($path='css/m5.css'){
   self::set('htptemp',
   '<!DOCTYPE html>'."\n".
   self::ta('html',
    self::ta('head',
     self::tg('meta', 'http-equiv="Content-Type" content="text/html;" charset="utf-8"').
     self::ta('title','#TITLE#').
     self::tg('link','rel="stylesheet" media="screen" href="'.$path.'" type="text/css"')).
    self::tg('body','','#BODY#'.'#ERRORS#')));  
 
   static::route();   /* word static does "Late static bindings" */
  }
  
  /** Route method
   * @return void
   * This method routes the parametres. It is called from the skeleton.  */

  static public function route(){
    getparm();  /* minimal route is to call getparm() */
  }

  /** put text into output text/html buffer */
  static function putht($htext){
    if (is_array($htext) || is_object($htext)) $htext=print_r($htext,true);
    self::set('htpr',self::get('htpr').$htext);
  }
  
  /** put text into part for downloading the frontend functionality
   *  @param string $htext 
   *  @param string $key - the key is unique when the same functionality is required more then once 
   *                (f.e. two datefields in one form - the CSS,JS is downloaded only once )
   */
  static function puthf($htext,$key){
    static $keys=[];
    if (!isset($keys[$key])){
      $keys[$key]=true;
      self::set('htfr',self::get('htfr').$htext);
    }
  }
  
  /** Flush the buffer to output, depending if is called from command line interface (cli)
   * or differently (apache2handler)
   * @return void
   */
  static function htpr_all(){
   static $done=false; 
   if ($done) return 0;   
   if (self::get('sapi_name')=='cli'){
      if (self::get('header')!='' && !M5::get('immediate')) 
        echo self::get('header')."\n".str_repeat("=",80)."\n";
      echo str_replace('<br>','',self::get('htpr'))."\n";
      if (self::get('debug')) 
        echo "DEBUG\n".str_repeat("-",80)."\n".self::get('errors').str_repeat("-",80)."\n";
   }else{  
     if (self::get('title')=='') self::set('title',self::get('header'));
     self::set('htptemp',
      str_replace('#TITLE#',self::get('title'),
      str_replace('#HEADER#',self::get('header'),
      str_replace('#BODY#',self::get('htpr'),
      str_replace('#ACTIONS#',self::get('htactions'),
      str_replace('#___#',self::get('htfr'), self::get('htptemp')))))));
     if (self::get('debug')){
       self::set('htptemp',
        str_replace('#ERRORS#',
          tg('div','class="m5-errors"',' '.
           str_replace("\n",br(),str_replace(' ',nbsp(1),self::get('errors')))),
          self::get('htptemp')));
     }else{
       self::set('htptemp',
        str_replace('#ERRORS#','',self::get('htptemp')));
     }
     echo self::get('htptemp');
   }
   $done=true;
   return 0;  
  }

  /** Replace something in the output buffer
   * @param string $r what replace
   * @param string $s with replace
   * @return void
   */
  static function htpr_replace($r,$s){
    self::set('htpr',str_replace($r,$s,self::get('htpr')));
  }
  
  /** Registry design template for strings - common setter */
  static function set($k,$v){
    self::$ent[$k]=$v;
  }

  /** Registry design template for strings - common getter */
  static function get($k){
    /* common setter for class variables */
    return isset(self::$ent[$k])?self::$ent[$k]:'';  
  }

  static function getparm(){
    /* fill a hash with all input parameters, sets the api_name variable */
    $DATA=array();
    /* initialize the sapi_name flag, the only point the standard PHP function is used*/ 
    /* command-line params are in form p1=val1 p2=val2 ..etc delimiter is blank char
       when no = is present, then the param is set to '' (but not null) */
    if (self::get('sapi_name')=='cli'){
      $sep='=';
      if (isset($_SERVER['argv'])){
        for($i=1;$i<count($_SERVER['argv']);$i++) {
          if (strpos($_SERVER['argv'][$i],$sep)){
            $t=explode($sep,$_SERVER['argv'][$i]);
            $DATA[$t[0]]=$t[1];
          }else{
            $DATA[$_SERVER['argv'][$i]]='';
          }  
        }  
      }
    }else{  
      /*if (count($_POST)>0 && count($_GET)==0 ){$DATA=$_POST;}
      if (count($_GET)>0 && count($_POST)==0 ){$DATA=$_GET;}
      if (count($_GET)>0 && count($_POST)>0 ){$DATA=array_merge($_GET,$_POST);}*/
      $DATA=array_merge($_GET,$_POST);
    }  
    return $DATA;
  }

  
  /** implicit dispatch procedure
   *  it provides inspect of $_SERVER['REQUEST_URI'] and search "subfolders"
   *  accorning to the rules on routes params table
   *  it ends with success on first match
   * it works together with recomended Apache mod_rewrite settings
   *  @return bool true on success param route
  */
  static function getroute(){
    //$p=M5::get('path_relative'); deb($p,false);
    $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $rel=M5::get('path_relative');
    $request_url = strtok(rtrim($request_url, '/'), '?');

    foreach(self::get('routes') as $route=>$action){
      $route_parts = explode('/', $rel.$route); 
      if( $route_parts[count($route_parts)-1]=='') array_pop($route_parts);
      $request_url_parts = explode('/', $request_url);
      array_shift($route_parts);       /* it removes the empty item from the begin .. */
      array_shift($request_url_parts); /* ..on both arrays */
      //deb($route_parts,false);
      //deb($request_url_parts,false);
      if( $route_parts[0] == '' && count($request_url_parts) == 0 ){
        //deb('route primitive :'.$route,false);
        return $route;
      }
      if(count($route_parts)!=count($request_url_parts)){ 
        //deb('none +:'.$route,false);
        continue; 
      }
      $parameters = []; 
      for($i=0,$ok=true;$i<count($route_parts);$i++){
        $rp=$route_parts[$i];
        if( preg_match("/^[$]/",$rp) ){
          $rp=ltrim($rp,'$');
          array_push($parameters,$request_url_parts[$i]);
          setpar($rp,$request_url_parts[$i]);
          //deb('set '.$rp.':'.getpar($rp),false);
        }elseif($route_parts[$i] != $request_url_parts[$i] ){
          //deb('none *:'.$route,false);
          $ok=false;
          break;
        } 
      }
      //deb($i);
      if ($ok){
        if (!strstr($route,'$')){
          //deb('route static link++'.$route);
          if (is_callable($action)){   
            call_user_func_array($action, $parameters);
          }else{
            eval($action); 
          }  
          return $route;
        }else{
          //deb('route dynamic link++'.$route);
          if (is_callable($action)){
            call_user_func_array($action, $parameters);
          }else{
            eval($action); 
          }  
          return $route;
        }  
      } 
    }
    return '';
  }

   
   /** 
    * @param string $text1
    * @param string $text2
    * @param string $text3
    */
   static function lan($text1, $text2, $text3=''){
      if (self::get('http_lan') == 'E') return $text1;
      if (self::get('http_lan') == 'C') return $text2;
      return $text3;
   }

   /**
    * synonym for htpr_all method
    */
   static function done(){
      self::htpr_all();
   }
}

/** Merkur5 class
 * 
 * The real Merkur-based server-side application should extend this class.
 * Methods skeleton and route are to override.
 * Default skeleton contains recommended boostrap-base layout for quick prototyping
 */

abstract class M5 extends M5_core{
  
  /** skeleton method sets a HTML template and provide a basic route . 
   * The route methods is to be override. */
  static function skeleton($path=''){
   if ($path==''){$path=M5::get('path_relative').'/';} 
   self::set('htptemp','<!DOCTYPE html>'."\n".
   tg('html','lang="cs"',
    ta('head',
     ta('title','#TITLE#').
     tg('meta','http-equiv="content-type" content="text/html; charset=utf-8"','noslash').
     tg('meta','name="language" content="cs"','noslash').
     tg('meta','name="viewport" content="width=device-width, initial-scale=1.0"','noslash').
     tg('meta','name="description" lang="cs" content="Merkur 5 kit set"','noslash').
     tg('meta','name="keywords" lang="cs" content="Merkur5 kit"','noslash').
     tg('link','rel="stylesheet" media="screen,print" href="'.$path.'css/m5.css?a=11" type="text/css" ','noslash').
     tg('link','rel="stylesheet" media="screen,print" href="'.$path.'vendor/bootstrap/css/bootstrap.css" type="text/css" ','noslash').
     tg('script','src="'.$path.'vendor/jquery/jquery.min.js"',' ').
     tg('script','src="'.$path.'vendor/bootstrap/js/bootstrap.bundle.min.js"',' ').
     '#___#'
     ).
     tg('body','style="padding-top: 3.5rem;"',
      tg('nav','class="navbar navbar-expand-ld navbar-dark bg-dark fixed-top"',
       tg('a', 'class="navbar-brand" href="?"','#HEADER#')
      ).
      ta('main',
      tg('div',
         'id="page-content-wrapper"',
       tg('div',
         ' class="container-fluid"','#BODY#'))).'<hr>'.
       tg('div','class="d-flex justify-items-end float-right"',
        self::get('version').
      
      tg('div','class="m5-loader"',
       tg('div','class="d-flex justify-content-center"',
        tg('div','class="spinner-border text-primary big"',' ')).
         
      tg('script',' ',
         '$(window).bind("beforeunload", function(){
           document.body.style.opacity=0.6;
          $(".m5-loader").css("visibility","visible");          
           });'
      ))).'#ERRORS#')));

   static::route();   /* keyword "static::" does "Late static bindings" */
 }

}

/* global functions and global aliases */

/**
  * Global function: Autoload function
  * 
  * The core library lib.php defines and activates this function via spl_autoload_register.
  * The function provides autoload queue .
  * Incorporated parts/classes of the framework system are then instatnly available without using require statement.
  * Currently are suppoerted : Edit_table, View_table, OpenDB* classes and Cm class.
  * @param $class Class 
  * @return none */

function m5_autoload($class){
  /* rizeny autoload jednotlivych modulu  zakladni knihovny */
  $path=__DIR__; /* $path by mel obsahovat cestu z zakladni knihovne lib, ktera je nactena jeko prvni */
  //echo $path,";";  //puvodne $path='lib';
  $f=array('VisTab'=>$path.'/vistab.php',
           'EdiTab'=>$path.'/vistab.php',
           'OpenDB_Oracle'=>$path.'/mdbOracle.php',
           'OpenDB_MySQL'=>$path.'/mdbMySQL.php',
           'OpenDB_SQLite'=>$path.'/mdbSQLite.php',
           'OpenDB_ODBC'=>$path.'/mdbODBC.php',
           'Cm'=>$path.'/mlib_cm.php'
         );
  foreach ($f as $k=>$v){
    if ($k==$class){
      require($v);  
      return 1;
    }
  }
  /* ostatni tridy jsou ve slozkach lib (systemove) nebo php (aplikacni) */
  if (file_exists('lib/'.$class.'.php')){
    require('lib/'.$class.'.php'); return 1;
  }
  if (file_exists($path.'/'.$class.'.php')){ /* pokud je path nenulova */
    require($path.'/'.$class.'.php'); return 1;
  }
  if (file_exists('php/'.$class.'.php')){
    require('php/'.$class.'.php'); return 1;
  }
}


/* globl function aliasses */

/** The function "prints" the HTML content into a HTML buffer M5::get('htptemp')
  * 
  * Global shortcut for the internal method for creating a HTML output: It stores the conontent of it's parametres into
  * the $htptemp buffer, which is finally send to the standard output using htpr_all function.
  * @param string $args,...  one or more strings with a HTML content 
  */

function htpr(){
  static $first;
  if (M5::get('sapi_name')=='cli' && M5::get('immediate')){
    if (!isset($first)) {
      echo M5::get('header')."\n".str_repeat("=",80)."\n"; /* print header when direct output */
      $first=false;
    }  
    for ($i = 0; $i < func_num_args(); $i++){
      echo(str_replace('<br>','',func_get_arg($i)));
    }
    return;
  }
  for ($i = 0; $i < func_num_args(); $i++){
    M5::putht(func_get_arg($i));
  }
}

/** The function finalizes and echoes the script output. It is used only once in the response, typically at the end of the sript.
  * 
  * Global shortcut for the method M5::htpr_all(). It replaces all the #XXXX# marks with its real content, 
  * prints the parametres into * the $htptemp buffer, which is finally send to the standard output using "echo" .
  * No params.
  * @return none
  */

function htpr_all(){
  M5::htpr_all();
}

/** Replaces the already htpr - "printed" text $r with $s
 * @param $r - text to replace
 * @param $s - replacement
 */ 

function htpr_replace($r,$s){
  M5::htpr_replace($r,$s);
}

/** The function returns its parametres as a string
 *  @param ... one or more strings
 *  @return string
 */

function gl(){
  $r='';
  for ($i = 0; $i < func_num_args(); $i++){
    $r.=(string)func_get_arg($i);
  }
  return $r;
}

/** The function fills the global array $DATA with the both $_GET and $_POST parametres
 *  It publishes the $DATA hash as a global.
 *  No params.
 *  @return none
 */

function getparm(){
  global $DATA;
  $DATA=M5::getparm();
}

/** Debugging tool in the framework Prints debug information about a PHP variable.
  * 
  * Deb provides debugging messages. When $debug attribute in the controller class is set to true 
  * and the output html template contains '#ERRORS#' label, the result will be visible on the
  * processed page. The Debug ouput contains the exact place in the source code and the content of the variable
  * @param [object] $t any PHP variable to be printed - variable of any type (string, number, array, objects)
  */

function deb($t,$btrace=true){  /* funkce realizujici ladici vypisy */  
  $d=$btrace?debug_backtrace():'';  /* zjisti, odkud byla funkce deb zavolana */
  $s='';
  for ($i=0;isset($d[$i]);$i++){
    $s.=(isset($d[$i]['file'])?$d[$i]['file']:'').':'.
        (isset($d[$i]['function'])?$d[$i]['function']:'').':'.
        //(isset($d[$i]['method'])?$d[$i]['method']:'').':'.
        (isset($d[$i]['line'])?$d[$i]['line']:'').':'.
        "\n";
  }
  $er=M5::get('errors');
  if (is_null($t)) {
    $er.=$s.'NULL'."\n";
  }elseif (is_bool($t)){
    $er.=$s.'[bool]:'.($t?'TRUE':'FALSE')."\n";
  }elseif (is_float($t)) {
    $er.=$s.'[float]:'.$t."\n";
  }elseif (is_array($t) || is_object($t)) {
    $er.=$s.print_r($t,true)."\n";
    //return;
  }elseif (is_string($t)) {
    $er.=$s."[string]:'".htmlentities($t,ENT_COMPAT,'utf-8')."'\n"; 
  }elseif (is_int($t)) {
    $er.=$s.'[int]:'.$t."\n";
    M5::set('errors',$er);
    return;
  }elseif (is_resource($t)) {
    $er.=$s.'[resource]:'.$t."\n";
  }elseif (is_scalar($t)) {
    $er.=$s.'[scalar]:'.$t."\n";
  }
  M5::set('errors',$er);
  if (M5::get('sapi_name')=='cli' && M5::get('immediate')){
    echo 'DEBUG: '.M5::get('errors')."\n";
    M5::set('errors','');
  }    
}

/** Sets the time-measure tool
 *  First call in the script starts the clok, the next calls display the time difference since
 *  the first call and optionally some text hwich can identify the call
 * @param string  -a text to be printed in the debug area. 
 */ 

function tick($text=''){
  static $t;
  if (!isset($t)) $t=microtime(true);
  $msg=sprintf(" %2.4f s %s",microtime(true)-$t,$text."\n");
  M5::set('errors',M5::get('errors').$msg);
  return $msg;
}

/** Tag expression function.
   * 
   * The function a string which contains a tag expression. Ihis prevents using tags in source code at all.
   * @param $tagname string the name of the tag
   * @param $content string the internal content of the tag
   * @return string
*/   

function ta($tagname,$content=''){
  return M5::ta($tagname,$content);
}

 /** Tag with parameters expression function.
   * 
   * It returns a string which contains a tag expression with parametres. This prevents using tags in source code at all.
   * @param string $tagname  the name of the tag
   * @param string $params   the tag parametres in a string form [name]=[value] space delimited 
   * @param string $content='' the internal content of the tag
   * @param boolean $nopack when is true, the empty content do not simplyfy the tag into non-pair form 
   * @return string
   */ 

function tg($tagname,$params='',$content='',$nopack=false){
  return M5::tg($tagname,$params,$content,$nopack);
}

/* dalsi flobalni funkce */

/** The function returns HTML non-breaking spaces. 
 * @param number $count - how many, default=1 
 * @return string */

function nbsp($count=1){
  return str_repeat('&nbsp;',$count);
}

/** The function returns the HTML tag for the page-break.  
 * @param number $count - number of page-braks, default=1 
 * @return string 
 */ 
function br($count=1){
  for($k='';$count--; $k.="<br>\n");
  return $k;
}

/** The function returns the HTML tag for the vizible horizontal break-line
 * @return string 
 */
 
function hr(){
  return '<hr>'."\n";
}

/** The function returns the HTML tag for a text input
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param number $size - the size in chars
 * @param number $maxl - the maximum allowed input size in chars
 * @param string $value - initial value in the text input
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string */
 
function textfield($label,$name,$size,$maxl,$value,$add=''){
  if ($label!='') {$p='&nbsp;';}else{$p='';}
  $value=str_replace('"',"&quot;",$value);
  return ta('span',$label.$p).
         tg('input','type="text" name="'.$name.'" size="'.$size.'" '.
            'maxlength="'.$maxl.'" value="'.$value.'" '.$add,'noslash');
}

/** The function returns the HTML tag for a text input, initial value is taken from a global $DB hash
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param number $size - the size in chars
 * @param number $maxl - the maximum allowed input size in chars
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string */
 
function dbtext($label,$name,$size,$maxl,$add=''){
  global $DB;
  return textfield($label,$name,$size,$maxl,isset($DB[$name])?$DB[$name]:'',$add);
}

/** The function returns the HTML tag for a text area
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param number $rows - the number of rows
 * @param number $cols - the number of columns
 * @param string $value - initial value in the text area
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string */
 
function textarea($label,$name,$rows,$cols,$value,$add=''){
  return $label.tg('textarea', 'name="'.$name.'" rows='.$rows.(' cols='.$cols).' '.$add,$value,true);
}

/** The function returns the HTML tag for a text area, initial value is taken from a global $DB hash
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param number $rows - the number of rows
 * @param number $cols - the number of columns
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string */
 
function dbtextarea($label,$name,$rows,$cols,$add=''){
  global $DB;
  return textarea($label,$name,$rows,$cols,
    isset($DB[$name])?(gettype($DB[$name])=="string"?$DB[$name]:$DB[$name]->load()):'',$add);
}

/** The function returns the HTML tag for a check box
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param string $value - the posted value, when the element is checked
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string HTML */
 
function checkbox($label,$name,$value,$add=''){
  $value=str_replace('"',"&quot;",$value);
  $c=($value!='0' && $value!='' && $value!='N')?'checked':'';
  return tg('input','type="checkbox" name="'.$name.'" value="'.$value.'" '.$add.' '.$c).nbsp(1).$label;
}

/** The function returns the HTML tag for a check box
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param boolean $checked - the posted value, when the element is checked
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string HTML  */
 
function check_box($label,$name,$checked,$add=''){
  $c=($checked)?'checked':'';
  return $label.nbsp().tg('input','type="checkbox" name="'.$name.'" value="1" '.$add.' '.$c);
}

/** The function returns the HTML tag for a check box, value to be posted when checked is taken from a global $DB hash
 * @param string $label - label before the input
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string HTML  */
 
function dbcheckbox($label,$name,$add=''){
  global $DB;
  return checkbox($label,$name,isset($DB[$name])?$DB[$name]:'0',$add);
}

/** The function returns the HTML tag for a drop-down list of items, it can be dynamic from database or static list
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param string $connection - db connection string for dynamic list or empty string for static lists
 * @param mixed $sele - the word 'static' followed by a list of item=value or SQL command selecting items from DB
 *     or and array in form SQL command and bind attributes,
 * @param string $def - initial selected value in the list
 * @param string $js - other added parametres in the tag (useful for javascript client-side functionality or styling)
 * @param integer $limit - maximal total count of items taken from the database source
 * @return string HTML  */
 
function lov($label,$name,$connection,$sele,$def='',$js='',$limit=12000){
 $i=0;
 $klichodnota=array();
 $ret=$label.(($label!='')?'&nbsp;':'')."<select name=\"$name\" $js> ";
 if (is_array($sele)){
   $bind=$sele[1];
   $sele=$sele[0];
 }else{
   $bind=array();
 }
 
 /* staticky seznam */
 if (preg_match('/^static\s+(.*)$/',$sele,$m)){
   $pary=preg_split('/,/',$m[1]);
   foreach ($pary as $par){
     $klichodnota=preg_split('/=/',$par);
     $klic=$klichodnota[0];
     if (isset($klichodnota[1])){
       $hodnota=$klichodnota[1];
     }else{
       $hodnota='';
     }
     $klic=str_replace('&eq;','=',$klic);
     $hodnota=str_replace('&eq;','=',$hodnota);
     $hodnota=str_replace('"',"&quot;",$hodnota);
     $ret.="<option ";
     if ($klic==$def) {
       $ret.="selected ";
     }
     $ret.="value=\"$klic\">$hodnota</option>\n";
   }
   $ret=$ret."</select>\n";
   return $ret;
 }
 
 /* dynamicky seznam generovany z databaze */
 if (is_string($connection)) {
   $db=new OpenDB_Oracle($connection);
 }elseif(is_object($connection)){
   $db=$connection;
 }else{
   $db=null;
 }  
 if ($db){
   $db->Sql($sele,$bind);
   $ret.="<option value=\"\">[]\n";  //prazdny radek
   while ($db->FetchRowA() && ($i<=$limit)){
     //print_r ($db->Data); break;
     $klic=$db->Data(0);
     $hodnota=$db->Data(1);
     $ret.="<option ";
     if ($klic==$def) {$ret=$ret."selected ";}
     $ret.="value=\"$klic\">$hodnota</option>\n";
     $i++;
   }
   $ret.="</select>\n";
 }
 if (is_string($connection)) $db->Close();
 return $ret;
}

/** The Function returns the HTML tag for a drop-down list, dynamic or static, initial value is taken from a global $DB hash
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param string $connection - db connection string for dynamic list or empty string for static lists
 * @param string $sele - the word 'static' followed by a list of item=value or SQL command selecting items from DB
 * @param string $js - other added parametres in the tag (useful for javascript client-side functionality or styling)
 * @param integer $limit - maximal total count of items taken from the database source
 * @return string HTML */

function dblov($label,$name,$napojeni,$sele,$js='',$limit=1200){
  global $DB;
  return lov($label,$name,$napojeni,$sele,isset($DB[$name])?$DB[$name]:'',$js,$limit);
}

/** The function returns the HTML tag for a drop-down list of items, list is an input array
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param array $list - the key/value array of given options, use to_hash function to convert SQL result for this input
 * @param string $def - initial selected value in the list
 * @param string $js - other added parametres in the tag (useful for javascript client-side functionality or styling)
 * @param bool $null_value - when false, null value is not in the options, default value is true
 * @param array $styling - the possibility to kontrol style of individual items
 * @return string HTML*/
 
function combo($label,$name,$list,$def='',$js='',$null_value=true,$styling=[]){
  if ($null_value) {
    $r=tg('option','value=""'.($def==''?' selected ':''),'[]');
  }else{
    $r='';
  }  
  foreach ($list as $k=>$v){
    $style=(isset($styling[$k]))?(' '.$styling[$k]):'';
    $r.=tg('option','value="'.$k.'"'.($k==$def?' selected':'').$style,$v);
  }  
  $r=$label.tg('select','name="'.$name.'" '.$js,$r);
  return $r;
}

/** Set of radio buttons
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param array $list - a has of value/visible item, the key is the field value in the HTML option tag
 * @param string $def - initial selected value in the list
 * @param string $js - other added parametres in the tag (useful for javascript client-side functionality or styling)
 * @return string HTML 
 */ 

function radio($label,$name,$list,$def='',$js=''){
  $r=$label;
  foreach ($list as $k=>$v){
    $r.=tg('input',
         'type="radio" name="'.$name.'" value="'.$k.'"'.
         ' id="'.$name.'_'.$k.'" style="display:inline;" '.($k==$def?'checked="checked"':''),
         'noslash').
        $v.nbsp(1)."\n";
  }
  return $r;  
}


/** returns HTML sequence useful for one line in the parametric form. Parametric form is used when subset of the data is specified.
 * @param string $label - label before the input query
 * @param string $par - the name of the parameter
 * @param string $pardb - the true name of the parameter in the database
 * @param string $type - type of the parameter - line behavioral. 
 * Possible values are 'E': general , 'S': combobox/list of cathegories, 'T': text, 'TT': text with case sensitivity option,
 * 'D': date,
 * @return string HTML */

function paramline($label,$par,$pardb,$typ="E"){
  $lov_all=M5::lan(
  'static &eq;=&eq;,&lt;=&lt;,&lt;&eq;=&lt;&eq;,&gt;&eq;=&gt;&eq;,&gt;=&gt;,&lt;&gt;=&lt;&gt;,'.
  'like=contains,null=empty,not null=not empty,in=v,not in=not in',
  'static &eq;=&eq;,&lt;=&lt;,&lt;&eq;=&lt;&eq;,&gt;&eq;=&gt;&eq;,&gt;=&gt;,&lt;&gt;=&lt;&gt;,'.
  'like=obsahuje,null=je prázdné,not null=je neprázdné,in=v,not in=není v');
  $lov_number=M5::lan(
  'static &eq;=&eq;,&lt;=&lt;,&lt;&eq;=&lt;&eq;,&gt;&eq;=&gt;&eq;,&gt;=&gt;,&lt;&gt;=&lt;&gt;',
  'static &eq;=&eq;,&lt;=&lt;,&lt;&eq;=&lt;&eq;,&gt;&eq;=&gt;&eq;,&gt;=&gt;,&lt;&gt;=&lt;&gt;');
  $lov_text=M5::lan(
  'static like=contains,begins=begins,ends=ends,&eq;=&eq;',
  'static like=obsahuje,begins=začíná,ends=končí,&eq;=&eq;');
  // vychozi typ pro parametr je vse
  $lov=$lov_all; $default='like'; $add='';
  if ($typ=='E' || $typ==''){ //tj. typ nebyl zvolen
    if (preg_match('/<select /',$pardb)){
      $lov=$lov_number; $default='=';
    }
  }
  if ($typ=='S'){ //tj. typ combobox
    $lov=$lov_number; $default='=';
  }
  if ($typ=='T'){ //tj. typ jen text
    $lov=$lov_text; $default='like';
  }
  if ($typ=='TT'){ //tj. typ jen text
    $lov=$lov_text; $default='like';
    $add=checkbox(M5::lan('Case Sensitivity','Citlivost:'),$par.'_uns',1);
  }
  if ($typ=='D'){ //datumova polozka
    $lov=$lov_number;
    $add='';
  }
  return trtd().$label.tdtd().lov('',$par.'_par','',$lov,$default).tdtd().
     $pardb.$add.trow();
}

/** The Function returns HTML sequence useful for one line in the parametric form in default/hidden format. 
 * Parametric form is used when subset of the data is specified.
 * @param string $label - label before the input query
 * @param string $par - the name of the parameter
 * @param string $pardb - the true name of the parameter in the database
 * @param string $hid_par - the value of the default parameter
 * @return string HTML */
 
function paramline_hidd($label,$par,$pardb,$hid_par){
  return trtd().$label.
   tdtd().tg('input','type="hidden" name="'.$par.'_par'.'" value="'.$hid_par.'"','noslash').
   tdtd().$pardb.trow();
}

/** The Function returns HTML tag with the hidden input tag  
 * @param string $name - the name of the hidden parameter
 * @param string $value - the value of the hidden parameter 
 * @return string */
 
function para($name,$value){
  return "\n".tg('input','type="hidden" name="'.$name.'" value="'.$value.'"','noslash')."\n";
}

/** The Function returns HTML form submit button  
 * @param string $name - the name of the tag
 * @param string $value - the visible label on the button
 * @param string $class - the CSS class for special buttons
 * @param string $title - optional title
 * @return string HTML  */
 
 function submit($name,$value,$class='btn btn-primary',$title=''){
  if ($class=='ulozit' || $class=='vlozit' || $class=='smazat') {
    return tg('input','type="submit" title="'.$value.'" name="'.$name.'" value=" " '.'class="'.$class.'"','noslash');
  }else{
    return tg('input','type="submit" name="'.$name.'" value="'.$value.
     '" title="'.($title!=''?$title:$value).'" '.'class="'.
    ($class<>''?$class:'submit').'"','noslash');
  }
}

/** The Function returns a HTML form reset button  
 * @param string $class - the CSS class for special buttons
 * @return string HTML */
 
function subres($class='ui-button ui-widget ui-corner-all'){
  return tg('input type="reset" value="Reset" class="'.$class.'"');
}

/** The Function returns a HTML td tag for contruction HTML tables  
 * @param integer $col - number of columns 
 * @param string $add - aditional parametres
 * @return string HTML */
 
function td($col='',$add=''){
  return tg('td',(($col<>'')?(' colspan="'.$col.'"'):'').($add<>''?(' '.$add):''),'noslash');
}

/** The Function returns a HTML sequence between two cells in the table  
 * @param integer $col - number of columns for the next cell 
 * @param string $add - aditional parametres
 * @return string HTML */
 
function tdtd($col='',$add=''){
  return '</td>'.td($col,$add);
}

/** The Function returns a HTML sequence for the first cell in a table row  
 * @param integer $col - number of columns for the next cell 
 * @param string $add - aditional parametres
 * @return string HTML  */
function trtd($col='',$add=''){
  return '<tr>'.td($col,$add);
}

/** The function returns a HTML sequence for the end of the table row  
 * @return string HTML */
 
function trow(){
  return '</td></tr>';
}

/** The function replaces quotation marks for all the $DATA items  
  */
  
function trans(){
  /* pokud data obsahuji jednoduche uvozovky, zmen je na chr(39), aby byl sql dotaz syntakticky spravne*/
  global $DATA;
  foreach ($DATA as $klic=>$pol ){
    if ($klic <>'WHR_'){
      $DATA[$klic]=str_replace("'","&#039;",$DATA[$klic]);
      $DATA[$klic]=str_replace(chr(92),'',$DATA[$klic]);
      /* koment toto ne pro skripty $DATA[$klic]=str_replace('"',"&quot;",$DATA[$klic]);*/
    }
  }
}

/** The signal for the language mutation  
 * @return boolean */
function http_lan(){
  /*je true mimo prime nastaveni anglictiny*/
  global $http_lan;
  return $http_lan=='E';
}

/** The Function prepares HTML squence for the pop-up menu based on the $menu definition object 
 * @param array $menu - the menu definition hash/object  
 * @param integer $level - initial level of the menu structure
 * @return string HTML */
 
function popup_menu($menu,$level=0){
  /* vytiskne predanou stromovou strukturu na podoby ul, li, aby byla pouzitelna pro Simple jQuery Dropdowns */
  $s='';
  foreach ($menu as $k=>$v){
    if (empty($menu[$k]['href'])){
      $s.=ta('li', ahref('#',$k)."\n".tg('ul','class="sub_menu"',"\n".popup_menu($menu[$k],$level+1)));
    }else{
      /* terminal leave */
      $s.=ta('li',ahref($menu[$k]['href'],$k));
    }
  }
  return ($level==0?tg('ul','class="dropdown"',nbsp(10).$s):$s);
}

/** The Function returns PHP-array based on DB table select
 * @param string $sel - SQL commad
 * @param object $db - open database object  
 * @param array $bind - parametres to bind in SQL command
 * @return array */
 
function to_array($sel, $db, $bind=array()){
  /* prevede obsah databazoveho selectu do pole radku v PHP */
  $a=array();
  if ($db){
    for($db->Sql($sel,$bind),$n=1;
       $db->FetchRow();
       $n++){
      $a[$n]=$db->DataHash();  
    }
  }
  return $a;
}

/** The Function returns PHP-array based on DB table select
 * @param string $sel - SQL commad
 * @param object $db - open database object  
 * @param array $bind - parametres to bind in SQL command
 * @return array */
 
function to_hash($sel, $db, $bind=array()){
  /* prevede obsah databazoveho selectu do pole radku v PHP */
  $a=array();
  if ($db){
    for($db->Sql($sel,$bind);$t=$db->FetchRowA();){
      $a[$t[0]]=$t[1];  
    }
  }
  return $a;
}


/** The Function returns appropriate natinal text message based on centralized setup (global $lan variable) 
 *  @param string $text1  international message
 *  @param string $text2  national message
 */ 

function http_lan_text($text1,$text2){
  if (isset($GLOBALS['lan'])){
    $http_plan=$GLOBALS['lan'];
    if ($http_plan == 'E') {return $text1;}
    if ($http_plan == 'C' || $http_plan=='') {return $text2;}
  }else return $text2;  
} 

/** The getter for global script parameters
 * @param string $key parameter identifier
 * @param string $default default value when no data - default is ''
 * @return mixed global $DATA[$key] ( = result of $_GET or $_POST request )
 */ 

function getpar($key,$default=''){
  //global $DATA;
  //if (isset($DATA[$key])) return $DATA[$key];  
  if (isset(M5::$ent['DATA'][$key])) return M5::$ent['DATA'][$key];

  return $default;
}

/** The setter for global script parameters
 * @param $key parameter identifier (POST or GET method )
 * @param $value
 */ 

function setpar($key,$value){
  //global $DATA;
  //$DATA[$key]=$value;  
  M5::$ent['DATA'][$key]=$value;
  return 0;
}

/** The getter for all global script parameters
 * @param none
 * @return all GET and PUT parameters on the page.
 */
  
function getpars(){
  global $DATA;
  return $DATA;
}

/** This function returns true value when the input string represents a formally valid e-mail address 
 * @param string $email - the address to be formally validated
 * @return boolean */
 
function is_email($email){
  if (preg_match("/^[\w-\.]+@([\w-]+\\.)+[a-zA-Z]{2,4}$/", $email)){
     return true;
  }else{
     return false;
  }
}

/** This function shortcuts a HTML link/ reference tag. 
 * @param string $link - relative or absolute URL 
 * @param string $text - label text over the link, default '.' 
 * @param string $text - text, default '' 
 * @return string HTML  */
 
function ahref($link='?', $text='.', $params=''){
  return tg('a','href="'.$link.'"'.($params==''?'':' ').$params,$text);
}

/** The Function returns a HTML span with context-sensitive help information
 * @param string $text - infromation text 
 * @return HTML string */
 
function helptext($text){
  return tg('span','class="helptext"',tg('img','src="img/info.gif"').ta('span'.$text));
}

/** The Function converts an Array created by database methods to the HTML table format
 * @param string $caption - table caption text
 * @param array $head - table of head strings, if not empty, only these columns will in the table in given order
 * @param array $content - Array of rows with table data.
 * @param string no data text - optional
 * @param string $class - css class of the table
 * @return string HTML */

function ht_table($caption,$head,$content,$nodata='',$class='class="table"'){
  $s=''; 
  if (!is_array($content)) return '';
  
  $is_head=is_array($head)&&(count($head)>0);
  $is_content=(count($content)>0);
  if (!isset($content[0]) ){
    if ($nodata=='') return '';
    $s=ta('tr',tg('td','colspan="1"',$nodata.nbsp()));
  }else{
    for ($hlav='',
        $L=$is_head?array_keys($head):
        ($is_content?array_keys($content[0]):array('0'=>nbsp())),
        $i=0;
       $i<count($L);
       $i++) 
      $hlav.=ta('th',isset($head[$L[$i]])?$head[$L[$i]]:$L[$i]);
    $n=0;          
    foreach ($content as $row){
      $rkapsa='';
      for ($i=0;$i<count($L);$i++){
        $ktisku=$row[$L[$i]];
        if (gettype($ktisku)=="object" && gettype($ktisku)!="NULL")
          $ktisku=$ktisku->load();
        if ($ktisku=='') $ktisku=nbsp(1);  
        $rkapsa.=ta('td',$ktisku);
      }
      $s.=tg('tr',$n%2?' class="sudy"':'',$rkapsa);
      $n++; 
    }
   }
   $s=tg('table',$class,
       (isset($caption)?ta('caption',$caption):'').
        ta('thead',
          ta('tr',$head))
       .ta('tbody',$s));
   return $s;
}

/** The Function returns all GET parameters as a string to recall the page 
 */
  
function getparm_string(){
  $s='';
  if (count($_GET)>0 ){
    foreach($_GET as $k=>$v){
      $s.=($s==''?'':'&amp;').$k.'='.urlencode($v);
    }
  }
  return $s;
}

/** Date conversion for HTML5 date input in the form 
 * @param string - national form of date
 * @return string - form of date for HTML5 input
*/
function konvdat($s){
  if (preg_match("/^(\d+)\.(\d+)\.(\d+)$/", $s, $m)){
    $day=$m[1];
    $month=$m[2];
    $year=$m[3]; 
    if ($year<100) $year+=2000; 
    return sprintf("%04d-%02d-%02d",$year,$month,$day);
  }
  return $s;  
}

/**  autocomplete_format - 
 *  @param $a Array input Array with elements ['V'=>somekey, 'T'=>somevisible text to key ] 
 *  @return - autocomplete format, JSON encoded
*/
function autocompleteFormat($a=[]){
  $r=[];
  for ($i=0;$i<count($a);$i++){
    $r[$i]=['value'=>$a[$i]['V'],'text'=>$a[$i]['T']];
  }
  return json_encode($r,JSON_UNESCAPED_UNICODE);
}

/**  get_resp - return a JSON response message to the client
 *   @param string $response - an JSON encoded data
 *   @param bool $zip - true when packed
 *   @param bool $text - true when header content type is text/plain, otherwise 
 *              application/json
 */ 
function getResp($response,$zip=true,$text=false){
    header('Cache-Control: private, must-revalidate, max-age=0');
    if ($text){
      header('Content-Type: text/plain;charset=UTF-8'); /* jediny dovoleny format pro CORS*/
    }else{
      header('Content-Type: application/json;charset=UTF-8');
    }  
    if ($zip){
      header('Content-Encoding: gzip');
      echo gzencode($response);
    }else{  
      echo $response;
    }  
}

/** page link with post request method 
 * 
 * @param string $link - target page link (f.e '?')
 * @param string $label - visible label on link
 * @param array $params - an array of posted parameters
 * @param string $addpar - additional code in visible 'button'/anchor tag  
 * @return string HTML code with POST link
*/

function postLink($link,$label,$params,$addpar=''){
  $r='';
  $p='';
  foreach ($params as $k=>$v){
     $p.=tg('input','type="hidden" name="'.$k.'" value="'.$v.'"','noslash');
  };


  $r.=tg('form','method="post" action="'.$link.'" style="display: inline;"',
        $p.
        tg('button','type="submit" name="submit_param" value="submit_value" '.$addpar,$label));

  return $r;
}

/** sanitizer for non-existing variables call
 *  @param $par - variable
 *  @param $default - when does not exist, return this.
 *   It can be another type, f.e. array: df($hash,$par,['KEY'=>'nothing'])['KEY']
 *  @return value if exists or default
 */
function df($hash,$par,$default=''){
  return isset($hash[$par])?$hash[$par]:$default;
}

/** detects mobile device 
 *  @return bool true when mobile
*/
function isMobDev() {
  return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo
|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i"
, $_SERVER["HTTP_USER_AGENT"]);
}

/** language selector according to the sesson la parameter
 * @param string $r1 text in default language
 * @param string $r2 text in the second language
 * @return string  - appropriate string 
*/
function la($r1,$r2=''){
  if (isset($_SESSION['la'])){
    return $_SESSION['la']=='cz'?$r1:$r2;
  }
  return $r1; 
}

?>