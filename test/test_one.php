<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
M5::set('header','Test One');
M5::set('debug',true);
M5::skeleton('../');
htpr(ta('h1','Hello'),
     'This is the test');
$db=new OpenDB_SQLite('file=../data/m5.sqlite3,mode=1');
//deb($db);
htpr(
  ht_table('Name days',array('den'=>'Den','mesic'=>'Měsíc','jmena'=>'Jména'),
            $db->SqlFetchArray("select * from jmeniny order by den, mesic "),
            'Nejsou data.',
            'class="table table-striped table-bordered table-hover table-sm"'));
$db->Close();     
htpr_all();

?>