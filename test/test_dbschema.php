<?php
/** Merkur 5 application test : test browsing all tables in Oracle schema (user) with data filtering
 * @author Petr Coupek
 * @date 16.11.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
define('SCHEMA',"dat_sur");

class App extends M5{
  static $dbconnect=CONN_APP_DKB_02;
  static $db;

  static function route(){
     getparm();
     self::$db=new OpenDB_Oracle(self::$dbconnect);
     if (getpar('table')){
       self::view_table(); 
     }else{
       self::home();
     }    
     htpr_all();
     self::$db->Close();
  }

  static function view_table(){
    $tt= new VisTab(['table'=>getpar('table')],self::$db); 
    $tt->route("&vyhl=1&table=".getpar('table'));
  }

  static function home(){ 
     $a=self::$db->SqlFetchArray(
          "select table_name, num_rows,last_analyzed ".
          "from tabs order by table_name");     
     for($i=0;$i<count($a);$i++) 
       $a[$i]['TABLE_NAME']=ahref('?table='.$a[$i]['TABLE_NAME'],$a[$i]['TABLE_NAME']); 
     htpr(ht_table('List of entities in the schema',[],$a));
  }

}
App::set('header','Browsing the schema '.SCHEMA);
App::set('debug',true);
App::skeleton('../');

?>