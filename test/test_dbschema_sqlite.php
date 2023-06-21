<?php
/** Merkur 5 application test : test browsing all tables in Oracle schema (user) with data filtering
 * @author Petr Coupek
 * @date 16.11.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
define('DBFILE',"d:/Data/QGIS/vrty_k2.sqlite");

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
     self::$db->Close();
  }

  static function view_table(){
    $tt= new VisTab(['table'=>getpar('table')],self::$db); 
    $tt->route("&vyhl=1&table=".getpar('table'));
  }

  static function home(){ 
     $a=self::$db->Pragma("catalog"); 
     //deb($a);

     //$a=self::$db->SqlFetchArray("select * from sqlite_master order by name asc");
     //deb($a);

     for($i=0;$i<count($a);$i++) 
       $a[$i]['name']=ahref('?table='.$a[$i]['name'],$a[$i]['name']); 

     htpr(ht_table('List of entities in the schema',[],$a));
  }

}
App::set('header','Browsing the file '.DBFILE);
App::set('debug',true);
App::skeleton('../');
App::done();
?>