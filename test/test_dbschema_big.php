<?php
/** Merkur 5 application test : test browsing all tables in Oracle schema (user) with data filtering
 * @author Petr Coupek
 * @date 16.11.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

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
     $a=self::$db->SqlFetchArray(
          "select table_name, num_rows,last_analyzed ".
          "from tabs order by table_name");     
     for($i=0;$i<count($a);$i++) 
       $a[$i]['TABLE_NAME']=ahref('?table='.$a[$i]['TABLE_NAME'],$a[$i]['TABLE_NAME']); 
     htpr(ta('h2',SCHEMA),
     ht_table('List of entities in the schema',[],$a,'no ',
      'class="table table-bordered table-hover table-sm"')
     );
  }
}
App::set('header','Browsing the schema '.SCHEMA.' (bigger version)');
App::set('debug',true);
App::skeleton('../');


?>