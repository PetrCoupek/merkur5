<?php
/** Merkur 5 complex application template
 * 
 * @author Petr Coupek
 * @date 06.11.2020 
 */
session_start();
include_once '../../lib/mlib.php';    /* implicitni include ini.php  */
include_once '../../lib/mbt.php'; /* Bootstrap components */


//class OpenDB extends OpenDB_SQLite {} /* default database wrapper is SQlite DB */

class App extends M5{
  static $db;
  public static $dbconnect;
  public static $cms; 
  
  static function init(){
    self::$dbconnect=$GLOBALS['dbconnect_cms'];
  } 

  static function skeleton($path=''){
    global $navbar;
    //$verse='1.0';
    $default_item=1;      /* vychozi polozka nacitana v CMS */
    
    self::route();

    //self::set('htptemp',$GLOBALS['htptemp']);
    //parent::skeleton($path); /* nastavi htptemp a provede m5::route coz zpusobi getparm */
  
    //getparm();
    
    self::$db=new OpenDB_SQLite(self::$dbconnect);
    $item=getpar('item')?getpar('item'):$default_item;
    
    /* Application object creation. In its constructor the user login is detected 
     * eventually (when the POST from the login form is detected) is called the check_login() method, 
     * which ceck the user acces to the application.
     * After successful login there is established a PHP $_SESSION['uzivatel'] 
     */
    self::$cms= new Cm("M5",self::$db,true,false);
    
    /* application logoof */
    if (getpar('logout')=='1'){
      self::$cms->log_mess('Odhlášen');
      self::$cms->process_logout();
      self::set('htptemp',str_replace('#SIDEBAR#','', self::get('htptemp')));
      self::set('htptemp',str_replace('#UPPERBAR#','',self::get('htptemp')));
      self::set('htptemp',str_replace('#HEADERADD#', '',self::get('htptemp'))); /* pokud nejsme na mapove strance, vypust */
      htpr_all();
      return 0;
    }      
 
    /* class Cm / internal content management system */    
    if (isset($_SESSION['uzivatel']) && $_SESSION['uzivatel']!=''){
      /* the structure cms->tree contains the appliaction menu */
      self::$cms->generateMenuTree(true) ;
      $rootnode=self::$cms->rootNode($item);
      /* end of edit mode when it was set previously */
      if (getpar('ed')!='') $_SESSION['editace']=getpar('ed');
      /* left menu */
      self::set('htptemp',str_replace('#SIDEBAR#',
                     self::$cms->sidebar(self::$cms->getTree(),$item,$rootnode,self::$cms,''),
                     self::get('htptemp')));
      /* upper breadcrump */               
      $navbar=str_replace('#BREADCRUMB#', self::$cms->breadCrumb($item), $navbar); 
      $navbar=str_replace('#EDIT_LINK#', self::$cms->editLink($item), $navbar);
      
      self::$cms->folder($item); /* folder calls the application parts */     
    }else{
      /* login form - bootstrap center  */
      htpr(tg('div','class="container"',
       tg('div','class="row h-50 d-flex justify-content-center"',
        tg('div','class="col-sm-3 h-100 d-table"',
        tg('form','method="post" class="card card-body d-table-cell align-middle"',
        
         ta('table',        
          ta('tr',ta('td','Jméno ').ta('td',textfield('','NAME',20,20,''))).
          ta('tr',ta('td','Heslo ').ta('td',tg('input','type="password" name="PASS"',''))).
          ta('tr',ta('td',' ').ta('td',nbsp(5).submit('__LOG','Přihlášení','btn btn-primary'))))
          
         )))));
      self::set('htptemp',preg_replace('/#SIDEBAR#/','', self::get('htptemp')));
    }
    
    self::set('htptemp',str_replace('#UPPERBAR#',
      isset($_SESSION['uzivatel'])&&$_SESSION['uzivatel']!=''?$navbar:'',self::get('htptemp')));
    
    /* place for specific application settings.. there is only
      an example of centralizad dynamic template modification*/     
    self::set('htptemp',str_replace('#HEADERADD#', '',self::get('htptemp'))); 
       
    htpr_all();
    self::$db->Close();
  }      
} 

App::init();
App::set('header','CMS test');
App::set('debug',true);
App::set('htptemp',$GLOBALS['htptemp']);
App::skeleton('../../'); /* sigleton template, skeleton() method is called */

?>