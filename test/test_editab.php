<?php
/** Merkur 5 test EdiTab
 * @author Petr Coupek
 * @date 09.01.2023
 */

include_once '../lib/mlib.php';
//include_once '../lib/mbt.php';
//  include_once '../lib/vistab.php';
include_once 'ini.php';

M5::set('header','Test Editab');
M5::set('debug',true);
M5::skeleton('../');

$db= new OpenDB_Oracle('dsn=sdedb02;uid=app_dkb;pwd='.PASS_DAT_DKB);
$tt= new EdiTab(['table'=>'DKB_SKUP'],$db); 
$tt->route("&item=1");
$db->Close();   
M5::htpr_all();

?>