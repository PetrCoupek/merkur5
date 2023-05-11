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
    htpr_all();                        /* Zapis bufferu na standarni vystup */
 }

 static function form(){
   $db=new OpenDB_SQLite('file=../data/m5.sqlite3,mode=0');  
   
   /* toto způsobí efektní tooltip */
   htpr(ta('script','$(function () {
    $(\'[data-toggle="tooltip"]\').tooltip()
    })'));

   
   htpr(bt_tooltip('Test informační bubliny.','Info'.bt_icon('info')).
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
     ta('h4','Hlavička formuláře').
     bt_container(['col-4','col-8'],
      [[bt_tooltip('Pokud nezadáte text při odeslání, vyvoláte upozornění o problému.','Zadejte text '.bt_icon('info').' :'),
        textfield("",'TXTFLD',20,20,getpar('TXTFLD'))],
       ['Tvar odpovědi:',
        combo("",'RESPFO',['1'=>'Výstraha nahoře na stránce',
                           '2'=>'Dialog přes obrazovku',
                           '3'=>'nic'],getpar('RESPFO')?getpar('RESPFO'):'1')],
       [hr(),hr()], 
       ['České datum',bt_datefield('','DATEF',getpar('DATEF'))],
       ['Databázový seznam', 
         combo("",'DBLIST',to_hash("select den||'.'||mesic||'.',jmena from jmeniny order by jmena asc",$db),
         getpar('DBLIST'))],
       ['Radio seznam', 
         radio("",'DBRADIO',to_hash("select den||'.'||mesic||'.',jmena from jmeniny order by jmena asc",$db),
         getpar('DBRADIO'))],
       ['Odstavec',textarea('','AREA',3,80,getpar('AREA'),'class="form-control" style="min-width: 100%"')],   
       ['Checkbox',check_box('','CH1',getpar('CH1')!=''?true:false)], 
       ['Range',bt_range('','RANGE',0,100,10,getpar('RANGE'),'')],
       ['Našeptávač -Obec',
         bt_autocomplete('','OBEC','ajax/auto_obec.php',getpar('OBEC'))],
       ['České datum II',bt_datefield('','DATEF2',getpar('DATEF2'))], 
       ['Našeptávač -Obec 2 ',
         bt_autocomplete('','OBEC2','ajax/auto_obec.php',getpar('OBEC2'))], 
       ['Multiselect (VannilaSelectBox)',
         bt_multiselect('','MULTI', 
          bt_getoptions($db,
            "select kod, nazev ".
            "from sn_ciselniky ".
            "where ciselnik='faktory' ".
            "order by poradi asc"),getpar('MULTI'))],
       ['Doplňovací seznam',
        bt_comboauto('','CA1',to_hash("select distinct den+100*mesic,jmena from jmeniny",$db),getpar('CA1'))],
       ['Select (VannilaSelectBox)',
        bt_select("",'DBLIST2',to_hash("select den||'.'||mesic||'.',jmena from jmeniny order by jmena asc",$db),
        getpar('DBLIST2'),
        [ "disableSelectAll"=>true, 
          "maxHeight"=> 200, 
          "search"=> false,
          "translations"=>["all"=>"Vše","items"=>"položek","selectAll"=>"Označ vše","clearAll"=>"Zruš označení"]
        ])
       ] 
      ]).      
      '<hr>'.        
     bt_container(['col-8','col-4'],
       [[nbsp(20).
        tg('input',' type="reset" class="btn btn-secondary" value="Nastavit původní stav"','noslash'),
        submit('OK','Odeslat')]
       ]),
     hr()));
 }

 static function result(){
   $tn='Výsledek textového pole je prázdný';
   $tp='Výsledek je '.implode(';'.nbsp(1),[getpar('TXTFLD'),
                                           getpar('DATEF'),
                                           getpar('DBLIST'),
                                           getpar('DBRADIO'),
                                           getpar('CH1')?'CH1':'!CH1',
                                           getpar('AREA'),
                                           getpar('RANGE'),
                                           getpar('OBEC'),
                                           getpar('OBEC2'),
                           '['.implode(';',(array)getpar('MULTI')).']',
                                           getpar('CA1'),
                                           getpar('DBLIST2')
                              ]);
   if (getpar('RESPFO')==1)
    htpr(tg('div','class="p-2"',
     getpar('TXTFLD')?bt_alert($tp):
                      bt_alert($tn,'alert-danger')));
   if (getpar('RESPFO')==2)
    htpr(tg('div','class="p-2"',
     getpar('TXTFLD')?bt_dialog('Oznámení',$tp):
                      bt_dialog('Varování',$tn)));                      
 }

}

Testform::set('header','Test českého formuláře a jeho potvrzení');
Testform::set('debug',true);
Testform::skeleton('../'); /* volani skriptu */

/** ComboBox with the abitity of typing a new value
 * @param string $lab label
 * @param string $id field identifier
 * @param array list of available options, use function to_hash() to generate it from an SQL command
 * @param string $val default value
 */
function bt_comboauto_testing_version($lab,$id,$data=[],$val=''){
  deb($data);
  M5::puthf(
    tg('link','href="'.M5::get('path_relative').'/vendor/comboAutocomplete/cbac.css" rel="stylesheet"').
    tg('script','src="'.M5::get('path_relative').'/vendor/comboAutocomplete/cbac.js"',' '),
    'comboauto'
  );
  $s='';
  foreach ($data as $k=>$v) $s.=tg('li','id="'.$id.'_'.$k.'" role="option"',$v);
  $s=tg('div','class="combobox combobox-list"',
      tg('div','class="group"',
       tg('input','id="'.$id.'-input" name="'.$id.'" class="cb_edit" type="text" role="combobox" aria-autocomplete="list" '.
                  'aria-expanded="false" aria-controls="'.$id.'-listbox" value="'.$val.'"','noslash').
       tg('button','id="'.$id.'-button" tabindex="-1" aria-label="States" aria-expanded="false" aria-controls="'.$id.'-listbox" type="button"',
       '<svg width="18" height="16" aria-hidden="true" focusable="false" style="forced-color-adjust: auto">
        <polygon class="arrow" stroke-width="0" fill-opacity="0.75" fill="currentcolor" points="3,6 15,6 9,14"></polygon>
        </svg>'      
        )).
       tg('ul','id="'.$id.'-listbox" role="listbox" aria-label="'.$lab.'"',$s)); 
  if ($lab!='') $s=tg('label','for="'.$id.'"',$lab).$s;   
  
  return $s;
}

?>