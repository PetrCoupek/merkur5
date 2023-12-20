<?php
/** Merkur 5 test Vistab
 * @author Petr Coupek
 * @date 08.09.2022
 */

include_once '../lib/mlib.php';
include_once 'ini.php';
//include_once '../lib/vistab.php';

M5::set('header','Test Vistab');
M5::set('debug',true);
M5::skeleton('../');

$db = new OpenDB_Oracle(CONN_APP_DKB_02);
//deb($db->Pragma("table_info('dat_lok1.lok')") );
$tt= new VisTab(
  ['header'=>'Hlavička',
   'sprikaz'=>"select id, nazev, ochrana_stup_kod, ochrana_kat_kod, ochrana_dop from dat_lok1.lok", 
   'cprikaz'=>'select count(*) as pocet from dat_lok1.lok',
   'pragma'=>[['name'=> 'ID',  'comment' => 'Identifikátor záznamu'],
              ['name'=> 'NAZEV','comment' => 'Název lokality'],
              ['name'=> 'OCHRANA_STUP_KOD','comment' => 'Ochrana stup kód'],
              ['name'=> 'OCHRANA_KAT_KOD','comment' => 'Ochrana kat kód'],
              ['name'=> 'OCHRANA_DOP','comment' => 'Doporučení ochrany']],
   'dprikaz'=>'select * from dat_lok1.lok '],$db); 

$tt->route("&vyhl=1");
$db->Close();   
M5::htpr_all();

?>