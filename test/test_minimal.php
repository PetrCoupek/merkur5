<?php
/** Merkur 5 minimal solution @author Petr Coupek*/
include_once '../lib/mlib.php';
M5::set('header','Header Hello');
M5::skeleton('../');
htpr('World body');
M5::done();
?>