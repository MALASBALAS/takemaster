// Confirm modal JS
(function(){
    if (typeof window === 'undefined') return;
    function q(s, ctx){return (ctx||document).querySelector(s)}
    function qa(s, ctx){return Array.from((ctx||document).querySelectorAll(s))}

    const modal = q('#confirm-modal');
    if(!modal) return;
    const msg = q('#confirm-modal-message', modal);
    const closeBtn = q('[data-confirm-close]', modal);
    const cancelBtn = q('[data-confirm-cancel]', modal);
    const confirmBtn = q('[data-confirm-confirm]', modal);
    const backdrop = q('[data-confirm-backdrop]', modal);
    let opener = null;
    let targetId = null;
    let _keydownHandler = null;

    function open(id, openerEl, message){
        targetId = id;
        opener = openerEl || null;
        if(message) msg.textContent = message;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden','false');
        // focus confirm button
        if(confirmBtn) confirmBtn.focus();
        _keydownHandler = function(e){
            if(e.key === 'Escape'){ e.preventDefault(); close(); return; }
            if (e.key === 'Tab') {
                const focusables = qa('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])', modal).filter(function(el){ return !el.disabled && el.offsetParent !== null; });
                if (focusables.length === 0) return;
                const first = focusables[0], last = focusables[focusables.length-1];
                if (e.shiftKey) { if (document.activeElement === first) { e.preventDefault(); last.focus(); } }
                else { if (document.activeElement === last) { e.preventDefault(); first.focus(); } }
            }
        };
        document.addEventListener('keydown', _keydownHandler);
    }

    function close(){
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden','true');
        if (_keydownHandler) { document.removeEventListener('keydown', _keydownHandler); _keydownHandler = null; }
        try{ if(opener && typeof opener.focus === 'function') opener.focus(); }catch(e){}
        opener = null; targetId = null;
    }

    // click handler for delete buttons
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.delete-btn');
        if(!btn) return;
        e.preventDefault();
        const id = btn.getAttribute('data-plantilla-id');
        if(!id) return;
        open(id, btn, '¿Deseas eliminar esta plantilla? Esta acción no se puede deshacer.');
    });

    confirmBtn && confirmBtn.addEventListener('click', function(){
        if(!targetId) return close();
        
        // Buscar el formulario con el token CSRF
        var form = document.querySelector('form[data-plantilla-form="'+targetId+'"]');
        if(!form){
            console.warn('No se encontró formulario para eliminar plantilla', targetId);
            close();
            return;
        }
        
        console.log('[DELETE] Enviando formulario de eliminación de plantilla:', targetId);
        
        // Enviar el formulario tradicional (POST a dashboard.php que está incluido en micuenta.php)
        // El servidor elimina la plantilla y redirige a micuenta.php?section=dashboard
        close();
        form.submit();
    });

    cancelBtn && cancelBtn.addEventListener('click', close);
    closeBtn && closeBtn.addEventListener('click', close);
    backdrop && backdrop.addEventListener('click', close);
})();
