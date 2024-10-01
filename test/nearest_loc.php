<?php
/** Nearest documentation
 * @author Petr Coupek
 * @date 29.09.2024
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


/** vraci HTML div s odkazy na nejblizsi body a vzdalenosti k nim 
 */ 
function get_nearest($x,$y){
  $db=new OpenDB_SQLite(CONN_GDO_SQLITE);
  //htpr(print_r($db));
  $url='https://appdev.geology.cz/dkb/';
  $x=-floor($x);
  $y=-floor($y);
  $ra=1000*1000;
  /* lokalni SQlite v PHP nemusi mit prikompilovany mat. fce sqrt, power .. */
  $prik="select klic_gdo, x,y, (y - :x)*(y - :x)+(x - :y)*(x - :y) as ra ".
        "from gdo_tvar_o where ra <= :ra ".
        "order by ra asc";  
          
  $a=$db->SqlFetchArray($prik,[':x'=>$x,':y'=>$y,':ra'=>$ra],20);
  $db->Close();
  $r='';
  for ($i=0;$i<count($a);$i++){
    $r.=ahref($url.'?item=4&obj='.$a[$i]['KLIC_GDO'],
        $a[$i]['KLIC_GDO'].' '.
        sprintf("%5.0f m",sqrt($a[$i]['ra']))).br();
  }
  return $r;
  //return 'ahoj '.$x.' '.$y;
}

?>