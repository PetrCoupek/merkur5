<?php
/** Merkur 5 test complex modal dialog application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Test modal');
   if (getpar('ajax')){               /* ajax větev nahrazuje samostatny skript */ 
      self::ajax();
      return;
   }
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
   htpr_all();                        /* Zapis bufferu na standarni vystup */
 }
 
 static function form(){
   htpr(br(4),
     bt_container(['col-12'],
     [
      [tg('form','method="post" action="?"',
       bt_autocomplete("Obec:",
               'OBEC',
               'ajax/auto_obec.php',
               getpar('OBEC')?getpar('OBEC'):'583758').nbsp(5).
       bt_modal_win('OBEC',
                  'id',
                  'klikni..',
                  'Okno',
                  'empModal',
                  '?ajax=1'),
       submit('OK','Ok'))
      ]
     ]));
 }

 static function result(){
   htpr(getpar('TXTFLD')?
    bt_alert('Result is '.getpar('TXTFLD')):bt_alert('Result is empty','alert-danger'));   
 }
 
 /** asynchronous part of the script */
 static function ajax(){
   self::set('htptemp','#BODY#');
     
   
   /* test input content */
   htpr("Get data: ".getpar('data').br(),
        "Name: ",getpar('name'),'<hr>');
   
   /* test output value */
   foreach (['583391','598267','554197','535290'] as $p ) 
     htpr(tg('span','class="ret-value btn btn-info mt-3"',$p),br());
     
     $url='ajax/auto_obec.php';

   htpr(tg('script','type="text/javascript"',
      "$('.ret-value').click(
    function(){
       $('#".getpar('id_window')."').modal('hide');
       console.log(this.innerHTML);
       $('#".getpar('name')."').val(this.innerHTML); \n". 
       '$.ajax({url:"'.$url.'?id="'.'+this.innerHTML'.','."\n".
           'success: function(result){ '."\n".
           "$('.basicAutoSelect".getpar('name')."').autoComplete('set', { value: this.innerHTML, text: result['text'] });"."\n".
        '}});'.
     " });"
         
     ));
     
   /* test inner link in the window - relay the data parameter - here it is an ID counter 
     js function href_#id_wihdow# is defined in the bt_modal_win call */  
   htpr(
    tg('span','class="btn btn-secondary mt-3" id="get-url'.getpar('id_window').'0"','Backward'),
     tg('script','type="text/javascript"',
      "$('#get-url".getpar('id_window')."0').click(function(){href_".getpar('id_window')."('".$_SERVER['PHP_SELF'].'?ajax=1'."','".((int)getpar('data')-1)."'); });"));
     
   
   htpr(
     tg('span','class="btn btn-secondary mt-3" id="get-url'.getpar('id_window').'1"','Forward'),
      tg('script','type="text/javascript"',
       "$('#get-url".getpar('id_window')."1').click(function(){href_".getpar('id_window')."('".$_SERVER['PHP_SELF'].'?ajax=1'."','".((int)getpar('data')+1)."'); });"));
   
    
    
  /* front-end funkcionalita navratu hodnoty */
  
  
    htpr_all();
  }

}

Myform::skeleton(); /* volani skriptu */

function bt_modal_win0($name,$id,$text='Click me',$title='Window',$id_window='empModal'){
  //$r=tg('button','type="button" class="btn btn-primary" ',$text);
  $r=tg('a','class="btn btn-secondary m-1" id="'.$id.'" ',$text);
  $script=tg('script','type="text/javascript"',
   "$(document).ready(function(){
      $('#".$id."').click(function(){ $('#$id_window').modal('show');
        /* action to fill the modal window */
        var tmp=$('#$name').val();
        //console.log(tmp);
        /* AJAX request */
        $.ajax({
               url: '?ajax=1',
               type: 'get',
               data: {data: tmp, id_window: '$id_window', name: '$name' },
               success: function(response){ 
                        // Add response in Modal body
                          $('.modal-body').html(response);                            
                        }
         });
      });
      
      $('.ret-value').click(function(){
         $('#$id_window').modal('hide');
         $('#$name').val(this.innerHTML); 
        });
              
   });
   
   
   function href(url,data){
       $.ajax({
        url: url,
        type: 'get',
        data: {data: data, id_window: '$id_window', name: '$name'},
        success: function(response){ 
               $('.modal-body').html(response);                            
               }
       });}"     
     
  );
  $modal=tg('div','class="modal fade" id="'.$id_window.'" role="dialog"',
          tg('div','class="modal-dialog"',     
           tg('div','class="modal-content"',
            tg('div','class="modal-header"',
               tg('h4','class="modal-title"',$title).
               tg('button','type="button" class="close" data-dismiss="modal"','&times;')).
            tg('div','class="modal-body"', '..').
               tg('div','class="modal-footer"',
               tg('button','type="button" class="btn btn-primary" data-dismiss="modal"','OK')))));

  return $r.$script.$modal;
}

?>