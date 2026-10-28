/* JASSCO Steel — minimal site script: navigation, machinery filter, form validation. */
(function(){
  var d=document;
  // Mobile navigation
  var burger=d.querySelector('.burger'), nav=d.getElementById('site-nav'), header=d.querySelector('.site-header');
  if(burger&&nav){
    burger.addEventListener('click',function(){
      var open=nav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded',open?'true':'false');
      if(open){nav.style.top=header.getBoundingClientRect().bottom+'px';d.body.style.overflow='hidden';}
      else{d.body.style.overflow='';}
    });
  }
  // Dropdowns (click/keyboard; hover on desktop)
  var items=d.querySelectorAll('.nav .has-mega');
  function closeAll(except){items.forEach(function(li){if(li!==except){li.classList.remove('open');var b=li.querySelector('button');if(b)b.setAttribute('aria-expanded','false');}});}
  items.forEach(function(li){
    var btn=li.querySelector('button');
    btn.addEventListener('click',function(e){e.stopPropagation();var o=!li.classList.contains('open');closeAll(li);li.classList.toggle('open',o);btn.setAttribute('aria-expanded',o?'true':'false');});
    li.addEventListener('mouseenter',function(){if(window.matchMedia('(min-width:1121px)').matches){closeAll(li);li.classList.add('open');btn.setAttribute('aria-expanded','true');}});
    li.addEventListener('mouseleave',function(){if(window.matchMedia('(min-width:1121px)').matches){li.classList.remove('open');btn.setAttribute('aria-expanded','false');}});
  });
  d.addEventListener('click',function(e){if(!e.target.closest('.has-mega'))closeAll();});
  d.addEventListener('keydown',function(e){if(e.key==='Escape')closeAll();});

  // Machinery filter
  var chips=d.querySelectorAll('.chip[data-filter]');
  chips.forEach(function(c){c.addEventListener('click',function(){
    var f=c.getAttribute('data-filter');
    chips.forEach(function(x){x.setAttribute('aria-pressed',x===c?'true':'false');});
    d.querySelectorAll('.mcard').forEach(function(m){m.hidden=!(f==='all'||m.getAttribute('data-group')===f);});
  });});

  // Forms: timestamp, error display, validation, file checks
  var params=new URLSearchParams(location.search);
  d.querySelectorAll('form.js-form').forEach(function(form){
    var ts=form.querySelector('input[name=ts]'); if(ts) ts.value=String(Math.floor(Date.now()/1000));
    var alert=form.querySelector('.alert');
    var msgs={invalid:'Please complete the required fields and try again.',file:'One of the files could not be accepted. Use PDF, DXF, DWG, STEP, IGES, JPG, PNG or ZIP, up to 10 MB each and 20 MB in total.',send:'Sorry — your message could not be sent. Please email us directly at the address on this page.',spam:'Your submission could not be verified. Please try again.',expired:'That download link has expired. Please enter your details again to get a new link.'};
    if(alert&&params.get('error')&&msgs[params.get('error')]){alert.textContent=msgs[params.get('error')];alert.classList.add('show');}
    var pre=params.get('product'); var sel=form.querySelector('select[name=product]');
    if(pre&&sel){for(var i=0;i<sel.options.length;i++){if(sel.options[i].value===pre){sel.selectedIndex=i;break;}}}
    form.addEventListener('submit',function(e){
      var ok=true;
      form.querySelectorAll('.field').forEach(function(f){f.classList.remove('invalid');var m=f.querySelector('.err-msg');if(m)m.remove();});
      form.querySelectorAll('[required]').forEach(function(el){
        var bad=(el.type==='checkbox')?!el.checked:!el.value.trim();
        if(!bad&&el.type==='email'){bad=!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());}
        if(!bad&&el.type==='tel'){bad=el.value.replace(/\D/g,'').length<7;}
        if(bad){ok=false;var f=el.closest('.field');if(f){f.classList.add('invalid');var m=d.createElement('div');m.className='err-msg';m.textContent=el.type==='email'?'Enter a valid business email address.':(el.type==='tel'?'Enter a valid phone number with country code.':'This field is required.');f.appendChild(m);}}
      });
      var fi=form.querySelector('input[type=file]');
      if(fi&&fi.files.length){var tot=0,allowed=/\.(pdf|dxf|dwg|step|stp|iges|igs|jpg|jpeg|png|zip)$/i;
        for(var j=0;j<fi.files.length;j++){var fl=fi.files[j];tot+=fl.size;if(fl.size>10*1048576||!allowed.test(fl.name)){ok=false;}}
        if(tot>20*1048576||fi.files.length>5)ok=false;
        if(!ok&&alert){alert.textContent=msgs.file;alert.classList.add('show');}
      }
      if(!ok){e.preventDefault();var first=form.querySelector('.invalid input,.invalid select,.invalid textarea');if(first)first.focus();if(alert&&!alert.classList.contains('show')){alert.textContent=msgs.invalid;alert.classList.add('show');}}
      else{var b=form.querySelector('button[type=submit]');if(b){b.disabled=true;b.textContent='Sending…';}}
    });
  });
  var y=d.getElementById('yr'); if(y) y.textContent=new Date().getFullYear();
})();
/* Latest blog posts on the home page (hidden if none) */
(function(){
  var box=document.getElementById('latest-posts'); if(!box||!window.fetch) return;
  fetch('/blog/latest.php',{credentials:'omit'}).then(function(r){return r.ok?r.json():[]}).then(function(list){
    if(!list||!list.length) return;
    var esc=function(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;};
    box.innerHTML=list.map(function(p){return '<a class="card" href="'+esc(p.url)+'">'+(p.cover?'<div class="ci"><img src="'+esc(p.cover)+'" alt="" loading="lazy"></div>':'')+'<div class="cb"><span class="tag">'+esc(p.date)+'</span><h3>'+esc(p.title)+'</h3><p>'+esc(p.excerpt)+'</p><span class="more">Read article →</span></div></a>';}).join('');
    document.getElementById('latest-insights').hidden=false;
  }).catch(function(){});
})();
/* Product carousel: steady auto-scroll, pause on hover/focus/touch, arrows */
(function(){
  var root=document.querySelector('.pcarousel'); if(!root) return;
  var vp=root.querySelector('.pc-viewport'), track=root.querySelector('.pc-track');
  var cards=[].slice.call(track.children); if(!cards.length) return;
  cards.forEach(function(c){var k=c.cloneNode(true);k.setAttribute('aria-hidden','true');k.tabIndex=-1;track.appendChild(k);});
  var reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var x=0, paused=false, last=null, SPEED=38; // pixels per second
  function half(){return track.scrollWidth/2;}
  function step(t){ if(last===null) last=t; var dt=(t-last)/1000; last=t;
    if(!paused&&!reduce){x+=SPEED*dt; if(x>=half()) x-=half(); track.style.transform='translateX('+(-x)+'px)';}
    requestAnimationFrame(step);}
  requestAnimationFrame(step);
  function pause(){paused=true;} function play(){paused=false;}
  vp.addEventListener('mouseenter',pause); vp.addEventListener('mouseleave',play);
  vp.addEventListener('focusin',pause); vp.addEventListener('focusout',play);
  vp.addEventListener('touchstart',pause,{passive:true}); vp.addEventListener('touchend',function(){setTimeout(play,2500);});
  function jump(dir){var w=cards[0].getBoundingClientRect().width+22; var target=x+dir*w; var start=x, t0=null;
    function anim(t){if(!t0)t0=t; var k=Math.min(1,(t-t0)/450); x=start+(target-start)*(1-Math.pow(1-k,3)); if(x<0)x+=half(); if(x>=half())x-=half(); track.style.transform='translateX('+(-x)+'px)'; if(k<1)requestAnimationFrame(anim);}
    requestAnimationFrame(anim);}
  root.querySelector('.prev').addEventListener('click',function(){jump(-1);});
  root.querySelector('.next').addEventListener('click',function(){jump(1);});
})();
/* Factory tour video: shows the video once a link is saved in Admin > Settings */
(function(){
  var box=document.getElementById('tour-box'); if(!box||!window.fetch||location.protocol==='file:') return;
  fetch('/site-config.php',{credentials:'omit'}).then(function(r){return r.ok?r.json():{}}).then(function(c){
    var u=(c&&c.tour_video||'').trim(); if(!u) return; var m, el;
    if((m=u.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/))){el=document.createElement('iframe');el.src='https://www.youtube-nocookie.com/embed/'+m[1]+'?rel=0';el.allow='accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen';el.allowFullscreen=true;el.title='JASSCO factory tour';}
    else if((m=u.match(/vimeo\.com\/(\d+)/))){el=document.createElement('iframe');el.src='https://player.vimeo.com/video/'+m[1];el.allow='fullscreen; picture-in-picture';el.allowFullscreen=true;el.title='JASSCO factory tour';}
    else if(/\.mp4(\?|$)/i.test(u)){el=document.createElement('video');el.src=u;el.controls=true;el.preload='metadata';el.poster=box.getAttribute('data-poster');}
    if(el){box.innerHTML='';box.appendChild(el);}
  }).catch(function(){});
})();
