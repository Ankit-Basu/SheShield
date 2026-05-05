<script>
function showToast(message,type='success'){
  const c={success:['rgba(34,197,94,0.15)','rgba(34,197,94,0.4)','#4ade80'],error:['rgba(239,68,68,0.15)','rgba(239,68,68,0.4)','#f87171'],emergency:['rgba(239,68,68,0.2)','rgba(239,68,68,0.6)','#fca5a5'],warning:['rgba(245,158,11,0.15)','rgba(245,158,11,0.4)','#fbbf24']};
  const[bg,border,tc]=c[type]||c.success;
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:24px;right:24px;z-index:9999;background:${bg};border:1px solid ${border};backdrop-filter:blur(16px);border-radius:14px;padding:14px 20px;color:${tc};font-size:13px;font-family:Inter,sans-serif;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,0.4);animation:toastIn .4s cubic-bezier(.34,1.56,.64,1)`;
  t.textContent=message;document.body.appendChild(t);
  setTimeout(()=>{t.style.animation='toastOut .3s ease forwards';setTimeout(()=>t.remove(),300)},4000);
}
</script>
