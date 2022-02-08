<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
M5::set('header','Test no. 1');
M5::skeleton('../');
htpr(ta('h1','Hello'),
     'This is the test');
$db=new OpenDB_SQLite('file=../data/m5.sqlite3,mode=1');
//print_r($db);die;
htpr(ht_table('Name days',array(),
   $db->SqlFetchArray("select * from jmeniny order by den, mesic ")));
$db->Close();     
htpr_all();

?>