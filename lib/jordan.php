<?php
if (0){
$poi[0]=200;
$poi[1]=190;
 
/* How many times the ray crosses a line-segment */
$crossings = 0;
 
/* Coordinates of the points */
$pol[0][0] = 100;     $pol[1][0] = 100;
$pol[0][1] = 200;     $pol[1][1] = 200;
$pol[0][2] = 300;     $pol[1][2] = 200;
$pol[0][3] = 300;     $pol[1][3] = 170;
$pol[0][4] = 240;     $pol[1][4] = 170;
$pol[0][5] = 240;     $pol[1][5] = 90;
$pol[0][6] = 330;     $pol[1][6] = 140;
$pol[0][7] = 270;     $pol[1][7] = 30;

if (is_in_polygon($poi,$pol)){
  echo "ano";
}else{
  echo "ne";
}

}

/** Jordan algorithm for is in the polygon decision 
 * @param array $poi
 * @param array $pol
 * @return bool true if the point is inside the polygon
*/
function is_in_polygon($poi,$pol){
  /* Iterate through each line */
  $n=count($pol);
  $crossings=0;
  for($i=0; $i< $n; $i++ ){
    /* This is done to ensure that we get the same result when
       the line goes from left to right and right to left */
    if ( $pol[$i][0] < $pol[ ($i+1)%$n ][0] ){
       $x1 = $pol[$i][0];
       $x2 = $pol[($i+1)%$n][0];
    } else {
       $x1 = $pol[($i+1)%$n][0];
       $x2 = $pol[$i][0];
    }
    /* First check if the ray is possible to cross the line */
    if ( $poi[0] > $x1 && $poi[0] <= $x2 && ( $poi[1] < $pol[$i][1] || $poi[1] <= $pol[($i+1)%$n][1] ) ) {
      $eps = 1e-9;
      /* Calculate the equation of the line */
      $dx = $pol[($i+1)%$n][0] - $pol[$i][0];
      $dy = $pol[($i+1)%$n][1] - $pol[$i][1];
      $k = (abs($dx)<$eps)?1e12:$dy/$dx;
      $m = $pol[$i][1] - $k * $pol[$i][0];
      /* Find if the ray crosses the line */
      $y2 = $k * $poi[0] + $m;
      if ($poi[1] <= $y2) $crossings++;
    }
  }
  //echo $crossings;
  return ($crossings % 2 == 1) ;
}
?>