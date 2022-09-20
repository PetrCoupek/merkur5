<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
M5::set('header','Zero test');
M5::skeleton('../');
htpr(ta('h1','Hello'),
     'This is the zero functionality test ', br(),
     'PHP version: '.PHP_VERSION_ID);
htpr_all();

?>