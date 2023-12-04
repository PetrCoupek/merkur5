<?php
/** Nearest documentation
 * @author Petr Coupek
 * @date 13.10.2023 04.12.2023
 */

include_once '../lib/mlib.php';
include_once 'ini.php';


M5::set('debug',true);
M5::skeleton('../');
M5::set('htptemp','#BODY#');

if (getpar('x') && getpar('y') )
  htpr(get_nearest(getpar('x'),getpar('y')));
else
  htpr('x and y are required.');
                 
M5::done();


/** vraci HTML div s odkazy na nejblizsi body a vydalenosti k nim 
 */ 
function get_nearest($x,$y){
  $db=new OpenDB_Oracle(CONN_APP_DKB_02);
  $url='https://appdev.geology.cz/dkb/';
  $x=floor($x);
  $y=floor($y);
  $ra=5000;
  $prik="select cbod, typ_bod, dat_geom_bod.obj, sde.st_distance(dat_geom_bod.shape,sde.st_point($x,$y,5514)) dd ".
        "from dat_dkb.dat_geom_bod, dat_dkb.dat_lokalizace ".
        "where sde.st_envintersects(dat_geom_bod.shape,$x-$ra,$y-$ra,$x+$ra,$y+$ra)=1 ".
        "and sde.st_distance(dat_geom_bod.shape,sde.st_point($x,$y,5514))<$ra ".
        "and dat_geom_bod.obj=dat_lokalizace.obj ".
        "order by dd";
  $a=$db->SqlFetchArray($prik,[],20);
  $db->Close();
  $r='';
  for ($i=0;$i<count($a);$i++){
    $r.=ahref($url.'?item=4&obj='.$a[$i]['OBJ'],$a[$i]['CBOD']).' '.
        $a[$i]['TYP_BOD'].' '.
        sprintf("%5.0f m",$a[$i]['DD']).br();
  }
  return $r;
  //return 'ahoj '.$x.' '.$y;
}

?>