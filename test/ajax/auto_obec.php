<?php
/** Merkur 5 JSON response to autocomplete field
 * Katastralni uzemi
 */

include_once '../../lib/mlib.php';
                        
getparm();

$db = new OpenDB_SQLite('file=../../data/m5.sqlite3,mode=0');
if (getpar('q'))
  $r=autocompleteFormat(
    $db->SqlFetchArray(
      "select lau2_kod as V, lau2_vyznam||' ['||lau2_kod||']' as T ".
      "from kod_all_obec ".
      "where lau2_vyznam like :vyz||'%' ".
      "order by lau2_vyznam asc ",
      [':vyz'=>getpar('q')],
      15));
if (getpar('id')){
   $r=$db->SqlFetch(
     "select lau2_vyznam||' ['||lau2_kod||']' as T ".
     "from kod_all_obec ".
     "where lau2_kod =:id ",
      [':id'=>getpar('id')]  );
  $r=json_encode(['text'=>$r],JSON_UNESCAPED_UNICODE);

}

$db->Close();
getResp($r);

?>