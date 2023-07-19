<?php

/** import geometrie z ArcSDE tabulky do SQLite tabulky */

include "ini.php"; /* connect info file */
include "../lib/mlib.php";
define('DBFILE',"d:/Data/sqlite/prostorova.sqlite");

$dbo= new OpenDB_Oracle(CONN_APP_DKB_01);
$dbl= new OpenDB_SQLite("file=".DBFILE.",mode=1");
$tab='M25';
$atrib="KOD";

$er=$dbl->Sql("create table if not exists $tab ( ".
          "obj integer not null, ".
          "ind integer not null, ".
          "x integer not null, ".
          "y integer not null, ".
          "$atrib text, ".
          "primary key(obj,ind) )");
if ($er===true) echo $dbl->Error,"\n";          
$er=$dbl->Sql("create index if not exists i_".$tab."_xy on $tab (x,y)"); 
if ($er===true) echo $dbl->Error,"\n";
$re=$dbl->Sql("create index if not exists i_".$tab."_".$atrib." on $tab ($atrib)");          
if ($er===true) echo $dbl->Error,"\n";

$dbo->Sql("select sde.st_astext(shape) as g, kod ".
          "from dat_dkb.dat_obl where typ='M'");
$obj=1;
$dbl->Sql('BEGIN');
while ($dbo->FetchRow()){
  $D=$dbo->DataHash();
  /* parse geometrie */
  $a=parse_geometry($D['G']);
  for ($i=1;$i<=count($a);$i++){
    $prik="insert into ".$tab." (obj,ind,x,y,".$atrib.") ".
          "values ($obj,$i,".round($a[$i-1][0]).",".round($a[$i-1][1]).",'".$D[$atrib]."' )";
    $er=$dbl->Sql($prik);
    if ($er===true) echo ":".$dbl->Error,"\n";         
  }
  $obj++;
  if ($obj%10==0) echo $obj,"\r";
}
$dbl->Sql('COMMIT');

$dbo->Close();
$dbl->Close();

/** Vraci pole vertexu z predane geometrie, vcetne jednotlivych poradi a obj
 *  @param string $geom  vstup geometrie ve tvaru WKT
 *  @return array pole vertexu
 */
function parse_geometry($geom){
  $a=[];
  if(preg_match("/^POLYGON\s+\(\( (.*)\)\)$/",$geom,$m )){
    $po=explode('),( ',$m[1]); $a1=0; $a2=0;
    for($j=0;$j<count($po);$j++){
      $vertex = explode(', ',$po[$j]);
      for($i=0;$i<count($vertex);$i++){
        $b = explode(' ', $vertex[$i]);
        array_push($a,[$b[0],$b[1]]);
      }
    }
  }      
  return $a;
}

?>