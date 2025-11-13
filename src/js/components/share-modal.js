// ShareModal component JS - Con campos dinámicos y control de permisos
// Busca el fragmento #share-modal en el DOM y gestiona apertura/cierre, envío via fetch, y adición dinámica de campos.
(function(){
    if (typeof window === 'undefined') return;
    
    function initShareModal() {
        function q(sel, ctx){return (ctx||document).querySelector(sel)}
        function qa(sel, ctx){return Array.from((ctx||document).querySelectorAll(sel))}
        
        // Obtener BASE_URL del meta tag
        const baseUrlMeta = document.querySelector('meta[name="base-url"]');
        const BASE_URL = baseUrlMeta ? baseUrlMeta.getAttribute('content') : '';
        
        // Obtener CSRF token del meta tag
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        // Obtener permisos del usuario
        // IMPORTANTE: En dashboard.php no hay meta tags de permisos porque el usuario está compartiendo sus PROPIAS plantillas
        // En miplantilla.php SI hay meta tags. Si no existen, asumimos que CAN_SHARE=true (default para propias)
        const canShareMeta = document.querySelector('meta[name="can-share"]');
        const CAN_SHARE = canShareMeta ? canShareMeta.getAttribute('content') === 'true' : true; // Default: true (propias)
        
        const canDeleteSharesMeta = document.querySelector('meta[name="can-delete-shares"]');
        const CAN_DELETE_SHARES = canDeleteSharesMeta ? canDeleteSharesMeta.getAttribute('content') === 'true' : true; // Default: true (propias)
        
        const userRoleMeta = document.querySelector('meta[name="user-role"]');
        const USER_ROLE = userRoleMeta ? userRoleMeta.getAttribute('content') : 'propietario'; // Default: propietario
        
        console.log('[ShareModal] CSRF_TOKEN:', CSRF_TOKEN ? 'Present (length: ' + CSRF_TOKEN.length + ')' : 'MISSING!');
        console.log('[ShareModal] BASE_URL:', BASE_URL);
        console.log('[ShareModal] USER_ROLE:', USER_ROLE);
        console.log('[ShareModal] CAN_SHARE:', CAN_SHARE);
        console.log('[ShareModal] CAN_DELETE_SHARES:', CAN_DELETE_SHARES);
        
        // Helper para construir URLs API
        function apiUrl(path) {
            if (BASE_URL && BASE_URL.startsWith('http')) {
                try {
                    const url = new URL(BASE_URL);
                    const currentUrl = new URL(window.location.href);
                    if (url.hostname !== currentUrl.hostname) {
                        return BASE_URL + path;
                    }
                } catch (e) {}
            }
            return path;
        }

        const modal = q('#share-modal');
        if(!modal) {
            console.warn('[ShareModal] Modal element not found');
            return;
        }

        const form = q('#share-modal-form', modal);
        if (!form) {
            console.warn('[ShareModal] Form element not found');
            return;
        }
        
        const status = q('.share-modal__status', modal);
        const closeBtn = q('[data-share-modal-close]', modal);
        const cancelBtn = q('[data-share-modal-cancel]', modal);
        const backdrop = q('[data-share-modal-backdrop]', modal);
        const emailsContainer = q('[data-emails-container]', modal);
        const addEmailBtn = q('[data-add-email-field]', modal);
        const sharedListDiv = q('[data-shared-list]', modal);
        const sharedUsersDiv = q('[data-shared-users]', modal);
        
        let currentPlantillaId = null;
        let openerEl = null;
        let _keydownHandler = null;
        let emailFieldCount = 1;

        function addEmailField() {
            emailFieldCount++;
            const newField = document.createElement('div');
            newField.className = 'share-modal__email-row';
            
            const input = document.createElement('input');
            input.type = 'email';
            input.className = 'share-modal__email';
            input.setAttribute('data-share-email', '');
            input.setAttribute('placeholder', 'Correo electrónico');
            
            const roleSelect = document.createElement('select');
            roleSelect.className = 'share-modal__role';
            roleSelect.setAttribute('data-share-role', '');
            roleSelect.innerHTML = '<option value="lector">Lector</option><option value="editor">Editor</option><option value="admin">Admin</option>';
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'share-modal__remove-btn';
            removeBtn.textContent = '✕';
            removeBtn.style.cssText = 'background:none;border:none;color:#999;cursor:pointer;font-size:1.2rem;padding:0.4vh 0.8vh;border-radius:0.4vh;transition:all 0.2s;flex-shrink:0;';
            
            removeBtn.addEventListener('mouseenter', function(){ this.style.background = 'rgba(0,0,0,0.08)'; this.style.color = '#333'; });
            removeBtn.addEventListener('mouseleave', function(){ this.style.background = 'none'; this.style.color = '#999'; });
            removeBtn.addEventListener('click', function(e){ e.preventDefault(); newField.remove(); });
            
            newField.appendChild(input);
            newField.appendChild(roleSelect);
            newField.appendChild(removeBtn);
            emailsContainer.appendChild(newField);
            
            setTimeout(() => { emailsContainer.scrollTop = emailsContainer.scrollHeight; input.focus(); }, 100);
        }

        function showStatus(msg, success){
            status.style.display='block';
            status.textContent = msg;
            status.style.color = success ? '#2d5a2d' : '#b00020';
            status.style.background = success ? '#f0f8f0' : '#fff5f5';
            status.style.border = success ? '1px solid #90ee90' : '1px solid #f5c6cb';
        }

        function open(id, opener){
            currentPlantillaId = id;
            openerEl = opener || null;
            emailFieldCount = 1;
            
            if (!CAN_SHARE) {
                console.warn('[ShareModal] Sin permisos para compartir');
                showStatus('No tienes permiso para compartir esta plantilla', false);
                return;
            }
            
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden','false');
            
            const existingFields = qa('[data-share-email]', emailsContainer);
            existingFields.forEach((field, idx) => {
                if (idx > 0) field.closest('div').remove();
                else field.value = '';
            });
            
            console.log('[ShareModal] Cargando usuarios compartidos para plantilla:', id);
            fetch(apiUrl(`/plantillas/obtener_compartidos.php?id=${id}`), { credentials: 'same-origin' })
            .then(r => { console.log('[ShareModal] Status:', r.status); return r.json(); })
            .then(data => {
                console.log('[ShareModal] Data:', data);
                if (data.usuariosCompartidos && Array.isArray(data.usuariosCompartidos)) {
                    if (data.usuariosCompartidos.length > 0 && sharedListDiv) {
                        sharedListDiv.style.display = 'block';
                        if (sharedUsersDiv) {
                            sharedUsersDiv.innerHTML = '';
                            data.usuariosCompartidos.forEach(item => {
                                const email = typeof item === 'string' ? item : item.email;
                                let rol = typeof item === 'string' ? 'lector' : (item.rol || 'lector');
                                
                                // Validar que rol tiene un valor permitido
                                if (!['lector', 'editor', 'admin'].includes(rol)) {
                                    console.warn('[ShareModal] Rol inválido recibido:', rol, 'usando lector por defecto');
                                    rol = 'lector';
                                }
                                
                                const userCard = document.createElement('div');
                                userCard.className = 'share-modal__shared-item';
                                
                                const userInfo = document.createElement('div');
                                userInfo.className = 'share-modal__shared-info';
                                
                                const emailSpan = document.createElement('span');
                                emailSpan.className = 'share-modal__shared-email';
                                emailSpan.textContent = email;
                                
                                const roleBadge = document.createElement('span');
                                roleBadge.className = 'share-modal__shared-role';
                                roleBadge.textContent = rol;
                                
                                userInfo.appendChild(emailSpan);
                                userInfo.appendChild(roleBadge);
                                
                                const deleteBtn = document.createElement('button');
                                deleteBtn.type = 'button';
                                deleteBtn.className = 'share-modal__user-delete-btn';
                                deleteBtn.textContent = 'Eliminar';
                                deleteBtn.setAttribute('data-email', email);
                                
                                if (!CAN_DELETE_SHARES) {
                                    deleteBtn.disabled = true;
                                    deleteBtn.style.opacity = '0.5';
                                    deleteBtn.style.cursor = 'not-allowed';
                                    deleteBtn.title = 'No tienes permiso para eliminar';
                                }
                                
                                deleteBtn.addEventListener('click', function(e){
                                    e.preventDefault();
                                    if (!CAN_DELETE_SHARES) { showStatus('No tienes permiso para eliminar', false); return; }
                                    if (confirm(`¿Eliminar acceso de ${email}?`)) {
                                        deleteBtn.disabled = true;
                                        deleteBtn.textContent = 'Eliminando...';
                                        
                                        fetch(apiUrl('/plantillas/eliminar_compartido.php'), {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                                            body: JSON.stringify({ plantillaId: currentPlantillaId, email: email }),
                                            credentials: 'same-origin'
                                        })
                                        .then(r => r.json())
                                        .then(js => {
                                            if (js.success) {
                                                userCard.style.animation = 'slideOut 0.3s ease';
                                                setTimeout(() => userCard.remove(), 300);
                                                showStatus(`Acceso eliminado`, true);
                                                if (sharedUsersDiv.children.length === 1) {
                                                    setTimeout(() => { if (sharedUsersDiv.children.length === 0) sharedListDiv.style.display = 'none'; }, 300);
                                                }
                                            } else {
                                                deleteBtn.disabled = false;
                                                deleteBtn.textContent = 'Eliminar';
                                                showStatus(js.message || 'Error', false);
                                            }
                                        })
                                        .catch(err => { console.error(err); deleteBtn.disabled = false; deleteBtn.textContent = 'Eliminar'; showStatus('Error de red', false); });
                                    }
                                });
                                
                                userCard.appendChild(userInfo);
                                userCard.appendChild(deleteBtn);
                                sharedUsersDiv.appendChild(userCard);
                            });
                        }
                    } else if (sharedListDiv) {
                        sharedListDiv.style.display = 'none';
                    }
                }
            })
            .catch(err => console.warn('[ShareModal] Error cargando compartidos:', err));
            
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = scrollbarWidth + 'px';
            
            const first = q('[data-share-email]', modal);
            if(first) setTimeout(() => first.focus(), 100);

            _keydownHandler = function(e){
                if (e.key === 'Escape') { e.preventDefault(); close(); return; }
                if (e.key === 'Tab') {
                    const focusables = Array.from(modal.querySelectorAll('a,button,input,textarea,select,[tabindex]:not([tabindex="-1"])')).filter(el => !el.disabled && el.offsetParent !== null);
                    if (focusables.length === 0) return;
                    const firstEl = focusables[0];
                    const lastEl = focusables[focusables.length-1];
                    if (e.shiftKey && document.activeElement === firstEl) { e.preventDefault(); lastEl.focus(); }
                    else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); firstEl.focus(); }
                }
            };
            document.addEventListener('keydown', _keydownHandler);
        }
        
        function close(){
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden','true');
            status.style.display='none';
            status.textContent='';
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            if (_keydownHandler) { document.removeEventListener('keydown', _keydownHandler); _keydownHandler = null; }
            try{ if (openerEl && typeof openerEl.focus === 'function') openerEl.focus(); }catch(e){}
            openerEl = null;
        }

        function serializeEmailsWithRoles(){
            const emailInputs = qa('[data-share-email]', modal);
            const roleSelects = qa('[data-share-role]', modal);
            const result = [];
            emailInputs.forEach((input, idx) => {
                const email = input.value.trim();
                if (email) result.push({ email: email, rol: roleSelects[idx] ? roleSelects[idx].value : 'lector' });
            });
            return result;
        }

        if (addEmailBtn) {
            addEmailBtn.addEventListener('click', function(e){ e.preventDefault(); addEmailField(); });
        }

        document.addEventListener('click', function(e){
            const btn = e.target.closest('.share-btn, .share-btn-shared');
            if(!btn) return;
            e.preventDefault();
            const id = btn.getAttribute('data-plantilla-id');
            if(!id){ console.warn('share-btn sin data-plantilla-id'); return; }
            open(id, btn);
        });

        if (closeBtn) closeBtn.addEventListener('click', close);
        if (cancelBtn) cancelBtn.addEventListener('click', close);
        if (backdrop) backdrop.addEventListener('click', close);

        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                if (!CAN_SHARE) { showStatus('No tienes permiso para compartir', false); return; }
                const emailsWithRoles = serializeEmailsWithRoles();
                if(emailsWithRoles.length === 0){ showStatus('Introduce al menos un correo', false); return; }
                showStatus('Enviando...', true);
                fetch(apiUrl('/plantillas/compartir_plantilla.php'), {
                    method:'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({ plantillaId: currentPlantillaId, emailsWithRoles: emailsWithRoles }),
                    credentials: 'same-origin'
                }).then(r => r.text().then(text => { try { return JSON.parse(text); } catch(e){ return { _raw: text, httpStatus: r.status }; } }))
                .then(js => {
                    const ok = (js && (js.success === true || js.status === 'success')) || (js && js.httpStatus === 200);
                    if(ok){ 
                        showStatus('Compartido con éxito', true);
                        document.dispatchEvent(new CustomEvent('share:success',{detail:{id:currentPlantillaId, emailsWithRoles}}));
                        setTimeout(close, 1200);
                    } else {
                        showStatus((js && js.message) ? js.message : 'Error al compartir', false);
                    }
                }).catch(err => { console.error('Error:', err); showStatus('Error de red', false); });
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShareModal);
    } else {
        initShareModal();
    }
})();
