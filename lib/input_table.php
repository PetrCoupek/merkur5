<?php

/** Class for multiple/table input of table data
 */
class Clipboard_table {
var $h, $idform, $comments;

/**
 * @param array $h - header and table definition
 * @param string $idform - identifier for data area
 */
function __construct($h,$idform="frm"){
  for ($i=0;$i<count($h);$i++) if (!isset($h[$i]['title'])) $h[$i]['title']=$h[$i]['head'];
  $this->h=$h;
  $this->idform=$idform;
  $this->comments=[];
  /* prepare comments structure - depend also on posted content, whe exists */
  for ($i=0;$i<$this->count_rows();$i++) {
    $this->comments[$i]=[];
    for ($j=0;$j<count($h);$j++){
      $this->comments[$i][$h[$j]['name']]=''; /* no comment/error */
    }
  }  
}  

/** routes the functionality - it calls the input_table or when posting, the post_table 
 *  @param array $comments - array of comments (possibly rejected cells ) for reparing - used in post_table
 * 
*/
function input_table(){
  return getpar($this->idform.'_POST')?$this->post_table():$this->init_table();
}

/** It prepares an area and waits for Ctrl+V - javascript task
 */
function init_table(){
  $r='';
  /* generuj hlavicku */
  $hlav=ta('th','n');
  for ($i=0;$i<count($this->h);$i++){
    $hlav.=tg('th','title="'.$this->h[$i]['title'].'" ',$this->h[$i]['head'] );
  }
  $th=json_encode($this->h);
  $script=
 <<<EOT
  document.addEventListener("paste", function (event) {
    window.clipText = event.clipboardData.getData("Text");
    render_form(window.clipText,$th); 
  });


  function render_form(cb,h){
     ncol=h.length;
     if (cb==""){
        document.querySelector("#$this->idform").innerHTML = "Neni zvolena vybrana oblast."+cb;
           return 0;
     }
     //console.log(cb);
     var a=cb.split('\\n');
     for (var i=0;i<a.length;i++){
      if(a[i]){
       if (a[i].split("\\t").length!==ncol){
           document.querySelector("#$this->idform").innerHTML = "Pocet sloupcu vybrane oblasti musi byt "+ncol+" .";
           return 0;
        }
      }
     } 
    var html = "<table class=\"tabe\"><th>n</th>";
    for(i=0;i<ncol;i++){
      html+="<th title=\""+h[i].title+"\">"+h[i].head+"</th>";
    }
    ccol=1;  
    cb.split("\\n").forEach(function(line,index){
      if(line){
        html += "<tr><td>"+ccol+"</td>";
        var a=line.split('\\t');
        for(i=0;i<ncol;i++){
          html += '<td><input type="text" name="'+h[i].name+index+'" size="'+h[i].size+'" maxlength="'+h[i].maxlength+'" value="'+ a[i] +'"></td>';
        }
        html += "</tr>";
        ccol++;
      }
    });
    html += "</table>";
    html +='<input type="hidden" name="$this->idform'+'_POST'+'" value="1">';
    document.querySelector("#$this->idform").innerHTML = html;
  }
  EOT
  ;
  $r.=ta('script',$script).
    tg('div','id="'.$this->idform.'"',ta('table',$hlav).'[místo pro vložení dat Ctrl + v ] ');
  return $r;
}

/** render the Post- data back to be repaired/edited in the front-end - PHP task
 */ 
function post_table(){
  $hlav=ta('th','n');
  for ($i=0;$i<count($this->h);$i++){
    $hlav.=tg('th','title="'.$this->h[$i]['title'].'" ',$this->h[$i]['head'] );
  }
  $hlav.=ta('th','pozn.');
  $telo='';
  $pov=$this->count_rows();
  if ($pov>0){
    for ($i=0;$i<$pov;$i++){
      $pom=ta('td',$i+1);
      $rowc=''; /* row comment/error message */
      for($j=0;$j<count($this->h);$j++){
        if ($this->comments[$i][$this->h[$j]['name']]!='') {
          $add='style="background-color:pink"';
          $rowc.=($rowc==''?'':', ').$this->h[$j]['head'].': '.$this->comments[$i][$this->h[$j]['name']];
        }else{
          $add='';
        }
        $pom.=ta('td',tg('input','type="text" name="'.$this->h[$j]['name'].$i.'" '.
                         'size="'.$this->h[$j]['size'].'" maxlength="'.$this->h[$j]['maxlength'].'" '.
                         'value="'.getpar($this->h[$j]['name'].$i).'" '.  
                         $add,'noslash'));
      }
      $pom.=tg('td',$rowc!=''?'style="background-color:pink"':'',$rowc);
      $telo.=ta('tr',$pom);
    } 
  } 
  $r=tg('div','id="'.$this->idform.'"',tg('table','class="tabe"',$hlav.$telo)).
     para($this->idform.'_POST','1');      
  return $r;      
} 


/** pocita vstupni parametry az do mista, kde na radku nic neni 
 * */
function count_rows(){
  $pov=0;
  while (true){
    for($i=0;$i<count($this->h);$i++)
      if (getpar($this->h[$i]['name'].$pov)!='') break;
    if ($i==count($this->h)) return $pov;
    $pov++; 
  }
} 

}
?>