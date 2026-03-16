<?php
// ============================================================
//  SITE HUB — index.php
//  Edit the $sites array below to add / remove your projects
// ============================================================

$sites = [
    [
        "id"          => "caller",
        "title"       => "Caller System",
        "subtitle"    => "Manage & route incoming calls",
        "path"        => "/Caller/index.php",          // ← your folder path
        "icon"        => "📞",
        "color"       => "#60a5fa",
        "glow"        => "rgba(96,165,250,0.35)",
        "tag"         => "CRM",
        "status"      => "live",
    ],
    [
        "id"          => "admission",
        "title"       => "Admission Portal",
        "subtitle"    => "Student admission & registration",
        "path"        => "/admission/index.php",
        "icon"        => "🎓",
        "color"       => "#a78bfa",
        "glow"        => "rgba(167,139,250,0.35)",
        "tag"         => "EDU",
        "status"      => "live",
    ],
    [
        "id"          => "College",
        "title"       => "College site",
        "subtitle"    => "Full Clg connection",
        "path"        => "coming soon.php",
        "icon"        => "🏫",
        "color"       => "#f87171",
        "glow"        => "rgba(248,113,113,0.35)",
        "tag"         => "CLG",
        "status"      => "Pending",
    ],

];

$total  = count($sites);
$live   = count(array_filter($sites, fn($s) => $s['status'] === 'live'));
$siteJson = json_encode($sites);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Site Hub — Command Center</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
/* ── reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow-x:hidden;}

/* ── cursor ── */
body{cursor:none;}
#cur-ring{
  position:fixed;width:36px;height:36px;border:1.5px solid rgba(255,255,255,0.55);
  border-radius:50%;pointer-events:none;z-index:9999;
  transition:transform 0.12s ease,border-color 0.2s,width 0.2s,height 0.2s;
  transform:translate(-50%,-50%);
}
#cur-dot{
  position:fixed;width:6px;height:6px;background:#fff;
  border-radius:50%;pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);
  transition:transform 0.05s,background 0.2s;
}
body.hovering #cur-ring{
  width:54px;height:54px;
  border-color:var(--accent);
  background:rgba(255,255,255,0.04);
}
body.hovering #cur-dot{background:var(--accent);}

/* ── root vars ── */
:root{
  --bg:#05060f;
  --surface:rgba(255,255,255,0.04);
  --border:rgba(255,255,255,0.10);
  --border-hover:rgba(255,255,255,0.28);
  --text:#e8eaf6;
  --muted:rgba(255,255,255,0.42);
  --accent:#60a5fa;
  --font-head:'Syne',sans-serif;
  --font-body:'DM Sans',sans-serif;
  --radius:18px;
}

/* ── bg canvas ── */
#bg-canvas{
  position:fixed;inset:0;width:100%;height:100%;
  z-index:0;background:var(--bg);
}

/* ── page shell ── */
#app{
  position:relative;z-index:2;
  min-height:100vh;
  display:flex;flex-direction:column;align-items:center;
  padding:60px 24px 80px;
}

/* ── header ── */
.hub-header{
  text-align:center;margin-bottom:56px;
  animation:fadeUp 0.8s ease both;
}
.hub-eyebrow{
  font-family:var(--font-body);font-size:11px;letter-spacing:3px;
  text-transform:uppercase;color:var(--muted);margin-bottom:14px;
}
.hub-title{
  font-family:var(--font-head);font-size:clamp(2.4rem,6vw,4rem);
  font-weight:800;color:var(--text);line-height:1.05;
  background:linear-gradient(135deg,#e8eaf6 30%,#60a5fa 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.hub-sub{
  margin-top:12px;font-family:var(--font-body);font-size:15px;
  color:var(--muted);font-weight:300;
}

/* ── stat chips ── */
.stats-row{
  display:flex;gap:12px;justify-content:center;margin-bottom:52px;flex-wrap:wrap;
  animation:fadeUp 0.85s 0.1s ease both;
}
.stat-chip{
  display:flex;align-items:center;gap:8px;
  background:var(--surface);border:0.5px solid var(--border);
  border-radius:100px;padding:8px 16px;
  font-family:var(--font-body);font-size:12px;color:var(--muted);
}
.stat-chip strong{color:var(--text);font-size:14px;font-weight:500;}
.pulse{
  width:7px;height:7px;border-radius:50%;background:#4ade80;
  box-shadow:0 0 6px #4ade80;animation:pulse 2s infinite;
}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.35}}

/* ── grid ── */
.cards-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(290px,1fr));
  gap:20px;width:100%;max-width:1120px;
}

/* ── card ── */
.site-card{
  position:relative;
  background:var(--surface);
  border:0.5px solid var(--border);
  border-radius:var(--radius);
  padding:28px 26px 24px;
  overflow:hidden;
  cursor:none;
  text-decoration:none;
  display:flex;flex-direction:column;gap:0;
  transition:transform 0.28s cubic-bezier(.22,1,.36,1),
             border-color 0.25s,
             background 0.25s;
  animation:fadeUp 0.7s ease both;
}
.site-card:nth-child(1){animation-delay:.05s}
.site-card:nth-child(2){animation-delay:.10s}
.site-card:nth-child(3){animation-delay:.15s}
.site-card:nth-child(4){animation-delay:.20s}
.site-card:nth-child(5){animation-delay:.25s}
.site-card:nth-child(6){animation-delay:.30s}

.site-card::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(circle at 30% 30%, var(--card-glow,rgba(96,165,250,0.12)), transparent 65%);
  opacity:0;transition:opacity 0.35s;pointer-events:none;border-radius:var(--radius);
}
.site-card:hover{
  transform:translateY(-8px) scale(1.02);
  border-color:var(--border-hover);
  background:rgba(255,255,255,0.07);
}
.site-card:hover::before{opacity:1;}

/* glow border on hover */
.site-card::after{
  content:'';position:absolute;inset:-1px;border-radius:calc(var(--radius) + 1px);
  background:transparent;
  transition:box-shadow 0.3s;pointer-events:none;
}
.site-card:hover::after{
  box-shadow:0 0 0 1px var(--card-color,#60a5fa),
             0 8px 40px var(--card-glow,rgba(96,165,250,0.2));
}

/* card top row */
.card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;}
.card-icon{
  width:48px;height:48px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;
  background:rgba(255,255,255,0.06);
  border:0.5px solid rgba(255,255,255,0.10);
}
.card-tag{
  font-family:var(--font-body);font-size:10px;letter-spacing:2px;
  text-transform:uppercase;padding:4px 10px;border-radius:100px;
  border:0.5px solid var(--card-color,#60a5fa);
  color:var(--card-color,#60a5fa);
}
.card-title{
  font-family:var(--font-head);font-size:19px;font-weight:700;
  color:var(--text);margin-bottom:6px;
}
.card-sub{
  font-family:var(--font-body);font-size:13px;
  color:var(--muted);font-weight:300;line-height:1.5;
  margin-bottom:22px;
}

/* status */
.card-footer{
  margin-top:auto;
  display:flex;align-items:center;justify-content:space-between;
}
.status-badge{
  display:flex;align-items:center;gap:6px;
  font-family:var(--font-body);font-size:11px;
}
.status-dot{width:6px;height:6px;border-radius:50%;}
.status-live .status-dot{background:#4ade80;box-shadow:0 0 5px #4ade80;animation:pulse 2s infinite;}
.status-maintenance .status-dot{background:#fbbf24;box-shadow:0 0 5px #fbbf24;}
.status-live .status-text{color:#4ade80;}
.status-maintenance .status-text{color:#fbbf24;}

.card-arrow{
  width:30px;height:30px;border-radius:50%;
  border:0.5px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--muted);font-size:14px;
  transition:border-color 0.2s,color 0.2s,background 0.2s;
}
.site-card:hover .card-arrow{
  border-color:var(--card-color,#60a5fa);
  color:var(--card-color,#60a5fa);
  background:rgba(96,165,250,0.08);
}

/* path line deco */
.card-path{
  font-family:monospace;font-size:10px;
  color:rgba(255,255,255,0.22);margin-top:4px;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}

/* ── connection lines ── */
#conn-svg{
  position:fixed;inset:0;width:100%;height:100%;
  z-index:1;pointer-events:none;
}

/* ── footer ── */
.hub-footer{
  margin-top:60px;font-family:var(--font-body);
  font-size:11px;color:rgba(255,255,255,0.2);
  letter-spacing:1.5px;text-transform:uppercase;
  animation:fadeUp 1s 0.4s ease both;
}

@keyframes fadeUp{
  from{opacity:0;transform:translateY(24px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── search bar ── */
.search-wrap{
  position:relative;margin-bottom:36px;width:100%;max-width:420px;
  animation:fadeUp 0.82s 0.08s ease both;
}
.search-wrap input{
  width:100%;padding:12px 20px 12px 44px;
  background:var(--surface);border:0.5px solid var(--border);
  border-radius:100px;color:var(--text);
  font-family:var(--font-body);font-size:14px;outline:none;
  transition:border-color 0.2s,background 0.2s;
}
.search-wrap input::placeholder{color:var(--muted);}
.search-wrap input:focus{border-color:rgba(255,255,255,0.3);background:rgba(255,255,255,0.07);}
.search-icon{
  position:absolute;left:16px;top:50%;transform:translateY(-50%);
  color:var(--muted);font-size:15px;pointer-events:none;
}
</style>
</head>
<body>

<!-- 3D Canvas Background -->
<canvas id="bg-canvas"></canvas>

<!-- Connection lines SVG overlay -->
<svg id="conn-svg" id="connSvg"></svg>

<!-- Custom cursor -->
<div id="cur-ring"></div>
<div id="cur-dot"></div>

<div id="app">

  <!-- Header -->
  <header class="hub-header">
    <p class="hub-eyebrow">Command Center · <?= date('d M Y') ?></p>
    <h1 class="hub-title">Site Hub</h1>
    <p class="hub-sub">All your web applications, one place.</p>
  </header>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-chip"><span class="pulse"></span><strong><?= $live ?></strong> live</div>
    <div class="stat-chip">🔗 <strong><?= $total ?></strong> apps</div>
    <div class="stat-chip">🖥 <strong>1</strong> server</div>
    <div class="stat-chip" id="clockChip">⏱ --:--:--</div>
  </div>

  <!-- Search -->
  <div class="search-wrap">
    <span class="search-icon">🔍</span>
    <input type="text" id="searchInput" placeholder="Search apps…" autocomplete="off"/>
  </div>

  <!-- Cards grid -->
  <div class="cards-grid" id="cardsGrid">
    <?php foreach($sites as $i => $s): ?>
    <a
      class="site-card"
      href="<?= htmlspecialchars($s['path']) ?>"
      id="card-<?= $s['id'] ?>"
      data-title="<?= htmlspecialchars(strtolower($s['title'])) ?>"
      data-tag="<?= htmlspecialchars(strtolower($s['tag'])) ?>"
      style="
        --card-color:<?= $s['color'] ?>;
        --card-glow:<?= $s['glow'] ?>;
      "
    >
      <div class="card-top">
        <div class="card-icon"><?= $s['icon'] ?></div>
        <span class="card-tag"><?= htmlspecialchars($s['tag']) ?></span>
      </div>
      <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
      <div class="card-sub"><?= htmlspecialchars($s['subtitle']) ?></div>
      <div class="card-path"><?= htmlspecialchars($s['path']) ?></div>
      <div class="card-footer">
        <div class="status-badge status-<?= $s['status'] ?>">
          <span class="status-dot"></span>
          <span class="status-text"><?= $s['status'] === 'live' ? 'Online' : 'Maintenance' ?></span>
        </div>
        <div class="card-arrow">↗</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <p class="hub-footer">root / index.php · <?= $total ?> modules loaded</p>
</div>

<!-- ══════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════ -->
<script>
/* ── 1. Custom cursor ── */
const ring = document.getElementById('cur-ring');
const dot  = document.getElementById('cur-dot');
let mx = -200, my = -200, rx = -200, ry = -200;
document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });
(function animCursor(){
  rx += (mx - rx) * 0.14;
  ry += (my - ry) * 0.14;
  ring.style.left = rx + 'px';
  ring.style.top  = ry + 'px';
  dot.style.left  = mx + 'px';
  dot.style.top   = my + 'px';
  requestAnimationFrame(animCursor);
})();
document.querySelectorAll('a,.stat-chip,input').forEach(el => {
  el.addEventListener('mouseenter', () => document.body.classList.add('hovering'));
  el.addEventListener('mouseleave', () => document.body.classList.remove('hovering'));
});

/* ── 2. Clock ── */
function tickClock(){
  const now = new Date();
  const hh = String(now.getHours()).padStart(2,'0');
  const mm = String(now.getMinutes()).padStart(2,'0');
  const ss = String(now.getSeconds()).padStart(2,'0');
  document.getElementById('clockChip').textContent = `⏱ ${hh}:${mm}:${ss}`;
}
setInterval(tickClock, 1000); tickClock();

/* ── 3. Search filter ── */
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase().trim();
  document.querySelectorAll('.site-card').forEach(c => {
    const match = !q || c.dataset.title.includes(q) || c.dataset.tag.includes(q);
    c.style.display = match ? '' : 'none';
  });
});

/* ── 4. 3D Particle Background ── */
(function(){
  const canvas = document.getElementById('bg-canvas');
  const ctx    = canvas.getContext('2d');
  let W, H;

  function resize(){ W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
  resize();
  window.addEventListener('resize', resize);

  const COLORS = ['#60a5fa','#a78bfa','#2dd4bf','#fbbf24','#4ade80','#f87171'];
  const N = 90;

  const nodes = Array.from({length: N}, () => ({
    x: Math.random(), y: Math.random(), z: Math.random(),
    vx: (Math.random()-.5)*.0007,
    vy: (Math.random()-.5)*.0007,
    vz: (Math.random()-.5)*.0005,
    r: 1.2 + Math.random()*2.2,
    col: COLORS[Math.floor(Math.random()*COLORS.length)]
  }));

  let rotX = 0, rotY = 0, tRX = 0, tRY = 0;
  document.addEventListener('mousemove', e => {
    tRY = ((e.clientX / window.innerWidth)  - .5) * .55;
    tRX = ((e.clientY / window.innerHeight) - .5) * .35;
  });

  function project(x,y,z){
    const cx=x-.5, cy=y-.5, cz=z-.5;
    const cosX=Math.cos(rotX), sinX=Math.sin(rotX);
    const y2=cy*cosX-cz*sinX, z2=cy*sinX+cz*cosX;
    const cosY=Math.cos(rotY), sinY=Math.sin(rotY);
    const x3=cx*cosY+z2*sinY, z3=-cx*sinY+z2*cosY;
    const fov=1.9, s=fov/(fov+z3+.5);
    return { sx:(x3*s+.5)*W, sy:(y2*s+.5)*H, scale:s, depth:z3+.5 };
  }

  function draw(){
    ctx.clearRect(0,0,W,H);
    // deep bg
    const bg = ctx.createRadialGradient(W*.5,H*.4,0,W*.5,H*.4,W*.85);
    bg.addColorStop(0,'#0c1030');bg.addColorStop(.6,'#070b22');bg.addColorStop(1,'#05060f');
    ctx.fillStyle = bg; ctx.fillRect(0,0,W,H);

    rotX += (tRX - rotX) * .04;
    rotY += (tRY - rotY) * .04;

    const proj = nodes.map(n => ({ ...project(n.x,n.y,n.z), n }));
    proj.sort((a,b)=>b.depth-a.depth);

    for(let a=0;a<proj.length;a++){
      for(let b=a+1;b<proj.length;b++){
        const pa=proj[a], pb=proj[b];
        const dx=pa.n.x-pb.n.x, dy=pa.n.y-pb.n.y, dz=pa.n.z-pb.n.z;
        const dist=Math.sqrt(dx*dx+dy*dy+dz*dz);
        if(dist<.20){
          const a0=(1-dist/.20)*0.18*Math.min(pa.scale,pb.scale);
          ctx.beginPath();
          ctx.moveTo(pa.sx,pa.sy); ctx.lineTo(pb.sx,pb.sy);
          ctx.strokeStyle=`rgba(140,160,255,${a0})`;
          ctx.lineWidth=.6*Math.min(pa.scale,pb.scale);
          ctx.stroke();
        }
      }
    }

    for(const p of proj){
      const sz = p.n.r * p.scale * 1.5;
      const al = .5 + p.depth*.4;
      const g = ctx.createRadialGradient(p.sx,p.sy,0,p.sx,p.sy,sz*3);
      g.addColorStop(0, p.n.col.replace(')',`,${al})`).replace('rgb','rgba').replace('#',`rgba(`));
      g.addColorStop(1,'transparent');
      // simplified glow
      ctx.beginPath(); ctx.arc(p.sx,p.sy,sz*3,0,Math.PI*2);
      ctx.fillStyle=hexToRgba(p.n.col, al*.22); ctx.fill();
      ctx.beginPath(); ctx.arc(p.sx,p.sy,sz,0,Math.PI*2);
      ctx.fillStyle=hexToRgba(p.n.col, al*.85); ctx.fill();
    }

    for(const n of nodes){
      n.x+=n.vx; n.y+=n.vy; n.z+=n.vz;
      if(n.x<0||n.x>1)n.vx*=-1;
      if(n.y<0||n.y>1)n.vy*=-1;
      if(n.z<0||n.z>1)n.vz*=-1;
    }
    requestAnimationFrame(draw);
  }

  function hexToRgba(hex, alpha){
    const r=parseInt(hex.slice(1,3),16);
    const g=parseInt(hex.slice(3,5),16);
    const b=parseInt(hex.slice(5,7),16);
    return `rgba(${r},${g},${b},${alpha.toFixed(2)})`;
  }

  draw();
})();

/* ── 5. SVG Connection Lines between cards ── */
(function(){
  const svg = document.getElementById('conn-svg');
  const sites = <?= $siteJson ?>;
  // edges: which cards to connect (index pairs)
  const edges = [[0,1],[1,2],[0,2],[2,3],[3,4],[4,5],[1,4],[0,5]];
  const COLORS = sites.map(s=>s.color);

  function getCentre(id){
    const el = document.getElementById('card-' + id);
    if(!el || el.style.display==='none') return null;
    const r = el.getBoundingClientRect();
    return { x: r.left+r.width/2, y: r.top+r.height/2 };
  }

  function redraw(){
    svg.innerHTML='';
    svg.setAttribute('viewBox',`0 0 ${window.innerWidth} ${window.innerHeight}`);
    const defs = document.createElementNS('http://www.w3.org/2000/svg','defs');

    edges.forEach(([ai,bi],idx)=>{
      const a = sites[ai], b = sites[bi];
      const ca = getCentre(a.id), cb = getCentre(b.id);
      if(!ca||!cb) return;
      const mid = { x:(ca.x+cb.x)/2, y:(ca.y+cb.y)/2-40 };

      const gId = `g${idx}`;
      const grad = document.createElementNS('http://www.w3.org/2000/svg','linearGradient');
      grad.setAttribute('id',gId);
      grad.setAttribute('gradientUnits','userSpaceOnUse');
      grad.setAttribute('x1',ca.x); grad.setAttribute('y1',ca.y);
      grad.setAttribute('x2',cb.x); grad.setAttribute('y2',cb.y);
      const s1=document.createElementNS('http://www.w3.org/2000/svg','stop');
      s1.setAttribute('offset','0%'); s1.setAttribute('stop-color',a.color); s1.setAttribute('stop-opacity','0.5');
      const s2=document.createElementNS('http://www.w3.org/2000/svg','stop');
      s2.setAttribute('offset','100%'); s2.setAttribute('stop-color',b.color); s2.setAttribute('stop-opacity','0.5');
      grad.appendChild(s1); grad.appendChild(s2); defs.appendChild(grad);

      const path = document.createElementNS('http://www.w3.org/2000/svg','path');
      path.setAttribute('d',`M${ca.x},${ca.y} Q${mid.x},${mid.y} ${cb.x},${cb.y}`);
      path.setAttribute('fill','none');
      path.setAttribute('stroke',`url(#${gId})`);
      path.setAttribute('stroke-width','1');
      path.setAttribute('stroke-dasharray','5 8');
      path.setAttribute('id','edge'+idx);

      const animD = document.createElementNS('http://www.w3.org/2000/svg','animate');
      animD.setAttribute('attributeName','stroke-dashoffset');
      animD.setAttribute('from','0'); animD.setAttribute('to','-260');
      animD.setAttribute('dur',(2.8+idx*.35)+'s'); animD.setAttribute('repeatCount','indefinite');
      path.appendChild(animD);

      const dot = document.createElementNS('http://www.w3.org/2000/svg','circle');
      dot.setAttribute('r','4');
      dot.setAttribute('fill',a.color); dot.setAttribute('opacity','0.85');
      const mot = document.createElementNS('http://www.w3.org/2000/svg','animateMotion');
      mot.setAttribute('dur',(2.8+idx*.35)+'s'); mot.setAttribute('repeatCount','indefinite');
      const mp = document.createElementNS('http://www.w3.org/2000/svg','mpath');
      mp.setAttributeNS('http://www.w3.org/1999/xlink','href','#edge'+idx);
      mot.appendChild(mp); dot.appendChild(mot);

      svg.appendChild(defs); svg.appendChild(path); svg.appendChild(dot);
    });
  }

  setTimeout(redraw,150);
  window.addEventListener('resize',()=>setTimeout(redraw,150));
  // redraw if search hides cards
  document.getElementById('searchInput').addEventListener('input',()=>setTimeout(redraw,50));
})();
</script>
</body>
</html>