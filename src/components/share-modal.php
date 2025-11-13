<?php
// Componente: ShareModal Dinámico
// Uso: incluir este fragmento y cargar `src/js/components/share-modal.js`
// El componente añade el modal al DOM y expone la API mediante botones con
// class="share-btn" y data-plantilla-id="<id>".
// Permite añadir campos de email dinámicamente sin límite.
?>
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;" aria-labelledby="share-modal-title">
    <div class="share-modal__backdrop" data-share-modal-backdrop></div>
    <div class="share-modal__panel" role="document" aria-labelledby="share-modal-title">
        <header class="share-modal__header">
            <h3 id="share-modal-title">Compartir plantilla</h3>
            <button type="button" class="share-modal__close" data-share-modal-close aria-label="Cerrar">✕</button>
        </header>
        <div class="share-modal__body">
            <p>Introduce los correos electrónicos para compartir la plantilla. Puedes añadir más campos con el botón "Añadir".</p>
            
            <!-- Sección de usuarios ya compartidos -->
            <div class="share-modal__shared-list" data-shared-list style="display:none; margin-bottom: 1.5vh; padding: 1.2vh; background: #f9f9f9; border-radius: 0.6vh; border: 1px solid #eee;">
                <label style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.8vh; color: #333;">
                    ✓ Ya compartida con:
                </label>
                <div data-shared-users style="display: flex; flex-direction: column; gap: 0.6vh;"></div>
            </div>
            
            <form id="share-modal-form" novalidate>
                <div class="share-modal__emails" data-emails-container>
                    <!-- Los campos se generarán dinámicamente con JavaScript -->
                    <div class="share-modal__email-row">
                        <label class="sr-only" for="share-email-1">Correo 1</label>
                        <input type="email" id="share-email-1" class="share-modal__email" data-share-email aria-label="Email 1" placeholder="Correo electrónico" required />
                        <select class="share-modal__role" data-share-role aria-label="Rol 1">
                            <option value="lector">Lector</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="share-modal__add-field">
                    <button type="button" class="btn btn-outline" data-add-email-field aria-label="Añadir otro correo">+ Añadir otro</button>
                </div>
                
                <div class="share-modal__actions">
                    <button type="button" class="btn btn-secondary" data-share-modal-cancel>Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-share-modal-send>Compartir</button>
                </div>
            </form>
            <div class="share-modal__status" aria-live="polite" style="display:none;"></div>
        </div>
    </div>
</div>

<style>
/* Minimal styles scoped to the component - Fully Responsive */
.share-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: none;
    overflow-y: auto;
    padding: 2vh;
}

.share-modal__backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1;
}

.share-modal__panel {
    position: relative;
    z-index: 2;
    width: min(90vw, 720px);
    max-height: 90vh;
    margin: 5vh auto;
    background: #fff;
    border-radius: 1.2vh;
    box-shadow: 0 0.5vh 2.5vh rgba(0, 0, 0, 0.2);
    padding: 2vh;
    overflow-y: auto;
}

.share-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1vh;
    margin-bottom: 1.5vh;
}

.share-modal__header h3 {
    margin: 0;
    font-size: clamp(1.2rem, 2.5vw, 1.5rem);
}

.share-modal__close {
    background: none;
    border: none;
    font-size: clamp(1.5rem, 3vw, 2rem);
    cursor: pointer;
    color: #999;
    padding: 0.5vh;
    min-width: 4vh;
    min-height: 4vh;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5vh;
    transition: all 0.2s ease;
}

.share-modal__close:hover {
    background: rgba(0, 0, 0, 0.08);
    color: #333;
}

.share-modal__body {
    margin-top: 1.5vh;
}

.share-modal__body > p {
    margin: 0 0 1.5vh 0;
    font-size: clamp(0.9rem, 1.8vw, 1rem);
    color: #555;
}

.share-modal__emails {
    display: flex;
    flex-direction: column;
    gap: 1vh;
    margin-bottom: 1.5vh;
    max-height: 50vh;
    overflow-y: auto;
    padding-right: 0.5vh;
}

.share-modal__email-row {
    display: flex;
    gap: 0.8vh;
    align-items: center;
}

.share-modal__email {
    display: block;
    flex: 1;
    box-sizing: border-box;
    padding: 1.2vh;
    margin: 0;
    border-radius: 0.6vh;
    border: 1px solid #ddd;
    font-size: clamp(0.9rem, 1.8vw, 1rem);
    font-family: inherit;
    transition: border-color 0.2s ease;
}

.share-modal__role {
    padding: 1.2vh 0.8vh;
    border-radius: 0.6vh;
    border: 1px solid #ddd;
    font-size: clamp(0.85rem, 1.6vw, 0.95rem);
    background: white;
    cursor: pointer;
    transition: border-color 0.2s ease;
    min-width: 10vh;
}

.share-modal__email:focus {
    outline: none;
    border-color: #0b69ff;
    box-shadow: 0 0 0 0.3vh rgba(11, 105, 255, 0.2);
}

.share-modal__email::placeholder {
    color: #bbb;
}

.share-modal__add-field {
    margin-bottom: 2vh;
    display: flex;
    justify-content: center;
}

.btn-outline {
    background: transparent;
    color: #0b69ff;
    border: 1.5px solid #0b69ff;
}

.btn-outline:hover {
    background: rgba(11, 105, 255, 0.08);
    border-color: #0a58d8;
    color: #0a58d8;
}

.btn-outline:active {
    transform: scale(0.98);
}

.share-modal__actions {
    display: flex;
    gap: 1vh;
    justify-content: flex-end;
    flex-wrap: wrap;
    margin-top: 2vh;
}

.btn {
    padding: clamp(0.7rem, 1.2vw, 0.9rem) clamp(1rem, 2vw, 1.5rem);
    border-radius: 0.6vh;
    border: none;
    cursor: pointer;
    font-size: clamp(0.85rem, 1.6vw, 0.95rem);
    font-weight: 500;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-primary {
    background: #0b69ff;
    color: #fff;
}

.btn-primary:hover {
    background: #0a58d8;
}

.btn-primary:active {
    transform: scale(0.98);
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e8e8e8;
}

.share-modal__status {
    display: none;
    margin-top: 1.5vh;
    padding: 1.2vh;
    border-radius: 0.6vh;
    font-size: clamp(0.85rem, 1.6vw, 0.95rem);
    text-align: center;
    animation: slideIn 0.3s ease;
}

.share-modal__status[aria-live] {
    background: #f0f8f0;
    border: 1px solid #90ee90;
    color: #2d5a2d;
}

.share-modal__status[aria-live][style*="color: rgb(176"]:not([style*="color: rgb(176"]) {
    background: #fff5f5;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-1vh);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(10vh);
    }
}

/* Mobile (< 480px) */
@media (max-width: 480px) {
    .share-modal {
        padding: 1.5vh;
    }
    
    .share-modal__panel {
        width: 100%;
        margin: 2vh auto;
        padding: 1.5vh;
        border-radius: 1vh;
    }
    
    .share-modal__header {
        gap: 0.5vh;
        margin-bottom: 1.2vh;
    }
    
    .share-modal__close {
        min-width: 3.5vh;
        min-height: 3.5vh;
    }
    
    .share-modal__body > p {
        font-size: 0.9rem;
    }
    
    .share-modal__emails {
        gap: 0.8vh;
        max-height: 45vh;
    }
    
    .share-modal__email {
        padding: 1vh;
        font-size: 0.9rem;
    }
    
    .share-modal__actions {
        flex-direction: column;
        gap: 0.8vh;
    }
    
    .btn {
        width: 100%;
        padding: 1rem;
        font-size: 0.9rem;
    }
}

/* Tablet (480px - 920px) */
@media (min-width: 480px) and (max-width: 920px) {
    .share-modal__panel {
        width: 95vw;
        margin: 3vh auto;
    }
    
    .share-modal__actions {
        flex-wrap: wrap;
    }
}

/* Desktop (> 920px) */
@media (min-width: 920px) {
    .share-modal__panel {
        max-height: 85vh;
    }
}

.sr-only {
    position: absolute !important;
    height: 1px;
    width: 1px;
    overflow: hidden;
    clip: rect(1px, 1px, 1px, 1px);
    white-space: nowrap;
    border: 0;
    padding: 0;
    margin: -1px;
}

.share-modal__badge {
    display: inline-block;
    background: #e8f5e9;
    color: #2d5a2d;
    padding: 0.5vh 1vh;
    border-radius: 2vh;
    font-size: 0.85rem;
    border: 1px solid #c8e6c9;
}

.share-modal__shared-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1vh 1.2vh;
    background: #f5f5f5;
    border-radius: 0.6vh;
    border-left: 3px solid #4CAF50;
    font-size: clamp(0.9rem, 1.6vw, 1rem);
    gap: 1vh;
}

.share-modal__shared-info {
    display: flex;
    align-items: center;
    gap: 0.8vh;
    flex: 1;
}

.share-modal__shared-email {
    font-weight: 500;
    color: #333;
}

.share-modal__shared-role {
    display: inline-block;
    padding: 0.4vh 0.8vh;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 0.4vh;
    font-size: 0.85em;
    font-weight: 600;
}

.share-modal__user-delete-btn {
    padding: 0.6vh 1vh;
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 0.4vh;
    cursor: pointer;
    font-size: 0.85em;
    font-weight: 600;
    transition: background 0.2s ease, transform 0.1s ease;
    white-space: nowrap;
}

.share-modal__user-delete-btn:hover {
    background: #ff5252;
    transform: scale(1.05);
}

.share-modal__user-delete-btn:active {
    transform: scale(0.95);
}
</style>
