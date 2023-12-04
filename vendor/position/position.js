
function getLocation(namex, namey) {
    if (navigator.geolocation) {
      var idstatus="position_status";
      navigator.geolocation.getCurrentPosition((position) => {
        if (position.coords.latitude && position.coords.longitude){ 
          var krovak = wgs84ToKrovak([position.coords.latitude, position.coords.longitude]);
          document.getElementById(namex).value=Math.round(krovak.x);
          document.getElementById(namey).value=Math.round(krovak.y);
        }else{
          document.getElementById(idstatus).innerHTML="Určení polohy není dostupné.";
        }
      }, (err)=>{
        document.getElementById(idstatus).innerHTML=err.message;
      });
      //navigator.geolocation.watchPosition(show_pos, err_pos);
    }else{
       document.getElementById(idstatus).innerHTML = "Určení polohy prohlížeč nepodporuje.";
    }
}

function processLocation(fun,idstatus,id) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((position) => {
        if (position.coords.latitude && position.coords.longitude){ 
          var krovak = wgs84ToKrovak([position.coords.latitude, position.coords.longitude]);
          fun(Math.round(krovak.x),Math.round(krovak.y),id);
        }else{
          document.getElementById(idstatus).innerHTML="Určení polohy není dostupné.";
        }
      }, (err)=>{
        document.getElementById(idstatus).innerHTML=err.message;
      });
      //navigator.geolocation.watchPosition(show_pos, err_pos);
    }else{
       document.getElementById(idstatus).innerHTML = "Určení polohy prohlížeč nepodporuje.";
    }
}


function wgs84ToKrovak(point) {
  proj4.defs("EPSG:5514", "+proj=krovak +lat_0=49.5 +lon_0=24.8333333333333 +alpha=30.2881397527778 +k=0.9999 +x_0=0 +y_0=0 +ellps=bessel +towgs84=589,76,480,0,0,0,0 +units=m +no_defs +type=crs");
  //proj4.defs("EPSG:5514", "+proj=krovak +title=Krovak +lat_0=49.5 +lon_0=24.83333333333333 +alpha=30.28813972222222 +k=0.9999 +x_0=0 +y_0=0 +ellps=bessel +units=m +towgs84=570.8,85.7,462.8,4.998,1.587,5.261,3.56 +no_defs" );
  var sourceCRS = proj4.Proj('EPSG:4326');
  var destCRS = proj4.Proj('EPSG:5514');
  var pt = new proj4.toPoint([point[1], point[0]]); 
  var rt=proj4.transform(sourceCRS, destCRS, pt);
  return rt;
}

function listNearest(x,y,id){
  $.ajax({url: "nearest.php?x="+x+"&y="+y,
          success: function(r){document.getElementById(id).innerHTML=r;}
          }
        );

  //document.getElementById(id).innerHTML=x+' '+y; 

}
