<?php
/** Merkur 5 test ODBC functionality
 * @author Petr Coupek
 * @date 06.03.2024
 *  definuje vstupní tabulku a obsahuje ukázku, jak implementovat kontrolu předaného vstupu
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
include_once '../lib/input_table.php';

M5::set('header','Test Input table');
M5::set('debug',true);
M5::skeleton('../');

$ct= new Clipboard_table(
  [['head'=>'Sloupec A', 'name'=>'SL','size'=>12,'maxlength'=>12,'title'=>'Toto je sloupec A'],
   ['head'=>'Sloupec B', 'name'=>'B', 'size'=>6, 'maxlength'=>10],
   ['head'=>'Sloupec C', 'name'=>'C', 'size'=>8, 'maxlength'=>8],
   ['head'=>'Sloupec D ','name'=>'D', 'size'=>30,'maxlength'=>30,'title'=>'Toto je sloupec D'],
   ['head'=>'Sloupec Ex','name'=>'E', 'size'=>1, 'maxlength'=>1, 'title'=>'Toto je poslední sloupec']]);

$chyba=proved_kontrolu($ct);
if ($chyba) {
  htpr(bt_alert('Ve vstupních datech nalezeny chyby','alert-warning'));
}else{
  if (getpar($ct->idform.'_POST')) htpr(bt_alert('Vstupní data OK'));
}

htpr(tg('form','method="post" action="?" id="frmobal" ',$ct->input_table().submit('SAV_','OK')));
   
M5::htpr_all();

/* uživatelská kontrola tabulky - chyby zapíše do comments
 * zde se kontrolují všechna pole na vyplnění číslem
 */

function proved_kontrolu($ct){
  $chyba=false;
  $pov=$ct->count_rows();
  if ($pov>0){
    for ($i=0;$i<$pov;$i++){
      for ($j=0;$j<count($ct->h);$j++){
        $testcell=getpar($ct->h[$j]['name'].$i);
        if ($testcell=='') {
          $ct->comments[$i][$ct->h[$j]['name']]="je prázdný ";
          $chyba=true;
        }elseif (!is_numeric($testcell)) {
          $ct->comments[$i][$ct->h[$j]['name']]="není číslo ";
          $chyba=true;
        }  
      }
    }
  }
  return $chyba;
}

?>