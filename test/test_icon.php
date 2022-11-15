<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 14.09.2022  15.11.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test icon, test mbt ');
M5::skeleton('../');

htpr(ta('h1','Icons'),
  ikona_sekvence(
    'Bootstrap',
    ['chevron-down','chevron-left','chevron-right','chevron-up','arrow-left','arrow-right',
     'caret-down','caret-up','check','check-circle','geo-alt','menu-app',
     'power','plusminus','floppy-add','','aaaa'
    ]),
  br(2),  
  ikona_sekvence(
    'Moon',
    ['floppy-disc','left','right','home','file-pdf','file-word','file-excel',
     'file-text','pencil','cross','plus','search'
    ]));

htpr_all();

function ikona($class,$name){
  return tg('span',
            $class.' title="'.$name.'"',
            bt_icon($name));
}

function ikona_sekvence($header,$set){
  $cls=['class="btn btn-secondary mt-2"','class="btn btn-primary mt-2"','class="btn btn-success mt-2"',''];
  $r=ta('h2',$header);
  foreach($cls as $class){ 
    foreach($set as $iconname) $r.=ikona($class,$iconname); 
    $r.=br(2);
  }
  return $r;  
}    

?>