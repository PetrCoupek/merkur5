<?php
/** oracle export - funkcionalita exportu databazovych tabulek
 *  @author Petr Coupek
 *  @date 25.06.2019
 *  03.07.2019
 *  16.07.2019 - upravy,opravy
 *  28.11.2023 - upravy,opravy
 *  29.01.2024 - upravy pro m5
 *  08.02.2024 - upravy
 *  09.02.2024 - upravy
 *  26.02.2024 - serazeni exportniho souboru podle primarniho klice
 *  18.09.2026 - schopnost exportu jen některých sloupců, ale zaznamenání jména cílové tabulky a typy atributů
 */

class Ora_exp {
  var $kam, 
      $oddelovac, 
      $verbose, 
      $schema, 
      $db;
  private $tabulka, 
      $column, 
      $comment, 
      $popcomment, 
      $nullable, 
      $datalength, 
      $datatype, 
      $dataprecision, 
      $pomcomment, 
      $tablecomment, 
      $prim, 
      $primk, 
      $sprikaz, 
      $cprikaz,
      $no_schema;

/**
 * @param string $napojeni  - connect string do databáze Oracle
 * @param array $pars - rezerva pro další parametry ovlivňující chování exportu 
 *              'no_schema'== 1 : pro vystup do .csv a .inf souboru nepoužívej schema.tabulka, ale jen tabulka
 */ 
function __construct($napojeni,$pars=[]){
  $this->kam = 'export/';
  $this->oddelovac = ';';
  $this->verbose = true;
  $this->schema = '';
  if (preg_match('/^dsn=(.+);uid=(.+);pwd=(.+)$/i',$napojeni,$m)){
    $this->schema = strtoupper($m[2]);
  }
  if (isset($pars['no_schema']) && $pars['no_schema']) {
    $this->no_schema = true; 
  } else {
    $this->no_schema = false;
  }    
  $this->db = new OpenDB_Oracle($napojeni);
}

function __destruct(){
  $this->db->Close();
}

function set_export_dir($kam){
  $this->kam = $kam;
}

/** provede export tabulky ve schematu
 * @param string $tabulka - jméno entity/tabulky
 * @param string $where - omezení exportovaných dat
 * @param string $kam - případné označení schématu - export pak jde do douboru kam.tabulka.csv
 */ 
function exportuj($tabulka, $where='', $kam=''){
  tick();
  if ($tabulka != '') {
    $this->tabulka = strtoupper($tabulka); /* načtení struktury tabulky do polí*/
    $this->column =  [];              /* pole uchovává názvy sloupců DB column_name*/
    $this->comment = [];              /* pole uchovává popisky sloupců DB comments*/
    $this->pomcomment = [];           /* pole uchovává popisky sloupců DB comments*/
    $this->nullable = [];             /* pole uchovává zda může být sloupec null (not/yes) */
    $this->datalength = [];           /* pole uchovává max. délky znaků daného sloupce */
    $this->datatype = [];             /* pole datovych typu polozek - dulezite hlavne pro datumy */
    $this->dataprecision = [];        /* pole presnosti - dulezite pro ciselne typy */
    /* pokud má sloupec ID, je KOD změněn na ID ve funkci init()  */
    
    if (preg_match('/^(\w+)\.(\w+)$/',$this->tabulka,$m)){  
      /* tabulka uvedena jako schema.tabulka */
	    $nazev_tabulky = $m[2];
      $dodatek = " and owner='$m[1]'"; 
      $dodatek2 = " and all_constraints.owner='$m[1]'";
	  } else {
	    /* tabulka s prostym nazvem */
	    $nazev_tabulky = $this->tabulka;
      if ($this->schema != ''){ 
        $dodatek=" and owner = '".$this->schema."' ";
      }else{
        $dodatek2 = '';
      }   
      $dodatek2 = '';
    }
    /* komentar k tabulce - pokud je */
    $this->db->Sql(
      "select comments ".
      "from all_tab_comments ".
      "where table_name = '".$nazev_tabulky."'".$dodatek);
    if ($this->db->FetchRow()){
      $this->tablecomment=str_replace(["\r", "\n"], '',(string)$this->db->Data('COMMENTS'));
    }else{
      $this->tablecomment='';
    }
    
    /* komentare k polim - v tomto pohledu nejsou pole nijak serazena - orientace jen podle nazvu atributu/pole */	
    $this->db->Sql(
      "select comments,column_name ".
      "from all_col_comments ".
      "where table_name='".$nazev_tabulky."'".
      $dodatek);
    while ($this->db->FetchRow()){
      $komentar=(string)$this->db->Data('COMMENTS');
      $komentar=str_replace(["\r", "\n"], '', $komentar);
      $atribut=$this->db->Data('COLUMN_NAME');  
		  $this->pomcomment[$atribut]=$komentar;
	  }
	  /* inicializace pole nullable, inicializace pole datalength */ 
	  $this->db->Sql(
      "select column_name,nullable,data_length,data_type,data_precision,data_scale ".
      "from all_tab_columns ".
      "where table_name='".$nazev_tabulky."'".$dodatek." order by column_id asc");
      
    $i=0; 
    $seznampoli=''; 
    $seznamsloupcu='';
    while ($this->db->FetchRow()){
      $this->column[$i]=$this->db->Data('COLUMN_NAME');
      $this->comment[$i]=$this->pomcomment[$this->db->Data('COLUMN_NAME')]; /* doplneni komentaru k atributu */
		  $this->nullable[$i]=$this->db->Data('NULLABLE');
		  $this->datalength[$i]=$this->db->Data('DATA_LENGTH');
		  $this->datatype[$i]=$this->db->Data('DATA_TYPE');//echo $this->datatype[$i]."\n";
      /* pro typ date a long se neuvadi delka - pak do vystupu */
      if ($this->datatype[$i]=='DATE' || $this->datatype[$i]=='LONG') $this->datalength[$i]='';
		  $this->dataprecision[$i]=($this->db->Data('DATA_SCALE')==0)?'0':$this->db->Data('DATA_PRECISION');
      $seznamsloupcu.=($seznamsloupcu==''?'':',').$this->db->Data('COLUMN_NAME');
		  if ($this->datatype[$i]=='DATE'){
		    $pole="to_char(".$this->db->Data('COLUMN_NAME').",'YYYY-MM-DD HH24:MI:SS') as ".$this->db->Data('COLUMN_NAME');
      }elseif($this->datatype[$i]=='ST_GEOMETRY'){
        $pole="sde.st_astext(".$this->db->Data('COLUMN_NAME').") as ".$this->db->Data('COLUMN_NAME');  
		  }else{
		    $pole=$this->db->Data('COLUMN_NAME');
		  }  
		  $seznampoli.=($seznampoli==''?'':',').$pole;
  	  $i++;
	  }
    if ($seznampoli==''){
      $this->hlaseni("Tabulka $tabulka neexistuje");
      return 0;
    }
    /* primarni klic do tabulky */
    $this->db->Sql(
      "select column_name from all_constraints, all_cons_columns ".
      "where all_cons_columns.constraint_name=all_constraints.constraint_name ".
      "and all_constraints.table_name=upper('$nazev_tabulky') ".
      "and all_constraints.constraint_type='P'".$dodatek2);
    $this->prim='';
    $i=0;
    while ($this->db->FetchRow()){
      $this->prim.=($this->prim!=''?',':'').$this->db->Data('COLUMN_NAME');
      $this->primk[$i]=$this->db->Data('COLUMN_NAME');
      $i++;
    }

    if ($this->prim=='row_id'){
      $seznampoli.=',rowidtochar(rowid) as row_id';
      array_push($this->column,'ROW_ID');
    }  
    $this->sprikaz="select $seznampoli from $tabulka ".
                   ($where==''?'':"where $where".($pl) ).
                   ($this->prim==''?'':"order by $this->prim");
    $this->cprikaz="select count(*) as KOLIKZ from ".$tabulka;       
  }
  /**/
  //echo $this->no_schema."\n";
  //echo $nazev_tabulky."\n";
  //echo $tabulka."\n";


  $jms=($kam==''?($this->kam):$kam).($this->no_schema?$nazev_tabulky:$tabulka).'.csv';
  $jmst=($kam==''?($this->kam):$kam).($this->no_schema?$nazev_tabulky:$tabulka).'.inf';
  $f=@fopen($jms,"w");
  
  if ($f!==false){ 
    /* hlavicka - netiskne se seznampoli jako do selectu, ale seznamsloupcu */
    fwrite($f,str_replace(',',';',$seznamsloupcu."\n"));
    $n=0;
    $chyba=$this->db->Sql($this->sprikaz);
    if ($chyba){
      $this->hlaseni($this->db->Error);
    }
    while ($this->db->FetchRow()){
      $radek='';
      for ($i=0;$i<count($this->column);$i++){
        $typ=strtoupper($this->datatype[$i]);
        $hodnota=$this->db->Data($this->column[$i]);
        if (gettype($hodnota)=="object"){
          /* CLOB, BLOB */
          $hodnota=$hodnota->load();
        } 
        if ($typ=='CLOB' || $typ=='ST_GEOMETRY' || $typ=='LONG'){
          $hodnota='"'.$this->proper_text($hodnota,false).'"';
        }        
        if ($typ=='CHAR' || $typ=='VARCHAR' || $typ=='VARCHAR2' || $typ=='DATE' || 
            $typ=='NVARCHAR' || $typ=='NVARCHAR2' || $typ=='TIMESTAMP(6)'){
          $hodnota='"'.$this->proper_text($hodnota).'"';
        }
        if ($typ=='NUMBER' || $typ=='INT' || $typ=='FLOAT'){
          $hodnota=str_replace(',','.',$hodnota);
        }
        $radek.=($i==0?'':$this->oddelovac).$hodnota;
      }
      $radek.="\n"; 
      fwrite($f, $radek);
      $n++;
      if ($this->verbose && !($n%1000) ) echo "$n\r";   
    }

    fclose($f);
    
    $this->hlaseni("Exportovano $n zaznamu do $jms.".str_replace("\n","",tick(" / ")));
    
    /* informace o strukture tabulky */
    $f=@fopen($jmst,"w");
    fwrite($f,'table '.($this->no_schema?$nazev_tabulky:$tabulka).'('."\n");
    if ($this->tablecomment!=''){
      fwrite($f,' -- '.$this->tablecomment."\n");
    }
    for ($i=0;$i<count($this->column);$i++){
       $typ=$this->datatype[$i];
       if ($this->datalength[$i]!='' && $this->dataprecision[$i]!=''){
         $typ.='('.$this->datalength[$i].($this->dataprecision[$i]>0?(','.$this->dataprecision[$i]):'').')';
       }elseif( $this->datalength[$i]!='' && $this->dataprecision[$i]==''){ 
         $typ.='('.$this->datalength[$i].')';
       }
       if ($this->nullable[$i]=='N') $typ.=' NOT NULL';
       $s=$this->column[$i].' '.$typ;
       $s.=',  -- ['.strtolower($typ).'] ';  
       if ($this->comment[$i]!=''){
         $s.=$this->comment[$i];
       }
       fwrite($f,$s."\n");
    }
    fwrite($f,'primary key ('.$this->prim.")\n");
    fwrite($f,')'."\n");
    fclose($f);    
  }else{
    $this->hlaseni('Nelze otevrit '.$jms);
  }
}


/** provede export dat podle dotazu SQL ve schematu
 * Datové typy exportovaných sloupců (včetně aliasů) se nezjišťují z datového katalogu,
 * ale přímo z metadat OCI příkazu po jeho provedení (oci_field_*).
 * @param string $sql_select - SQL select příkaz, který definuje datový výstup
 * @param string $export_tabulka - jméno výsledné tabulky (použije se pro soubory .csv a .inf)
 * @param string $primary - nepovinné složení primárního klíče, např. "id" nebo "id,poradi"
 * @return int počet exportovaných záznamů
 */ 
function exportuj_sql($sql_select, $export_tabulka, $primary=''){
  tick();
  $export_tabulka = strtoupper($export_tabulka);
  /* reset poli pro strukturu vysledku dotazu */
  $this->column = [];
  $this->comment = [];
  $this->nullable = [];
  $this->datalength = [];
  $this->datatype = [];
  $this->dataprecision = [];
  $this->prim = trim($primary);
  $this->tablecomment = '';

  /* spusteni dotazu - po provedeni je v $this->db->parse platny OCI statement,
     ze ktereho se ziska metadata sloupcu vcetne pouzitych aliasu */
  $chyba = $this->db->Sql($sql_select);
  if ($chyba){
    $this->hlaseni($this->db->Error);
    return 0;
  }
  $stmt = $this->db->parse;
  $pocet = oci_num_fields($stmt);
  if ($pocet == 0){
    $this->hlaseni("Dotaz nevraci zadne sloupce");
    return 0;
  }
  $seznamsloupcu = '';
  for ($c = 1; $c <= $pocet; $c++){
    $i = $c - 1;
    $nazev = oci_field_name($stmt, $c);        /* nazev sloupce = alias uvedeny v dotazu */
    $typ   = oci_field_type($stmt, $c);         /* Oracle datovy typ jako retezec */
    $delka = oci_field_size($stmt, $c);
    $prec  = @oci_field_precision($stmt, $c);
    $scale = @oci_field_scale($stmt, $c);
    $this->column[$i] = $nazev;
    $this->comment[$i] = '';                    /* u vyrazu/aliasu nejsou komentare */
    $this->nullable[$i] = '';                   /* nullable nelze u vyrazu spolehlive zjistit */
    $this->datatype[$i] = $typ;
    /* pro typ date a long se neuvadi delka - shodne jako v metode exportuj */
    $this->datalength[$i] = ($typ == 'DATE' || $typ == 'LONG') ? '' : $delka;
    $this->dataprecision[$i] = ($scale == 0) ? '0' : $prec;
    $seznamsloupcu .= ($seznamsloupcu == '' ? '' : ',').$nazev;
  }

  $jms = $this->kam.$export_tabulka.'.csv';
  $jmst = $this->kam.$export_tabulka.'.inf';
  $f = @fopen($jms, "w");
  if ($f === false){
    $this->hlaseni('Nelze otevrit '.$jms);
    return 0;
  }
  /* hlavicka */
  fwrite($f, str_replace(',', ';', $seznamsloupcu."\n"));
  $n = 0;
  while ($this->db->FetchRow()){
    $radek = '';
    for ($i = 0; $i < count($this->column); $i++){
      $typ = strtoupper($this->datatype[$i]);
      $hodnota = $this->db->Data($this->column[$i]);
      if (gettype($hodnota) == "object"){
        /* CLOB, BLOB */
        $hodnota = $hodnota->load();
      }
      if ($typ == 'CLOB' || $typ == 'ST_GEOMETRY' || $typ == 'LONG' || $typ == 'BLOB'){
        $hodnota = '"'.$this->proper_text($hodnota, false).'"';
      }
      if ($typ == 'CHAR' || $typ == 'VARCHAR' || $typ == 'VARCHAR2' || $typ == 'DATE' ||
          $typ == 'NVARCHAR' || $typ == 'NVARCHAR2' || substr($typ, 0, 9) == 'TIMESTAMP'){
        $hodnota = '"'.$this->proper_text($hodnota).'"';
      }
      if ($typ == 'NUMBER' || $typ == 'INT' || $typ == 'FLOAT'){
        $hodnota = str_replace(',', '.', $hodnota);
      }
      $radek .= ($i == 0 ? '' : $this->oddelovac).$hodnota;
    }
    $radek .= "\n";
    fwrite($f, $radek);
    $n++;
    if ($this->verbose && !($n % 1000)) echo "$n\r";
  }
  fclose($f);
  $this->hlaseni("Exportovano $n zaznamu do $jms.".str_replace("\n", "", tick(" / ")));

  /* informace o strukture vysledku dotazu */
  $f = @fopen($jmst, "w");
  fwrite($f, 'table '.$export_tabulka.'('."\n");
  for ($i = 0; $i < count($this->column); $i++){
    $typ = $this->datatype[$i];
    if ($this->datalength[$i] != '' && $this->dataprecision[$i] != ''){
      $typ .= '('.$this->datalength[$i].($this->dataprecision[$i] > 0 ? (','.$this->dataprecision[$i]) : '').')';
    }elseif( $this->datalength[$i] != '' && $this->dataprecision[$i] == ''){
      $typ .= '('.$this->datalength[$i].')';
    }
    if ($this->nullable[$i] == 'N') $typ .= ' NOT NULL';
    $s = $this->column[$i].' '.$typ;
    $s .= ',  -- ['.strtolower($typ).'] ';
    if ($this->comment[$i] != ''){
      $s .= $this->comment[$i];
    }
    fwrite($f, $s."\n");
  }
  fwrite($f, 'primary key ('.$this->prim.")\n");
  fwrite($f, ')'."\n");
  fclose($f);
  return $n;
}

/** metoda pro export celeho schematu
 * @param $schema string - jmeno exportovaneho schematu
 * @param $kam string - cesta ke slozce, kde budou exportovane soubory
 */
function exportuj_schema($schema,$kam=''){
  $schema=strtoupper($schema);
  $this->db->Sql(
   "select table_name ".
   "from all_tables ".
   "where owner='$schema' ".
   "order by table_name");
  $tabs=[];
  while ($this->db->FetchRow()){
    array_push($tabs,$this->db->Data('TABLE_NAME'));
  }
  for($i=0;$i<count($tabs);$i++){
    $this->exportuj($schema.'.'.$tabs[$i],'',$kam);
  }
}

/** metoda smaze slozku, kam ma ukladat vystupni soubory.
 * Vhodne volani pred provedenim exportu.
 */
function purge_folder(){
  $tmp=$this->kam;
  if ($tmp=='' || $tmp=='..' || $tmp=='.' || !is_dir($tmp)) return;
  foreach (glob($tmp.'/*') as $file) if (is_file($file)) {
    unlink($file);
    echo 'smazan '.$file."\n";
  }  
}

/** metoda pro nahrazeni znaku v textu atributu, ktere by mohly zpusobit problem v CSV souboru
 * nahrazuje znaky specialnimi tagy, nebo mezerami 
 * @param string $s - vstupni text
 * @param bool $tags - zda pouzi specialni tagy, nebo mezery ($tags==false)
 * @return string - upraveny text
 */
function proper_text($s,$tags=true){
  if ($s=='') return '';
  if (@is_numeric($s)) return $s;
  $r=str_replace("\n",'<br>',$s); /* odradkovani je nahrazeno sekvenci <br>*/
  $r=str_replace($this->oddelovac,'<sep>',$r); /* pouzity oddelovac je nahrazen sekvenci <sep>*/
  $r=str_replace(chr(12),$tags?'<chr12>':' ',$r);
  $r=str_replace(chr(13),$tags?'<chr13> ':' ',$r);
  $r=str_replace(chr(10),$tags?'<chr10>':' ',$r);
  $r=str_replace(chr(9),$tags?'<chr9>':' ',$r);
  //$r=str_replace("\"",'\"',$r);  /* uvozovky uvnitr retezce jsou uvozeny znakem vyjimky */
   $r=str_replace('"', '""', $r);  /* uvozovky uvnitr retezce jsou zdvojeny */
  return $r;
} 

/** hlaseni skriptum zpravidla chybove
 *  @param string $s - text hlaseni
 */
function hlaseni($s){
  echo $s."\n";
}

} /*konec definice tridy oraexp */

?>