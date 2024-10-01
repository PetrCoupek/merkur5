<?php
/** Merkur 5 test passive dialog
 * @author Petr Coupek
 * @date 03.08.2023
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test Passive dialog');
M5::set('debug',true);
M5::skeleton('../');

/* pristupna funkce process location */
M5::puthf(
          tg('script','src="'.M5::get('path_relative').'/vendor/position/position.js"',' ').
          tg('script','src="'.M5::get('path_relative').'/vendor/position/proj4.js"',' '),
          'position'
  );

htpr(br(),
tg('input','type="text" id="sour_x" name="sour_x" size="7" maxlenght="15" value="" ','noslash'),
tg('input','type="text" id="sour_y" name="sour_y" size="8" maxlenght="15" value="" ','noslash'),
  common_process_position()
);
                 
M5::done();

function common_process_position(){
 return 
  tg('button','type="button" class="btn btn-primary" '.
                 'onclick="show_bt_dialog(\'bt_pas\');"',
                 bt_icon('power')).
  common_passive_dialog('Náhled polohy','bt_pas','',
    tg('input','type="hidden" id="bt_pas_x" value=""','noslash').
    tg('input','type="hidden" id="bt_pas_y" value=""','noslash')).
  tg('div','id="status"',' ').
  ta('script','function show_bt_dialog(id,obsah){
    processLocation(function(x,y){
    var obsah=\'<center><img src="msm.php?x=\'+x+\'&y=\'+y+\'"></center>\';
    $(\'#\'+id+\'_x\').val(x);
    $(\'#\'+id+\'_y\').val(y);
    $(\'#_\'+id).html(obsah);
    $(\'#\'+id).modal(\'show\'); },\'status\');}').
  ta('script','$(document).ready(function(){ '.
   '$("#__bt_pas").click(function(){ '.
   '$("#sour_x").val($("#bt_pas_x").val());'.
   '$("#sour_y").val($("#bt_pas_y").val());'.
   '});  });');
}

function common_passive_dialog($title,$id='bt_dialog_pas',$body='',$add=''){
 return tg('div','class="modal" id="'.$id.'" tabindex="-1" role="dialog"',
   tg('div', 'class="modal-dialog modal-dialog-centered" role="document"',
    tg('div','class="modal-content"',
     tg('div','class="modal-header"',
      tg('h5','class="modal-title"',$title).
       tg('button','type="button" class="close" data-dismiss="modal" aria-label="Close"',
        tg('span','aria-hidden="true"','&times;'))).
     tg('div','class="modal-body"',tg('p','style="word-wrap:break-word;" id="_'.$id.'" ',$body)).
     tg('div','class="modal-footer"',
      $add.
      tg('button','type="button" class="btn btn-secondary" data-dismiss="modal"','Storno polohy').
      tg('button','type="button" class="btn btn-primary" data-dismiss="modal" id="__'.$id.'" ','OK')))));

}

?>