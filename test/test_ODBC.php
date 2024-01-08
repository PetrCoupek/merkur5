<?php
/** Merkur 5 test ODBC functionality
 * @author Petr Coupek
 * @date 08.01.2024
 */

include_once '../lib/mlib.php';
include_once 'ini.php';
//include_once '../lib/vistab.php';

M5::set('header','Test ODBC - Vistab');
M5::set('debug',true);
M5::skeleton('../');

$db = new OpenDB_ODBC(CONN_ODBC_TEST);
//deb($db->Pragma("table_info('REPORT')") );
$tt= new VisTab(
  ['table'=>'REPORT'],$db); 

$tt->route("&vyhl=1");
/**/

$db->Close();   
M5::htpr_all();

?>