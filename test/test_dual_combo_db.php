<?php
/** Merkur 5 test form response application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
include_once "ini.php";
M5::set('debug',true);

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Test výběru s-typu');
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
   htpr_all();                        /* Zapis bufferu na standarni vystup */
 }
 
 static function form(){

   $db=new OpenDB_Oracle("dsn=sdedb02;uid=APP_DKB;pwd=".PASS_DAT_DKB);
   $list_1=to_hash(
    "select distinct kod,nazev ". 
    "from dat_dkb.kod_s_typ ".
    "order by nazev asc",$db);
   $list_pom=$db->SqlFetchArray(
    "select kod,podkod,nazev,podnazev from dat_dkb.kod_s_typ order by kod,podkod asc");
   /* sestaveni obou komobo boxu */ 
   $list_2=[];   
   for ($i=0;$i<count($list_pom);$i++){
     if (!isset($list_2[$list_pom[$i]['KOD']])){
      $list_2[$list_pom[$i]['KOD']]=[];
     }
     $list_2[$list_pom[$i]['KOD']][$list_pom[$i]['PODKOD']]=$list_pom[$i]['PODNAZEV'];
   }

   htpr(bt_container(['col-12'],[[tg('form','method="post" action="?"',
    combo_dual('S typ: ','NAZEV',
      $list_1, 
      $list_2,
     [getpar('NAZEV'),
      getpar('NAZEV_slave')],'').
     
     nbsp(5).
     tg('span','id="napo" data-toggle="popover"',bt_icon('question')).
     ta('script',<<<EOT
      
  $(document).ready(function(){
      function odpoved(){
        return '.. '; 
      }
  
      let popOver= $('#napo').popover({
      html: true,
      trigger: 'hover',
      placement: 'bottom',
      content: odpoved
     });
    
    $('#napo').on('show.bs.popover', function (e) {
      $.ajax({
        url: 'ajax/auto_nap.php',
        type: 'get',
        cache: false,
        data:{kod: $('#NAZEV_master').val(),
              podkod:$('#NAZEV_slave').val()
              },
        success: function (data) {
            console.log(data['text']);
            //$('#napo').popover('hide'); 
            $('#napo').attr('data-content', data['text']);
            //$('#napo').popover('show');
            popOver.data('bs.popover').setContent();
                                      
        }
      });
    });
  });
      
EOT
      
      ).
     nbsp(5).
     submit('OK','Ok'))]]));
   $db->Close();  
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