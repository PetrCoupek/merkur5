
/** specselect */
function m5_ts_switchContent(cid){
  if (document.getElementById(cid).style.display==''){
    document.getElementById(cid).style.display="block";
  }
  document.getElementById(cid).style.display=(document.getElementById(cid).style.display!="block")? "block" : "none";
}

function m5_ts_pridej(kam,co){
  if (typeof document.getElementById(kam) !== 'undefined'){
    var p=document.getElementById(kam).value;
    document.getElementById(kam+'_alert').innerHTML='';
    document.getElementById(kam+'_alert').style.display="none";
    if (typeof p !== 'undefined'){
      if (p==''){
        document.getElementById(kam).value=co;
      }else{
        if (p.indexOf(co)<0){
          document.getElementById(kam).value+=(document.getElementById(kam).value==''?'':',')+' '+co;
        }else{
          var t='<div class="alert alert-warning">';
          t+=co+' obsažen'+'<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
          t+='<span aria-hidden="true">&times;</span>  </button>';
          t+='</div>';
          document.getElementById(kam+'_alert').innerHTML=t;        
          document.getElementById(kam+'_alert').style.display="block";
        }  
      }      
    }
  }  
}

function m5_ic_pridej(kam,co){
  if (typeof document.getElementById(kam) !== 'undefined'){
    var p=document.getElementById(kam).value;
    document.getElementById(kam+'_alert').innerHTML='';
    document.getElementById(kam+'_alert').style.display="none";
    if (typeof p !== 'undefined'){
      if (p==''){
        document.getElementById(kam).value=co;
        const gr0=document.getElementById(kam+'_item_'+co).innerHTML;
        document.getElementById(kam+'_view').insertAdjacentHTML( "beforeend",gr0); 
      }else{
        if (p.indexOf(co)<0){
          document.getElementById(kam).value+=(document.getElementById(kam).value==''?'':';')+co;
          const gr=document.getElementById(kam+'_item_'+co).innerHTML;
          document.getElementById(kam+'_view').insertAdjacentHTML( "beforeend",gr); 

        }else{
          var t='<div class="alert alert-warning">';
          t+='již obsažen'+'<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
          t+='<span aria-hidden="true">&times;</span>  </button>';
          t+='</div>';
          document.getElementById(kam+'_alert').innerHTML=t;        
          document.getElementById(kam+'_alert').style.display="block";
        }  
      }      
    }
  }  
}

function m5_ic_zrus(kam){
  if (typeof document.getElementById(kam) !== 'undefined'){
    document.getElementById(kam).value='';
    document.getElementById(kam+'_view').innerHTML='';
  }  
}

function m5_ic_nahrada(kam,co){
  if (typeof document.getElementById(kam) !== 'undefined'){
    document.getElementById(kam).value=co;
    const gr=document.getElementById(kam+'_item_'+co).innerHTML;
    document.getElementById(kam+'_view').innerHTML=gr; 
  }
}