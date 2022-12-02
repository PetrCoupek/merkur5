<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Testform extends M5{

 static function route(){
    getparm();                         /* vyzvednuti parametru */
    if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
    self::form();                      /* formular se tiskne vzdy */
    
    /* knihovna pro datumuvou polozku */
    self::set('htptemp',
      str_replace('#___#',
       tg('script','src="../vendor/datepicker/js/bootstrap-datepicker.js"',' ').
       tg('script','src="../vendor/datepicker/js/locales/bootstrap-datepicker.cs.js"',' ').
       tg('link','rel="stylesheet" media="screen,print" href="../vendor/datepicker/css/bootstrap-datepicker3.css" type="text/css" ','noslash'),
      self::get('htptemp')));
          
    htpr_all();                        /* Zapis bufferu na standarni vystup */
 }

 static function form(){
   htpr(tg('form','method="post" action="?" class="bg-light p-2 border" ',
    ta('h4','Hlavička formuláře').
    bt_container(['col-3','col-7','col-2'],
      [['Zadejte text:',
        textfield("",'TXTFLD',20,20,getpar('TXTFLD')),
        nbsp()],
       ['Tvar odpovědi:',
        combo("",'RESPFO',['1'=>'Výstraha nahoře na stránce',
                           '2'=>'Dialog přes obrazovku',
                           '3'=>'nic'],getpar('RESPFO')?getpar('RESPFO'):'1'),
        nbsp()],
        ['České datum',bt_datefield('','DATEF',getpar('DATEF')),'[nic]'],
       [nbsp(),nbsp(),submit('OK','Ok')]
      ])));
 }

 static function result(){
   if (getpar('RESPFO')==1)
    htpr(tg('div','class="p-2"',
     getpar('TXTFLD')?bt_alert('Výsledek je '.getpar('TXTFLD')):
                      bt_alert('Výsledek je prázdný','alert-danger')));
   if (getpar('RESPFO')==2)
    htpr(tg('div','class="p-2"',
     getpar('TXTFLD')?bt_dialog('Oznámení','Výsledek je '.getpar('TXTFLD')):
                      bt_dialog('Varování','Výsledek je prázdný')));                      
 }

}

Testform::set('header','Test českého formuláře a jeho potvrzení');
Testform::skeleton('../'); /* volani skriptu */

?>