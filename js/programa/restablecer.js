document.addEventListener('DOMContentLoaded',()=> {
    const f=document.getElementById('resetForm'),m=document.getElementById('mensaje');
    if(!f)return;
    const params=new URLSearchParams(location.search);
    const t=params.get('token') || '';
    if(t){ history.replaceState(null,'',location.pathname); }
    f.addEventListener('submit',async e=>{
        e.preventDefault();
        const p=document.getElementById('password').value,p2=document.getElementById('password2').value;
        if(!/^[a-f0-9]{64}$/.test(t)||p.length<12||p.length>255||!/[A-Z]/.test(p)||!/[a-z]/.test(p)||!/[0-9]/.test(p)||!/[^A-Za-z0-9]/.test(p)||p!==p2){
            m.textContent='La contraseña debe tener 12 caracteres, mayúscula, minúscula, número y símbolo; ambas deben coincidir.';return;
        }
        try{
            const r=await fetch('../../backend/restablecer.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,password:p})});
            const d=await r.json();m.textContent=d.message;
            if(d.ok){f.reset();setTimeout(()=>location.href='/control_combustible/index.php',2000)}
        }catch(_){m.textContent='No fue posible completar la operación.'}
    });
});
