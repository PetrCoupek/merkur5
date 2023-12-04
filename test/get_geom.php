<?php
/** Merkur 5 JSON response to get a gemetry from ArcSDE geodatabase
 * Litostratigrafie
 * 16.01.2023 13.03.2023
 * 
 */
error_reporting(E_ALL);
include_once '../lib/mlib.php';

getparm(); /* processing id or q parameter */

$db = new OpenDB_Oracle(CONN_APP_DKB_02);

if (getpar('kod')){
   $geom=$db->SqlFetch(
     "select sde.st_astext(shape) as at ".
     "from dat_dkb.dat_obl ".
     "where kod=:kod ",
     [':kod'=>getpar('kod')]  
     );
  
  //print_r( json_encode(parse_polygon($geom)));      
  $r=json_encode(['geometry'=>parse_polygon($geom)],JSON_UNESCAPED_UNICODE);

}

$db->Close();
if (isset($r))getResp($r);


function parse_polygon($geom){
  $res='';
  if (preg_match("/POLYGON\s+\(\( (.*)\)\)/",$geom,$m )){      
    $vertex=explode(', ',$m[1]);
    $res .= '[';
    $first = true; 
    for($i=0;$i<count($vertex);$i++){
      $a = explode(' ',$vertex[$i]);
      if($first){
        $first = false;
      }else{
        $res .= ',';
      }  
      $res .= '['.$a[0].','.$a[1].']';
    }
    $res .= ']';
  }
  return json_decode($res);
}

?>