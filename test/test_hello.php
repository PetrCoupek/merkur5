<?php
/** Merkur 5 hello world application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';


class Hello extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route */
   parent::set('header','Minimal application');
   htpr(ta('p','Hello world. That\'s it !'));      /* Tisk funkcionality */
   htpr_all();                        /* Zapis bufferu na standarni vystup */
 }

} /* enf of class definition Hello */

/* design sigleton,
 */

Hello::skeleton();  /* Let's call the skeleton method of the class Hello */

?>