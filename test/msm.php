<?php

/* script na generovani staticke mapky z ArcGIs serveru 
   05.06.2023 - refaktoring: vyuziti sp. fce get_file pro nactitani potencialne zabezpeceneho zdroje             
   ------------------------------------------------------- 
   parametry v URL:
   w, h: výška a šířka mapky v pixelech (výchozí 400)
   
   s1 - identifikace spodní mapové služby, jen cesta ke službě na serveru
   l2 - parametr layers pro spodní mapovou vrstvu (nepovinný) - je ve tvaru show:1,3 nebo hide:2,4 atd.
   s2 - identifikace horní mapové služby, jen cesta ke službě na serveru,
   l2 - parametr layers pro horní mapovou vrstvu (nepovinný) - je ve tvaru show:1,3 nebo hide:2,4 atd.
   
*/

include_once "../lib/mlib.php"; 
define('MAPSERVER1','https://mapy.geology.cz/ArcGIS/rest/services/');
define('MAPOVA_SLUZBA1','Topografie/ZABAGED_komplet/MapServer');
define('MAPSERVER2','https://app.geology.cz/sm/');
define('MAPOVA_SLUZBA2','dkb/');

$dbconnect='dsn=sdedb02;uid=APP_DKB;pwd=jsdn*6343Jkjsedn*324';
$mapserver1=MAPSERVER1;
$mapserver2=MAPSERVER2;
$mapova_sluzba1=MAPOVA_SLUZBA1;
$mapova_sluzba2=MAPOVA_SLUZBA2;

$sizex=getpar('w')?getpar('w'):300;
$sizey=getpar('h')?getpar('h'):300;
$size="$sizex,$sizey";
$fade=90; /* zblednuti pozadi*/
$dist=400;

/* rozmery vyrezu*/
$x=getpar('x');
$y=getpar('y');
if ($x=='' || $y==''){
  chybka('-');    
	exit;
}
$x1=$x+$dist;
$x2=$x-$dist;
$y1=$y-$dist;
$y2=$y+$dist;
/*$x1=isset($_GET['x1'])?$_GET['x1']:596098;
$x2=isset($_GET['x2'])?$_GET['x2']:594156;
$y1=isset($_GET['y1'])?$_GET['y1']:1162217;
$y2=isset($_GET['y2'])?$_GET['y2']:1163903;
*/
$l1=isset($_GET['l1'])?$_GET['l1']:'';
$l2=isset($_GET['l2'])?$_GET['l2']:''; /* pokud bude l2 '', tak se vrstva nevola */


$mapova_sluzba1=isset($_GET['s1'])?$_GET['s1']:$mapova_sluzba1;
$mapova_sluzba2=isset($_GET['s2'])?$_GET['s2']:$mapova_sluzba2;
$typ=0;

/* nejprve se musi zjistit bbox, ve kterem je omisten dany objekt */
/* zakres loziska muze byt nekolik zaznamu ve trech tabulkach */

  
/* x1,x2, y1,y2 se musi upravit tak aby rozdil (x2-x1)/(y2-y1) bylo stejne jako sizex/sizey */
if (0){
$dnew=($y2-$y1)*$sizex/$sizey; /* kolik by mělo být na x-ose, aby byl zachován poměr */
$dcur=$x1-$x2; /* kolik je nyní */
if ($dnew>$dcur){ /* pokud je stavajici stav mensi, rozsir osu x - vyrez nelze zmensit */
  $pul=($dnew-$dcur)/2;
  $x1+=$pul;
  $x2-=$pul;
}else{
  /* zvets osu y - toto ale zlobi pro linie .. */   
  if ($typ=='pol'){
    $dnew=abs(($y2-$y1)*$sizex/$sizey); /* kolik by mělo být na x-ose, aby byl zachován poměr */
    $dcur=$y1-$y2; /* kolik je nyní */
    $pul=($dnew-$dcur)/2;
    $y1-=$pul;
    $y2+=$pul;
  }
}
}

$cesta=$mapserver1.$mapova_sluzba1."/export?bbox=-$x1,-$y2,-$x2,-$y1&f=image&format=png24&size=".$size;
if ($l1!='') $cesta.='&layers='.$l1;
if(!$im = @imagecreatefrompng($cesta)){
  chybka('-');    
	exit;
}
if ($x1==0 || $y2==0){
  chybka('-');
}

if ($l2!='none'){
  $cesta=$mapserver2.$mapova_sluzba2."/export?bbox=-$x1,-$y2,-$x2,-$y1&f=image&format=png24&size=".$size;
  
  if ($l2!='') $cesta.='&layers='.$l2;
  $png=get_image($cesta);
  if ($png[0]==200) if($im2 = imagecreatefromstring($png[1])){
    imagealphablending($im2, false);
    imagesavealpha($im2,true);
    //$transparent = imagecolorallocatealpha($im2, 255, 255, 255, 127);  nepracuje
    $transparent=imagecolorat($im2, 0, 0); // toto neni uplne ciste, ale jinak pruhlednou barvu zatim neumime
    //$transparent=imagecolorallocate($im,255,255,255);  nepracuje
    //imagecolortransparent($im,$transparent);
    imagecolortransparent($im2,$transparent);
    imagecopymerge($im, $im2, 0, 0, 0, 0, $sizex, $sizey, $fade);
  }else{
    chybka('-');
  }
}  
//imagecopy($im, $im2, 0, 0, 0, 0, $sizex, $sizey);
$pcolor=imagecolorallocatealpha($im, 253, 133, 238, 0);  
$lx1=-$x1; $ly1=-$y1; 
$lx2=-$x2; $ly2=-$y2;
$dx=$lx2-$lx1;  $dy=$ly2-$ly1;
for($h=20;$h<40;$h+=1){
  imageellipse($im,round((-$x-$lx1)/$dx*$sizex),
  round((-$y-$ly1)/$dy*$sizey), $h, $h, $pcolor);
  
}  




meritko($im,imagecolorallocate($im, 0, 0, 0),$sizex,$sizey,$x1,$x2);
header("Content-type: image/png");
imagepng($im); 
imagedestroy($im); /*zruseni puvodniho obrazku */

/*---------------------------------------------------------------------------*/

/** funkce zobazujici chybove hlaseni jako obrazek misto puvodniho
 * 
 */
function chybka($retezec){
  header("Content-type: image/png");
	$im = @imagecreate(30, 30)
    or die("Cannot Initialize new GD image stream");
	$background_color = imagecolorallocate($im, 255, 255, 255);
	$text_color = imagecolorallocate($im, 233, 14, 91);
	imagestring($im, 1, 5, 5,  $retezec, $text_color);
	imagepng($im);
	imagedestroy($im);
}

function imageBoldLine($resource, $x1, $y1, $x2, $y2, $Color, $BoldNess=2, $func='imageLine'){
 $center = round($BoldNess/2);
 for($i=0;$i<$BoldNess;$i++) { 
  $a = $center-$i; if($a<0){$a -= $a;}
  for($j=0;$j<$BoldNess;$j++){
   $b = $center-$j; if($b<0){$b -= $b;}
   $c = sqrt($a*$a + $b*$b);
   if($c<=$BoldNess){
    $func($resource, $x1 +$i, $y1+$j, $x2 +$i, $y2+$j, $Color);
   }
  }
 }        
}

/**
 *  nakresli vpravo dole orientacni meritko mapoveho vyrezu 
 * */
function meritko($im,$mcolor,$sizex,$sizey,$x1,$x2){
  global $im;
  $mp=($x1-$x2)/$sizex;   /* nasobitel pro umisteni meritka */
  $pixelu=round(100/$mp); /* kolik pixelu je 100 metru. */
  
  imageBoldLine($im, $sizex-35-$pixelu, $sizey-25, $sizex-35, $sizey-25, $mcolor, 4);
  imageLine($im, $sizex-35-$pixelu, $sizey-18, $sizex-35-$pixelu, $sizey-25, $mcolor);
  imageLine($im, $sizex-32, $sizey-18, $sizex-32, $sizey-25, $mcolor);
  imagestring($im, 2, $sizex-35-$pixelu, $sizey-15, '0', $mcolor);
  imagestring($im, 2, $sizex-40, $sizey-15, '100 m', $mcolor);
}

/** vrati obrazek generovany z URL jako string
 * @param string $imgUrl     URL obrazku 
 * @param string $username   uzivatelske jmeno pro pristup, pokud je potrea (nepovinne)
 * @param string $password   heslo pro pristup, pokud je potrea (nepovinne)
 * @return array [$rescode, $content] navratovy kod a obsah. Navratovy kod 200 je uspech, jinak $content obsahuje chybu
 */ 
function get_image($imgUrl,$username=null,$password=null){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $imgUrl);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11');
  if (isset($username)){
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
  }    
  $res = curl_exec($ch);
  $rescode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$rescode, $res];
}  


?>