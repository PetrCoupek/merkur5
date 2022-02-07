<?php
/** Merkur 5 zero appliation
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
M5::set('header','Zero test');
M5::skeleton('../');
htpr(ta('h1','Hello'),
     'This is the zero functionality test');
htpr_all();

?>