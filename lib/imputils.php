<?php
/** Import utility functions
 * @author Petr Coupek
 */ 


 /** it constructs a sql insert comment based on data hash and a table name
  * @param $table string table name
  * @param $d hash a datahash
  * @param $types has a type infromation
  */ 
 function make_insert($table,$d,$types=array()){
      $pole=array_keys($d);
      $hodnoty=array_values($d);
      /* nastaveni vychozich typu */
      for($i=0;$i<count($pole);$i++){
        if (!isset($types[$pole[$i]])){
          $types[$pole[$i]]='C';
        }
      }  
      $nenul=true;
      for($i=0;$i<count($hodnoty);$i++){
        if ($hodnoty[$i]=='') {
          $hodnoty[$i]='null'; 
          $nenul=false;
        }else{
          $nenul=true;
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        }   
        if ($hodnoty[$i]!='null'){   
          if (strstr($hodnoty[$i],'\"')){
            $hodnoty[$i]=str_replace('\"',"'||chr(34)||'",$hodnoty[$i]);
          }
        
          if ($types[$pole[$i]]=='C'){
            $hodnoty[$i]="'".$hodnoty[$i]."'";          
          }
          if ($types[$pole[$i]]=='D'){
            $hodnoty[$i]=" to_date('".$hodnoty[$i]."','YYYY-MM-DD HH24:MI:SS')";          
          }          
        }else{
          $hodnoty[$i]='null';
        }
      }
      
            
      $prikaz="insert into $table (".implode($pole,', ').') values ('.implode($hodnoty,', ').')';
      return $prikaz;
 }
 
?>
