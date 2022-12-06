<?php
/** Merkur 5 test date application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class App extends M5{

 static function route($path=''){
   getparm();
   self::form();
   if (getpar('OK')) self::calculate();
 }

 static function form(){
   htpr(br(),
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
     bt_container(['col-4','col-4','col-4'],
      [ ['Počáteční datum',bt_datefield('','DATEFROM',getpar('DATEFROM')),'[DD.MM.YYYY]'],
        ['Koncové datum',bt_datefield('','DATETO',getpar('DATETO')),'[DD.MM.YYYY]'],
        [nbsp(),nbsp(),submit('OK','Ok')]
      ])));
 }
 
 static function calculate(){  
   if (preg_match('/^(\d+)\.(\d+).(\d+)$/',getpar('DATEFROM'),$m)) {
     $date1 = new DateTime($m[3].'-'.$m[2].'-'.$m[1]); 
     if (preg_match('/^(\d+)\.(\d+).(\d+)$/',getpar('DATETO'),$m)) {
       $date2 = new DateTime($m[3].'-'.$m[2].'-'.$m[1]); 
       $interval = $date1->diff($date2);
       htpr(tg('div','class="text-center"',
        tg('span','class="display-1"',$interval->days)));
     }else{
      htpr(bt_alert('Datum konce není správně','alert-warning'));  
     }
   }else{
     htpr(bt_alert('Datum počátku není správně','alert-warning'));
   }
 }   
} /* end of class definition */

App::set('header','Rozdíl mezi daty');
//App::set('debug',true);  
App::skeleton('../'); 
App::done();

?>