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

   
   htpr(tg('form','method="post" action="?" class="bg-light p-2 border" ',
    ta('h4','Hlavička formuláře').
    bt_container(['col-3','col-7','col-2'],
      [[bt_tooltip('Pokud nezadáte text při odeslání, vyvoláte upozornění o problému.','Zadejte text '.bt_icon('info').' :'),
        textfield("",'TXTFLD',20,20,getpar('TXTFLD')),
        nbsp()],
       ['Tvar odpovědi:',
        combo("",'RESPFO',['1'=>'Výstraha nahoře na stránce',
                           '2'=>'Dialog přes obrazovku',
                           '3'=>'nic'],getpar('RESPFO')?getpar('RESPFO'):'1'),
        nbsp()],
       ['České datum',bt_datefield('','DATEF',getpar('DATEF')),'[nic]'],
       ['Databázový seznam', 
         combo("",'DBLIST',to_hash("select den||'.'||mesic||'.',jmena from jmeniny order by jmena asc",$db),
         getpar('DBLIST')),
         '[nic]'],
       ['Radio seznam', 
         radio("",'DBRADIO',to_hash("select den||'.'||mesic||'.',jmena from jmeniny order by jmena asc",$db),
         getpar('DBRADIO')),
         nbsp()],
       ['Odstavec',textarea('','AREA',3,80,getpar('AREA'),'class="form-control" style="min-width: 100%"')],   
       ['Checkbox',check_box('','CH1',getpar('CH1')!=''?true:false)], 
       ['Range',bt_range('','RANGE',0,100,10,getpar('RANGE'),''),' '],
       ['Našeptávač -Obec',
         bt_autocomplete2('','OBEC','ajax/auto_obec.php',getpar('OBEC')),'[nic]'],
       ['České datum II',bt_datefield('','DATEF2',getpar('DATEF2')),' '], 
       ['Našeptávač -Obec 2 ',
       bt_autocomplete2('','OBEC2','ajax/auto_obec.php',getpar('OBEC2')),'[nic]'], 
       [nbsp(),
        tg('input',' type="reset" class="btn btn-secondary" value="Nastavit původní stav"','noslash'),
        submit('OK','Odeslat')],
      ])));
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
                                           getpar('OBEC2')]);
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

?>