<?php
// Notice area (accessible banners). Include once near top of page or in fragments that need to show notices.
?>
<div id="site-notice" aria-live="polite" aria-atomic="true" style="position:fixed;top:12px;right:12px;z-index:1400;max-width:360px;pointer-events:none"></div>

<style>
#site-notice .notice{pointer-events:auto;padding:10px 12px;border-radius:8px;margin-bottom:8px;box-shadow:0 6px 18px rgba(0,0,0,0.12);font-size:0.95rem}
#site-notice .notice.info{background:#f8f9fa;color:#111}
#site-notice .notice.success{background:#d4edda;color:#155724}
#site-notice .notice.error{background:#f8d7da;color:#721c24}
</style>
