<?php
/** Database wrapper for the Oracle database
 *  
 *  @author Petr Čoupek
 */ 
     
 /*  29.10.2014
 *  27.5.2016 - uprava kodu 
 *  8.8.2017 - OCI_RETURN_NULLS
 *  31.8.2018 - pridan oci_commit do metody SQl + parametr na ovladani ...
 *  27.2.2019 - metoda pragma
 *  03.10.2019 - FetchRowA
 *  24.08.2020 - rozsireni metody SqlFetchKeys o vraceni poli
 *  12.1.2021 - DataHash vraci LOB polozky jiz prevedene do retezcu
 *  03.05.2021 - rozsireni o bind parametru pri pouzivani metdo Sql, SqlFetchArray, SqlFetchKeys. SQLFetch.
 *  Zmena priznaku commit
 *  02.09.2021 - navratovy typ sqlFetch
 */

//namespace Microbe; /* namespace pro framework, volani globalnich objektu pak zacina \ */
 
class OpenDB_Oracle {
  var $conn;      // pripojeni - vysledek po volani ocilogon
  var $parse;     // dotaz sql - vysledek ociparse
  var $data;      // struktura, ve ktere je radek z databaze
  var $stav;      // stav po selhani SQL dotazu
  var $p_sloupcu; // pocet sloupcu
  var $com_kontr; // kontrola zda je zapnuty commit
  var $Error;     // retezec obsahujici chybu SQL. (kod, popis, ofset)..
  //var $charset="EE8MSWIN1250";
  var $charset="AL32UTF8";
  var $commit=true;
  var $typedb='oracle';
  //var $charset="UTF-8";
  //var $utf8=false; /* zde bude probihat konverze dat do a z UTF-8 */
  
  /** $db = new OpenDB_Oracle($connection_string)
   * 
   * connect to the dabasase, if database does not exist, it will be made a creation attempt 
   * @param string $connect - connection string
   * @return OpenDB_Oracle a new database wrapper object, or false when connection was not established
   */
  function __construct($napojeni){ 
    $this->typedb='oracle';
    //putenv ("NLS_LANG=CZECH_CZECH REPUBLIC.EE8MSWIN1250");
    putenv ("NLS_LANG=CZECH_CZECH REPUBLIC.AL32UTF8");   //?jede
    putenv ("NLS_NUMERIC_CHARACTERS=.,");
    putenv ("NLS_DATE_FORMAT=DD.MM.YYYY"); 
    $m=array();
    if (preg_match('/^dsn=(.+);uid=(.+);pwd=(.+)$/i',$napojeni,$m)){  
      $this->conn=@oci_connect($m[2],$m[3],$m[1],$this->charset);
      $this->com_kontr=true; //nastav natrue autocomit je implicitne zaply
      if (!$this->conn){
        $this->Error='Oracle connect failed.';
        //print($this->Error);
        $this->stav=false;                                
      }else{
        $this->stav=true;
        $this->Error='';  
      }
      return $this->stav;
    }else{
      $this->Error='Incorrect connect string.';
      //print($this->Error);
      $this->stav=false;              
      return $this->stav; 
    }  
  } 
  
  /** $error = $db->Sql($sql_command)
   * 
   * Provide a SQL command in the target database 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return boolean, true when an error has occured, false on success
   */
  function Sql($command,$bind=array()){
    $this->parse=@ociparse($this->conn,$command);
    if (count($bind)){
      foreach($bind as $k=>$v){
         @oci_bind_by_name($this->parse,$k,$bind[$k]); /* pozor $v nefunguje! musi byt $bind[$k] !*/
      }
    }
    $x=@ociexecute($this->parse,OCI_DEFAULT);
    if(!$x){
      $em=@ocierror($this->parse);
      $this->Error=$em['code'].'; '.$em['message'];  //.'; '.$em['offset'].'; '.$em['sqltext'];
      $this->stav=true;
      return $this->stav;
    }
    $this->p_sloupcu=@ocinumcols($this->parse); 
    if(! $this->parse){ 
      $this->stav=true;
      $this->Error='Parse error';
      return $this->stav;      
    }else{
      $this->stav=false;
      $this->Error='';
      if ($this->commit) { @oci_commit($this->conn);}
      return $this->stav;
    }
  }
  
  /** $error = $db->SqlLOB($sql_command,$lob_field,$lob_content)
   * 
   * Provide a SQL command in the target database with special LOB-type field 
   * @param string $command - and sql command
   * @param string $lob_field  - special LOB field
   * @param string $lob_content - LOB content 
   * @return boolean, true when an error has occured, false on success
   */
  function SqlLOB($dotaz,$lob_field,$lob_content){
    $delkalob=100000;
    $this->parse=@ociparse($this->conn,$dotaz);
    $clob = @oci_new_descriptor($this->conn, OCI_DTYPE_LOB); // OCI_D_LOB ?
    if ($clob){ 
      @oci_bind_by_name($this->parse, $lob_field, $clob, $delkalob, OCI_B_CLOB);  
      /* posledni parametr OCI_C_CLOB nejede - v prikladu i  SQLT_CLOB*/
      $x=@ociexecute($this->parse,OCI_DEFAULT);
      if(!$x){
        $em=@ocierror($this->parse);
        $this->Error=$em['code'].'; '.$em['message'];  //.'; '.$em['offset'].'; '.$em['sqltext'];
        $this->stav=true;
        return $this->stav;
      }      
      $zapis=$clob->save($lob_content,$delkalob);  /*neni jasne, zda volat metodu save nebo write*/
      /* nevraci, na rozdil od dokumentace, pocet ulozenych byte, pouye hodnotu 1 nebo 0*/
      if ($zapis){
         $this->Error='';
         @oci_commit($this->conn);
         $this->stav=false;
      }else{
         $this->Error='CLOB save failed';
         $this->stav=true;
      }
    }else{
      $this->Error='CLOB descriptor failed';
      $this->stav=true;
    }
    return $this->stav;  
  }
  
  /** $result = $db->FetchRow();
   * 
   * Provide fetch of one row of the data from the database table to the local Hash
   * @return boolean, true when next row has been fetched, false at the end of data
   */  
  function FetchRow(){
    if($this->data=@oci_fetch_array($this->parse,OCI_ASSOC+OCI_RETURN_NULLS)){
      return $this->data;
    }else{
      return false;
    }   
  }
  
  /** $error = $db->Pragma("table_info('TABLE_NAME'");
   * 
   * Provide special non- standartized task with the database - supported is table_info pragma 
   * @param string $command - table info pragma
   * @return boolean, true when an error has occured, false on success
   */
  function Pragma($dotaz){
    /* metoda vraci strukturu s udaji - napr. struktura tabulky a nebo false v pripade chyby*/
    /* duvodem teto metody je sjednoceni pristupu k datovemu katalogu napric databazemi */
    $m=array(); $struktura=array();
    if (preg_match('/^\s*table_info\(\'(.+)\'\)\s*$/',$dotaz,$m)){
      $table_name=strtoupper($m[1]);  
           
      /* vrat strukturu tabulky $m[1] - generuje se interoperabilni tvar spolecny pro ruzne databaze */
      if (preg_match('/^(\w+)\.(\w+)$/',$table_name,$m)){  
        /* tabulka uvedena jako schema.tabulka */
		$table_name=strtoupper($m[2]);  
        $dodatek=" and owner='$m[1]'";
        $dodatek2=" and all_constraints.owner='$m[1]'"; /* uvodni mezera dulezita */
	    }else{
	      /* tabulka s prostym nazvem */
        $dodatek='';  $dodatek2='';
      }	
      /* inicializace pole nullable, datalength, colimnid jde od nuly - sjednoceni s ostatnimi DB */ 
	  $this->Sql("select column_id-1 as columnid,column_name,nullable,data_length,data_type,data_precision ".
        "from all_tab_columns where table_name='".$table_name."'".$dodatek." order by column_id asc");
      while($this->FetchRow()){
        $struktura[$this->Data('COLUMNID')]=array(
          'name'=>$this->Data('COLUMN_NAME'),
          'type'=>$this->Data('DATA_TYPE'),
		      'notnull'=>$this->Data('NULLABLE')=='N'?1:0,
          'default'=>'',  /* dopsat */
          'datalength'=>$this->Data('DATA_LENGTH'),
          'precision'=>$this->Data('DATA_PRECISION'),
          'datename'=>($this->Data('DATA_TYPE')=='date'?
            ("to_char(".$this->db->Data('COLUMN_NAME').",'DD.MM.YYYY HH24:MI:SS') as ".$this->db->Data('COLUMN_NAME')):''));
        /* vazba cislo sloupce nazev pro nasledne doplneni comment */  
        $prevod[$this->Data('COLUMN_NAME')]=$this->Data('COLUMNID');   		    
	    }
      
      for($this->Sql("select comments,column_name from all_col_comments where table_name='".$table_name."'".$dodatek);
          $this->FetchRow();){
        $col=$this->Data('COLUMN_NAME');
        $komentar=$this->Data('COMMENTS');
        $struktura[$prevod[$col]]['comment']=($komentar!='')?$komentar:$col;
	    }
      	      
      /* primarni klic do tabulky */
      $this->Sql("select position,column_name from all_constraints, all_cons_columns".
       " where all_cons_columns.constraint_name=all_constraints.constraint_name ".
       " and all_constraints.table_name=upper('$table_name') and all_constraints.constraint_type='P'".$dodatek2.
       " order by position asc");
      while($this->FetchRow()){
        $col=$this->Data('COLUMN_NAME');
        $struktura[$prevod[$col]]['pk']=$this->Data('POSITION');
      }
      //htpr(print_r($struktura,false));   
      return($struktura);
    }
    
    if (preg_match('/^\s*catalog\s*$/',$dotaz,$m)){
      /* vraci seznam tabulek - pohledu */
      $this->parse=@ociparse($this->conn,"select table_name from tabs order by table_name asc");
      $x=@ociexecute($this->parse,OCI_DEFAULT);
      $n=0;
      while ($this->data=@oci_fetch_array($this->parse,OCI_ASSOC+OCI_RETURN_NULLS)) {
        $struktura[$n++]=array(
           'name'=>$this->data['TABLE_NAME'],
           'type'=>'table');      
      }
      return($struktura);
    }     
    
    $this->stav=false;
    return($this->stav);
  }
  
  /** $result = $db->FetchRowA();
   * 
   * Provide fetch of one row of the data from the database table to the local Array
   * @return boolean, true when next row has been fetched, false at the end of data
   */ 
  function FetchRowA(){
    if($this->data=@oci_fetch_array($this->parse,OCI_NUM+OCI_RETURN_NULLS)){
      return $this->data;
    }else{
      return false;
    }   
  }
  
  /** $value = $db->Data('attribute');
   * 
   * This method returns current attribute value
   * @param string $attribute - the name of the attribute (in the view/table), 
   *   automatic case sensitivity detection 
   * @return string with the attribute value
   */
  function Data($sloupec){
    if (isset($this->data[$sloupec])) {
      return $this->data[$sloupec];
    }else{
      return '';
    }
  }
  
  /** $value = $db->DataHash();
   * 
   * This method returns current attribute value
   * @return hash with the current fetched row values BLOB are converted to strings.
   */
  function DataHash(){
    $h=array();
    if ($this->data) foreach ($this->data as $k=>$v){
      $h[$k]=isset($this->data[$k])?(
       (gettype($this->data[$k])=="string")?$v:$this->data[$k]->load()
       ):'';      
    }
    return $h;  
  }  
 
  /** $db->Close();
   * 
   * It closes the database connection
   */  
  function Close(){
    if ($this->conn) {oci_close($this->conn);}
  }
  
  /** $string = $db->SqlFetch($sql_command)
   * 
   * combine Sql and FetchRow method into one step and returns data hash 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return string with the data content
   */
  function SqlFetch($prikaz,$bind=array()){
    /* zjednoduseni nacteni hodnoty z db primo do promenne */
    if (!$this->Sql($prikaz,$bind) && $this->FetchRowA()) {
      return (string)($this->data[0]);
    }else{
      return '';
    }  
  }
  
  /** $array = $db->SqlFetchArray($sql_command,$limit=0)
   * 
   * combine Sql and FetchRow method into one step and returns data array
   * @param string $sql_command - and sql command
   * @param integer $limit - max. count of resuts , 0= no limit
   * @param array $bind - list of bind parameters
   * @return array with the data content
   */
  function SqlFetchArray($prikaz,$limit=0,$bind=array()){
    /* zjednoduseni nacteni celeho vysledku select primo do pole v PHP s volitelnym limitem */
    $a=array();
    //$a= new SplFixedArray(10000);$i=0;
    if (!$this->Sql($prikaz,$bind)){
      while ($this->FetchRow()){
        array_push($a,$this->DataHash());
        //$a[++$i]=$this->DataHash();
        if ($limit && $limit<=count($a)) break;
      }
    }
    return $a;    
  }
  
  /** $error = $db->SqlFetchKeys($sql_command,$key)
   * 
   * combine Sql and FetchRow method into one step and returns data array
   * @param string $sql_command - and sql command
   * @return array with the data content
   */
  function SqlFetchKeys($prikaz,$key,$bind=array()){
    /* zjednoduseni nacteni celeho vysledku select primo do pole podle klice */
    $a=array();
    if (!$this->Sql($prikaz,$bind)){
      while ($this->FetchRow()){
        if (isset($a[$this->Data($key)])){
          /* tato hodnota klice se opakuje, struktura bude pole */
          if (!isset($a[$this->Data($key)][0]) ){
            /* pole zatim neexistuje, vlozeni jiz zarazeneho prvku do pole */
            $tmp=$a[$this->Data($key)];
            $a[$this->Data($key)]=array();
            array_push($a[$this->Data($key)],$tmp);
          }
          /* pripojeni prvku k poli */
          array_push($a[$this->Data($key)],$this->DataHash());  
        }else{
          $a[$this->Data($key)]=$this->DataHash();
        }  
      }
    }
    return $a;    
  }
}
 
?>