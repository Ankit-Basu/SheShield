import { landmarks, LPU_CENTER } from '@/lib/campus';
import { LPU_BOUNDARY } from '@/lib/lpu-boundary';
import type { Theme } from '@/lib/theme';

export type MapHandle = {
  setUser: (lat: number, lng: number, accuracy?: number) => void;
  setSafeSpaces: (items: { latitude: number; longitude: number; description: string }[]) => void;
  setLayer: (layer: 'satellite' | 'streets') => void;
  recenter: () => void;
  focus: (name: string) => void;
};

// The Leaflet page shared by the native WebView and the web iframe.
// It exposes window.ss for the app to call, and reports landmark taps with postMessage.
export const buildMapHtml = (theme: Theme) => `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
html,body,#m{margin:0;height:100%;background:${theme.base}}
.leaflet-control-attribution{background:rgba(0,0,0,.45)!important;color:#ddd!important;font:10px sans-serif}
.leaflet-control-attribution a{color:#fff!important}
.pin{transition:transform .25s;width:22px;height:22px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:${theme.primary};border:2px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,.45)}
.lbl{background:rgba(20,12,18,.82);color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:8px;padding:3px 7px;font:600 11px sans-serif;box-shadow:none}
.lbl:before{display:none}
.far .lbl{display:none}
.me{width:18px;height:18px;border-radius:50%;background:#4aa3ff;border:3px solid #fff;box-shadow:0 0 0 8px rgba(74,163,255,.25)}
.safe{width:22px;height:22px;border-radius:50%;background:#4ed589;border:3px solid #fff;box-shadow:0 0 0 7px rgba(78,213,137,.22)}
</style></head><body><div id="m"></div><script>
const map=L.map('m',{zoomControl:false,attributionControl:true}).setView([${LPU_CENTER.lat},${LPU_CENTER.lng}],16);
const sat=L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19,attribution:'Imagery &copy; Esri, Maxar'}).addTo(map);
const st=L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',{maxZoom:19,attribution:'&copy; Esri'});
const ring=L.polygon(${JSON.stringify(LPU_BOUNDARY)},{color:'${theme.accent}',weight:2.5,fillColor:'${theme.primary}',fillOpacity:.08,dashArray:'6 6'}).addTo(map);
const send=(o)=>{const m=JSON.stringify(o);if(window.ReactNativeWebView)window.ReactNativeWebView.postMessage(m);else if(window.parent!==window)window.parent.postMessage({ssMap:m},'*');};
const pins={};
${JSON.stringify(landmarks)}.forEach(p=>{pins[p.name]=L.marker([p.lat,p.lng],{icon:L.divIcon({className:'',html:'<div class="pin"></div>',iconSize:[22,22],iconAnchor:[11,22]})}).addTo(map).bindTooltip(p.name,{permanent:true,direction:'top',offset:[0,-22],className:'lbl'}).on('click',()=>send({type:'select',name:p.name}));});
let me=null,safeLayer=L.layerGroup().addTo(map);
window.ss={
 user(lat,lng,acc){if(!me){me=L.marker([lat,lng],{icon:L.divIcon({className:'',html:'<div class="me"></div>',iconSize:[18,18]})}).addTo(map);}else{me.setLatLng([lat,lng]);}map.flyTo([lat,lng],17,{duration:.8});},
 safe(items){safeLayer.clearLayers();items.forEach(s=>L.marker([s.latitude,s.longitude],{icon:L.divIcon({className:'',html:'<div class="safe"></div>',iconSize:[22,22]})}).addTo(safeLayer).bindPopup(s.description));},
 layer(n){if(n==='streets'){map.removeLayer(sat);st.addTo(map);}else{map.removeLayer(st);sat.addTo(map);}},
 home(){map.flyToBounds(ring.getBounds(),{padding:[20,20],duration:.8});},
 focus(name){const m=pins[name];if(m)map.flyTo(m.getLatLng(),18,{duration:.9});}
};
const labels=()=>document.body.classList.toggle('far',map.getZoom()<17);
map.on('zoomend',labels);
map.fitBounds(ring.getBounds(),{padding:[20,20]});
labels();
send({type:'ready'});
</script></body></html>`;
