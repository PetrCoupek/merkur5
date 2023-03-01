<?php
/** Merkur 5 JSON response to autocomplete field
 * Katastralni uzemi
 */

include_once '../../lib/mlib.php';
                        
getparm();

$db = new OpenDB_Oracle('dsn=sdedb02;uid=app_dkb;pwd=jsdn*6343Jkjsedn*324');
if (getpar('kod') && getpar('podkod')){
   
  $r=$db->SqlFetch(
     "select definice as T ".
     "from dat_dkb.kod_s_typ ".
     "where kod=:kod and podkod=:podkod ",
      [':kod'=>getpar('kod'),
       ':podkod'=>getpar('podkod')]
  );
  $r=json_encode(['text'=>$r],JSON_UNESCAPED_UNICODE);

}else{
  $r=json_encode(['text'=>'...'],JSON_UNESCAPED_UNICODE);
}

$db->Close();
getResp($r);

?>