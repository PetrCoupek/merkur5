<?php
/** Database wrapper for the  PostgreSQL database
 * @author Petr Coupek
 * 11.10.2021
 * 
 */
include_once "mdbAbstract.php";

class OpenDB_pg extends OpenDB{
  var $conn;       //pripojeni - vysledek po volani ocilogon
  var $parse;      //dotaz sql - vysledek ociparse
  var $data;       //struktura, ve ktere je radek z databaze
  var $stav;       //stav po selhani SQL dotazu
  var $p_sloupcu;  //pocet sloupcu
  var $com_kontr;  //kontrola zda je zapnuty commit
  var $Error;      //retezec obsahujici chybu SQL. (kod, popis, ofset)..
  var $dbname;    // schema databaze - vyuzito v katalogu
  
  /** $db = new OpenDB_pg($connection_string)
   * 
   * connect to the dabasase, if database does not exist, it will be made a creation attempt 
   * @param string $connect - connection string
   * @return OpenDB_pg a new database wrapper object, or false when connection was not established
   */
  function OpenDB_pg($napojeni){
    $m=array();
    if (preg_match('/^dns=(.+);uid=(.+);pwd=(.+)$/i',$napojeni,$m)){
      if (preg_match('/^(.+)\:(.+)\:(.+)$/i',$m[1],$mm)) {
        //$this->conn=@pg_connect('host='.$mm[1].' port='.$mm[2].' dbname='.$mm[3].' user='.$m[2].' password='.$m[3]);
        try{
          $this->conn=new PDO('pgsql:host='.$mm[1].';port=5432;dbname='.$mm[3],$m[2],$m[3]);
        }catch (PDOException $e){  
          $this->Error='PgSQL connect failed.'.($e->getMessage());
          $this->stav=false;              
          return $this->stav;
        }
        
        $this->com_kontr=true; //nastav natrue autocomit je implicitne zaply
        $this->stav=true;
        $this->Error='';
        $this->dbname=$mm[3];
        return $this->stav;        
      }else{
        $this->Error='Incorrect connect string - uid.';
        //echo($this->Error);
        $this->stav=false;              
        return  $this->stav;
      }   
    }else{
      $this->Error='Incorrect connect string.';
      //echo($this->Error);
      $this->stav=false;              
      return  $this->stav;
    }  
  }

  /** $error = $db->Sql($sql_command,$bind=array())
   * 
   * Provide a SQL command in the target database 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return boolean, true when an error has occured, false on success
   */ 
  function Sql($dotaz,$bind=array()){
    if (!isset($this->conn)) {
      return -1;
    }
    try{
       $this->parse=$this->conn->prepare($dotaz);
    }catch (PDOException $e){
         $this->Error=$e->getMessage();
         $this->stav=true;
         return $this->stav;
    }
    try{
      $this->parse->execute($bind);
    }catch (PDOException $e){
      $this->Error=$e->getMessage();
      $this->stav=true;
      return $this->stav;
    }
    $this->stav=false;
    $this->Error='';
    return $this->stav;        
  }
 
  /** $result = $db->FetchRow();
   * 
   * Provide fetch of one row of the data from the database table to the local Hash
   * @return boolean, true when next row has been fetched, false at the end of data
   */  
  function FetchRow(){
    if ($this->parse){
      $this->data = $this->parse->fetch(PDO::FETCH_ASSOC);
      return $this->data;
    }else{
      return false;
    }
  }
  
  /** $result = $db->FetchRowA();
   * 
   * Provide fetch of one row of the data from the database table to the local Array
   * @return boolean, true when next row has been fetched, false at the end of data
   */ 
  function FetchRowA(){
    if ($this->parse){
      $this->data = $this->parse->fetch(PDO::FETCH_NUM);
      return $this->data;
    }else{
      return false;
    }    
  }

  /** $error = $db->Pragma("table_info('TABLE_NAME'");
   * 
   * Provide special non- standartized task with the database - supported is table_info pragma 
   * @param string $command - table info pragma
   * @return array|boolean, true when an error has occured, false on success
   */
  function Pragma($dotaz){
    /* metoda vraci strukturu s udaji - napr. struktura tabulky a nebo false v pripade chyby*/
    /* duvodem teto metody je sjednoceni pristupu k datovemu katalogu napric databazemi */
    $m=array(); $struktura=array();
    if (preg_match('/^\s*table_info\(\'(.+)\'\)\s*$/',$dotaz,$m)){
      $table_name=strtolower($m[1]);  
           
      /* vrat strukturu tabulky $m[1] - generuje se interoperabilni tvar spolecny pro ruzne databaze */
      if (preg_match('/^(\w+)\.(\w+)$/',$table_name,$m)){  
        /* tabulka uvedena jako schema.tabulka */
		    $table_name=strtolower($m[2]);  
        $table_schema=strtolower($m[1]);

	    }else{
	      /* tabulka s prostym nazvem */
        $table_schema=$this->dbname;
      }	
      
      /* inicializace pole nullable, datalength, colimnid jde od nuly - sjednoceni s ostatnimi DB */ 
	    $this->Sql(
        "select * ".
        "from information_schema.columns ".
        "where table_name='".$table_name."' and table_catalog='".$this->dbname."' ".
        "order by ordinal_position asc");
      while($this->FetchRow()){
        $struktura[$this->Data('dtd_identifier')]=array(
          'name'=>$this->Data('column_name'),
          'type'=>$this->Data('udt_name'), /* not 'data_type' ! */
		      'notnull'=>$this->Data('is_nullable')=='YES'?0:1,
          'default'=>$this->Data('column_default'),
          'datalength'=>$this->Data('character_maximum_length'),
          'precision'=>$this->Data('numeric_precision_radix'),
          'datename'=>($this->Data('udt_name')=='"timestamp without time zone"'?
            ("to_char(".$this->db->Data('column_name').",'DD.MM.YYYY HH24:MI:SS') as ".$this->db->Data('COLUMN_NAME')):''));
        /* vazba cislo sloupce nazev pro nasledne doplneni comment */  
        $prevod[$this->Data('column_name')]=$this->Data('dtd_identifier');   		    
	    }
      for($this->Sql(
         "select cols.column_name, (SELECT
           pg_catalog.col_description(c.oid, cols.ordinal_position::int)
           FROM
           pg_catalog.pg_class c
           WHERE
           c.oid = (SELECT ('\"' || cols.table_name || '\"')::regclass::oid)
           AND c.relname = cols.table_name) AS column_comment
         FROM information_schema.columns cols
         WHERE
          cols.table_catalog    = '".$table_schema."'
          AND cols.table_name   = '".$table_name."'
          AND cols.table_schema = '".$table_schema."';");
        $this->FetchRow();){
          $col=$this->Data('column_name');
          $komentar=$this->Data('column_comment');
          $struktura[$prevod[$col]]['comment']=($komentar!='')?$komentar:$col;
	    }
      	      
      /* primarni klic do tabulky */
      $this->Sql("select kcu.table_schema,
      kcu.table_name,
      tco.constraint_name,
      kcu.ordinal_position as position,
      kcu.column_name as key_column
      from information_schema.table_constraints tco
      join information_schema.key_column_usage kcu 
      on kcu.constraint_name = tco.constraint_name
      and kcu.constraint_schema = tco.constraint_schema
      and kcu.constraint_name = tco.constraint_name
      where tco.constraint_type = 'PRIMARY KEY'
      and kcu.table_name='".$table_name."' and kcu.table_schema='".$table_schema."'
      order by kcu.table_schema, kcu.table_name, position ");
      while($this->FetchRow()){
        $col=$this->Data('key_column');
        $struktura[$prevod[$col]]['pk']=$this->Data('position');
      }
      return $struktura;
    }
    
    if (preg_match('/^\s*catalog\s*$/',$dotaz,$m)){
      /* vraci seznam tabulek - pohledu */
      $this->parse=pg_query($this->conn,
       "select table_name ".
       "from information_schema.tables ".
       "where table_schema='".$this->dbname."'");
      $n=0;
      while ($this->data=@pg_fetch_assoc($this->parse)) {
        $struktura[$n++]=array(
           'name'=>$this->data['table_name'],
           'type'=>'table');      
      }
      return($struktura);
    }     
    
    $this->stav=false;
    return($this->stav);
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
    }elseif (isset($this->data[strtoupper($sloupec)])){
      return $this->data[strtoupper($sloupec)];
    }elseif (isset($this->data[strtolower($sloupec)])){
      return $this->data[strtolower($sloupec)];
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
    return($this->data);
  }  
  
  /** $db->Close();
   * 
   * It closes the database connection
   */  
  function Close(){
    if ($this->conn) $this->conn=null;
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
   * @param string $sql_command - an SQL command
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