{{-- =====================================================================
     PUBLIC HOME PAGE  (shown at the site's main address, "/")
     ---------------------------------------------------------------------
     - Self-contained: all styling and animation is inside this file, so it
       works without running "npm run build".
     - It shows NO business data. The numbers in the preview cards are
       clearly labelled sample figures.
     - Business name, logo and currency come from Settings.
     - Respects visitors who turned animations off on their device
       (prefers-reduced-motion).
     ===================================================================== --}}
@php
    $name = \App\Models\Setting::get('business_name', "Barbara's Boutique");
    $logo = \App\Models\Setting::get('business_logo');
    $sym  = \App\Models\Setting::get('currency_symbol', '₵');

    // Category names for the scrolling ribbon (falls back to a sample list if the database cannot be read)
    try {
        $cats = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->limit(14)->pluck('name')->all();
    } catch (\Throwable $e) { $cats = []; }
    if (count($cats) < 4) $cats = ['Dresses', 'Tops', 'Shoes', 'Bags', 'Jewelry', 'Perfumes', 'Watches', 'Accessories'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }}</title>
    <meta name="description" content="Boutique management for {{ $name }}: sales, stock, customers, reports and backups in one place.">
    <meta name="theme-color" content="#1c1917">
@verbatim
<style>
/* ---------- Colours and basics ---------- */
:root{
  --ink:#1c1917; --ink2:#44403c; --muted:#78716c;
  --cream:#faf6f1; --paper:#ffffff; --line:#eadfd2;
  --gold:#b8863b; --gold2:#e0b36a; --rose:#f0c4c4; --rose2:#e7a3a8; --sage:#16a34a;
  --serif: 'Playfair Display', 'Georgia', 'Times New Roman', serif;
  --sans: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--sans);color:var(--ink);background:var(--cream);line-height:1.6;overflow-x:hidden;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
.wrap{width:min(1180px,92%);margin-inline:auto}

/* ---------- Top bar ---------- */
.nav{position:fixed;inset:0 0 auto 0;z-index:50;transition:background .3s,box-shadow .3s,padding .3s;padding:18px 0}
.nav.scrolled{background:rgba(250,246,241,.88);backdrop-filter:blur(12px);box-shadow:0 1px 0 var(--line);padding:10px 0}
.nav .wrap{display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:10px;font-family:var(--serif);font-size:1.35rem;font-weight:700;letter-spacing:.2px}
.brand img{height:38px;width:auto;border-radius:8px}
.brand .mark{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--gold),var(--rose2));display:grid;place-items:center;color:#fff;font-size:1.1rem;box-shadow:0 6px 16px rgba(184,134,59,.35)}
.btn{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:12px 26px;font-weight:600;font-size:.95rem;border:1.5px solid transparent;cursor:pointer;transition:transform .25s,box-shadow .25s,background .25s,color .25s}
.btn-dark{background:var(--ink);color:#fff;box-shadow:0 10px 24px rgba(28,25,23,.25)}
.btn-dark:hover{transform:translateY(-3px);box-shadow:0 16px 30px rgba(28,25,23,.3);background:#000}
.btn-ghost{border-color:var(--ink);color:var(--ink);background:transparent}
.btn-ghost:hover{background:var(--ink);color:#fff;transform:translateY(-3px)}
.btn .arrow{transition:transform .25s}
.btn:hover .arrow{transform:translateX(4px)}

/* ---------- Hero ---------- */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;padding:130px 0 80px;overflow:hidden;
  background:radial-gradient(1200px 600px at 85% -10%,#f8dede 0%,transparent 60%),radial-gradient(900px 500px at -10% 110%,#f6e3c0 0%,transparent 60%),var(--cream)}
.blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;animation:drift 18s ease-in-out infinite;pointer-events:none}
.blob.b1{width:420px;height:420px;background:var(--rose);top:-90px;right:-60px}
.blob.b2{width:360px;height:360px;background:#f3d9a4;bottom:-120px;left:-80px;animation-delay:-6s}
.blob.b3{width:260px;height:260px;background:#f9cfd8;top:42%;left:38%;animation-delay:-11s;opacity:.35}
@keyframes drift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(40px,-30px) scale(1.08)}66%{transform:translate(-30px,35px) scale(.95)}}
.hero-grid{position:relative;display:grid;grid-template-columns:1.05fr 1fr;gap:56px;align-items:center}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;letter-spacing:.18em;text-transform:uppercase;font-weight:700;color:var(--gold);background:rgba(184,134,59,.1);padding:7px 14px;border-radius:999px}
.eyebrow i{width:7px;height:7px;border-radius:50%;background:var(--gold);animation:pulse 2s infinite}
h1{font-family:var(--serif);font-size:clamp(2.5rem,5.6vw,4.6rem);line-height:1.06;margin:22px 0 20px;letter-spacing:-.5px}
.shimmer{background:linear-gradient(100deg,var(--gold) 20%,var(--rose2) 45%,var(--gold2) 60%,var(--gold) 80%);background-size:220% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:shine 6s linear infinite}
@keyframes shine{to{background-position:-220% 0}}
.lead{font-size:1.15rem;color:var(--ink2);max-width:34rem}
.cta{display:flex;flex-wrap:wrap;gap:14px;margin-top:34px}
.trust{display:flex;flex-wrap:wrap;gap:18px 28px;margin-top:38px;color:var(--muted);font-size:.9rem}
.trust span{display:flex;align-items:center;gap:8px}
.trust svg{width:18px;height:18px;color:var(--gold)}

/* hero entrance (staggered by --d) */
.rise{opacity:0;transform:translateY(26px);animation:rise .9s cubic-bezier(.2,.7,.2,1) forwards;animation-delay:var(--d,0s)}
@keyframes rise{to{opacity:1;transform:none}}

/* ---------- Hero preview cards ---------- */
.stage{position:relative;height:520px}
.ring{position:absolute;inset:6% 4%;border:1.5px dashed rgba(184,134,59,.45);border-radius:50%;animation:spin 40s linear infinite}
.ring::after{content:"";position:absolute;top:-7px;left:50%;width:14px;height:14px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 6px rgba(184,134,59,.18)}
@keyframes spin{to{transform:rotate(360deg)}}
.glass{position:absolute;background:rgba(255,255,255,.82);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.9);border-radius:22px;padding:18px 20px;box-shadow:0 24px 50px -12px rgba(120,80,40,.28);animation:float 7s ease-in-out infinite;will-change:transform}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
.glass small{display:block;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:700}
.g1{top:2%;left:4%;width:270px;animation-delay:-1s}
.g2{top:34%;right:0;width:250px;animation-delay:-3s}
.g3{bottom:6%;left:10%;width:260px;animation-delay:-5s}
.g4{top:0;right:8%;width:170px;padding:14px 16px;animation-delay:-2s}
.big{font-family:var(--serif);font-size:2rem;font-weight:700;margin-top:4px}
.bars{display:flex;align-items:flex-end;gap:7px;height:58px;margin-top:12px}
.bars b{flex:1;border-radius:6px 6px 3px 3px;background:linear-gradient(180deg,var(--gold2),var(--gold));transform-origin:bottom;transform:scaleY(0);animation:grow 1.2s cubic-bezier(.2,.8,.2,1) forwards}
@keyframes grow{to{transform:scaleY(1)}}
.line{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:.92rem;margin-top:10px}
.qty{display:inline-flex;align-items:center;gap:8px;background:var(--cream);border-radius:999px;padding:3px 10px;font-weight:700}
.chip{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 12px;font-size:.8rem;font-weight:700;margin-top:12px;background:#e8f7ee;color:#15803d;opacity:0;animation:pop 6s ease-in-out infinite}
@keyframes pop{0%,35%{opacity:0;transform:scale(.8)}45%,90%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(.9)}}
.warn{display:flex;gap:12px;align-items:center}
.warn .dot{width:38px;height:38px;border-radius:12px;background:#fff1e0;display:grid;place-items:center;color:#ea8a1c;font-weight:800;animation:pulse 2s infinite}
.tick{width:40px;height:40px}
.tick circle{fill:#e8f7ee}
.tick path{fill:none;stroke:var(--sage);stroke-width:3.2;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:30;stroke-dashoffset:30;animation:draw 3.5s ease-in-out infinite}
@keyframes draw{0%,15%{stroke-dashoffset:30}40%,85%{stroke-dashoffset:0}100%{stroke-dashoffset:30}}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(184,134,59,.45)}70%{box-shadow:0 0 0 12px rgba(184,134,59,0)}100%{box-shadow:0 0 0 0 rgba(184,134,59,0)}}
.sample{position:absolute;bottom:-4px;right:6px;font-size:.72rem;color:var(--muted);font-style:italic}

/* ---------- Scrolling category ribbon ---------- */
.ribbon{background:var(--ink);color:#f5e9d6;padding:18px 0;overflow:hidden;white-space:nowrap}
.track{display:inline-flex;gap:46px;animation:marquee 32s linear infinite;padding-left:46px}
.ribbon:hover .track{animation-play-state:paused}
.track span{font-family:var(--serif);font-size:1.25rem;display:inline-flex;align-items:center;gap:46px}
.track span::after{content:"✦";color:var(--gold2);font-size:.9rem}
@keyframes marquee{to{transform:translateX(-50%)}}

/* ---------- Sections ---------- */
section.block{padding:110px 0}
.head{max-width:40rem;margin-bottom:56px}
.head .eyebrow{margin-bottom:14px}
h2{font-family:var(--serif);font-size:clamp(2rem,4vw,3rem);line-height:1.12;letter-spacing:-.3px}
.head p{color:var(--ink2);margin-top:14px;font-size:1.08rem}

/* scroll-reveal: items fade up when they come into view */
.reveal{opacity:0;transform:translateY(30px);transition:opacity .8s cubic-bezier(.2,.7,.2,1),transform .8s cubic-bezier(.2,.7,.2,1);transition-delay:var(--d,0s)}
.reveal.in{opacity:1;transform:none}

/* ---------- Feature cards ---------- */
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.card{position:relative;background:var(--paper);border:1px solid var(--line);border-radius:24px;padding:30px 28px;overflow:hidden;transition:transform .35s,box-shadow .35s,border-color .35s}
.card::before{content:"";position:absolute;inset:0;background:radial-gradient(400px 160px at var(--mx,50%) -20%,rgba(224,179,106,.28),transparent 70%);opacity:0;transition:opacity .35s}
.card:hover{transform:translateY(-8px);box-shadow:0 26px 50px -18px rgba(120,80,40,.3);border-color:var(--gold2)}
.card:hover::before{opacity:1}
.ico{position:relative;width:54px;height:54px;border-radius:16px;display:grid;place-items:center;margin-bottom:20px;background:linear-gradient(135deg,#fbefd9,#f8dede);color:var(--gold);transition:transform .4s}
.card:hover .ico{transform:rotate(-8deg) scale(1.1)}
.ico svg{width:26px;height:26px}
.card h3{font-family:var(--serif);font-size:1.3rem;margin-bottom:8px;position:relative}
.card p{color:var(--ink2);font-size:.97rem;position:relative}

/* ---------- How a sale goes (steps) ---------- */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;position:relative}
.steps::before{content:"";position:absolute;top:34px;left:12%;right:12%;height:2px;background:repeating-linear-gradient(90deg,var(--gold2) 0 10px,transparent 10px 20px);opacity:.7}
.step{text-align:center;position:relative}
.num{width:68px;height:68px;border-radius:50%;margin:0 auto 20px;display:grid;place-items:center;font-family:var(--serif);font-size:1.6rem;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--gold),var(--rose2));box-shadow:0 12px 26px rgba(184,134,59,.35);position:relative;transition:transform .4s}
.step:hover .num{transform:scale(1.12) rotate(6deg)}
.step h3{font-family:var(--serif);font-size:1.3rem;margin-bottom:6px}
.step p{color:var(--ink2);max-width:19rem;margin:0 auto}

/* ---------- Safety band ---------- */
.safe{background:var(--ink);color:#f5ede1;position:relative;overflow:hidden}
.safe .blob{opacity:.18}
.safe-grid{position:relative;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.safe h2{color:#fff}
.safe .head p{color:#d6cbbd}
.safe .eyebrow{background:rgba(224,179,106,.15);color:var(--gold2)}
.list{list-style:none;display:grid;gap:14px;margin-top:26px}
.list li{display:flex;gap:12px;align-items:flex-start;color:#e8dccb}
.list li::before{content:"✓";flex:none;width:24px;height:24px;border-radius:50%;background:rgba(224,179,106,.18);color:var(--gold2);display:grid;place-items:center;font-size:.8rem;font-weight:800;margin-top:2px}
.shield{position:relative;width:min(320px,70vw);aspect-ratio:1;margin-inline:auto;display:grid;place-items:center}
.shield::before,.shield::after{content:"";position:absolute;inset:0;border-radius:50%;border:1.5px solid rgba(224,179,106,.5);animation:ripple 3.6s ease-out infinite}
.shield::after{animation-delay:1.8s}
@keyframes ripple{0%{transform:scale(.55);opacity:.9}100%{transform:scale(1.15);opacity:0}}
.shield svg{width:55%;color:var(--gold2);filter:drop-shadow(0 12px 30px rgba(224,179,106,.4));animation:float 6s ease-in-out infinite}

/* ---------- Final call to action ---------- */
.final{text-align:center;padding:120px 0;position:relative;overflow:hidden;background:radial-gradient(800px 400px at 50% 0,#f8dede,transparent 70%),var(--cream)}
.final h2{max-width:36rem;margin:0 auto 18px}
.final p{color:var(--ink2);max-width:30rem;margin:0 auto 32px}
footer{border-top:1px solid var(--line);padding:30px 0;color:var(--muted);font-size:.9rem}
footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px}

/* ---------- Phones and tablets ---------- */
@media (max-width:980px){
  .hero-grid,.safe-grid{grid-template-columns:1fr;gap:40px}
  .stage{height:470px;max-width:520px;margin-inline:auto;width:100%}
  .grid{grid-template-columns:repeat(2,1fr)}
  .steps{grid-template-columns:1fr;gap:34px}.steps::before{display:none}
  section.block{padding:80px 0}
}
@media (max-width:620px){
  .grid{grid-template-columns:1fr}
  .stage{height:440px}
  .g1{left:0;width:230px}.g2{width:215px}.g3{left:2%;width:230px}.g4{display:none}
  .hero{padding-top:110px}
  .btn{padding:13px 22px}
  .cta .btn{flex:1;justify-content:center}
}

/* ---------- If the visitor turned animations off on their device, keep it calm ---------- */
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
  .rise,.reveal{opacity:1;transform:none}
  .bars b{transform:none}.chip{opacity:1}.tick path{stroke-dashoffset:0}
  html{scroll-behavior:auto}
}
</style>
@endverbatim
    {{-- If the visitor has JavaScript off, show everything instead of leaving it invisible --}}
    <noscript><style>.reveal{opacity:1!important;transform:none!important}</style></noscript>
</head>

{{-- data-sym hands the currency symbol to the script below --}}
<body data-sym="{{ $sym }}">

{{-- ===================== TOP BAR ===================== --}}
<header class="nav" id="nav">
    <div class="wrap">
        <a href="/" class="brand">
            @if ($logo)<img src="{{ asset('storage/' . $logo) }}" alt="">@else<span class="mark">✦</span>@endif
            <span>{{ $name }}</span>
        </a>
        {{-- Logged-in staff go straight to the dashboard; everyone else sees Log in --}}
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-dark">Open dashboard <span class="arrow">→</span></a>
        @else
            <a href="{{ route('login') }}" class="btn btn-dark">Owner log in <span class="arrow">→</span></a>
        @endauth
    </div>
</header>

{{-- ===================== HERO ===================== --}}
<section class="hero">
    {{-- Soft colour blobs drifting in the background --}}
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
    <div class="wrap hero-grid">

        {{-- Left: headline and buttons --}}
        <div>
            <span class="eyebrow rise" style="--d:.05s"><i></i> Boutique management system</span>
            <h1 class="rise" style="--d:.15s">Your boutique,<br><span class="shimmer">beautifully in order.</span></h1>
            <p class="lead rise" style="--d:.28s">Sales, stock, customers, suppliers and reports for {{ $name }}: one calm place that works on the till, the tablet and the phone.</p>
            <div class="cta rise" style="--d:.4s">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-dark">Open dashboard <span class="arrow">→</span></a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-dark">Owner log in <span class="arrow">→</span></a>
                @endauth
                <a href="#features" class="btn btn-ghost">See what it does</a>
            </div>
            <div class="trust rise" style="--d:.55s">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg> Automatic backups</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg> Works on phones</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg> Staff permissions</span>
            </div>
        </div>

        {{-- Right: floating preview cards (sample figures, not real data) --}}
        <div class="stage rise" style="--d:.35s" id="stage">
            <div class="ring"></div>

            <div class="glass g1" data-depth="14">
                <small>Today's sales</small>
                <div class="big"><span id="count" data-to="1250">0.00</span></div>
                <div class="bars">
                    <b style="height:38%;animation-delay:.6s"></b><b style="height:62%;animation-delay:.7s"></b><b style="height:48%;animation-delay:.8s"></b>
                    <b style="height:80%;animation-delay:.9s"></b><b style="height:58%;animation-delay:1s"></b><b style="height:92%;animation-delay:1.1s"></b><b style="height:70%;animation-delay:1.2s"></b>
                </div>
            </div>

            <div class="glass g2" data-depth="22">
                <small>New sale</small>
                <div class="line"><span><b>Black Dress</b><br><span style="color:var(--muted);font-size:.82rem">Size M · Black</span></span><span class="qty">− 2 +</span></div>
                <div class="line"><span style="color:var(--muted)">Total</span><b>{{ $sym }} 500.00</b></div>
                <span class="chip">✓ Mobile Money paid</span>
            </div>

            <div class="glass g3" data-depth="18">
                <div class="warn">
                    <span class="dot">!</span>
                    <div><small>Low stock</small><b>Red Dress · S</b><div style="color:var(--muted);font-size:.85rem">2 left, time to reorder</div></div>
                </div>
            </div>

            <div class="glass g4" data-depth="10">
                <svg class="tick" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20"/><path d="M11 21l6 6 12-13"/></svg>
                <small style="margin-top:8px">Backup completed</small>
            </div>
            <span class="sample">Sample preview</span>
        </div>
    </div>
</section>

{{-- ===================== SCROLLING CATEGORY RIBBON (real category names) ===================== --}}
<div class="ribbon" aria-hidden="true">
    <div class="track">
        {{-- The list is written twice so the loop never shows a gap --}}
        @foreach (array_merge($cats, $cats) as $c)<span>{{ $c }}</span>@endforeach
    </div>
</div>

{{-- ===================== FEATURES ===================== --}}
<section class="block" id="features">
    <div class="wrap">
        <div class="head reveal">
            <span class="eyebrow">Everything in one place</span>
            <h2>Made for the way a boutique really works</h2>
            <p>From the first scan at the till to the end-of-month profit, every step is recorded and easy to find.</p>
        </div>
        <div class="grid">
            <article class="card reveal" style="--d:.0s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg></div><h3>Fast point of sale</h3><p>Search or scan, tap a size and colour, take cash, Mobile Money, card or a mix, and print the receipt.</p></article>
            <article class="card reveal" style="--d:.08s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4M3 17l9 4 9-4"/></svg></div><h3>Sizes and colours, counted right</h3><p>Every variation has its own stock, price and barcode, so a Medium is never mistaken for a Large.</p></article>
            <article class="card reveal" style="--d:.16s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9z"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></div><h3>Low-stock alerts</h3><p>Know what is running out before a customer asks. Every change in stock is recorded with who and why.</p></article>
            <article class="card reveal" style="--d:.0s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c.8-3.5 3.5-5.5 7-5.5s6.2 2 7 5.5"/><path d="M17 4.5a3.5 3.5 0 010 7M18 14.8c2 .6 3.4 2.3 4 5.2"/></svg></div><h3>Customers and credit</h3><p>Purchase history, store credit, and who still owes what, with due dates and gentle reminders.</p></article>
            <article class="card reveal" style="--d:.08s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg></div><h3>Reports you can trust</h3><p>Profit uses the cost at the time of each sale, so old figures never change. Print, save as PDF, or open in Excel.</p></article>
            <article class="card reveal" style="--d:.16s"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg></div><h3>Roles and permissions</h3><p>Cashiers see the till. Managers see the stock. Only the owner sees everything, including profit and backups.</p></article>
        </div>
    </div>
</section>

{{-- ===================== HOW A SALE GOES ===================== --}}
<section class="block" style="padding-top:0">
    <div class="wrap">
        <div class="head reveal" style="margin-inline:auto;text-align:center">
            <span class="eyebrow">Three taps</span>
            <h2>A sale in under a minute</h2>
        </div>
        <div class="steps">
            <div class="step reveal" style="--d:0s"><div class="num">1</div><h3>Scan or search</h3><p>Point the scanner at the tag, or type a few letters. The right size and colour appears instantly.</p></div>
            <div class="step reveal" style="--d:.12s"><div class="num">2</div><h3>Take payment</h3><p>Cash, Mobile Money, card, bank transfer, or part of each. The balance and change are worked out for you.</p></div>
            <div class="step reveal" style="--d:.24s"><div class="num">3</div><h3>Print the receipt</h3><p>Stock goes down, profit is recorded, and the customer leaves with a tidy receipt.</p></div>
        </div>
    </div>
</section>

{{-- ===================== SAFETY BAND ===================== --}}
<section class="block safe">
    <span class="blob b1"></span>
    <div class="wrap safe-grid">
        <div class="head reveal" style="margin:0">
            <span class="eyebrow">Your data, protected</span>
            <h2>Backed up while you sleep</h2>
            <p>The system saves a copy of your business every day, and a full copy with photos every week, so one mistake never becomes a disaster.</p>
            <ul class="list">
                <li>Daily and weekly automatic backups, plus a Backup Now button</li>
                <li>Restore with a safety copy taken first, so you can always go back</li>
                <li>Every important change is written to a log nobody can edit</li>
            </ul>
        </div>
        <div class="shield reveal" style="--d:.15s" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5l8 3v6.2c0 4.9-3.4 8.2-8 9.8-4.6-1.6-8-4.9-8-9.8V5.5l8-3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>
        </div>
    </div>
</section>

{{-- ===================== FINAL CALL TO ACTION ===================== --}}
<section class="final">
    <div class="wrap">
        <h2 class="reveal">Ready to open for the day?</h2>
        <p class="reveal" style="--d:.1s">Staff can log in to start selling, check stock, or see how the shop is doing.</p>
        <div class="reveal" style="--d:.2s">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-dark">Open dashboard <span class="arrow">→</span></a>
            @else
                <a href="{{ route('login') }}" class="btn btn-dark">Owner log in <span class="arrow">→</span></a>
            @endauth
        </div>
    </div>
</section>

<footer>
    <div class="wrap">
        <span>© {{ date('Y') }} {{ $name }}</span>
        <span>Owner access only</span>
    </div>
</footer>

@verbatim
<script>
(function () {
  // The currency symbol comes from Settings (stored on the <body> tag)
  var SYM = document.body.dataset.sym || '₵';
  document.querySelectorAll('.g2 .line b').forEach(function (el) { el.textContent = el.textContent.replace('{SYM}', SYM); });

  // 1. Top bar gets a soft background after you scroll a little
  var nav = document.getElementById('nav');
  function onScroll() { nav.classList.toggle('scrolled', window.scrollY > 24); }
  onScroll(); window.addEventListener('scroll', onScroll, { passive: true });

  // 2. Scroll reveal: items fade up as they come into view
  var items = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  } else { items.forEach(function (el) { el.classList.add('in'); }); }

  // 3. The sample "Today's sales" number counts up from zero
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var c = document.getElementById('count');
  if (c) {
    var to = parseFloat(c.dataset.to), t0 = null;
    var fmt = function (n) { return SYM + ' ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    if (reduce) { c.textContent = fmt(to); }
    else {
      var step = function (ts) {
        if (!t0) t0 = ts;
        var p = Math.min((ts - t0) / 1800, 1), eased = 1 - Math.pow(1 - p, 3);
        c.textContent = fmt(to * eased);
        if (p < 1) requestAnimationFrame(step);
      };
      setTimeout(function () { requestAnimationFrame(step); }, 700);
    }
  }

  // 4. Gentle parallax: the preview cards drift slightly as the mouse moves (computers only)
  var stage = document.getElementById('stage');
  if (stage && !reduce && window.matchMedia('(hover: hover)').matches) {
    var cards = stage.querySelectorAll('.glass'), raf = null;
    stage.addEventListener('mousemove', function (e) {
      var r = stage.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(function () {
        cards.forEach(function (el) { var d = +el.dataset.depth || 12; el.style.translate = (x * d * -1) + 'px ' + (y * d * -1) + 'px'; });
      });
    });
    stage.addEventListener('mouseleave', function () { cards.forEach(function (el) { el.style.translate = '0 0'; }); });
  }

  // 5. Feature cards glow where the mouse is
  document.querySelectorAll('.card').forEach(function (card) {
    card.addEventListener('mousemove', function (e) { var r = card.getBoundingClientRect(); card.style.setProperty('--mx', (e.clientX - r.left) + 'px'); });
  });
})();
</script>
@endverbatim
</body>
</html>