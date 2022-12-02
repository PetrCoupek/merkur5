<?php
/** Merkur 5 test lister 
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test Lister');
M5::set('debug',true);
M5::skeleton('../');

$db = new OpenDB_Oracle('dsn=sdedb02;uid=app_dkb;pwd=jsdn*6343Jkjsedn*324');
$where="id<18";
$pole=$db->SqlFetchArray(
    "select id, nazev, ochrana_stup_kod, ochrana_kat_kod, ochrana_dop ".
    "from dat_lok1.lok where $where",[],15,getpar('_ofs',1));
$total=$db->SqlFetch("select count(*) as pocet from dat_lok1.lok where $where",[]);
htpr(
   bt_lister(
      'Nalezené lokality',
      ['ID'=>'Id lok.',
        'NAZEV'=>'Název',
        'OCHRANA_STUP_KOD'=>'Kód ochrany',
        'OCHRANA_KAT_KOD'=>'Kategorie ochrany',
        'OCHRANA_DOP'=>'Doporučení ochrany'],
       $pole,
       'Nejsou záznamy.','',
       bt_pagination(getpar('_ofs',1),$total,15)));
   
htpr_all();

?>