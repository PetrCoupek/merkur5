## Třída VisTab - vizualizace databázové entity

### Úvod

Třída VisTab slouží pro přístup a vizualizaci databázové entity. Samotná vizualizace se skládá ze tří různých stránek. Základní stránka je **seznam záznamů** v tabulkové formě. Další stránka je formulář, pomocí kterého můžeme omezit viditelný rozsah záznamů, též nazýván jako **parametrický formulář** či filtr. Ze seznamu nalezených záznamů lze pak přejít do **detailu záznamu**, kdy jsou zobrazeny podrobnější informace, či celý záznam, který by se do základního uspořádání nevešel. Základní funkcionalita umí toto:

- seznamem lze listovat po stránkách. Při přechodu do detailu lze listovat po záznamech a při listingu v detailu se pomocí tlačítka zpět dá dostat na přislušnou stránku seznamu. Neustále je zobrazen celkový počet záznamů.
- klikem na záhlaví příslušného sloupce je seznam seřazen podle tohoto sloupce vzestupně, dalším klikem sestupně. Listování po stránkách a v detailu se přizpůsobí zvolenému řazení
- je přítomen parametrický formulář a po jeho vyvolání lze zadat podmínku omezující množinu záznamů. Podmínka se zachovává při návratu do tohoto formuláře a je viditelná v seznamu a detalu záznamu.

Protože reálné situace bývají často dosti komplexní povahy, rozebereme postupně možnosti využití vlastností třídy VisTab.
Základní volání je ve tvaru:

```php
$t= new Vistab("parametry", "databázový wrapper");
```

Konstruktoru se předává pole parametrů a odkaz na otevřený databázový wrapper.
V nejjednoduším případě je entita definována jako tabulka databáze.

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

Pragma může být získáno z datového katalogu příslušné databáze a některé jeho údaje jsou pak užitečné pro konstrukci náhledu na data. V tomto případě bylo zadáno přímo a to tak, aby výsledná tabulka splňovala přání zadavatele.


### Stránka se seznamem, stránka s detailem a parametrický formulář

V reálných situacích je třeba více ovlivnit chování základní třídy, když se zobrazuje seznam záznamů. Například je tu požadavek, aby řádky byly různě obarveny na základě hodnoty některého atributu. Nebo je požadavána náhrada textového údaje ikonou. Dalším častým úkolem je vytvoření funkčních odkazů nad různými atributy.

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

Zde je funkce **modify_row_before_print()** volána s datovým obsahem získaného řádku a předpokládá se, že příslušný řádek je vrácen v podobě, která se následně vytiskne. To dává široký prostor pro řešení rozličných vzhledových a funkčníh požadavků na seznam.

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
    parent::detail($context,$custom);            /* funkcionalita listovani a navratu do seznamu, manipulace s daprikazem se nepouzije */
  }

} 
```
Nově vytořená metoda **detail** má za úkol provést naplění obsahu stránky s detailem do řetězce **$custom** a následně z důvodu listování, řazení a filtrování volat původní metodu.

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
Důležité je dodržet páry ATRIBUT a ATRIBUT_par , které určují hodnotu hladaného parametru a jeho relační význam. Také je nutné obeslat formulář tlačítkem **_sg** . Nezbytné je i udržení kontektu **$context** .

### Metoda route()

Třída Vistab se spouští metodou route(). Jako parametr se předává aktuální kontext, který si doplní do všech ovládacích odkazů (GET o POST požadavky) a tím si tento kontext udržuje. To je výhoné například při jejím použití v rámci aplikace postavené na systému správy obsahu (třída Cm). Tam stačí udržovat parametr item, zančící číslo obsahové stránky aplikace.


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

Základní využití třídy pro editaci záznamů v databázi Editab je jen rozšířením VistTab s tím, že formulář s detailem záznam umožňuje jeho uložení, odstranění a je přitomno tlačítko pro založení nového záznamu.


```php
$t= new Editab(['table'=>'TABULKA'], new OpenDB_SQLite(CON_DB));
```
Aby editace fungovala, musí být zaručena jednoznačnost jednotlivých záznamů pomocí existence primárního klíče.
V základním stavu umí třída provést příslušné dotazy do databázového katalogu a zjistit si, jak vypadá primární klíč a
generovat základní formulář, a příslušné SQL dotazy na modifikaci dat s využitím informací buď z databáze, nebo z podtrčených parametrů, analogicky s VisTab .

Při reálném návrhu editačního formuláře je téměř vždy potřeba přesněji specifikovat jednotlivé editační SQL příkazy, nebo lépe nahradit jednotlivé výchozí metody **detail_form()**, **insert()**, **update()** a **delete()** vlastním kódem. Obojí přístup je možné kombinovat.



```php

```
