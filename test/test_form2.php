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
   
   htpr(
    bt_hidable_section('Instrukce pro vyplnění','Hidesec',
     'Tady může být text nebo návod potřebný pro vyplění formuláře '.
     str_repeat('Lorem Ipsum donor cealea gracit afede mortud leri pso. ',10)),
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
     ta('h4','Hlavička formuláře').
     bt_container(['col-4','col-8'],
      [[bt_tooltip('Pokud nezadáte text při odeslání, vyvoláte upozornění o problému.','Zadejte text '.bt_icon('info').' :'),
        textfield("",'TXTFLD',20,20,getpar('TXTFLD'))],
       ['Tvar odpovědi:',
        combo("",
             'RESPFO',
             ['1'=>'Výstraha nahoře na stránce',
              '2'=>'Dialog přes obrazovku',
              '3'=>'nic'],
             getpar('RESPFO')?getpar('RESPFO'):'1')],
       [hr(),hr()], 
       ['České datum',bt_datefield('','DATEF',getpar('DATEF'))],
       ['Databázový seznam', 
         combo("",'DBLIST',
               to_hash("select kod,hornina ".
                       "from kod_horniny ".
                       "order by hornina asc",$db),
         getpar('DBLIST'))],
       ['Radio seznam', 
         radio("",'DBRADIO',to_hash("select kod,hornina from kod_horniny where kod in (400,401,402) order by hornina asc",$db),
         getpar('DBRADIO'))],
       ['Odstavec',textarea('','AREA',3,80,getpar('AREA'),'class="form-control" style="min-width: 100%"')],   
       ['Checkbox',check_box('','CH1',getpar('CH1')!=''?true:false)], 
       ['Range',bt_range('','RANGE',0,100,10,getpar('RANGE'),'')],
       ['Našeptávač - Obec',
         bt_autocomplete('','OBEC','ajax/auto_obec.php',getpar('OBEC'))],
       ['České datum II',bt_datefield('','DATEF2',getpar('DATEF2'))], 
       ['Našeptávač - Obec 2 ',
         bt_autocomplete('','OBEC2','ajax/auto_obec.php',getpar('OBEC2'))], 
       ['Multiselect (VannilaSelectBox)',
         bt_multiselect('','MULTI', 
          bt_getoptions($db,
            "select kod, nazev ".
            "from sn_ciselniky ".
            "where ciselnik='faktory' ".
            "order by poradi asc"),
            getpar('MULTI'),
            [ "disableSelectAll"=>true, 
              "maxHeight"=> 300, 
              "search"=> true,
              "translations"=>["all"=>"","items"=>"položek","selectAll"=>"Označ vše","clearAll"=>"Zruš označení"]])],
       ['Doplňovací seznam',
        bt_comboauto('','CA1',to_hash("select kod,hornina from kod_horniny where kod in (400,401,402) order by hornina asc",$db),
         getpar('CA1'))],
       ['Select (VannilaSelectBox)',
        bt_select("",'DBLIST2',to_hash("select kod,hornina from kod_horniny order by hornina asc",$db),
        getpar('DBLIST2'))
       ],
       ['Volný výběr z připravených textů',
        bt_text_select('','VOLNY',
         ['Ab','Act','Afs','Amp','An','Ano','Aug','Bt','Cb','Cal','Chl','Cld','Cpx','Crd',
          'Di','Dol','Drv','Fsp','Fo','Hbl','Kfs','Mc','Ol','Pl','Px','Qz','Srp','Tr'],
          getpar('VOLNY'))
       ],
       ['Volný vícevýběr z připravené grafiky',
        bt_icon_multiselect("",'GRAFIKY',
         ['1'=>bt_icon('home'),
          '2'=>bt_icon('compass'),
          '3'=>bt_icon('geolocation'),
          '4'=>'bez symbolu'],
         getpar('GRAFIKY'))
      ],
      ['Výběr grafického symbolu',
        bt_icon_select("",'GRAFIKA',
         ['1'=>bt_icon('home'),
          '2'=>bt_icon('compass'),
          '3'=>bt_icon('geolocation'),
          '4'=>bt_icon('check-square')],
         getpar('GRAFIKA'))
      ]
      ]).      
      '<hr>'.        
     bt_justify_between(
        tg('input',' type="reset" class="btn btn-secondary" value="Nastavit původní stav"','noslash').
        '[nějaké další tlačítko]'.
        submit('OK','Odeslat')
        ),
     hr()));

 }

 static function result(){
   //deb(print_r($_POST,true),false);
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
                                           getpar('DBLIST2'),
                                           getpar('VOLNY'),
                                           getpar('GRAFIKY'),
                                           getpar('GRAFIKA')
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