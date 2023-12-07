<?php
/** Merkur 5 application test : test browsing all tables in SQLIte database with data filtering
 * @author Petr Coupek
 * @date 04.12.2023
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
define('DBFILE',"../data/m5.sqlite3");

class App extends M5{   
  static $dbconnect="file=".DBFILE.",mode=1";
  static $db;

  static function route(){
     getparm();
     self::$db=new OpenDB_SQLite(self::$dbconnect);
     if (getpar('table')){
       self::view_table(); 
     }else{
       self::home();
     }
     htpr_all();
     self::$db->Close();
  }

  /*static function view_table_raw(){
     $a=self::$db->SqlFetchArray("select * from ".getpar('table')); 
     htpr(ta('h3',getpar('table')),
     bt_lister('',
     [],
     $a));
  }*/

  static function view_table(){
     $tt= new VisTab(
          ['table'=>getpar('table')],
          /* AKA ['sprikaz'=>"select * from ".getpar('table'), 
           'cprikaz'=>'select count(*) as pocet from '.getpar('table'),
           'pragma'=>self::$db->Pragma("table_info('".getpar('table')."')"),
           'dprikaz'=>"select * from ".getpar('table')],
          */
           self::$db); 
        
        $tt->route("&vyhl=1&table=".getpar('table'));
  }

  static function home(){ 
     $a=self::$db->Pragma("catalog"); 
     for($i=0;$i<count($a);$i++) 
       $a[$i]['name']=ahref('?table='.$a[$i]['name'],$a[$i]['name']); 

     htpr(ht_table('List of entities in the schema',[],$a));
  }
}
App::set('header','Browsing the database '.DBFILE.' (bigger version)');
App::set('debug',true);
App::skeleton('../');


?>