<?php
/** Merkur 5 test ESRI map dialog
 * @author Petr Coupek
 * @date 05.06.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Map test 5');
M5::set('debug',true);
M5::skeleton('../');
M5::puthf(
 tg('link','rel="stylesheet" href="https://js.arcgis.com/4.25/esri/themes/light/main.css"').
 tg('script','src="https://js.arcgis.com/4.25/"',' ').
 ta('script', <<<EOT
  require([
    "esri/config",
    "esri/Map",
    "esri/views/MapView",
    "esri/layers/MapImageLayer",
    "esri/Basemap",
    "esri/Graphic",
    "esri/layers/GraphicsLayer",
    "esri/geometry/SpatialReference"

    ], function(esriConfig,Map, MapView, MapImageLayer, Basemap, Graphic, GraphicsLayer, SpatialReference) {

  //esriConfig.apiKey = "YOUR_API_KEY";
 var podklad=new MapImageLayer({
        url: "https://mapy.geology.cz/arcgis/rest/services/Topografie/ZABAGED_komplet/MapServer",
        title: "Basemap" });
 console.log(podklad);       

 var basemap = new Basemap({
    baseLayers: [podklad],
    title: "basemap",
    id: "basemap"});
    
 var map = new Map({
   basemap: basemap
   });

  const view = new MapView({
      container: "viewDiv", // Reference to the view div created in step 5
      map: map, // Reference to the map object created before the view
      center: [document.getElementById('Y').value, 
               document.getElementById('X').value], // Sets center point of view using longitude,latitude
      zoom: 10  // Sets zoom level based on level of detail (LOD)         
    });
 

 /* vyznamceni bodu */
    const graphicsLayer = new GraphicsLayer();
    
    map.add(graphicsLayer);
        
    const point = { //Create a point
     type: "point",
     //longitude: 16,
     //latitude: 49.5,
     y:parseFloat(document.getElementById('Y').value),
     x:parseFloat(document.getElementById('X').value),
     spatialReference: {
        wkid: 5514
      }
    };
    
    console.log(point);
    const simpleMarkerSymbol = {
     type: "simple-marker",
     color: [226, 119, 40],  // Orange
     outline: {
        color: [255, 255, 255], // White
        width: 1
     }
    };
    
    const pointGraphic = new Graphic({
      geometry: point,
      symbol: simpleMarkerSymbol
    });
    
    graphicsLayer.add(pointGraphic); 
    
    console.log(graphicsLayer); 
    
    // Create a polygon geometry with the coordinates 
    var tx=parseFloat(document.getElementById('X').value);
    var ty=parseFloat(document.getElementById('Y').value);
    const polygon = {
      type: "polygon", // autocasts as new Polyline()
      rings: [[tx-200, ty-200], [tx-200, ty-50], [tx+300, ty-100],[tx-200, ty-200]],
      spatialReference: {
        wkid: 5514
      }
    };
    const lineSymbol = {
      type: "simple-line", // autocasts as new SimpleLineSymbol()
      color: [226, 119, 40], // RGB color values as an array
      width: 4
    };
    const polygonGraphic = new Graphic({
      geometry: polygon, // Add the geometry created in step 3
      symbol: lineSymbol, // Add the symbol created in step 4
      attributes: [] // Add the attributes created in step 5
    });
    graphicsLayer.add(polygonGraphic); 
    
    //Create a polygon from online source
    $.get("get_geom.php?kod=eduz",function(data){
      console.log(data); 
      var polygon2 = {
         type: "polygon", // autocasts as new Polyline()
         rings: data.geometry,
         spatialReference: {
           wkid: 5514
         }  
      };
      var polygonGraphic2 = new Graphic({
       geometry: polygon2, 
       symbol: lineSymbol, // Add the symbol created in step 4
       attributes: [] // Add the attributes created in step 5
      });      
      graphicsLayer.add(polygonGraphic2);
    });
    

  }); 
 
 
EOT
 ),
 'maptest');
         
htpr(//tg('div','style="width: 300px; height: 300px;"',
  tg('form','method="post" action="?" class="bg-light p-2 border" ',
    gl(textfield('x','X',10,10,getpar('X')?getpar('X'):'-600000','id="X"'), 
      textfield(' y','Y',10,10,getpar('Y')?getpar('Y'):'-1100000','id="Y"'),
      nbsp(1),submit("center",'OK',"btn btn-primary"),
      tg('div','id="viewDiv" style="width: 800px; height: 800px;"' ,'')
    )
  )  
  //)
  );   
deb(getpar('X').'; '.getpar('Y'));
M5::done();

?>