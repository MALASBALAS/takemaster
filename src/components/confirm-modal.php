<?php
// Confirm modal fragment (accesible)
?>
<div id="confirm-modal" class="confirm-modal" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;" aria-labelledby="confirm-modal-title">
    <div class="confirm-modal__backdrop" data-confirm-backdrop></div>
    <div class="confirm-modal__panel" role="document">
        <header class="confirm-modal__header">
            <h3 id="confirm-modal-title">Confirmar acción</h3>
            <button type="button" class="confirm-modal__close" data-confirm-close aria-label="Cerrar">✕</button>
        </header>
        <div class="confirm-modal__body">
            <p id="confirm-modal-message">¿Estás seguro?</p>
            <div class="confirm-modal__actions">
                <button type="button" class="btn btn-secondary" data-confirm-cancel>Cancelar</button>
                <button type="button" class="btn btn-primary" data-confirm-confirm>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<style>
.confirm-modal{position:fixed;inset:0;z-index:1300;display:none}
.confirm-modal__backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.35)}
.confirm-modal__panel{position:relative;width:360px;max-width:94%;margin:12vh auto;background:#fff;border-radius:8px;padding:12px;box-shadow:0 8px 30px rgba(0,0,0,0.2)}
.confirm-modal__header{display:flex;justify-content:space-between;align-items:center}
.confirm-modal__actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.btn{padding:8px 12px;border-radius:6px;border:0;cursor:pointer}
.btn-primary{background:#007bff;color:#fff}
.btn-secondary{background:#f0f0f0;color:#333}
</style>
