<?php
/** Merkur 5 test php info 
 * @author Petr Coupek
 * @date 01.12.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test PHP info');
M5::set('debug',true);
M5::skeleton('../');

php_info();   
htpr_all();
function php_info(){
  ob_start(); 
  phpinfo();
  $phpinfo = array('phpinfo' => array());
  if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?>'.
   '<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>'.
   '(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
    foreach($matches as $match)
      if(strlen($match[1]))
        $phpinfo[$match[1]] = array();
      elseif(isset($match[3]))
        $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
      else
        $phpinfo[end(array_keys($phpinfo))][] = $match[2];

  foreach($phpinfo as $name => $section) {
    htpr("<h3>$name</h3>\n<table border=0 size=100%>\n");
    foreach($section as $key => $val) {
      if(is_array($val))
        htpr("<tr><td>$key</td><td>$val[0]</td><td>$val[1]</td></tr>\n");
      elseif(is_string($key))
        htpr("<tr><td>$key</td><td>$val</td></tr>\n");
      else
        htpr("<tr><td>$val</td></tr>\n");
    }
    htpr( "</table>\n");
  }
}
?>