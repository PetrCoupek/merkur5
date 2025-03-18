<?php
/** Merkur 5 test datatables frontend
 * @author Petr Coupek
 * @date 08.01.2024
 */

include_once '../lib/mlib.php';
include_once 'ini.php';
//include_once '../lib/vistab.php';

M5::set('header','Test DataTables frontend');
M5::set('debug',true);
$path ='../';
M5::skeleton($path);

M5::puthf(
  tg('script','src="'.$path.'/vendor/datatables/datatables.js"',' ').
  tg('link','rel="stylesheet" media="screen,print" href="'.$path.'/vendor/datatables/datatables.css" type="text/css" ','noslash')."\n",
 'datatables'
);

getparm();

route();


function test_table(){
  $r=ta('script',"
    $(document).ready(function() {
    var t=new DataTable('#ukazka', { 'ajax': { 'url': '?ajx=1' }, 
     'lengthMenu': [[5, 10, 25, 50, -1],[5, 10, 25, 50, 'Vše']]}); 
    });").
   tg('table','id="ukazka" class="table table-striped table-bordered table-hover table-sm" style="width:100%"',
    tg('thead','class="thead-light"',
      ta('tr',
        ta('th','Kód').
        ta('th','Název').
        ta('th','Anglicky').
        ta('th','Nadřízený').
        ta('th','Úroveň').
        ta('th','Definice').
        ta('th','Aktivní').
        ta('th','Poznámka')
      )
   ));
  return $r;
}

function get_ajax_data(){
  $db = new OpenDB_Oracle(CONN_APP_DKB_02);
  $sql = "select * from dat_dkb.kod_reg";
  $a = $db->SqlFetchArray($sql);
  for($b=[],$i=0;$i<count($a);$i++){
    array_push($b,array_values($a[$i]));
  }
  $r=json_encode(['data'=>$b],JSON_UNESCAPED_UNICODE);

  $db->Close();
  getResp($r);
 
}

function route(){
  if (getpar('ajx') == 1){
    get_ajax_data();
  }else{
    htpr(test_table());
    M5::htpr_all();
  }
}


?>