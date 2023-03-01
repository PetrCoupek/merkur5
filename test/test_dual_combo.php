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
     ['one'=>["one-one"=>"first one first", "one-two"=>"first one two", "one-three"=>"first one three" ],
      'two'=>["two-one"=>"second one first", "two-two"=>"second one two", "two-three"=>"second one three" ],
      'three'=>["three-one"=>"third one", "three-two"=>"third the two" ],
      'four'=>["four-one"=>"fourth the first", "four-two"=>"fourth the second", "four-three"=>"fourth the third" ]
    ],
     [getpar('NAZEV'),
      getpar('NAZEV_slave')],'').
     
     nbsp(5).
     submit('OK','Ok'))]]));
 }

 static function result(){
   htpr(getpar('NAZEV')?
    bt_alert('Result is '.getpar('NAZEV').';'.getpar('NAZEV_slave')):
    bt_alert('Result is empty','alert-danger'));   
 }

}

Myform::skeleton(); /* volani skriptu */

/** function returns two paired combos 
 * @param string $label label of the field
 * @param string $name name of the field
 * @param array $list_master an array of master's option/label combinations
 * @param array $list_slave - an array with keys of master options and values as arrays of slave option/label combination
 * @param array $def - default values of master nad slave
 * @param string $js - adsitional HMTL/Js in the tag
 */

function combo_dual($label,$name,$list_master,$list_slave,$def=['',''],$js=''){
  $s=combo($label,$name,$list_master,$def[0],$js.' id="'.$name.'_master"');
  /* if master has a value, set the slave and its value to be consistent afet form post */
  if ($def[0]!='' && $def[1]!=''){
    //$s.=tg('select','id="'.$name.'_slave" name="'.$name.'_slave" value="'.$def[1].'"',$list_slave[$def[0]][$def[1]]);
    $s.=combo('',$name.'_slave',$list_slave[$def[0]],$def[1],'id="'.$name.'_slave" ');
  }else{
    $s.=tg('select','id="'.$name.'_slave" disabled="disabled" name="'.$name.'_slave" value="'.$def[1].'"',' ');
  }  
  $s.=ta('script',
   ' var opt_'.$name.'='.json_encode($list_slave,JSON_UNESCAPED_UNICODE).';'."\n".
   '$(document).ready(function(){
    $("#'.$name.'_master").change(function(){
        var selectedClass = $(this).find("option:selected").attr("value");    
        var options = opt_'.$name.'[selectedClass];
        var newoptions = "";
        for(var key in options){
          if (options.hasOwnProperty(key)){
            var val=options[key];
            newoptions+="<option value="+key+">"+ val +"</option>";
          }                              
        }
        $("#'.$name.'_slave").html(newoptions).removeAttr("disabled");
    });        
   });'
  );
  return $s;
}

?>