<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Test form and submit');
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
   htpr_all();                        /* Zapis bufferu na standarni vystup */
 }
 
 static function form(){
   htpr(bt_container(['col-12'],[[tg('form','method="post" action="?"',
     textfield("Type the text:",'TXTFLD',20,20,getpar('TXTFLD')).nbsp(5).
     submit('OK','Ok'))]]));
 }

 static function result(){
   htpr(getpar('TXTFLD')?
    bt_alert('Result is '.getpar('TXTFLD')):bt_alert('Result is empty','alert-danger'));   
 }

}

Myform::skeleton(); /* volani skriptu */

?>