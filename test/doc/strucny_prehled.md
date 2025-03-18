# Stručný přehled ke zdrojovým kódům v PHP

Následující text osvětluje způsob návrhu některých PHP serverových aplikací v ČGS.

Řada současně běžících aplikací na aplikačním serveru [https://app.geology.cz](https://app.geology.cz) je postavena na konvencích využívající aplikační modul označený jako M5 (soubor lib/mlib.php) spolu s dalšími PHP třídami, které na něj navazují.
Termínem **"modul"** je myšlena situace, kdy se deklarovaná třída v PHP se statickými metodami a se svými statickými proměnnými využívá v aplikaci přímo, bez vytvoření instance. 
Vytvářené aplikace jsou databázové a jejich účelem je buď editační nástroj pro pořizování a úpravu dat v databázi Oracle, případně slouží k prezentaci existujících záznamů, ve spojení s ostatní infrastrukturou, jak je portál ČGS, mapový server ČSG a metadatový katalog ČGS.

Naše potřeby jsou zpravidla: jednoduché editační a prohlížecí aplikace nad databází Oracle, případně s využitím mapových webových služeb, generování výstupných sestav na základě dat v databázi, ukládání souborových příloh ne server, synchronizace různých systémů na pozadí, tvorba jednorázových skriptů na úpravy, import či export dat relační databáze, atd.

V přístupu MVC (Model-View-Controler) plní úlohu modelu databázové relační schéma a jeho data. Úloha PHP skriptu je v roli Controleru umožňující činnosti dat daty, typicky autentizovanými uživateli. Grafická podoba aplikace je z velké míry oddělena od funkční vrstvy použitím šablony stránky a připravených front-endových prvků a forma prezentace dat může být ovlivněna připravenými databázovými pohledy na data.

Jednotlivé kapitoly textu jsou:
- [Modul M5 - základní běhové prostředí pro návrh aplikace](#modul-m5---základ-pro-návrh-aplikace)
- [Třída OpenDB - databázový wrapper pro sjednocení přístupu k databázím](#třída-opendb---databázový-wrapper)
- [Třída VisTab - vizualizace databázové entity](#třída-vistab---vizualizace-databázové-entity)
- [Třída EdiTab - editace databázové entity](#třída-editab---editace-databázové-entity)
- [Třída Cm - systém správy obsahu pro komplexní aplikaci](#třída-cm---systém-správy-obsahu-pro-komplexní-aplikaci)


## Modul M5 - základ pro návrh aplikace

### Úvod, motivace

Třída (modul) M5 je základem serverové aplikace. Řeší základní běh skriptu a vytváří jednoduché aplikační prostředí oddělené od HTML vzhledu a vstupně - výstupních úloh. Obsahuje rovněž metodu pro odchyt chyb při běhu a možnost základního ladícího výpisu. 

Instrukce pro zavedení modulu

```php
include_once "lib/mlib.php";
```
obsahuje také několik příkazů, které jsou přímo provedeny a dále deklarace globálních funkcí, která představují synonyma pro názvy metod z M5. Smyslem synonym je zestručnění zápisu často používaných metod, jak je ukázáno díle.


### Módy běhu aplikačního skriptu

Aplikační PHP skript může být obecně spuštěn různým způsobem:

- jako skript řízený webovým serverem, typicky **Apache 2.4** s modulem **php_module** uvnitř paměťového poolu, popřípadě v režimu FAST-CGI . Takový běh skriptu je zdaleka nejčastější. Po skriptu se zpravidla očekává nějaká jednorázová akce, která netrvá delší dobu. Webový server, případně v našem případě HTTPS proxy, hlídá timeout, po který očekává odpověď skriptu. Tato doba je zpravidla kolem 30 vteřin, poté je spojení mezi proxy a Apache ukončeno. Apache má také nastavený timeout, po jehož vypršení je proces násilně ukončen. 
- jako skript spuštěný z příkazové řádky, případně automaticky pomocí cron. V PHP tento režim se nazývá **CLI** (command line interface). Běh skriptu není nijak časově omezen. Typické využití je při řešení úloh synchronizace různých systémů
- zvláštní situace je spuštění PHP skriptu pomocí volání CLI ze skriptu již běžícího na webovém serveru. Takový skript umožní generování úlohy na pozadí, která trvá delší dobu (například generování PDF výstupních sestav, příprava složitějších exportů a podobně). Vzniká nezávislé vlákno a to již není časově omezeno. Programátor může zajisti mechanismus, který umožní koncovému uživateli sledovat průběh a dokončení procesu a získání vygenerovaného výstupu.

PHP samo o sobě disponuje řadou velmi užitečných a komfortních funkcí pro sestavování odpovědí do webových služeb a předávání dat. Skript řízený webovým serverem může vracet různé odpovědi (v hlavičce odpovědi Content-type). Nejčastěji: text/html; charset=utf-8- text/xml,- application/json, application/pdf , image/jpg, image/png a další. 

**Při využití modulu M5 je možné psát skripty pro všechny výše zmíněné případy spuštění a vracení výsledku**, často jako jediný skript, který může být použit pro různé situace. Pro vstupní a výstupní operace se využívají připravené funkce **M5::getpar()** , **M5::htpr()**, či **M5::getresp()**. V souboru mlib jsou zároveň deklarovaná jejich synonyma jako globální funkce (není zde využita direktiva namespace ). Jednorázové odpovědi vracející např. JSON odpovědi pro komplexní frontend aplikaci mohou být pojaty jako metody uvnitř jediného aplikačního modulu, který vychází z M5 modulu.

Modul M5 je využit spolu s ostatními nástroji - databázovým wraperem. 

### Základní vzor aplikace běžící na aplikačním serveru

Předpokládáme nyní webovou aplikaci v samostatné složce. Konvence pro umístění souborů webové je následující:

```
css/
lib/
php/
vendor/
.htaccess
ini.php
aplikacni_skript.php
```

Knihovny aplikačního prostředí M5 jsou ve složce **lib/** . Centrální knihovna M5 je v souboru **lib/mlib.php**. Pro  vlastní skripty aplikačního řešení je připravena složka **php/**. Všechny použité kódy třetích stran jsou uloženy ve složce **vendor/** . Ta má často řadu podsložek. V následujícím příkladu používá Bootstrap 4.6 a JQuery 3.7.1, které jsou v příslušných podsložkách.
**.htaccess** je řídícím souborem pro **Apache 2.4** - konfigurace často počítá s tím, že je v rámci Apache aktivní **mod_rewrite** . Ten umožňuje hezká URL, skrývání podsložek a využití metody **M5::route()**, která umí zpracovat virtuální cesty předané od klienta. Každá solidní aplikace pak má svá nastavení, která jsou umístěna v souboru **ini.php**. Tento soubor automaticky připojen modulem M5 při startu aplikace. Zde bývají uloženy informace o připojení k databázím, centrální HTML šablona aplikace (základní jednoduchá šablona vázaná na Boostrap je součástí výchozího vzoru). Výchozí webová aplikace může mít pak například tuto podobu:


```php
include_once '../lib/mlib.php';

class Hello extends M5{

  static function skeleton($path=''){
    self::set('header','Nejmenší aplikace');  /* hlavička stránky */ 
    parent::skeleton();                       /* implicitní běh */
    htpr(ta('p','Haló, tak tady jsem.'));
    self::done();                             /* zapiš výstup a skonči */       
  }

} 

Hello::skeleton(); 

```

Modul **Hello** zde zdědil funkcionalitu výchozího běhového modulu a při znalosti jeho metod je můžeme rozšiřovat a doplňovat. Beztřídní varianta se stejnou funkcionalitou vypadá následovně:

```php
include_once "../lib/mlib.php";
M5::set('header','Nejmenší aplikace');
M5::skeleton(); 
htpr(ta('p','Haló, tak tady jsem.'));
M5::done();           
```

Po startu základní modul M5 zaregistruje vlastní autoload funkci, pokusí se nastavit neomezený limit svého běhu, nastaví svoji funkci na odchyt chyb. Tyto akce provede v globálním prostředí. Následně nastaví své vnitřní proměnné, ke kterým je přístup přes getter **M5::get()** a setter **M5::set()** . 

Pro návrh webové aplikace je důležitá podoba webové stránky. U skutečné aplikace se předpokládá úprava programátora, výchozí knihovna však již obsahuje připravenou šablonu, která je nastavena v metodě **M5::skeleton()** takto:






```php
self::set('htptemp','<!DOCTYPE html>'."\n".
   tg('html','lang="cs"',
    ta('head',
     ta('title','#TITLE#').
     tg('meta','http-equiv="content-type" content="text/html; charset=utf-8"','noslash').
     tg('meta','name="language" content="cs"','noslash').
     tg('meta','name="viewport" content="width=device-width, initial-scale=1.0"','noslash').
     tg('meta','name="description" lang="cs" content="Merkur 5 kit set"','noslash').
     tg('meta','name="keywords" lang="cs" content="Merkur5 kit"','noslash').
     tg('link','rel="stylesheet" media="screen,print" href="'.
       $path.'css/m5.css?a=11" type="text/css" ','noslash').
     tg('link','rel="stylesheet" media="screen,print" href="'.
       $path.'vendor/bootstrap/css/bootstrap.css" type="text/css" ','noslash').
     tg('script','src="'.$path.'vendor/jquery/jquery.min.js"',' ').
     tg('script','src="'.$path.'vendor/bootstrap/js/bootstrap.bundle.min.js"',' ').
     '#___#'
     ).
     tg('body','style="padding-top: 3.5rem;"',
      tg('nav','class="navbar navbar-expand-ld navbar-dark bg-dark fixed-top"',
       tg('a', 'class="navbar-brand" href="?"','#HEADER#')
      ).
      ta('main',
      tg('div',
         'id="page-content-wrapper"',
       tg('div',
         ' class="container-fluid"','#BODY#'))).'<hr>'.
       tg('div','class="d-flex justify-items-end float-right"',
        self::get('version').
      
      tg('div','class="m5-loader"',
       tg('div','class="d-flex justify-content-center"',
        tg('div','class="spinner-border text-primary big"',' ')).
         
      tg('script',' ',
         '$(window).bind("beforeunload", function(){
           document.body.style.opacity=0.6;
          $(".m5-loader").css("visibility","visible");          
           });'
      ))).'#ERRORS#')));
```

Modul M5 obsahuje metody **M5::ta()**, **M5::tg()**, které jsou volány synonymy **ta()**, **tg()**. Tyto metody plně nahrazují zápis HTML značek (tagů), takže aplikace samotná neobsahuje žádné surové HTML značky. Dále je zde využit jednoduchý systém šablon, kdy zde umístěné značky #TITLE#, #BODY#, #ERRORS#, popř. #HEADER# a další jsou nahrazeny před výstupem skriptu živými ekvivalenty. Zde bude při kompletaci stránky vše, co bylo "vytisknuto" funkcí **M5::htpr()** dosazeno místo #BODY# . Symbol #ERRORS# je nahrazen výstupem ladící funkce **M5::deb()** . Shodně je řešena integrace hotových externích front-end prvků, vyžadujících úvodní připojení JS kódu, atd.

Šablona může mít obecně podobu např- XML dokumentu: 

```php
self::set('htptemp','<?xml version="1.0" encoding="UTF-8"?>'."\n".'#BODY#' );
```


### Formuláře v režimu klient - server

Typickou úlohou je zobrazení a zpracování formuláře. V modulu M5 jsou je již připravená funkcionalita, která usnadňuje tvorbu webových formulářů s daty z databáze. Některé formulářové prvky jsou přímo v rámci modulu **M5**, prvky vyžadující návrh Bootstrap a určitou konfiguraci klienta jsou v souboru **lib/mbt.php** . Všimněte si, že globální funkce **textfield()**, **combo()**, **submit()** zastupují funkcionalitu HTML značek (input, select, button, atd.), či vzhledovou a responzivní funkcionalitu - **bt_container()** , **bt_alert()**, **bt_dialog()**. 

Následující kód obsahuje celou aplikaci jednoduchého formuláře a jeho zpracování. Uživatel má zadat text a zvolit, jakým způsobem bude výsledek po odeslání prezentován. Smyslem této jednoduché umělé úlohy je demostrovat základní možnosti interakce s formulářem a logiku jeho zpracování. 


```php

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';

class Myform extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Formulář a jeho potvrzení');   
   self::done();                        /* Zapis bufferu na standarni vystup */
 }

 static function route(){
   getparm();                         /* vyzvednuti parametru */
   if (getpar('OK')) self::result();  /* pokud byl odeslan formular, nastane akce */
   self::form();                      /* formular se tiskne vzdy */
 }
 
 static function form(){
   htpr(
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
      bt_container(['col-4','col-8'],
      [['Zadejte text: ' , textfield('','TXTFLD',20,20,getpar('TXTFLD'))],
       ['Forma výstupu: ', combo('','RESPFO',['1'=>'Výstraha nahoře na stránce',
                                              '2'=>'Dialog přes obrazovku',
                                              '3'=>'nic'],
                                 getpar('RESPFO')?getpar('RESPFO'):'1')],
       ['', submit('OK','Ok')]
      ])));
 }

 static function result(){
   $vysledek='Výsledek je '.getpar('TXTFLD');
   $chyba='Textové pole je prázdné';
  switch (getpar('RESPFO')) {
    case '1': htpr(getpar('TXTFLD') ? bt_alert($vysledek) : bt_alert($chyba, 'alert-danger'));
     break;
    case '2': htpr(getpar('TXTFLD') ? bt_dialog('Výstup',$vysledek) : bt_dialog('Varování',$chyba));
     break;
    case '3':
     /* Zde není vyžadována žádná akce */
     break;
  };
 }

}

Myform::skeleton(); /* volani skriptu */
```


### Metody skeleton(), route(), getparm() ..

Pokud je vlastní aplikace vytvořena jako následník modulu M5, spoléhá na jeho metody a jejich pojmenování. Zamýšlený mechanismus je takový, že metoda pro nastavení prostředí a základní běh (kostra aplikace) jsou soustředěny do metody **skeleton()** a v jejím rámci se na konci volá metoda **route()**, která je zodpovědná za větvení aplikace. Obě tyto statické metody mohou být programátorem rozšířeny či nahrazeny. Ve výchozím stavu se na konci metody **M5::skeleton()** volá metoda **M5::route()**, která obsahuje volání metody **M5::getparm()**. Metoda **M5::getparm()** naplní modul hodnotami parametrů $_POST a $_GET tak, aby následně byly parametry přístupné přes volání **getpar('JMENO_PARAMETRU')** . **skeleton()** může být nahrazen a **route()** je téměř vždy nahrazena kódem podle potřebného větvení aplikace. Jednotlivé další komponenty mají shodně pojmenovanou metodu **route()** všude tam, kde je třeba určit, které chování má na základě vstupu od klienta daná komponenta právě vykonat. (M5 variantně obsahuje i mechanismus, který umožňuje deklarovat větvení aplikace s využitím mod_rewrite modulu.)


### Paleta formulářových prvků

K dispozici je řada připravených formulářových prvků - například český dialog pro zadávání datumových položek, našeptávač, radio seznam, checkbox, databázový seznam, multiselect, volný výběr z připravené grafiky a jiné. V základní knihovně je rovněž funkce **bt_icon()** na vkládání SVG ikon přímo do HTML výstupu. Funkce **bt_hidable_section()** implementuje část, která je při vyvolání skryta a může být uživatelem rozbalena. Příklad sestavení formuláře s mnoha rozdílnými funkčními prvky je převzat z demonstračního testu.

```php
htpr(
 bt_hidable_section('Instrukce pro vyplnění','Hidesec',
     'Tady může být text nebo návod potřebný pro vyplnění formuláře '.
     str_repeat('Lorem Ipsum donor cealea gracit afede mortud leri pso. ',10)),
    tg('form','method="post" action="?" class="bg-light p-2 border" ',
     ta('h4','Hlavička formuláře').
     bt_container(['col-4','col-8'],
      [[bt_tooltip('Pokud nezadáte text při odeslání, vyvoláte upozornění o problému.',
       'Zadejte text '.bt_icon('info').' :'),
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
         radio("",'DBRADIO',to_hash("select kod,hornina 
                                    from kod_horniny
                                    where kod in (400,401,402) order by hornina asc",$db),
         getpar('DBRADIO'))],
       ['Odstavec',textarea('','AREA',3,80,
                    getpar('AREA'),'class="form-control" style="min-width: 100%"')],   
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
              "translations"=>["all"=>"",
                               "items"=>"položek",
                               "selectAll"=>"Označ vše",
                               "clearAll"=>"Zruš označení"]])],
       ['Doplňovací seznam',
        bt_comboauto('','CA1',to_hash(
           "select kod,hornina ".
           "from kod_horniny ".
           "where kod in (400,401,402) ".
           "order by hornina asc",$db),
         getpar('CA1'))],
       ['Select (VannilaSelectBox)',
        bt_select("",'DBLIST2',to_hash(
           "select kod,hornina from kod_horniny order by hornina asc",$db),
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
        tg('input',
         ' type="reset" class="btn btn-secondary" value="Nastavit původní stav"',
         'noslash').
        '[nějaké další tlačítko]'.
        submit('OK','Odeslat')
        ),
     hr()));

```

### Příklad skriptu vracející JSON odpověď pro našeptávač

V následujícím případě je ukázáno využití modulu M5 v roli samostatného našeptávače. Tento kód plní dvě role, jednak vrací pro volající frontend prvek seznam potenciálních záznamů k výběru a nebo vrátí pevný klíč již vybraného záznamu. To je potřeba v situaci, kdy je již prvek vybrán a celý formulář je načtený z databáze. Celé to zapadá do funkcionality našeptávače ukázaného výše. Příklad práce s databází je reálný. Uvedený kód našeptává obce v ČR.

```php
include_once '../../lib/mlib.php';                      
getparm();
$db = new OpenDB_Oracle(CONN_APP_DKB_02);

if (getpar('q'))
  $r=autocompleteFormat(
    $db->SqlFetchArray(
      "select lau2_kod as V, lau2_vyznam||' ['||lau2_kod||']' as T ".
      "from dat_kod.kod_all_obec ".
      "where lau2_vyznam like :vyz||'%' ".
      "order by lau2_vyznam asc ",
      [':vyz'=>getpar('q')],
      15));
if (getpar('id')){
   $r=$db->SqlFetch(
     "select lau2_vyznam||' ['||lau2_kod||']' as T ".
     "from dat_kod.kod_all_obec ".
     "where lau2_kod =:id ",
      [':id'=>getpar('id')]  );
  $r=json_encode(['text'=>$r],JSON_UNESCAPED_UNICODE);

} 
$db->Close();
getResp($r);
 
```

### Přidání další front-end funkcionality

Způsob vytváření front-endových prvků, které využívají připravené komponenty v Javascriptu+DHTML+CSS, je otevřený. V souboru **lib/mbt.php** je deklarována řada globálních funkcí, které vrací řetězec obsahující úsek HTML kódu. Předpona **bt_** značí, že jsou vázané na šablonu využívající CSS Bootstrap prostředí (srovnání s funkcemi značenými  **ht_** ) . Pokud implementace prvku vyžaduje připravenou externí funkcionalitu, založenou na Javascriptu nebo CSS, obsahuje tělo funkce instrukci **M5::puthf()** . Tato metoda vkládá do hlavičky generované stránky kód zajišťující připojení příslušných částí front-end kódu při načítání stránky do prohlížeče. Její volání obsahuje příslušný odkaz (zpravidla tag **script** nebo **link**) a zároveň je předán jedinečný identifikátor. Identifikátor slouží k tomu, aby v případě vícenásobného využití prvku na stránce, byly příslušné funkční části umístěny do hlavičky pouze jednou. Jako ukázku uvádím zdrojový kód výše použitého našeptávače **bt_autocomplete()** .

```php
function bt_autocomplete($label,$name,$url,$value='',$add='',$placeholder=''){
  $path=M5::get('path_relative');
  if ($placeholder=='') 
    $placeholder=(isset($_SESSION['la']) && $_SESSION['la']=='en')?
                  'enter text..':
                  'zadejte text..';
  M5::puthf(tg('script','src="'.$path.
    '/vendor/autocomplete/bootstrap-autocomplete_vlastni.js"','noslash')."\n",
  'autocomplete');
  $r=ta('span',$label).
     tg('select','class="form-control basicAutoSelect'.
       $name.'" name="'.$name.'" id="'.$name.'" placeholder="'.$placeholder.'" ',' ');
  $r.=ta('script',"$('.basicAutoSelect$name').autoComplete({
    resolverSettings: {
      url: '".$url."',     
      autocomplete: 'off',
      noResultsText: 'Nic nenalezeno.' },
      minLength: 1".($add!=''?(",\n".$add):'')."});");    
  if ($value!=''){
    /* set the appropriate value and 
       find also the text written on the screen */
    $r.=ta('script', 
    "console.log('$value') ;\n".
    '$.ajax({url:"'.$url.'?id='.$value.'",'.
            'success: function(result){ '.
            'console.log(result);'.
            "$('.basicAutoSelect$name').autoComplete('set', { value: '".
             $value."', text: result['text'] });".
            '}});');
  }
  return $r;
}
```
Tento přístup umožní rozumné oddělení obecné funkčnosti od funkčnosti vysloveně aplikační.


### Možnosti ladících výpisů

Šablona vzhledu aplikace obsahuje sekci #ERRORS#, která obsahuje modulem M5 odchycené běhové chyby. Pro běh v produkčním prostředí je při kompletaci výstupu tato sekce odstraněna, pokud je **M5** parametr **debug** nastaven na **false** . Nastavení

```php
M5::set('debug',true);
```

zajistí, že bude ve výstupu našeho kódu přítomna ladící informace. Ta je obvykle prezentována žlutým polem, s červeným neproporcionálním fontem (skutečný vzhled lze ovlivnit ve vlastní šabloně). Kdekoliv uvnitř kódu lze voláním funkce **deb()** zařídit ladící výpis. Funkce deb má dva parametry. První je libovolného typu a umožňuje inspekci předaného výrazu/proměnné. Druhý je boolean a je nepovinný. Pokud je druhý parametr **false**, nebsahuje ladící výpis místo, odkud byl zavolán. Ladícím výstupem je inspekce předaného výrazu - je uveden typ a v případě předání komplexního objektu (pole, hash, objekt) je vypsán i jeho aktuální obsah. Příklad ladícího výpisu příkazu deb($a) ($a je pole) včetně výpisu zásobníku a umístění samotného příkazu deb v kódu skriptu:

```
/srv/www/htdocs/share/lokality/php/vistab_lokality.php:deb:484:
/srv/www/htdocs/share/lokality/php/vistab_lokality.php:lister:83:
/srv/www/htdocs/share/lokality/l.php:route:796:
/srv/www/htdocs/share/lokality/l.php:vyhledavani:102:
:{closure}::
/srv/www/htdocs/share/lokality/lib/mlib.php:call_user_func_array:279:
/srv/www/htdocs/share/lokality/l.php:getroute:196:
/srv/www/htdocs/share/lokality/l.php:route:133:
/srv/www/htdocs/share/lokality/l.php:skeleton:984:
Array
(
    [0] => Array
        (
            [ID] => 2113
            [N1] => Ptačí stěna
            [OKRES] => Český Krumlov, Prachatice
            [MCHU_KOD] => 1837
            [VCHU_KOD] => 31
            [FOTO] => 
        )

    [1] => Array
        (
            [ID] => 718
            [N1] => Brňov - sesuvné území
            [OKRES] => Vsetín
            [MCHU_KOD] => 
            [VCHU_KOD] => 
            [FOTO] => 
        )

)        
```

### Monitorování běhu skriptu

Další ladící funkcí je **M5::tick()**. Umožní změřit čas běhu dvou míst v kódu. První volání funkce nuluje stopky. Další volání je s doprovodným textem a zobrazí čas od nulování stopek. Uvažujme tento kód:

```php
tick();
$a=$this->db->SqlFetchArray($sprikaz,[],15,getpar('_ofs',1));
tick('Mám data');
```
V ladícím výstupu bude následující odpověď:

```
 0.0000 s 
 1.0972 s Mám data

```

### CLI režim a průběžný výpis

Pokud uvažujeme webovou aplikaci nebo webovou službu řízenou Apache, je konečný výpis sestaven až na závěr skriptu. V dávkovém zpracování CLI režimu však může být užitečné, aby nám aplikace dávala vědět o své činnosti průběžně. To lze zařídit nastavením

```php
M5::set('immediate',true);
```

Následující segment kódu je převzat ze skutečné vývojové verze CLI aplikace pro synchronizaci dat, která se spouští v pomocí cron. 

```php
include_once "syn_ini.php";
include_once "lib/mlib.php"; /* Merkur5 helper */
include_once "lib/mbt.php";
M5::set('version','ČGS, 1.0 14.09.2022');
M5::skeleton('');            /* Merkur5 */
M5::set('debug',true);       /* nastaveni debug */ 
M5::set('immediate',true);
M5::set('header','Synchronizace z CHLÚ ISŽP do SurIS');
```

Funkce průběžného nebo jednorázového výpisu může být v rámci CLI využita při utvoření kolony. Následující situace je zcela reálná: po provedení denní synchronizace je administrátorovi odeslán e-mail, ve kterém je výpis činností synchronizačního skriptu v PHP:

```
php syn_chl.php db=1 | php send_mail.php
```

Modul M5 zaručí, že i případné běhové ne- fatální chyby, které se během činnosti skriptu vyskytnou, budou následně umístěny do e-mailu. Toto uspořádání transparentně odděluje posílání e-mailů od samotné synchronizace. Pokud chceme být zcela důslední a odeslat i protokol o úplné havárii syn_chl.php, může to vypadat takto:

```
php syn_loz.php db=1 2>&1 | php send_mail.php
```


## Třída OpenDB - databázový wrapper

### Úvod

Třída OpenDB je abstraktní třída s metodami pro práci s databázovými záznamy. Slouží pro sjednocení práce s databází nad různými databázemi. Jejím cílem není úplná abstrakce pro tvorbu plně přenositelných aplikací mimo úroveň SQL, ale slouží pro unifikaci volání databáze v rámci modulu M5. Metody spoléhají na předané SQL dotazy a neřeší rozdíly mezi SQL dialekty nad různými databázemi. Jejími potomky jsou pak prakticky použitelné třídy:

- OpenDB_Oracle - třída pro databázi Oracle využívající OCI8 PHP knihovnu
- OpenDB_SQLite - třída pro lokální souborovou databázi SQLite 3, využívající interní možnosti PHP
- OpenDB_pg     - třída pro databázi PostreSQL, aktuálně využívá PHP PDO modul a jeho metody
- OpenDB_ODBC   - třída pro propojení s ODBC zdroji na platformě Windows
- OpenDB_MySQL  - třída pro práci s databází MySql, , aktuálně využívá PHP PDO modul a jeho metody

Těžiště využití v ČGS je hlavně komunikace s databází Oracle. Protože OpenDB neřeší SQL dialekty, nemusí být u vytvořené aplikace zajištěna plná převoditelnost nahrazením jedné z vyjmenovaných tříd za jinou. 

Příklad použití :

```php
$db = new OpenDB_Oracle(CONN_DB_SCHEMA);
$a=$this->db->SqlFetchArray(
       "select nazev ".
       "from dkb_uskup,dkb_skup ".
       "where dkb_uskup.skupina=dkb_skup.skupina ".
       "and typ_vazby='U' ".
       "and dkb_uskup.uzivatel=:u",
       [':u'=>$this->rowid]);
htpr(ht_table('Členství ve skupinách',
      ['NAZEV'=>'Název skupiny'],
      $a,
      'Uživatel není ve skupinách',
      'class="table"'));       
$db->Close();       
```

Konstruktoru se předává připojovací řetězec. Doporučuje se tento řetězec držet odděleně v ini souboru aplikace, kde je definovaný jako konstanta.
Lze využít toho, že pomocí základní knihovny je ini soubor vždy načten. Uvedená metoda načte obsah výsledku SQL select dotazu do struktury $a

Základní wrapper obsahuje metodu pro položení dotazu **Sql()**, získání výsledku **FetchRow()**, přímé získání jednoho údaje kombinací předchozího **SqlFetch()**, získání pole **SqlFetchArray()**, pole s klíčem **SqlFetchKeys()**, či seznamu **SqlFetchList()**. Metody sjednocují zadávání parametrů pro **prepare** (zamezení SQL Injection)  pro různé databáze a umožňují jejich jednotnou kombinaci s dalšími prvky - našeptávači, combo boxy, listovacími, či pevnými tabulkami.



Popis metod:
### OpenDB :: __construct($connect) ###


```php
$db = new OpenDB_Oracle($connect);

```

Založení objektu a pokud o připojení se k databázi. Pro SQL_Lite - pokud databáze neexistuje, bude proveden pokus o vytvoření (založení souboru).

- Parametr: string $connect - připojovací řetězec
- Návratová hodnota: OpenDB_Oracle nový objekt obálky databáze nebo false, když nebylo navázáno spojení


### OpenDB :: Sql($command, $bind = Array) ###


```php
$error = $db->Sql($sql_command,$bind);

```

Zadejte příkaz SQL v cílové databázi
- Parametr: string $command - a sql příkaz
- Parametr: pole $bind - seznam parametrů vazby
- Návratová hodnota: boolean, true, když došlo k chybě, false v případě úspěchu


### OpenDB :: FetchRow() ###

```php
$vysledek = $db->FetchRow();
```

 Provede načtení jednoho řádku dat z databázové tabulky do hashe
- Návratová hodnota: boolean, true při načtení dalšího řádku, false na konci dat


### OpenDB :: Pragma($dotaz) ###


```php

$error = $db->Pragma("table_info('TABLE_NAME'");
```

Vyvolá akce databázového katalogu- podporováno je table_info pragma
- Parametr: string $command - informace o tabulce pragma
- Návratová hodnota: boolean, true, když došlo k chybě, false v případě úspěchu


### OpenDB :: FetchRowA() ###

```php
$vysledek = $db->FetchRowA();
```

Provede načtení jednoho řádku dat z databázové tabulky do pole (atributy jsou pod číslnými indexy)
- Návratová hodnota: boolean, **true** při načtení dalšího řádku, **false** na konci dat


### OpenDB :: Close() ###

```php
$db->Close();
```

Uzavře připojení k databázi.


### OpenDB :: Data ($sloupec) ###

```php
$value = $db->Data('atribut');
```

Tato metoda vrací aktuální hodnotu atributu
- Parametr: string $attribute - název atributu (v zobrazení/tabulce - u Oracle zásadně kapitálky),
  automatická detekce citlivosti na malá a velká písmena
- Návratová hodnota: řetězec (nebo objekt) s hodnotou atributu


### OpenDB :: DataHash() ###

```php
$value = $db->DataHash();
```

Tato metoda vrací aktuální hodnotu atributu
- Návratová hodnota: hash s aktuálně načtenými hodnotami řádků BLOB jsou převedeny na řetězce.


### OpenDB :: SqlFetch($prikaz, $bind = Array) ###

```php
$string = $db->SqlFetch($command);
```

zkombinujte metodu Sql a FetchRow do jednoho kroku a vrátí hash dat
- Parametr: string $command - a sql příkaz
- Parametr: pole $bind - seznam parametrů vazby
- Návratová hodnota: řetězec s obsahem dat


### OpenDB :: SqlFetchRow($prikaz, $bind = Array) ###

```php
$string = $db->SqlFetchRow($sql_command);
```

zkombinujte metodu Sql a FetchRow do jednoho kroku a vrátí hash dat
- Parametr: string $command - a sql příkaz
- Parametr: pole $bind - seznam parametrů vazby
- Návratová hodnota: výsledek pole Data hash nebo prázdné pole


### OpenDB :: SqlFetchArray($prikaz, $bind = pole, $limit = 0, $offset = 1) ###

```php
$array = $db->SqlFetchArray($sql_command,$limit=0);
```

zkombinujte metodu Sql a FetchRow do jednoho kroku a vrátí datové pole
- Parametr: řetězec $sql_command - a příkaz sql
- Parametr: pole $bind - seznam parametrů vazby
- Parametr: integer $limit - max. počet výsledků, 0= bez omezení
- Parametr: integer $offset - počáteční pozice ve výběru, výchozí=1
- Návratová hodnota: pole s obsahem dat


### OpenDB :: SqlFetchKeys($prikaz, $klíč, $bind = Array) ###

```php
$error = $db->SqlFetchKeys($sql_command,$key);
```

zkombinujte metodu Sql a FetchRow do jednoho kroku a vrátí datové pole
- Parametr: řetězec $sql_command - a příkaz sql
- Návratová hodnota: pole s obsahem dat


### OpenDB :: SqlFetchList($prikaz, $bind = Array, $limit = 0, $sep = ', ', $subsep = '-') ###

```php
$result = $db->SqlFetchList($prikaz,$limit,$sep,$bind);
```

Připraví seznam hodnot z výběrového dotazu do jednoho sloupce
- Parametr: řetězec $sql_command - a příkaz sql
- Parametr: pole $bind
- Parametr: int $limit výchozí 0
- Parametr: string $sep - oddělovač polí
- Parametr: string $subsep - oddělovač podsouborů
- Návratová hodnota: řetězec seznamu výsledků nebo prázdný řetězec (také v případě chyby)



## Třída VisTab - vizualizace databázové entity

### Úvod

Třída VisTab slouží pro přístup a vizualizaci databázové entity. Samotná vizualizace se skládá ze tří různých stránek. Základní stránka je **seznam záznamů** v tabulkové formě. Další stránka je formulář, pomocí kterého můžeme omezit viditelný rozsah záznamů, též nazýván jako **parametrický formulář** či filtr. Ze seznamu nalezených záznamů lze pak přejít do **detailu záznamu**, kdy jsou zobrazeny podrobnější informace, či celý záznam, který by se do základního uspořádání nevešel. Základní funkcionalita umí toto:

- seznamem lze listovat po stránkách. Při přechodu do detailu lze listovat po záznamech a při listingu v detailu se pomocí tlačítka zpět dá dostat na příslušnou stránku seznamu. Na každé stránce je zobrazen celkový počet záznamů.
- klikem na záhlaví příslušného sloupce je seznam seřazen podle tohoto sloupce vzestupně, dalším klikem sestupně. Listování po stránkách a v detailu se přizpůsobí zvolenému řazení
- je přítomen parametrický formulář a po jeho vyvolání lze zadat podmínku omezující množinu záznamů. Podmínka se zachovává při návratu do tohoto formuláře a je viditelná v seznamu a detailu záznamu.

Protože reálné situace bývají často dosti komplexní povahy, rozebereme postupně možnosti využití vlastností třídy VisTab.
Základní volání je ve tvaru:

```php
$t= new Vistab([parametry], $[databázový wrapper]);
```

Konstruktoru se předává pole parametrů a odkaz na otevřený databázový wrapper. Databázový wrapper je objekt, který realizuje komunikaci s některým typem SQL databáze. Viz popis třídy OpenDb a jejích potomků.
V nejjednodušším případě je entita definována jako tabulka databáze.

```php
$t= new Vistab(['table'=>'TABULKA'], new OpenDB_SQLite(CON_DB));
```

Pro složitější případy lze využít pohled na data SQL příkazem select. Následující příklad je vzat z reálné situace.

```php
$tt= new Vistab(
  ['header'=>' ',
   'sCmd'=>"select id_sog, sog_sd_ok, sog_vl_zn, sog_evidovano, sog_resitel, sog_termin, sog_lokalizace,".
           "sog_je_neni_sd_kod, sog_stav ".
           "from aplgeol.sog_sd_sog_vw", 
   'cCmd'=>'select count(*) as pocet from aplgeol.sog_sd_sog_vw',
   'pragma'=>[['name'=> 'SOG_VL_ZN',     'comment' => 'Vlastní značka'],
              ['name'=> 'SOG_EVIDOVANO', 'comment' => 'Evidováno SOG'],
              ['name'=> 'SOG_RESITEL',   'comment' => 'Řešitel'],
              ['name'=> 'SOG_TERMIN',    'comment' => 'Termín'],
              ['name'=> 'SOG_LOKALIZACE','comment' => 'Lokalizace'],
              ['name'=> 'SOG_JE_NENI_SD_KOD','comment' => 'Je/není SD'],
              ['name'=> 'SOG_STAV',      'comment' => 'Stav'],
              ['name'=> 'SOG_SD_OK','comment' => 'GIS zpracováno']
              ],
   'dCmd'=>'select * from aplgeol.sog_sd_sog_vw '],$db); 


```
Zde :
- **sCmd** je SQL select příkaz, který definuje data na stránce seznamu záznamů
- **cCmd** je SQL select příkaz, který zjistí celkový počet záznamů
- **dCmd** je SQL select příkaz použitý při zavolání detailu
- **pragma** - obsahuje obecně seřazené pole údajů o atributech entity, každý atribut je popsán několika znaky. 

Zároveň mohou select příkazy představovat dotazy do již připraveného pohledu nad složitěji strukturovanými daty. To je vhodné například v situaci, kdy hlavní zobrazovaná tabulka obsahuje pouze kódy údajů, které ale v seznamu chceme mít vyjádřeny jejich popisy. Například když záznam obsahuje kód obce, ale v seznamu chceme vidět její skutečný název, a pak také očekáváme řazení podle skutečného názvu, ne nikoliv podle interního kódu.

Pragma zde obsahuje informace o zobrazované entitě. Ve výchozím volání je získáno z datového katalogu příslušné databáze.  Některé údaje jsou pak užitečné pro konstrukci náhledu na data. Zde bylo zadáno přímo podle požadavků na obsah a popis sloupců. Pokud není pragma předáno jako vstupní parametr, generuje se výchozím voláním 

**$db->Pragma("table_info('TABLE_NAME'"))**.

Pragma obsahuje seznam hashů vztahujících se k jednotlivým atributům entity ['name','comment','default','datalength','precision','datename','pk']. Tento přístup sjednocuje nakládání s těmito údaji napříč různými databázovými řešeními.
Více u popisu třídy OpenDB.


### Stránka se seznamem, stránka s detailem a parametrický formulář

V reálných situacích je třeba více ovlivnit chování základní třídy, když se zobrazuje seznam záznamů. Například je tu požadavek, aby řádky byly různě obarveny na základě hodnoty některého atributu. Nebo je požadována náhrada textového údaje ikonou. Dalším častým úkolem je vytvoření funkčních odkazů nad různými atributy.

Reálné řešení spočívá ve vytvoření potomka třídy Vistab s nahrazenými metodami:

```php
class SOGtab extends Vistab{

  function modify_row_before_print($row){
    ...
  }

  function detail(){
    ...
  }

  ....
} 
```

Zde je funkce **modify_row_before_print()** volána s datovým obsahem získaného řádku a předpokládá se, že příslušný řádek je vrácen v podobě, která se následně vytiskne. To dává široký prostor pro řešení rozličných vzhledových a funkčních požadavků na seznam.

Většinou také dochází k nahrazení strojově vytvořeného detailu vlastní stránkou. Využijeme další vlastnosti třídy Vistab a nahradíme její metodu **detail()** vlastním kódem. ¨

Opět se jedná o reálnou situaci ze stejné aplikace:


```php
class SOGtab extends Vistab{

  function modify_row_before_print($row){
    if ($row['SOG_JE_NENI_SD_KOD']=='Y') $row['SOG_JE_NENI_SD_KOD']=tg('span','class="text-success"',bt_icon('check-circle'));
    if ($row['SOG_JE_NENI_SD_KOD']=='N') $row['SOG_JE_NENI_SD_KOD']=tg('span','class="text-danger"',bt_icon('dash-circle'));
    if ($row['SOG_JE_NENI_SD_KOD']=='X') $row['SOG_JE_NENI_SD_KOD']='';
    if ($row['SOG_STAV']=='R') $row['SOG_STAV']=tg('span','class="text-primary"',bt_icon('alarm')); /* R=rozpracováno=budík */
    if ($row['SOG_STAV']=='E') $row['SOG_STAV']=tg('span','class="text-success"',bt_icon('check-circle')); /* E=exportováno=zelená fajfka */
    if ($row['SOG_SD_OK']=='Y') $row['SOG_SD_OK']=tg('span','class="text-success"',bt_icon('check-circle'));
    if ($row['SOG_SD_OK']=='N') $row['SOG_SD_OK']=tg('span','class="text-warning"',bt_icon('dash-circle'));
    return $row;
  }

  function detail($context,$custom=''){
    $dprikaz=$this->genfilter($this->dCmd,false);
    $zaznam=$this->db->SqlFetchArray($dprikaz,[],1,getpar('_ofs',1)); /* skutecne zaznamy na zaklade podminky */
    $custom=ta('div',sog_detail($zaznam[0],$this->db,$context)); /* zavolani skutecneho obsahu */
    parent::detail($context,$custom);            /* funkcionalita listovani a navratu do seznamu, manipulace s $dprikaz se nepouzije */
  }

} 
```
Nově vytvořená metoda **detail** má za úkol provést naplnění obsahu stránky s detailem do řetězce **$custom** a následně z důvodu listování, řazení a filtrování volat původní metodu.

To je rozdíl oproti přepisu metody **form_param()** , kde se počítá s nahrazením původního formuláře. Na této stránce není listování a při změně parametrů se počítá s tím, že jsme opět na začátku nově vygenerovaného seznamu. Metodat **form_param()** obsahuje generování parametrického formuláře. Ten musí mít určitou strukturu. 
Zde je opět reálný příklad, respektive jeho část:

```php
function form_param($context){
  function parac($par,$op='like'){
    return combo('',$par.'_par',[
    'like'=>'obsahuje',
    'begins'=>'začíná',
    '='=>'='],
    getpar($par.'_par')?getpar($par.'_par'):$op);
  }
  
  $b=[
      ['Kód dokumentace',parac('CBOD','='),textfield('','CBOD',15,30,getpar('CBOD'))],
      ['Číslo výbrusu',parac('VYBRUS','='),textfield('','VYBRUS',15,30,getpar('VYBRUS'))],
      ['Mapový list',parac('CMAPA','='),
       combo('','CMAPA', to_hash(
        "select distinct cmapa, cmapa as c ".
        "from dat_dkb.v_vybrusy ".
        "order by cmapa asc", $this->db),getpar('CMAPA')),  
      ],
        ... 
        ,
      [nbsp(1),ahref('?item='.getpar('item'),'Storno','class="btn btn-secondary mt-4"'), /* storno nesmí být button */
       submit('_sg','Vyhledej','btn btn-primary mt-4')]
     ];

  htpr(tg('form','method="post" action="?'.$context.'&_o='.getpar('_o').'"',
           bt_container(['col-4','col-2','col-6'],$b,'row m-1'))); 

}
```
Při tvorbě vlastního parametrického formuláře je nutné si uvědomit, jak mechanismus převodu vstupních údajů na SQL podmínku where pracuje. Důležité je dodržet páry ATRIBUT a ATRIBUT_par , které určují hodnotu hledaného parametru a jeho relační význam. Také je nutné obeslat formulář tlačítkem **_sg** . U koomplexních aplikací je nezbytné je i udržení "kontextu" **$context** (to se zařídí snadno včeleněním kódu **para('item',getpar('item'))**). Po odeslání formuláře by měla metoda **route()** naší třídy zachytit přítomnost parametru **_sq** a vyvolat metodu **genwhere()**. Tato metoda pro každou předanou dvojici relační operátor a hodnota - ATRIBUT_par a ATRIBUT - zjišťuje, zda je hodnota ATRIBUT nenulová. Pokud je nulová, tedy uživatel nic u tohoto atributu neuvedl, nic se nestane . Pokud je nenulová, přidá se podmínka ve tvaru [jméno atributu] [relační operátor] [hodnota atributu]. Jednotlivé podmínky se spojují pomocí operátoru and. Sestavení SQL dotazu separuje již existující klauze WHERE a ORDER_BY . Případná již existující klauzule WHERE je doplněna o zadaný filtr. U složitých a komplexních dotazů SQL užívajících například klauzuli UNION, HAVING atd. to nebude fungovat a v takovém případě je potřeba vytvořit pohled na data (VIEW) v databázi a v PHP skriptu pracovat s tímto pohledem.

### Metoda route()

Třída Vistab se spouští metodou route(). Jako parametr se předává aktuální kontext, který si doplní do všech ovládacích odkazů (GET o POST požadavky) a tím si tento kontext udržuje. To je výhodné například při jejím použití v rámci aplikace postavené na systému správy obsahu (třída Cm). Tam stačí udržovat parametr item, značící číslo obsahové stránky aplikace.


```php
$tt->route("&item=".getpar('item'));
```
Činnost metody route není potřeba nahrazovat, pokud se nebude přidávat další speciální funkcionalita. Její kód je přímočarý:

```php
function route($context){
  if (getpar('_se')){
    htpr($this->form_param($context));
  }elseif (getpar('_det')){
    htpr($this->detail($context));
  }else{
    htpr($this->lister($context));
  }
}
```
Při nahrazení této metody by mělo dojít pouze k ošetření nové funkcionality. Například větví s testem splnění jiného parametru a jinak voláním **parent::route()** . 

### Mechanismus generování filtru a generování řazení

Po odeslání parametrického formuláře na server skript sestavuje nový parametr **_flt**, který si pak předává v komprimované podobě mezi stránkami při listování a odskoku do detailu. Rovněž si předává parametry **_o** (řazení) , **_ofs** (offset, aktuální pozice) a také vše, co je ve vstupním parametru **$context** . Na základě parametrů je pak skript schopen sestavit aktuální SQL podmínku pro výběr záznamů (metoda **genwhere(..)** volaná v případě potřeby v konstruktoru ), následně v metodě **genfilter(..)** doplní podmínku do klauzule **WHERE** a připojí klauzuli **ORDER BY** . Tento machanismus lze rozšířit v případě potřeby - například pokud koncept parametrického formuláře nevyhovuje a je třeba do něj doplnit další funkcionality. To lze udělat nejlépe na konci konstruktoru a následně doplnit svoji metodu na zpracování dalších parametrů z jinak koncipovaného vyhledávacího formuláře:

```php

function __construct($param,$db){
  ...

 $this->getfilter(); /* rozbaleni filtru podle parametru _flt */ 
 $whr=$this->genwhere();  /* generovani  podminky where - standard */
 $whr=$this->genwhere_special($whr); /* dalsi doplneni podminky where pro pridane funkcionality */
 setpar('_whr',$whr); /* interne se parametr odkazuje pomoci getpar('_whr') */
 
} /* konec konstruktoru zdedene tridy Vistab */

...
function genwhere_special($whr){
    $and=($whr==''?'':' and ');
    if (getpar('NAZEV')){
      $whr.=$and."upper(nazev) like upper('%".getpar('NAZEV')."%') ";
    }
    $and=($whr==''?'':' and ');
    if (getpar('OKRES')){
      $whr.=$and."id in (select id from dat_lok.lok_bod,dat_kod.kod_all_okres ". 
       "where kod_all_okres.lau1_kod='".getpar('OKRES')."' ".
       "and sde.st_relate(kod_all_okres.shape,lok_bod.shape,'T********')=1 ".
       "union select id from dat_lok.lok_pol,dat_kod.kod_all_okres ". 
       "where kod_all_okres.lau1_kod='".getpar('OKRES')."' ".
       "and sde.st_relate(kod_all_okres.shape,lok_pol.shape,'T********')=1".
       ")";
    }
    ....

```

### Mód ladění

Vyskytnou-li se problémy, lze nastavit třídě Vistab příznak **debug_mode** a skript bude vypisovat do ladící zóny informace o tom, jak sestavil konkrétní SQL dotaz a s jakým záznamem pracuje v detailu.


## Třída EdiTab - editace databázové entity

Třída EdiTab, která rozšiřuje třídu VisTab o metody modifikace, má metodu route postavenou takto:


```php
function route($context){  
    if (getpar('_se')){
      htpr($this->form_param($context));
    }elseif (getpar('_det')){
      $show_list=false;      
      if (getpar('_upd')){
        $this->update();
      }elseif (getpar('_del')){
        $show_list=$this->delete();
      }elseif (getpar('_ins')){
        $this->insert(); /* insert is always followed by detail */
      }elseif (getpar('_blank')){
        $this->mode='I';
      }
      if ($show_list){
        htpr($this->lister($context));
      }else{
        htpr($this->detail($context)); /* continue with detail form (also after succesfull insert) */
      }
    }elseif(getpar('_st')){
      /* it is necessary to clear the param content */
      setpar('_whr','');
      htpr(bt_alert('Podmínka nulována.'));
      htpr($this->lister($context));
    }else{
      htpr($this->lister($context));
    }
  }
```

Základní využití třídy pro editaci záznamů v databázi Editab je jen rozšířením VistTab s tím, že formulář s detailem záznam umožňuje jeho uložení, odstranění a je přítomno tlačítko pro založení nového záznamu.


```php
$t= new Editab(['table'=>'TABULKA'], new OpenDB_SQLite(CON_DB));
```
Aby editace fungovala, musí být zaručena jednoznačnost jednotlivých záznamů pomocí existence primárního klíče.
V základním stavu umí třída provést příslušné dotazy do databázového katalogu a zjistit si, jak vypadá primární klíč a
generovat základní formulář, a příslušné SQL dotazy na modifikaci dat s využitím informací buď z databáze, nebo z předaných parametrů, analogicky s VisTab .

Při reálném návrhu editačního formuláře je téměř vždy potřeba přesněji specifikovat jednotlivé editační SQL příkazy, nebo lépe nahradit jednotlivé výchozí metody **detail_form()**, **insert()**, **update()** a **delete()** vlastním kódem. Obojí přístup je možné kombinovat.



```php

```

## Třída Cm - systém správy obsahu pro komplexní aplikaci

### Úvod, stromová struktura uložená v databázi

Třída **Cm** navazuje na modul **M5** a realizuje systém správy obsahu, který je uložen v databázi. Umožňuje tak vytvářet komplexní aplikace. Komplexní aplikace navržená s využitím třídy **Cm** obsahuje stromové menu, které dělí aplikaci do jednotlivých stránek. Každá stránka má nastavitelná přístupová práva pro jednotlivé uživatele či role. V rámci stránky jsou obsaženy položky různých typů, ke kterým jsou opět přidělena práva na základě uživatelů či rolí. Položky mohou být různého typu, pro budování funkční aplikace je zásadní typ umožňující vkládat php skripty.

Aktuální funkcionalita, obsah aplikace a její přístupnost jednotlivým uživatelům je tak řízena z databáze. Samotná správa aplikace a řešení přístupů či rolí může být prováděno administrátorem bez zásahu do kódu aplikace. Zvolená dekompozice aplikace umožnuje rozdělit práci mezi více vývojářů a řídit proces vývoje kódu a nahrazování jednotlivých částí aplikace.

### Doporučené použití třídy Cm

Pro nasazení a využití této funkcionality je vhodné aplikaci umístit do samostatné složky s podsložkami, jak je popsáno u popisu modulu M5. V této složce bude řídící aplikační skript **d.php** spolu s definičním skriptem **ini.php** . Je vhodné využít mod_access a mod_rewrite a zajistit, aby d.php byl v této složce výchozím spuštěným skriptem. Řídící skript musí obsahovat instruktci pro PHP session_start(),  využije modul M5 a definuje jejích potomka App, u kterého zavolá staticky metodu **skeleton()**. V **ini.php** souboru je pak šablona komplexní aplikace, která obsahuje prvky k nahrazení za levé menu, drobečkovou navigaci, atd. (#EDIT_LINK#, #BREADCRUMB#, #SIDEBAR# ) dynamickými metodami třídy Cm. Na základě aplikačního prostředí může eventuálně řešit i přihlašovací formulář a situaci po odhlášení se od aplikace (v příkladu není uvedeno). Obsah skriptu d.php je přibližně následující:

```php
session_start();
include_once '../../lib/mlib.php';    /* implicitni include ini.php  */
include_once '../../lib/mbt.php'; /* Bootstrap components */

class App extends M5{
  static $db;
  public static $dbconnect;
  public static $cms; 
  
  static function skeleton($path=''){
    $default_item=1;      /* vychozi polozka nacitana v CMS */
    
    self::route();

    self::$db=new OpenDB_SQLite(M5_CONNECT_CMS);
    $item=getpar('item')?getpar('item'):$default_item;
    
    self::$cms= new Cm("APP",self::$db,true,false);
    
    if (isset($_SESSION['uzivatel']) && $_SESSION['uzivatel']!=''){
      self::$cms->generateMenuTree(true) ;
      $rootnode=self::$cms->rootNode($item);
      if (getpar('ed')!='') $_SESSION['editace']=getpar('ed');
      self::set('htptemp',str_replace('#SIDEBAR#',
                     self::$cms->sidebar(self::$cms->getTree(),$item,$rootnode,self::$cms,''),
                     self::get('htptemp')));
      $navbar=str_replace('#BREADCRUMB#', self::$cms->breadCrumb($item), $navbar); 
      $navbar=str_replace('#EDIT_LINK#', self::$cms->editLink($item), $navbar);
      
      self::$cms->folder($item); /* folder calls the application parts */     
    }else{
      login_form();
      self::set('htptemp',preg_replace('/#SIDEBAR#/','', self::get('htptemp')));
    }
    
    self::set('htptemp',str_replace('#UPPERBAR#',
      isset($_SESSION['uzivatel'])&&$_SESSION['uzivatel']!=''?$navbar:'',self::get('htptemp')));
    self::set('htptemp',str_replace('#HEADERADD#', '',self::get('htptemp'))); 
       
    self::$db->Close();
    App::done();
  }      
} 

App::init();
App::set('header','CMS test');
App::set('debug',true);
App::set('htptemp',$GLOBALS['htptemp']);
App::skeleton('../../'); /* sigleton template, skeleton() method is called */

```
Zároveň musí být v databázovém schématu (Oracle nebo SQLite) připravena struktura pro uložení samotného obsahu aplikace - menu, odkazy na vkládané skripty, seznamy účtů aplikace, další informace jako návody, odkazy na grafické soubory, atd.

V příkladu výše je volání konstruktoru třídy **Cm**. Jako první parametr se předává prefix názvu sady databázových tabulek, které budou odkazovány v databázovém schématu (připojení wrapperu je druhý parametr). V příkladu je to "APP", takže třída očekává ve schématu entity:


```
APP_LOG_TAB    -- entita logování událostí aplikace
APP_POLOZKY    -- entita položek obsahu na jednotlivých stránkách
APP_PRAVA      -- entita evidovaných práv ke stránkám a položkám
APP_SKUP       -- entita seznamu skupin / rolí v aplikaci
APP_STROM      -- entita stromové struktury stránek aplikace
APP_UNASTAV    -- entita individuálních nastavení uživatele
APP_USKUP      -- entita příslušnosti uživatele ke skupině / roli
APP_UZIV       -- entita seznamu uživatelských účtů pro aplikaci

```

Pro generování entity v konkrétní databázové instanci je připraven script **create.sql** ve složce s aplikačním vzorem. Zvolený systém s prefixem umožnuje sdílet v jednom schématu více různých aplikací.

Hlavní řídící skript obsahuje volání metody **folder()** . Tato metoda vizualizuje aktuální složku aplikace podle předaného parametru **item**. Hodnota parametru item je přirozené číslo. Pro správnou funkci všech formulářů, aktivních prvků musí být toto číslo předáno při každém POST či GET v dílčím skriptu volaném z aplikace. Dílčí skripty se nemusí o nic dalšího starat, jsou zavolány metodou **App::$cms->folder()**, která postupně prochází obsah stránky a podle typu položky provede buď include_once příkaz (pro 'inc' typ položky), či eval příkaz (pro 'app' typ položky). Pasivní typy obsahu z databáze pouze vkládá ('txt' typ položky). Činí tak až na základě ověření přístupových práv.  Výchozí aplikace obsahuje již administrační složku umožňující editaci uživatelů a skupin. Celý obsah aplikace je pak možno vytvořit pomocí ní samotné, na základě vhodně nastavených práv pro správcovský účet.

### Další užitečné skripty ve výchozí aplikaci

Výchozí aplikace řízená třídou Cm obsahuje několik obecných skriptů, které jsou v zásadě vhodné pro správce každé aplikace. Mimo editace uživatelů a skupin je to přehledná tabulka práv, prohlížení aplikačního logu. SQL workbench na dotazy do databáze, skript realizující parsovaný výstup z phpinfo() funkce a další.

Skript programátorské dokumentace využívá vlastnosti tzv. reflexní třídy v PHP a generuje přehled veškeré dokumentace ve zdrojových kódech aplikace, pokud dodržují normu pro PHP dokumentator. Samotné knihovny M5 toto splňují a proto je možno zobrazit popisy volání parametrů jednotlivých metod jednotlivých tříd a globálních funkcí. Popisy jsou ve zdrojovém kódu v anglickém jazyce, nebo v něčem co si autor myslel, že anglický jazyk je.

Aplikace může rovněž využít evidenci uživatelských nastavení a pro uživatele lze do aplikace zařadit skript, kterými si toto nastavení mohou měnit.

### Interakce aplikačních skriptů se třídou Cm

Aplikační skripty vytvářené komplexní aplikace by měly být umístěny ve složce **php/** a jsou pomocí výše popsaného mechanismu inkludovány při běhu metody **App::$cms->folder()** . V samotné databázi se jen jméno inkludovaného souboru. Skript musí při GET a POST requestech předat aktuální hodnotu parametru item (nejlépe pomocí **getpar('item')** ). Skript dále může využít aktuální informace o přihlášeném uživateli a podle toho přizpůsobit svou funkcionalitu. Třída **Cm** používá PHP session, ve které udržuje jméno uživatelského účtu  **$_SESSION['uzivatel']** a může testovat, zde tento uživatel je v určité skupině pomocí **App::$cms->is_in_group('SKUPINA'))** , dále je k dispozici skript pro získání hodnoty daného atributu individuálního nastavení uživatele **App::$cms->get_user_setting('kod_atributu')** .


