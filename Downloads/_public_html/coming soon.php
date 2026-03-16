<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Coming Soon</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow:hidden;background:#000;}
body{cursor:none;font-family:'Space Grotesk',sans-serif;}

/* ── CURSOR ── */
#cur-outer{
  position:fixed;width:44px;height:44px;border-radius:50%;
  border:1.5px solid rgba(255,200,50,0.7);
  pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);
  transition:width .25s,height .25s,border-color .25s,background .25s;
  mix-blend-mode:difference;
}
#cur-inner{
  position:fixed;width:8px;height:8px;border-radius:50%;
  background:#ffd700;pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);
  transition:transform .05s;
  box-shadow:0 0 12px #ffd700,0 0 24px rgba(255,215,0,.4);
}
body.hov #cur-outer{
  width:64px;height:64px;
  border-color:rgba(255,215,0,0.9);
  background:rgba(255,215,0,0.05);
}

/* ── CANVAS ── */
#bg{position:fixed;inset:0;z-index:0;}

/* ── MAIN WRAP ── */
#wrap{
  position:relative;z-index:2;
  width:100%;height:100vh;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  overflow:hidden;
}

/* ── GRID OVERLAY ── */
.grid-overlay{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background-image:
    linear-gradient(rgba(255,215,0,0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,215,0,0.03) 1px, transparent 1px);
  background-size:60px 60px;
  animation:gridShift 20s linear infinite;
}
@keyframes gridShift{from{background-position:0 0}to{background-position:60px 60px}}

/* ── SCANLINES ── */
.scanlines{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:repeating-linear-gradient(
    0deg,transparent,transparent 2px,
    rgba(0,0,0,0.08) 2px,rgba(0,0,0,0.08) 4px
  );
}

/* ── VIGNETTE ── */
.vignette{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:radial-gradient(ellipse at center,transparent 40%,rgba(0,0,0,0.75) 100%);
}

/* ── CONSTRUCTION ANIMATION ── */
.construction-ring{
  position:relative;
  width:200px;height:200px;
  margin-bottom:40px;
  animation:floatRing 4s ease-in-out infinite;
}
@keyframes floatRing{
  0%,100%{transform:translateY(0) rotateX(10deg)}
  50%{transform:translateY(-18px) rotateX(10deg)}
}

.ring-svg{
  position:absolute;inset:0;width:100%;height:100%;
  animation:spinRing 8s linear infinite;
}
.ring-svg-2{
  position:absolute;inset:0;width:100%;height:100%;
  animation:spinRing 5s linear infinite reverse;
  opacity:0.6;
}
@keyframes spinRing{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

.center-icon{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:center;
  font-size:52px;
  animation:pulse3d 2.5s ease-in-out infinite;
  filter:drop-shadow(0 0 20px rgba(255,215,0,0.8));
}
@keyframes pulse3d{
  0%,100%{transform:scale(1) rotateY(0deg)}
  50%{transform:scale(1.12) rotateY(15deg)}
}

/* ── GEAR PARTICLES ── */
.gear-wrap{
  position:fixed;inset:0;z-index:1;pointer-events:none;overflow:hidden;
}
.gear{
  position:absolute;font-size:20px;
  animation:gearFloat linear infinite;
  opacity:0.15;
  filter:sepia(1) saturate(5) hue-rotate(10deg);
}
@keyframes gearFloat{
  0%{transform:translateY(100vh) rotate(0deg);opacity:0}
  10%{opacity:0.15}
  90%{opacity:0.08}
  100%{transform:translateY(-120px) rotate(720deg);opacity:0}
}

/* ── SPARKS ── */
.spark{
  position:fixed;width:3px;height:3px;border-radius:50%;
  pointer-events:none;z-index:3;
  animation:sparkFade 0.8s ease-out forwards;
}
@keyframes sparkFade{
  0%{opacity:1;transform:translate(0,0) scale(1)}
  100%{opacity:0;transform:translate(var(--tx),var(--ty)) scale(0)}
}

/* ── EYEBROW ── */
.eyebrow{
  font-family:'Orbitron',monospace;
  font-size:11px;letter-spacing:6px;
  text-transform:uppercase;
  color:rgba(255,215,0,0.7);
  margin-bottom:20px;
  animation:fadeSlideDown 1s 0.2s ease both;
  position:relative;
}
.eyebrow::before,.eyebrow::after{
  content:'';display:inline-block;
  width:30px;height:1px;background:rgba(255,215,0,0.5);
  vertical-align:middle;margin:0 12px;
}

/* ── MAIN TITLE ── */
.main-title{
  font-family:'Orbitron',monospace;
  font-size:clamp(3.5rem,10vw,8rem);
  font-weight:900;
  line-height:0.9;
  text-align:center;
  background:linear-gradient(135deg,#fff 0%,#ffd700 40%,#ff8c00 70%,#ff4500 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  text-shadow:none;
  animation:fadeSlideUp 1s 0.4s ease both;
  filter:drop-shadow(0 0 60px rgba(255,165,0,0.3));
}

/* ── SUBTITLE ── */
.subtitle{
  font-size:clamp(0.9rem,2vw,1.15rem);
  color:rgba(255,255,255,0.5);
  font-weight:300;
  letter-spacing:2px;
  margin-top:20px;
  text-align:center;
  animation:fadeSlideUp 1s 0.6s ease both;
}

/* ── PROGRESS BAR ── */
.progress-wrap{
  margin-top:50px;width:min(480px,80vw);
  animation:fadeSlideUp 1s 0.8s ease both;
}
.progress-label{
  display:flex;justify-content:space-between;
  font-family:'Orbitron',monospace;font-size:10px;
  color:rgba(255,215,0,0.6);letter-spacing:2px;
  margin-bottom:10px;
}
.progress-track{
  height:3px;background:rgba(255,255,255,0.08);
  border-radius:100px;overflow:hidden;
  position:relative;
}
.progress-fill{
  height:100%;border-radius:100px;
  background:linear-gradient(90deg,#ff4500,#ffd700,#fff);
  width:0%;
  transition:width 0.05s;
  position:relative;
  box-shadow:0 0 12px rgba(255,215,0,0.8);
}
.progress-fill::after{
  content:'';position:absolute;right:0;top:50%;
  transform:translate(50%,-50%);
  width:8px;height:8px;border-radius:50%;
  background:#fff;
  box-shadow:0 0 10px #ffd700,0 0 20px rgba(255,215,0,0.6);
}

/* ── COUNTDOWN ── */
.countdown{
  margin-top:48px;display:flex;gap:24px;
  animation:fadeSlideUp 1s 1s ease both;
}
.count-box{
  display:flex;flex-direction:column;align-items:center;gap:6px;
}
.count-num{
  font-family:'Orbitron',monospace;
  font-size:clamp(1.8rem,5vw,3rem);font-weight:700;
  color:#fff;
  position:relative;min-width:70px;text-align:center;
  background:rgba(255,255,255,0.04);
  border:0.5px solid rgba(255,215,0,0.2);
  border-radius:12px;padding:12px 8px 8px;
  transition:color 0.2s;
}
.count-num::before{
  content:'';position:absolute;inset:0;border-radius:12px;
  background:linear-gradient(135deg,rgba(255,215,0,0.06) 0%,transparent 60%);
}
.count-label{
  font-family:'Orbitron',monospace;
  font-size:9px;letter-spacing:3px;
  color:rgba(255,215,0,0.5);text-transform:uppercase;
}
.count-sep{
  font-family:'Orbitron',monospace;
  font-size:2rem;color:rgba(255,215,0,0.4);
  align-self:center;padding-bottom:20px;
  animation:sepBlink 1s step-end infinite;
}
@keyframes sepBlink{0%,100%{opacity:1}50%{opacity:0.1}}

/* ── NOTIFY ── */
.notify-row{
  margin-top:44px;display:flex;gap:0;
  animation:fadeSlideUp 1s 1.2s ease both;
}
.notify-input{
  background:rgba(255,255,255,0.05);
  border:0.5px solid rgba(255,215,0,0.3);
  border-right:none;
  border-radius:100px 0 0 100px;
  padding:12px 20px;
  color:#fff;font-family:'Space Grotesk',sans-serif;
  font-size:13px;outline:none;width:230px;
  transition:border-color .2s,background .2s;
}
.notify-input::placeholder{color:rgba(255,255,255,0.3);}
.notify-input:focus{border-color:rgba(255,215,0,0.7);background:rgba(255,255,255,0.08);}
.notify-btn{
  padding:12px 22px;
  background:linear-gradient(135deg,#ffd700,#ff8c00);
  border:none;border-radius:0 100px 100px 0;
  color:#000;font-family:'Orbitron',monospace;
  font-size:10px;letter-spacing:2px;
  cursor:none;font-weight:700;
  transition:transform .15s,box-shadow .15s;
}
.notify-btn:hover{
  transform:scale(1.04);
  box-shadow:0 0 20px rgba(255,200,0,0.5);
}
.notify-success{
  display:none;margin-top:10px;text-align:center;
  font-size:12px;color:rgba(255,215,0,0.7);
  font-family:'Orbitron',monospace;letter-spacing:2px;
  animation:fadeSlideUp 0.4s ease both;
}

/* ── SOCIAL ── */
.social-row{
  margin-top:36px;display:flex;gap:16px;
  animation:fadeSlideUp 1s 1.4s ease both;
}
.social-btn{
  width:38px;height:38px;border-radius:50%;
  border:0.5px solid rgba(255,215,0,0.25);
  display:flex;align-items:center;justify-content:center;
  font-size:15px;color:rgba(255,255,255,0.5);
  background:rgba(255,255,255,0.03);
  cursor:none;
  transition:border-color .2s,color .2s,background .2s,transform .2s;
  text-decoration:none;
}
.social-btn:hover{
  border-color:rgba(255,215,0,0.7);
  color:#ffd700;
  background:rgba(255,215,0,0.08);
  transform:translateY(-3px);
}

/* ── CORNER DECO ── */
.corner{position:fixed;width:80px;height:80px;z-index:2;pointer-events:none;}
.corner-tl{top:20px;left:20px;
  border-top:1.5px solid rgba(255,215,0,0.4);
  border-left:1.5px solid rgba(255,215,0,0.4);}
.corner-tr{top:20px;right:20px;
  border-top:1.5px solid rgba(255,215,0,0.4);
  border-right:1.5px solid rgba(255,215,0,0.4);}
.corner-bl{bottom:20px;left:20px;
  border-bottom:1.5px solid rgba(255,215,0,0.4);
  border-left:1.5px solid rgba(255,215,0,0.4);}
.corner-br{bottom:20px;right:20px;
  border-bottom:1.5px solid rgba(255,215,0,0.4);
  border-right:1.5px solid rgba(255,215,0,0.4);}

/* ── STATUS TICKER ── */
.ticker{
  position:fixed;bottom:0;left:0;right:0;z-index:3;
  height:36px;
  background:rgba(255,215,0,0.06);
  border-top:0.5px solid rgba(255,215,0,0.15);
  display:flex;align-items:center;overflow:hidden;
}
.ticker-inner{
  display:flex;gap:80px;white-space:nowrap;
  animation:tickerMove 30s linear infinite;
  font-family:'Orbitron',monospace;font-size:10px;
  letter-spacing:3px;color:rgba(255,215,0,0.45);
}
@keyframes tickerMove{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.ticker-sep{color:rgba(255,215,0,0.2);margin:0 20px;}

@keyframes fadeSlideUp{
  from{opacity:0;transform:translateY(30px)}
  to{opacity:1;transform:translateY(0)}
}
@keyframes fadeSlideDown{
  from{opacity:0;transform:translateY(-20px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── MOUSE TRAIL ── */
.trail{
  position:fixed;border-radius:50%;
  pointer-events:none;z-index:1;
  animation:trailFade 0.6s ease-out forwards;
}
@keyframes trailFade{
  0%{opacity:0.35;transform:translate(-50%,-50%) scale(1)}
  100%{opacity:0;transform:translate(-50%,-50%) scale(0.2)}
}
</style>
</head>
<body>

<!-- canvas -->
<canvas id="bg"></canvas>

<!-- atmosphere -->
<div class="grid-overlay"></div>
<div class="scanlines"></div>
<div class="vignette"></div>

<!-- floating gears bg -->
<div class="gear-wrap" id="gearWrap"></div>

<!-- corner brackets -->
<div class="corner corner-tl"></div>
<div class="corner corner-tr"></div>
<div class="corner corner-bl"></div>
<div class="corner corner-br"></div>

<!-- cursor -->
<div id="cur-outer"></div>
<div id="cur-inner"></div>

<!-- main -->
<div id="wrap">

  <!-- construction ring -->
  <div class="construction-ring">
    <svg class="ring-svg" viewBox="0 0 200 200">
      <circle cx="100" cy="100" r="90" fill="none"
        stroke="url(#rg1)" stroke-width="1.5"
        stroke-dasharray="12 6" stroke-linecap="round"/>
      <circle cx="100" cy="100" r="72" fill="none"
        stroke="rgba(255,215,0,0.15)" stroke-width="0.5"/>
      <!-- tick marks -->
      <g stroke="rgba(255,215,0,0.6)" stroke-width="2">
        <line x1="100" y1="5" x2="100" y2="18"/>
        <line x1="100" y1="182" x2="100" y2="195"/>
        <line x1="5" y1="100" x2="18" y2="100"/>
        <line x1="182" y1="100" x2="195" y2="100"/>
      </g>
      <defs>
        <linearGradient id="rg1" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#ffd700" stop-opacity="0.9"/>
          <stop offset="50%" stop-color="#ff8c00" stop-opacity="0.6"/>
          <stop offset="100%" stop-color="#ff4500" stop-opacity="0.2"/>
        </linearGradient>
      </defs>
    </svg>
    <svg class="ring-svg-2" viewBox="0 0 200 200">
      <circle cx="100" cy="100" r="80" fill="none"
        stroke="rgba(255,215,0,0.3)" stroke-width="1"
        stroke-dasharray="4 12"/>
      <!-- corner diamonds -->
      <polygon points="100,12 106,18 100,24 94,18" fill="rgba(255,215,0,0.7)"/>
      <polygon points="100,176 106,182 100,188 94,182" fill="rgba(255,215,0,0.7)"/>
      <polygon points="12,100 18,94 24,100 18,106" fill="rgba(255,215,0,0.4)"/>
      <polygon points="176,100 182,94 188,100 182,106" fill="rgba(255,215,0,0.4)"/>
    </svg>
    <div class="center-icon">🔨</div>
  </div>

  <!-- eyebrow -->
  <div class="eyebrow">Under Construction</div>

  <!-- title -->
  <h1 class="main-title">COMING<br>SOON</h1>

  <!-- subtitle -->
  <p class="subtitle">Something extraordinary is being built</p>

  <!-- progress -->
  <div class="progress-wrap">
    <div class="progress-label">
      <span>BUILD PROGRESS</span>
      <span id="pct">0%</span>
    </div>
    <div class="progress-track">
      <div class="progress-fill" id="progressFill"></div>
    </div>
  </div>

  <!-- countdown -->
  <div class="countdown">
    <div class="count-box">
      <div class="count-num" id="cDays">00</div>
      <div class="count-label">Days</div>
    </div>
    <div class="count-sep">:</div>
    <div class="count-box">
      <div class="count-num" id="cHrs">00</div>
      <div class="count-label">Hours</div>
    </div>
    <div class="count-sep">:</div>
    <div class="count-box">
      <div class="count-num" id="cMin">00</div>
      <div class="count-label">Mins</div>
    </div>
    <div class="count-sep">:</div>
    <div class="count-box">
      <div class="count-num" id="cSec">00</div>
      <div class="count-label">Secs</div>
    </div>
  </div>

  <!-- notify -->
  <div class="notify-row">
    <input class="notify-input" type="email" id="notifyInput" placeholder="your@email.com"/>
    <button class="notify-btn" id="notifyBtn">NOTIFY ME</button>
  </div>
  <div class="notify-success" id="notifySuccess">✦ YOU'RE ON THE LIST ✦</div>

  <!-- social -->
  <div class="social-row">
    <a class="social-btn" href="#" title="Twitter">𝕏</a>
    <a class="social-btn" href="#" title="Instagram">◈</a>
    <a class="social-btn" href="#" title="LinkedIn">in</a>
    <a class="social-btn" href="#" title="GitHub">⌥</a>
  </div>

</div>

<!-- ticker -->
<div class="ticker">
  <div class="ticker-inner" id="tickerInner"></div>
</div>

<script>
/* ══ CURSOR ══ */
const curO = document.getElementById('cur-outer');
const curI = document.getElementById('cur-inner');
let mx=window.innerWidth/2, my=window.innerHeight/2;
let ox=mx, oy=my;

document.addEventListener('mousemove', e=>{ mx=e.clientX; my=e.clientY; spawnTrail(mx,my); });

(function animCur(){
  ox += (mx-ox)*0.13; oy += (my-oy)*0.13;
  curO.style.left=ox+'px'; curO.style.top=oy+'px';
  curI.style.left=mx+'px'; curI.style.top=my+'px';
  requestAnimationFrame(animCur);
})();

document.querySelectorAll('button,a,input').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hov'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hov'));
});

/* ══ MOUSE TRAIL ══ */
function spawnTrail(x,y){
  if(Math.random()>0.35) return;
  const t=document.createElement('div');
  t.className='trail';
  const s=4+Math.random()*8;
  t.style.cssText=`left:${x}px;top:${y}px;width:${s}px;height:${s}px;
    background:rgba(255,${180+Math.random()*75|0},0,0.5);`;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),600);
}

/* ══ SPARKS on click ══ */
document.addEventListener('click', e=>{
  for(let i=0;i<14;i++){
    const s=document.createElement('div');
    s.className='spark';
    const angle=Math.random()*Math.PI*2;
    const dist=30+Math.random()*60;
    s.style.cssText=`
      left:${e.clientX}px;top:${e.clientY}px;
      background:hsl(${40+Math.random()*30},100%,65%);
      --tx:${Math.cos(angle)*dist}px;
      --ty:${Math.sin(angle)*dist}px;
    `;
    document.body.appendChild(s);
    setTimeout(()=>s.remove(),800);
  }
});

/* ══ 3D CANVAS BG ══ */
const canvas=document.getElementById('bg');
const ctx=canvas.getContext('2d');
let W,H;
function rsz(){ W=canvas.width=window.innerWidth; H=canvas.height=window.innerHeight; }
rsz(); window.addEventListener('resize',rsz);

const COLS=['#ffd700','#ff8c00','#ff4500','#fff8dc','#ffa500'];
const N=100;
const pts=Array.from({length:N},()=>({
  x:Math.random(), y:Math.random(), z:Math.random(),
  vx:(Math.random()-.5)*.0006,
  vy:(Math.random()-.5)*.0006,
  vz:(Math.random()-.5)*.0004,
  r:1+Math.random()*2.2,
  col:COLS[Math.floor(Math.random()*COLS.length)]
}));

let tRX=0,tRY=0,rX=0,rY=0;
document.addEventListener('mousemove',e=>{
  tRY=((e.clientX/window.innerWidth)-.5)*.7;
  tRX=((e.clientY/window.innerHeight)-.5)*.45;
});

function proj(x,y,z){
  const cx=x-.5,cy=y-.5,cz=z-.5;
  const cX=Math.cos(rX),sX=Math.sin(rX);
  const y2=cy*cX-cz*sX, z2=cy*sX+cz*cX;
  const cY=Math.cos(rY),sY=Math.sin(rY);
  const x3=cx*cY+z2*sY, z3=-cx*sY+z2*cY;
  const fov=2, sc=fov/(fov+z3+.5);
  return{sx:(x3*sc+.5)*W, sy:(y2*sc+.5)*H, sc, depth:z3+.5};
}

function hex2rgba(h,a){
  const r=parseInt(h.slice(1,3),16);
  const g=parseInt(h.slice(3,5),16);
  const b=parseInt(h.slice(5,7),16);
  return `rgba(${r},${g},${b},${a.toFixed(2)})`;
}

function drawBg(){
  ctx.clearRect(0,0,W,H);
  const bg=ctx.createRadialGradient(W*.5,H*.45,0,W*.5,H*.45,W*.9);
  bg.addColorStop(0,'#0f0800');
  bg.addColorStop(.5,'#080500');
  bg.addColorStop(1,'#030200');
  ctx.fillStyle=bg; ctx.fillRect(0,0,W,H);

  rX+=(tRX-rX)*.04; rY+=(tRY-rY)*.04;

  const p=pts.map(n=>({...proj(n.x,n.y,n.z),n}));
  p.sort((a,b)=>b.depth-a.depth);

  for(let a=0;a<p.length;a++){
    for(let b=a+1;b<p.length;b++){
      const pa=p[a],pb=p[b];
      const dx=pa.n.x-pb.n.x,dy=pa.n.y-pb.n.y,dz=pa.n.z-pb.n.z;
      const d=Math.sqrt(dx*dx+dy*dy+dz*dz);
      if(d<.18){
        const al=(1-d/.18)*.15*Math.min(pa.sc,pb.sc);
        ctx.beginPath();
        ctx.moveTo(pa.sx,pa.sy); ctx.lineTo(pb.sx,pb.sy);
        ctx.strokeStyle=`rgba(255,180,0,${al})`;
        ctx.lineWidth=.5;ctx.stroke();
      }
    }
  }

  for(const pt of p){
    const sz=pt.n.r*pt.sc*1.8;
    const al=.4+pt.depth*.45;
    ctx.beginPath();ctx.arc(pt.sx,pt.sy,sz*2.5,0,Math.PI*2);
    ctx.fillStyle=hex2rgba(pt.n.col,al*.15); ctx.fill();
    ctx.beginPath();ctx.arc(pt.sx,pt.sy,sz,0,Math.PI*2);
    ctx.fillStyle=hex2rgba(pt.n.col,al*.8); ctx.fill();
  }

  for(const n of pts){
    n.x+=n.vx; n.y+=n.vy; n.z+=n.vz;
    if(n.x<0||n.x>1)n.vx*=-1;
    if(n.y<0||n.y>1)n.vy*=-1;
    if(n.z<0||n.z>1)n.vz*=-1;
  }
  requestAnimationFrame(drawBg);
}
drawBg();

/* ══ PROGRESS BAR ══ */
let pct=0, targetPct=73;
const fill=document.getElementById('progressFill');
const pctEl=document.getElementById('pct');
(function animProgress(){
  if(pct<targetPct){
    pct+=.4;
    fill.style.width=pct+'%';
    pctEl.textContent=Math.floor(pct)+'%';
  }
  setTimeout(animProgress,18);
})();

/* ══ COUNTDOWN (30 days from now) ══ */
const target=new Date(Date.now()+30*24*3600*1000);
function pad(n){return String(n).padStart(2,'0');}
function tick(){
  const diff=target-Date.now();
  if(diff<=0) return;
  const d=Math.floor(diff/86400000);
  const h=Math.floor((diff%86400000)/3600000);
  const m=Math.floor((diff%3600000)/60000);
  const s=Math.floor((diff%60000)/1000);
  document.getElementById('cDays').textContent=pad(d);
  document.getElementById('cHrs').textContent=pad(h);
  document.getElementById('cMin').textContent=pad(m);
  document.getElementById('cSec').textContent=pad(s);
}
setInterval(tick,1000); tick();

/* ══ GEAR PARTICLES ══ */
const gearIcons=['⚙️','🔧','🔩','⛏️','🔨','🛠️'];
const gw=document.getElementById('gearWrap');
setInterval(()=>{
  const g=document.createElement('div');
  g.className='gear';
  g.textContent=gearIcons[Math.floor(Math.random()*gearIcons.length)];
  const dur=8+Math.random()*14;
  const size=14+Math.random()*28;
  g.style.cssText=`
    left:${Math.random()*100}%;
    bottom:-60px;
    font-size:${size}px;
    animation-duration:${dur}s;
    animation-delay:${Math.random()*2}s;
  `;
  gw.appendChild(g);
  setTimeout(()=>g.remove(),(dur+2)*1000);
},600);

/* ══ TICKER ══ */
const msgs=[
  '✦ UNDER CONSTRUCTION','◈ BUILDING SOMETHING AMAZING',
  '⚙ SYSTEM INITIALIZING','✦ LAUNCHING SOON',
  '◈ STAY TUNED','⚙ ALMOST READY',
  '✦ CRAFTING PERFECTION','◈ BRACE YOURSELVES',
];
const ti=document.getElementById('tickerInner');
const full=[...msgs,...msgs].map(m=>`<span>${m}</span><span class="ticker-sep">|</span>`).join('');
ti.innerHTML=full+full;

/* ══ NOTIFY ══ */
document.getElementById('notifyBtn').addEventListener('click',()=>{
  const inp=document.getElementById('notifyInput');
  if(!inp.value.includes('@')) return;
  inp.parentElement.style.display='none';
  const suc=document.getElementById('notifySuccess');
  suc.style.display='block';
});
</script>
</body>
</html>