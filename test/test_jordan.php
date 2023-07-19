<?php
/** Merkur 5 service test: return whether polygon contains the given point.
 * @author Petr Coupek
 * @date 16.11.2022
 */

define('DBFILE',"d:/Data/sqlite/prostorova.sqlite");
error_reporting(E_ALL);
include_once '../lib/mlib.php';
include_once '../lib/jordan.php';
getparm();
$db=new OpenDB_SQLite("file=".DBFILE.",mode=1");
$x=getpar('x');$y=getpar('y');
$r='';
if ($x && $y){
  $eps=5000;
  $a=$db->SqlFetchArray(
     "select distinct obj ".
     "from M25 where x>:x-:eps and x<:x+:eps and y>:y-:eps and y<:y+:eps",
       [':x'=>$x,
        ':y'=>$y,
        ':eps'=>$eps]);  
  for($i=0;$i<count($a);$i++){
    $tp=get_polygon($a[$i]['obj'],$db);
    //print_r($tp['geom']);
    if (is_in_polygon([$x,$y],$tp['geom'])){
      $r.= 'je v '.$tp['KOD'].';';
      break;
    }else{
      $r.= 'neni '.$tp['KOD'].';';
    }

  }

  $r=json_encode(['data'=>$r],JSON_UNESCAPED_UNICODE);
}else{
  $r=json_encode([''],JSON_UNESCAPED_UNICODE);
} 

$db->Close();
getResp($r,false); 


function get_polygon($obj,$db){
  $a=$db->SqlFetchArray(
     "select * ".
     "from M25 ".
     "where obj=:obj ".
     "order by ind asc",
     [':obj'=>$obj]);
  $n=count($a);   
  for($i=0,$b=[];$i<$n;$i++) 
    array_push($b,[$a[$i]['x'],$a[$i]['y']]);
  /* zapomenuti posledniho vrcholu */
  if ($b[0][0]==$b[$n-1][0] && $b[0][1]==$b[$n-1][1]){
    array_pop($b);

  }  
  return ['obj'=>$obj,'KOD'=>$a[0]['KOD'],'geom'=>$b];
}

?>