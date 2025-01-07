<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Formulář a jeho potvrzení');   
   self::done();                        /* Zapis bufferu na standarni vystup */
 }

 static function route(){
   getparm();                         /* vyzvednuti parametru */
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
 }
 
 static function form(){
   htpr(
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
      bt_container(['col-4','col-8'],
      [['Zadejte text: ' , textfield('','TXTFLD',20,20,getpar('TXTFLD'))],
       ['Forma výstupu: ', combo('','RESPFO',['1'=>'Výstraha nahoře na stránce',
                                              '2'=>'Dialog přes obrazovku',
                                              '3'=>'nic'],
                                 getpar('RESPFO')?getpar('RESPFO'):'1')],
       ['', submit('OK','Ok')]
      ])));
 }

 static function result(){
   $vysledek='Výsledek je '.getpar('TXTFLD');
   $chyba='Textové pole je prázdné';
  switch (getpar('RESPFO')) {
    case '1': htpr(getpar('TXTFLD') ? bt_alert($vysledek) : bt_alert($chyba, 'alert-danger'));
     break;
    case '2': htpr(getpar('TXTFLD') ? bt_dialog('Výstup',$vysledek) : bt_dialog('Varování',$chyba));
     break;
    case '3':
     // No action needed for case '3'
     break;
  };
 }

}

Myform::skeleton(); /* volani skriptu */

?>