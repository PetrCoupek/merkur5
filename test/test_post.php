<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Test extends M5{

 static function skeleton($path=''){
   self::set('debug', 1);               /* zapnuti ladici rezim */
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Link send as POST request');   
   self::done();                        /* Zapis bufferu na standarni vystup */
 }

 static function route(){
   getparm(); 
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
 }
 
 static function form(){
   htpr(ahref_post('?OK=1&TXTFLD=Ahoj', 'Send POST request'),
     ahref_post('https://appdev.geology.cz/petr/archiv2/?_ofs=3&tt_=za&_o=&_flt=ID_SKUPINA_TYPU_MAPY~=~16~ROK~=~1990&_det=1','test')
  );
    
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
    default:  
     htpr($vysledek);
     deb(getpars());

     break;
  }
 }

}

TEst::skeleton(); /* volani skriptu */


/** page link with post request method
 *
 * @param string $link URL to which the form will be submitted
 * @param string $text Text to be displayed on the button
 * @param string $add .. CSS class for the button	
 * @return string HTML code with POST link
 */


function ahref_post($link, $text, $add='class="btn btn-primary"')
{
  $url_parts = parse_url($link);
  $action = isset($url_parts['path']) ? $url_parts['path'] : '';
  $query = isset($url_parts['query']) ? $url_parts['query'] : '';
  parse_str($query, $params);

  $hidden_inputs = '';
  foreach ($params as $key => $value) {
    $hidden_inputs .= tg('input', 'type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '"', 'noslash');
  }

  return tg('form', 'method="post" action="' . $action . '" style="display: inline;"',
    $hidden_inputs .
    tg('button', 'type="submit" '.$add, $text)
  );
}

?>