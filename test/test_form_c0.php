<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Test combo dual');
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
   htpr_all();                        /* Zapis bufferu na standarni vystup */
 }
 
 static function form(){
   htpr(bt_container(['col-12'],[[tg('form','method="post" action="?"',
    combo_dual('Lab','NAZEV',
     ['one'=>'first one',
      'two'=>'first two',
      'three'=>'first-three',
      'four'=>'first-four'
     ],
     ['one'=>["one-one", "one-two", "one-three" ],
      'two'=>["two-one", "two-two", "two-three" ],
      'three'=>["three-one", "three-two", "three-three" ],
      'four'=>["four-one", "four-two", "four-three" ]
    ],
     '','').
     
     nbsp(5).
     submit('OK','Ok'))]]));
 }

 static function result(){
   htpr(getpar('TXTFLD')?
    bt_alert('Result is '.getpar('TXTFLD')):bt_alert('Result is empty','alert-danger'));   
 }

}

Myform::skeleton(); /* volani skriptu */


function combo_dual($label,$name,$list_master,$list_slave,$def='',$js=''){
  $s=combo($label,$name,$list_master,$def,$js.' id="'.$name.'_master"');
  $s.=tg('select','id="'.$name.'_slave" disabled="disabled"',' ');
  $s.=ta('script',
   ' var opt_'.$name.'='.json_encode($list_slave,JSON_UNESCAPED_UNICODE).';'."\n".
   '$(document).ready(function(){
    $("#'.$name.'_master").change(function(){
        var selectedClass = $(this).find("option:selected").attr("value");    
        var options = opt_'.$name.'[selectedClass];
        var newoptions = "";
        for(var i = 0; i < options.length; i++){
            newoptions+="<option>"+ options[i] +"</option>";                            
        }
        $("#'.$name.'_slave").html(newoptions).removeAttr("disabled");
    });        
   });'
  );
  return $s;
}

?>