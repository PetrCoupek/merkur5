<?php
/** Merkur 5 test popover 
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('debug',true);

class Myform extends M5{
 

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Test popover .');
   htpr(bt_tooltip('Datum dokumentace v terénu','Datum '),
        bt_tooltip('Test informační bubliny.','Info'.bt_icon('info')));   
   htpr_all();                        
 }
 
 

}

Myform::skeleton(); /* volani skriptu */




?>