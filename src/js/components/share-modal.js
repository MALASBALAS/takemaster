// ShareModal component JS
// Busca el fragmento #share-modal en el DOM y gestiona apertura/cierre y envío via fetch.
(function(){
    if (typeof window === 'undefined') return;
    function q(sel, ctx){return (ctx||document).querySelector(sel)}
    function qa(sel, ctx){return Array.from((ctx||document).querySelectorAll(sel))}

    const modal = q('#share-modal');
    if(!modal) return;

    const form = q('#share-modal-form', modal);
    const status = q('.share-modal__status', modal);
    const closeBtn = q('[data-share-modal-close]', modal);
    const cancelBtn = q('[data-share-modal-cancel]', modal);
    const backdrop = q('[data-share-modal-backdrop]', modal);
    let currentPlantillaId = null;
    let openerEl = null; // element that opened the modal, to restore focus
    let _keydownHandler = null;

    function open(id, opener){
        currentPlantillaId = id;
        openerEl = opener || null;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden','false');
        // focus first email
        const first = q('[data-share-email]', modal);
        if(first) first.focus();

        // add keydown handler for ESC and focus trap
        _keydownHandler = function(e){
            if (e.key === 'Escape') { e.preventDefault(); close(); return; }
            if (e.key === 'Tab') {
                const focusables = Array.from(modal.querySelectorAll('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])')).filter(function(el){ return !el.disabled && el.offsetParent !== null; });
                if (focusables.length === 0) return;
                const firstEl = focusables[0];
                const lastEl = focusables[focusables.length-1];
                if (e.shiftKey) {
                    if (document.activeElement === firstEl) { e.preventDefault(); lastEl.focus(); }
                } else {
                    if (document.activeElement === lastEl) { e.preventDefault(); firstEl.focus(); }
                }
            }
        };
        document.addEventListener('keydown', _keydownHandler);
    }
    function close(){
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden','true');
        status.style.display='none';
        status.textContent='';
        if (_keydownHandler) { document.removeEventListener('keydown', _keydownHandler); _keydownHandler = null; }
        // restore focus to opener
        try{ if (openerEl && typeof openerEl.focus === 'function') openerEl.focus(); }catch(e){/* ignore */}
        openerEl = null;
    }

    function serializeEmails(){
        return qa('[data-share-email]', modal).map(i=>i.value.trim()).filter(Boolean);
    }

    function showStatus(msg, success){
        status.style.display='block';
        status.textContent = msg;
        status.style.color = success? 'green':'#b00020';
    }

    // Attach global handler for buttons that trigger the modal
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.share-btn');
        if(!btn) return;
        e.preventDefault();
        const id = btn.getAttribute('data-plantilla-id');
        if(!id){ console.warn('share-btn sin data-plantilla-id'); return; }
        open(id, btn);
    });

    closeBtn && closeBtn.addEventListener('click', close);
    cancelBtn && cancelBtn.addEventListener('click', close);
    backdrop && backdrop.addEventListener('click', close);

    form && form.addEventListener('submit', function(e){
        e.preventDefault();
        const emails = serializeEmails();
        if(emails.length === 0){ showStatus('Introduce al menos un correo.', false); return; }
        // CSRF token if present in meta
        const meta = document.querySelector('meta[name="csrf-token"]');
        const headers = {'Content-Type':'application/json'};
        if(meta) headers['X-CSRF-Token'] = meta.getAttribute('content');

        showStatus('Enviando...', true);
        fetch('/plantillas/compartir_plantilla.php', {
            method:'POST',
            headers: headers,
            body: JSON.stringify({ plantillaId: currentPlantillaId, emails: emails })
        }).then(r=>{
            // try parse JSON, but if server returns non-JSON fallback to status code
            return r.text().then(text=>{
                try { return JSON.parse(text); } catch(e){ return { _raw: text, httpStatus: r.status }; }
            });
        }).then(js=>{
            // backend may return { status: 'success' } or { success: true }
            const ok = (js && (js.success === true || js.status === 'success')) || (js && js.httpStatus === 200);
            if(ok){
                showStatus('Compartido con éxito.', true);
                document.dispatchEvent(new CustomEvent('share:success',{detail:{id:currentPlantillaId, emails}}));
                setTimeout(close, 1200);
            } else {
                showStatus((js && js.message) ? js.message : 'Error al compartir.', false);
            }
        }).catch(err=>{
            console.error('ShareModal fetch error', err);
            showStatus('Error de red al enviar.', false);
        });
    });
})();
