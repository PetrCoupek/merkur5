<?php
/** system information
 */ 

function parse_phpinfo() {
    ob_start(); 
    phpinfo(); 
    $s = ob_get_contents(); 
    ob_end_clean();
    $s = strip_tags($s, '<h2><th><td>');
    $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
    $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
    $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $r = array(); $count = count($t);
    $p1 = '<info>([^<]+)<\/info>';
    $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
    $p3 = '/'.$p1.'\s*'.$p1.'/';
    for ($i = 1; $i < $count; $i++) {
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
            $name = trim($matchs[1]);
            $vals = explode("\n", $t[$i + 1]);
            foreach ($vals AS $val) {
                if (preg_match($p2, $val, $matchs)) { // 3cols
                    $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                    $r[$name][trim($matchs[1])] = trim($matchs[2]);
                }
            }
        }
    }
    return $r;
}

  //htpr(print_r(parse_phpinfo(),true));
  $info=parse_phpinfo();
  foreach ($info as $k=>$v) {
    htpr(ahref("#$k",$k));
  }
  
  foreach ($info as $k=>$v) {
    htpr(tg('a','name="'.$k.'"',' '),ta('h3',$k));
    $s='';
    foreach ($v as $kk=>$vv){
      $s.=ta('tr',ta('td', ta('b',$kk).": ").ta('td',is_array($vv)?implode(',',$vv):$vv));
    }
    htpr(ta('p',ta('table',$s)));
  }            
?>