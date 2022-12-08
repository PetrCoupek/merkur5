<?php
/** This example shows the tick functionality for time delay measurement */

include_once '../lib/mlib.php';
M5::set('header','Sieve of Eratosthenes');
M5::set('debug',true); /* when debug is false, you can work with tick returned string */
M5::skeleton('../');

$s1=tick('end of initialization');
$limit=5000;
htpr(
  wordwrap(
    "Primes less or equal than $limit are : " . PHP_EOL .
    implode(' ', array_keys(iprimes_upto($limit), true, true)),80));
$s2=tick('calculating is done');
htpr(hr(),$s1,br(),$s2);
htpr_all();

/*----------------------------------*/
function iprimes_upto($limit){
  for($i=2;$i<$limit;$i++)
    $primes[$i]=true; 
  for($n=2;$n<$limit;$n++)
	if ($primes[$n])
	  for($i=$n*$n;$i<$limit;$i+=$n)
	    $primes[$i]=false;	   
  return $primes;
}
 

?>