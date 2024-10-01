<?php
/** Merkur 5 test EdiTab
 * @author Petr Coupek
 * @date 09.01.2023
 */

include_once '../lib/mlib.php';

M5::set('header','Test Editab');
M5::set('debug',true);
M5::skeleton('../');

$db= new OpenDB_SQLite(M5_DATA_WRITE);
$tt= new EdiTab(['table'=>'KOD_REG'],$db); 
$vistab_n=6;
$tt->route('');
//deb($tt);  // toto umozni nahled do atributu prohlizeciho objektu
$db->Close();   
M5::htpr_all();

?>