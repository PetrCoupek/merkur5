<?php

include_once '../lib/mlib.php';
M5::set('header','Sieve of Eratosthenes');
M5::skeleton('../');
$limit=100000;
htpr( wordwrap(
    "Primes less or equal than $limit are : " . PHP_EOL .
    implode(' ', array_keys(iprimes_upto($limit), true, true)),
    80
));

htpr_all();

/*----------------------------------*/
function iprimes_upto($limit)
{
    for ($i = 2; $i < $limit; $i++)
    {
	$primes[$i] = true;
    }
 
    for ($n = 2; $n < $limit; $n++)
    {
	if ($primes[$n])
	{
	    for ($i = $n*$n; $i < $limit; $i += $n)
	    {
		$primes[$i] = false;
	    }
	}
    }
 
    return $primes;
}
 

?>