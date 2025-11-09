<?php
// Componente: ShareModal
// Uso: incluir este fragmento y cargar `src/js/components/share-modal.js`
// El componente añade el modal al DOM y expone la API mediante botones con
// class="share-btn" y data-plantilla-id="<id>".

$maxEmails = $maxEmails ?? 10;
?>
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;" aria-labelledby="share-modal-title">
    <div class="share-modal__backdrop" data-share-modal-backdrop></div>
    <div class="share-modal__panel" role="document" aria-labelledby="share-modal-title">
        <header class="share-modal__header">
            <h3 id="share-modal-title">Compartir plantilla</h3>
            <button type="button" class="share-modal__close" data-share-modal-close aria-label="Cerrar">✕</button>
        </header>
        <div class="share-modal__body">
            <p>Introduce hasta <span class="share-modal__max"><?php echo intval($maxEmails); ?></span> correos electrónicos (uno por campo).</p>
            <form id="share-modal-form" novalidate>
                <div class="share-modal__emails">
                    <?php for ($i = 1; $i <= intval($maxEmails); $i++): ?>
                        <label class="sr-only" for="share-email-<?php echo $i; ?>">Correo <?php echo $i; ?></label>
                        <input type="email" id="share-email-<?php echo $i; ?>" class="share-modal__email" data-share-email aria-label="Email <?php echo $i; ?>" placeholder="Email <?php echo $i; ?>" <?php echo $i===1? 'required':''; ?> />
                    <?php endfor; ?>
                </div>
                <div class="share-modal__actions">
                    <button type="button" class="btn btn-secondary" data-share-modal-cancel>Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-share-modal-send>Compartir</button>
                </div>
            </form>
            <div class="share-modal__status" aria-live="polite" style="display:none;margin-top:8px;"></div>
        </div>
    </div>
</div>

<style>
/* Minimal styles scoped to the component */
.share-modal{position:fixed;inset:0;z-index:1200;display:none}
.share-modal__backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.45)}
.share-modal__panel{position:relative;width:min(720px,94%);margin:6vh auto;background:#fff;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,0.2);padding:16px;z-index:2}
.share-modal__header{display:flex;justify-content:space-between;align-items:center}
.share-modal__body{margin-top:8px}
.share-modal__emails input{display:block;width:100%;padding:8px;margin:6px 0;border-radius:6px;border:1px solid #ddd}
.share-modal__actions{display:flex;gap:8px;justify-content:flex-end;margin-top:10px}
.btn{padding:8px 12px;border-radius:6px;border:0;cursor:pointer}
.btn-primary{background:#007bff;color:#fff}
.btn-secondary{background:#f0f0f0;color:#333}
.sr-only{position:absolute!important;height:1px;width:1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap;border:0;padding:0;margin:-1px}
</style>
