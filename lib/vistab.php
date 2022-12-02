<?php
/** Data visualization
 * @author Petr Coupek
 * 
 *  class for table view, filtering sortin and more.
 *  call example:
 *    $db= new OpenDB_Oracle($napojeni);
 *    $tt= new VisTab(['table'=>$t],$db); 
 *    $tt->route("&vyhl=1&ROL_=1");
 *  or:
 *    $tt= new VisTab(['sprikaz'=>"select ...", 
 *                     'cprikaz'=>'select ..',
 *                     'pragma'=>'..',
 *                     'dprikaz'=>'..'],$db); 
 * 
 *  Special data searching instecting can be extended from this class
 *  18.10.2022
 */ 

class VisTab {

  var $separator='~';

function __construct($param,$db){

  $this->db=$db;
  $this->nastrane=15;
  if (isset($param['table'])){
    $t=$param['table'];
    $this->header=$t;
    $this->sprikaz="select * from $t ";
    $this->cprikaz="select count(*) as pocet from $t";
    $this->pragma=$this->db->Pragma("table_info('$t')");
    $this->dprikaz=$this->sprikaz;
    /* primarni klic z pragma informace */
    for($i=0,$pk='';$i<count($this->pragma);$i++)
      if (isset($this->pragma[$i]['pk'])) 
        $pk.=($pk==''?'':',').$this->pragma[$i]['name'];
    $this->pk=$pk;    
  }elseif(isset($param['sprikaz']) && isset($param['cprikaz'])){
    $this->header=(isset($param['header'])?$param['header']:'[head]');
    $this->sprikaz=$param['sprikaz'];
    $this->cprikaz=$param['cprikaz'];
    $this->pragma=$param['pragma'];
    $this->dprikaz=$param['dprikaz'];
  }
  if (isset($param['postlink']) && $param['postlink']){
    $this->postlink=true;  
  }else{
    $this->postlink=false;
  } 
  $this->getfilter(); /* rozbaleni filtru podle parametru flt */

  /* generovani where probiha vzdy */
  setpar('_whr',$this->genwhere()); /* interne se parametr odkazuje pomoci getpar('_whr') */
  //deb(M5::getparm());
}

function route($context){
  
  if (getpar('_se')){
    $this->form_param($context);
  }elseif (getpar('_de')){
    $this->detail($context);   
  }else{
    $this->lister($context);
  }

}

function lister($context){
  /* zpracovani potvrzeneho formulare pro omezeni - filtrovani */
  if (getpar('_sg')) {     
    setpar('_whr',$this->genwhere());
    setpar('_flt',urlencode($this->packfilter()));
  }
  $sprikaz=$this->genfilter($this->sprikaz);
  $cprikaz=$this->genfilter($this->cprikaz);
  
  $a=$this->db->SqlFetchArray($sprikaz,[],15,getpar('_ofs',1));
  /* pokud je nastaveny primarni klic, jde generovat sloupec
    s linkem na prechod do detailu */
  //if ($this->pk!=''){
  //  for($i=0;$i<count($a);$i++){
  //    $l='?_de=1&amp;_o='.getpar('_o').'&amp;_whr='.getpar('_whr').'&amp;_ofs='.getpar('_ofs');
  //    foreach (explode(',',$this->pk) as $v) $l.='&amp;'.$v.'='.$a[$i][$v];
  //    $a[$i]['detail']=ahref($l.$context,bt_icon('pencil'));        
  //  }   
  //}
  if (count($a)>0){
    for($i=0,$j=getpar('_ofs',1);$i<count($a);$i++,$j++){
      if ($this->postlink){
        $a[$i]['detail']=postLink('?'.$context,bt_icon('pencil'),
         ['_de'=>'1','_o'=>getpar('o'),'_flt'=>getpar('_flt'),'_ofs'=>$j],'class="card text-primary"');
      }else{
        $l='?_de=1&amp;_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$j;
        $a[$i]['detail']=ahref($l.$context,bt_icon('pencil'));
      }
    }  

    /* tisk tabulky a listovani */
    htpr(
      bt_lister(
        $this->header,
        $this->column_labels(),
        $a,
        'Nejsou záznamy.',
        '',
        bt_pagination(
          getpar('_ofs',1),
          $this->db->SqlFetch($cprikaz),
          15,
          $context.'&_o='.getpar('_o').'&_flt='.getpar('_flt'),
          $this->postlink
        ),
        $context.'&_flt='.getpar('_flt'),
        $this->postlink)
    );
  }else{
    htpr(bt_alert('Nejsou záznamy','alert-warning'));
    $this->form_param($context);

  }
}

/** parametric form for filtering the table view 
 *  to be overriden in extented class based on VisTab
*/
function form_param($context){
  
  //$this->dewhere(base64_decode(getpar('_whr')));
  /*$a=$this->db->Pragma("table_info('$t')");*/
  $a=$this->pragma;
  if (!is_array($a)) {deb('wrong pragma'); return 0;}
  $b=array();
  for ($i=0;$i<count($a);$i++){
    if (isset($a[$i]['name'])){
      $b[$i][0]=(isset($a[$i]['comment']))?$a[$i]['comment']:$a[$i]['name'];
      $b[$i][1]=combo('',$a[$i]['name'].'_par',[
                    'like'=>'obsahuje',
                    'begins'=>'začíná',
                    '='=>'='],
                    getpar($a[$i]['name'].'_par')?getpar($a[$i]['name'].'_par'):'like');
      $b[$i][2]=textfield('',$a[$i]['name'],20,40,getpar($a[$i]['name']));
    }                  
  }
  $b[$i]=[nbsp(1),submit('_st','Storno','btn btn-secondary'),submit('_sg','Vyhledej','btn btn-primary')];
  htpr(tg('form',
          'method="post" action="?'.$context.'&_o='.getpar('_o').'"',
           bt_container(['col-4','col-2','col-6'],$b))); 

}

/** based on pragma, it constructs the labels fo columns needed by bt_lister
 */ 
function column_labels(){
  $a=$this->pragma;
  if (!is_array($a)) {
    return ['detail'=>'Detail'];
  }
  $b=array();
  for ($i=0;$i<count($a);$i++){
    if (isset($a[$i]['name'])){
      $b[$a[$i]['name']]=(isset($a[$i]['comment']))?$a[$i]['comment']:$a[$i]['name'];
    }                  
  }
  $b['detail']='Detail';
  return $b;
}

/** genwhere - generovani podminky where z parametrickeho formulare 
 * 
*/
function genwhere(){
 
  //$DAT=M5::getparm();
  $DAT=$GLOBALS['DATA'];
  $where='';
  $find_ascii=false;
  if (getpar('GPA_')){
    /* generovani where u sestavovane podminky */
    $spojka=''; $zav=0;
    
    foreach ($DAT as $pol => $value){
      if (preg_match("/^par_(\d+)$/", $pol, $match)){
        if ($DAT[$pol]!=''){
          if (strpos($DAT[$pol.'_g'],'(')!==false) {$zav++;}
          if (strpos($DAT[$pol.'_g'],')')!==false) {$zav--;}
          if ($where != ''){
            $where.=$spojka;
          }
          if ($DAT[$pol.'_p'] == 'like') {
            $DAT[$pol.'_t'] = '%'.$DAT[$pol.'_t'].'%';
          }
          if ($DAT[$pol.'_p'] == 'begins') {
            $DAT[$pol.'_t'] = $DAT[$pol.'_t'].'%';
            $DAT[$pol.'_p'] = 'like';
          }
          if ($DAT[$pol.'_p'] == 'ends') {
            $DAT[$pol.'_t'] = '%'.$DAT[$pol.'_t'];
            $DAT[$pol.'_p'] = 'like';
          }
          if ($find_ascii){
            /* prevzeto 27.04.2022 modifikoval 28.8.2014 Vaclav Pospisil - podminka bere to, ze se odbourava diakritik*/
					  $where.="upper(convert(".$DAT[$pol].",'US7ASCII')) ".$DAT[$pol.'_p']." upper(convert('".$DAT[$pol.'_t']."','US7ASCII')) ";
					}else{  
            $where.=$DAT[$pol].' '.$DAT[$pol.'_p']." '".$DAT[$pol.'_t']."' ";
          }
          $spojka=$DAT[$pol.'_g']." ";
        }
      }
    }
    /* posledni spojka by mela byt bud uzaviraci zavorka nebo je ignorovana*/
    if(($spojka==') ')||($zav>0)){
       $where.=str_repeat(')',$zav);
    }
  }else{
    /* klasicky parametricky formular */
    foreach ($DAT as $pol => $value){
      if (preg_match("/^(.+)_par$/",$pol, $match)){ /* prochazej dvojice ATTR a ATTR_par*/       
        $bezpar = $match[1];
        $atribut=$bezpar;     /* $atribut obsahuje jmeno atributu, ktery je dotazovan */
        if (preg_match("/^(.+)_and(\d*)$/",$bezpar, $match)){
          $atribut= $match[1]; 
        } 
        /* jednotlive podminky se spojuji pomoci and , ale u prvniho and neni */
        $p=($where != '')?' and ':'';
        /* null a not null nemusi mit vyplnenou hodnotu $DATA{$bezpar} muze byt prazdne */
        if ($DAT[$pol] == 'null' || $DAT[$pol] == 'not null'){
          $where.=$p."$bezpar is $DAT[$pol]";  continue;
        }
        if (isset($DAT[$bezpar]) && $DAT[$bezpar]!=''){
          $citlivost=isset($DAT[$bezpar.'_uns']) && ($DAT[$bezpar.'_uns']!='');
          if ($DAT[$pol] == 'like'){
            $DAT[$bezpar] = "'%".$DAT[$bezpar]."%'";
          }elseif ($DAT[$pol] == 'begins') {
            $DAT[$bezpar] = "'".$DAT[$bezpar]."%'";
            $DAT[$pol]='like';
          }elseif ($DAT[$pol] == 'ends') {
            $DAT[$bezpar] = "'%".$DAT[$bezpar]."'";
            $DAT[$pol]='like';  
          }elseif ($DAT[$pol] == 'in' or $DAT[$pol] == 'not in'){
            /* muze jit bud o multiselect a nebo seznam hodnot oddelenych carkou */
            if (is_array($DAT[$bezpar])){
              $p1=$DAT[$bezpar];
            }else{
              $p1=explode(',',$DAT[$bezpar]); $p2='';
            }
            foreach ($p1 as $v){
              $p2.=(($p2=='')?'':',')."'".$v."'";
            }
            $DAT[$bezpar]="( ".$p2." )";
          }else{
             $DAT[$bezpar]="'".$DAT[$bezpar]."'";
          }
          if ($citlivost){
            /* podle Vaclav Pospisil - podminka bere to, ze se odbourava diakritika */
					  $where.=$p."upper(convert($atribut,'US7ASCII')) $DAT[$pol] upper(convert($DAT[$bezpar],'US7ASCII'))";
          }else{
              $where.=$p."$atribut $DAT[$pol] $DAT[$bezpar]";
          }
        }
      }      
    }
  }
  return $where;
}

/** packs filter params as one param named _flt  
 * 
*/
function packfilter(){  
  //$DAT=M5::getparm();
  $DAT=$GLOBALS['DATA'];
  $s='';
  foreach ($DAT as $pol => $value){
    if (preg_match("/^(.+)_par$/",$pol, $m)){
      $a= $m[1];
      if (isset($DAT[$a]) && $DAT[$a]!=''){
        $s.=($s==''?'':$this->separator).$a.$this->separator.$DAT[$a.'_par'].
         $this->separator.$DAT[$a];
      }  
    }
  }
  return $s;
}

/** packs filter params as one param named _flt  
 * @return string
*/
function packfilter0(){ 
  $s='';
  foreach ($this->filter as $k=>$v) if (preg_match("/^(.+)_par$/",$k, $m)){
    $ka=$m[1];
    $s.=($s==''?'':$this->separator).$ka.$this->separator.($this->filter[$k]).
     $this->separator.($this->filter[$ka]); 
  }
  return $s;
}

/** retrieve the flt parameter and stores it to the normal params */
function getfilter(){
  $flt=getpar('_flt');
  //deb('Rozbaleni',false);
  if ($flt!=''){
     $flt=urldecode($flt);
     //deb($flt,false);
     $F=explode($this->separator,$flt);
     //deb($F,false);
     for($i=0;$i<count($F);$i=$i+3){
       setpar($F[$i],$F[$i+2]);
       setpar($F[$i].'_par',$F[$i+1]);
     }
  }
  //deb($GLOBALS['DATA']);
}


/** sestaveni podminky where a serazeni do zadaneho prikazu  
 * @param  string $prikaz
 * @return string 
 * 
*/
function genfilter($sprikaz){
  $where=getpar('_whr');
  //deb($where);
  $sprikaz=preg_replace("/\x0d/",' ',$sprikaz);
  $sprikaz=preg_replace("/\x0a/",' ',$sprikaz); //odstran odradkovani, aby fungoval r. vyraz
  $oby=(getpar('_o')!='')?(' '.getpar('_o')):'';
  $whr=(getpar('_whr')!='')?(' where '.$where):'';
  $whradd=(getpar('_whr')!='')?(' and '.$where):'';
  
  if ($oby!=''){
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$oby; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].$whr.' order by '.$oby;
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$oby;
    }else{
      $sprikaz.=$whr.' order by '.$oby;
    }
  }else{
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$match[3]; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].$whr.' order by '.$match[2];
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].') '.$whradd;
    }else{
      $sprikaz.=' '.$whr;
    }
  }
  //deb($sprikaz);
  return $sprikaz;  
}

/** dewhere - pro formular parametru provede zpetne prevedeni where na parametry
 *  pokud je where ve tvaru konjukce podminek AND, prevod se povede
 *  @param $where
 *  result - set of M5 script parametres
 */
function dewhere($where){
  if ($where!=''){
    $a=explode(' and ',$where);
    //deb($a, false); 
    for ($i=0;$i<count($a);$i++){
      $b=explode(' ',$a[$i]);
      //deb($b[1],false);
      if (preg_match("/^'(.*)'$/",$b[2],$m)){
         $b[2]=$m[1];
      }
      if (preg_match("/^%(.*)%$/",$b[2],$m)){
        $b[2]=$m[1]; $b[1]='like';
      }
      if (preg_match("/^(.*)%$/",$b[2],$m)){
        $b[2]=$m[1]; $b[1]='begins';
      }
      setpar($b[0],$b[2]);
      setpar($b[0].'_par',trim($b[1]));
    } 
  } 
}

/** Stranka s detailem zaznamu
 * 
 */
function detail($context){

  /* pritahnuti vety dprikaz - sestaveni podminky na zaklade znalosti pk */
  
  $cprikaz=$this->genfilter($this->cprikaz);
  $dprikaz=$this->genfilter($this->dprikaz);
  $r=$this->db->SqlFetchArray($dprikaz,[],1,getpar('_ofs',1));
  /* popisy polozek mohou byt z popisu entity v databazi */
  $p=[];
  for($i=0;$i<count($this->pragma);$i++)
    if (isset($this->pragma[$i]['comment']) && $this->pragma[$i]['comment']!='')
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['comment'];
    else 
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['name'];

  $b=[[]];$i=0;
  foreach ($r[0] as $k=>$v){
    if (isset($p[$k])){
      $b[$i][0]=ta('b',$p[$k]);
      $b[$i][1]=$v; 
      $i++;
    }  
  }

  /* pocet zaznamu a listovani po zaznamech */
  $cprikaz=$this->genfilter($this->cprikaz);
  $ofs= getpar('_ofs')-getpar('_ofs')%$this->nastrane+1; /* navratovy offset odkazuje na naslitovanou stranku */
  if ($this->postlink){
    $back=postLink('?'.$context,'Zpět',
                   ['_o'=>getpar('_o'),
                   '_flt'=>getpar('_flt'),
                   '_ofs'=>$ofs],
                   'class="btn btn-primary"');
  }else{
    $back=ahref('?_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$ofs.$context,
      'Zpět',
      'class="btn btn-primary"');
  }     


  htpr(ta('h3','Detail'),
       bt_pagination(
            getpar('_ofs',1),
            $this->db->SqlFetch($cprikaz),
            1,
            $this->postlink?($context.'&_de=1&_flt='.getpar('_flt')):($context.'&_o='.getpar('_o').'&_flt='.getpar('_flt').'&_de=1'),
            $this->postlink
          ),
       bt_container(['col-4','col-8'],$b),
       $back
      );


}

} /* class */

 
?>