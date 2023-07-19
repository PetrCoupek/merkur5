<?php
/** Merkur 5 service test: select object froma large database based on point.
 * @author Petr Coupek
 * @date 16.11.2022
 */

define('DBFILE',"d:/Data/sqlite/gdo_data.sqlite");
error_reporting(E_ALL);
include_once '../lib/mlib.php';
getparm();
$db=new OpenDB_SQLite("file=".DBFILE.",mode=1");
$x=getpar('x');$y=getpar('y');
if ($x && $y){
  if (!getpar('eps')) setpar('eps',100);
  $r=$db->SqlFetchArray(
     "select klic_gdo, x, y, z, zlm200||'-'||zlm100||zlm50||zlm25 as M25, ".
     "zlm200||'-'||zlm100||zlm50||'-'||zlm10 as M10 ".
     "from gdo_tvar_o ".
     "where x>:x-:eps and x<:x+:eps and y>:y-:eps and y<:y+:eps",
      [':x'=>$x,
       ':y'=>$y,
       ':eps'=>getpar('eps')]);
  /* removing the points which are no in the radius eps */
  for($i=0;$i<count($r);$i++)
    if ( pow($r[$i]['X']-$x,2)+pow($r[$i]['Y']-$y,2) > pow(getpar('eps'),2) )
      array_splice($r,$i,1);
  $r=json_encode(['data'=>$r],JSON_UNESCAPED_UNICODE);
}else{
  $r=json_encode([''],JSON_UNESCAPED_UNICODE);
} 

$db->Close();
getResp($r,false); 
?>