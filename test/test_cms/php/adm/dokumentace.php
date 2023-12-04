<?php
  //include_once "lib/lib.php"; /* nove aplikacni mikrojadro */
  //Microbe::skeleton();
 
  $s.=tg('div','class="card"',
        bt_panels(
         array('1'=>'Global Functions',
               '2'=>'Application classes',
               '3'=>'Application Files'),
         array('1'=>globalni_fce(),
               '2'=>tg('div','class="table-responsive"',tridy()),
               '3'=>tg('div','class="table-responsive"',' '))));
  
  htpr($s); 
  
  function globalni_fce(){
    /* seznam vsech funkci, ve vetvi 'user' jsou fce definovane v aplikaci */
    $re='';
    $fce = get_defined_functions();
  
    /* projdi seznam a pro kazdou funkci zjisti relativni soubor, ve kterem se nachazi */
    $running_from=str_replace('d.php','',getenv('SCRIPT_FILENAME'));
    $by_file=array();
    sort($fce['user']);
    foreach($fce['user'] as $jmfce){
       $rf= new ReflectionFunction($jmfce);
       $source_file=str_replace($running_from,'',$rf->getFileName());  /* vyroba relativni cesty */
       unset($rf);
       if (!isset($by_file[$source_file])){
         $by_file[$source_file]=array();
       }
       array_push($by_file[$source_file],$jmfce);
    }
  
    /* seznam funkci podle zdrojoveho souboru a abecedy */
    foreach($by_file as $k=>$v){
      $s='';
      //sort($v);
      foreach ($v as $jmfce ){
        $s.=nbsp(1).ahref('#'.$jmfce,$jmfce).' ';
      }
      $re.=ta('h8','File '.$k).ta('p',$s); 
    }
   
    /* puvodni reseni - prosty seznam vsech fci   
     foreach ($fce['user'] as $jmfce ){
       htpr(nbsp(1).ahref('#'.$jmfce,$jmfce),' ');
     } */
  
    /* dokumentace pro jednotlive funkce */
    foreach ($fce['user'] as $jmfce ){
      $re.=dokumentace_fce($jmfce);
    }
    return $re;
  }
   
  function dokumentace_fce($jmeno_fce){
    $rf = new ReflectionFunction($jmeno_fce);
    $desc = $rf->getDocComment();
    $params=$rf->getParameters();
    foreach ($params as $param){
      $p='$'.$param->getName();
      $desc=str_replace($p,str_replace("\n",'',ta('b',$p)),$desc);
      //deb($p);
    }
    $desca=explode("\n",$desc); 
    $filename = $rf->getFileName();
    $start_line = $rf->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $rf->getEndLine();
    $length = $end_line - $start_line;
    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));
           
    $de='';
    for($i=0;isset($desca[$i]);$i++){
      if (preg_match('/^\s+\*\s+(.*)$/',$desca[$i],$m) ) {
        $desca[$i]=$m[1];
      }  
      if (substr($desca[$i],-2)=='*/') {
        $desca[$i]=substr($desca[$i],0,-2);
      }
      $desca[$i]=str_replace('@param ',ta('b','Parameter: '),$desca[$i]);
      $desca[$i]=str_replace('@return ',ta('b','Return value: '),$desca[$i]);  
      $de.=str_replace('/**',' ',$desca[$i]).br();
    }
    $par='';
    foreach ($params as $param ){
      $par.=($par==''?'':', ').'$'.($param->getName());
      $add=(string)$param;
      if (preg_match('/(.+) = (.+) ]$/',$add,$m)){
        $par.=' = '.$m[2];
      }
    }
    if (strpos($body,'func_get_arg')!==false){
       $par.='...'; 
    }
    $running_from=str_replace('d.php','',getenv('SCRIPT_FILENAME'));
    $de.=ta('b','File: ').str_replace($running_from,'',$filename).br();
    
    return tg('a','name="'.$jmeno_fce.'"',ta('h5',$jmeno_fce.'('.$par.')')).
           ta('p',$de); //.     ta('code',$body); //.       ta('p',$rf);
  }
  
  function tridy(){
    $re='';
    /* zjisti vsechny programatorem definovane tridy nyni pristupne */
    $tridy=smk_get_classes_from_project('.');
    
    /* hlavicka odkazu na jednotlive tridy podle jmeno tridy */
    $s='';
    foreach ($tridy as $trida){
      $s.=nbsp(1).ahref('#'.$trida,$trida).' ';
    }
    $re.=ta('p',$s); 
    
    foreach ($tridy as $trida){
      //$re.=ta('h5',$trida);
      $re.=dokumentace_trida($trida);
    }
    return $re;
  } 
  
  function dokumentace_trida($jmeno_tridy){
    $rc = new ReflectionClass($jmeno_tridy);
    $desc=$rc->getDocComment();
    $desca=explode("\n",$desc);
    $filename = $rc->getFileName();
    $de='';
    for($i=0;isset($desca[$i]);$i++){
      if (preg_match('/^\s+\*\s+(.*)$/',$desca[$i],$m) ) {
        $desca[$i]=$m[1];
      }  
      if (substr($desca[$i],-2)=='*/') {
        $desca[$i]=substr($desca[$i],0,-2);
      }
      $desca[$i]=str_replace('@param ',ta('b','Parameter: '),$desca[$i]);
      $desca[$i]=str_replace('@return ',ta('b','Return value: '),$desca[$i]);  
      $de.=str_replace('/**',' ',$desca[$i]).br();
    }
    $running_from=str_replace('d.php','',getenv('SCRIPT_FILENAME'));
    $de.=ta('b','File: ').str_replace($running_from,'',$filename).br();
    
    $methods=$rc->getMethods();
    $de.=ta('h3','Methods:').br();
    foreach ($methods as $method){
      $de.=dokumentace_metoda($jmeno_tridy,$method);
    }
     
    return tg('a','name="'.$jmeno_tridy.'"',ta('h1',$jmeno_tridy)).
           ta('p',$de).br();
  }  
 
   function dokumentace_metoda($jmeno_tridy,$method){
     $desc=$method->getDocComment();
     $params=$method->getParameters();
     $desca=explode("\n",$desc);
     $de='';
     for($i=0;isset($desca[$i]);$i++){
      if (preg_match('/^\s+\*\s+(.*)$/',$desca[$i],$m) ) {
        $desca[$i]=$m[1];
      }  
      if (substr($desca[$i],-2)=='*/') {
        $desca[$i]=substr($desca[$i],0,-2);
      }
      $desca[$i]=str_replace('@param ',ta('b','Parameter: '),$desca[$i]);
      $desca[$i]=str_replace('@return ',ta('b','Return value: '),$desca[$i]);  
      $de.=str_replace('/**',' ',$desca[$i]).br();
     }
     $par='';
     foreach ($params as $param ){
       $par.=($par==''?'':', ').'$'.($param->getName());
       $add=(string)$param;
       if (preg_match('/(.+) = (.+) ]$/',$add,$m)){
        $par.=' = '.$m[2];
       }
     }
     if (strpos($body,'func_get_arg')!==false){
       $par.='...'; 
     }
      
     return ta('h5',$jmeno_tridy.' :: '.$method->name.'('.$par.')').
            ta('p',$de);
   }

/**
 * Get all classes from a project.
 *
 * Return an array containing all classes defined in a project.
 *
 * @param string $project_path
 * @return array
 */
  function smk_get_classes_from_project( $project_path ){
    // Placeholder for final output
    $classes = array();

    // Get all classes
    $dc = get_declared_classes();

    // Loop
    foreach ($dc as $class) {
        $reflect = new \ReflectionClass($class);

        // Get the path to the file where is defined this class.
        $filename = $reflect->getFileName();
   
        // Only user defined classes, exclude internal or classes added by PHP extensions.
        if( ! $reflect->isInternal() ){

            // Replace backslash with forward slash.
            $filename = str_replace(array('\\'), array('/'), $filename);
            $project_path = str_replace(array('\\'), array('/'), $project_path);

            // Remove the last slash. 
            // If last slash is present, some classes from root will not be included.
            // Probably there's an explication for this. I don't know...
            $project_path = rtrim( $project_path, '/' );

            // Add the class only if it is defined in `$project_path` dir.
            if( stripos( $filename, $project_path ) !== false ){
                $classes[] = $class;
            }

        }
    }

    return $classes;    
  }





/*
   Jina moznost omezeni na definovane tridy :
   
$userDefinedClasses = array_filter(
    get_declared_classes(),
    function($className) {
        return !call_user_func(
            array(new ReflectionClass($className), 'isInternal')
        );
    }
);

*/



?>                                 