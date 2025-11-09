// Simple Notice utility: Notice.show(type, message, ttl)
window.Notice = (function(){
    function show(type, message, ttl){
        ttl = typeof ttl === 'number' ? ttl : 5000;
        var container = document.getElementById('site-notice');
        if(!container) return;
        var n = document.createElement('div');
        n.className = 'notice ' + (type||'info');
        n.setAttribute('role','status');
        n.style.opacity = '0';
        n.style.transition = 'opacity 200ms';
        n.innerHTML = message;
        container.appendChild(n);
        requestAnimationFrame(function(){ n.style.opacity = '1'; });
        setTimeout(function(){ n.style.opacity = '0'; setTimeout(function(){ try{ container.removeChild(n);}catch(e){} }, 200); }, ttl);
    }
    return { show: show };
})();
