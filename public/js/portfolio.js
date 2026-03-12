console.log("portfolio.js v4 loaded");
document.addEventListener('DOMContentLoaded', function () {

    var lightbox  = document.getElementById('lightbox');
    var lbClose   = document.getElementById('lightboxClose');
    var lbImg     = document.getElementById('lightboxImg');
    var lbTitle   = document.getElementById('lightboxTitle');
    var lbDesc    = document.getElementById('lightboxDesc');
    var lbMeta    = document.getElementById('lightboxMeta');
    var lbPrev    = document.getElementById('lbPrev');
    var lbNext    = document.getElementById('lbNext');
    var lbCounter = document.getElementById('lbCounter');
    var lbThumbs  = document.getElementById('lbThumbs');

    if (!lightbox) return;

    var images = [], thumbs = [], idx = 0;

    function openLightbox(trigger) {
        try { images = JSON.parse(trigger.getAttribute('data-images') || '[]'); } catch(e) { images = []; }
        try { thumbs = JSON.parse(trigger.getAttribute('data-thumbs')  || '[]'); } catch(e) { thumbs = []; }
        if (!images.length) { var s = trigger.getAttribute('data-img'); if (s) images = [s]; }
        if (!thumbs.length) thumbs = images.slice();
        idx = 0;

        lbTitle.textContent = trigger.getAttribute('data-title') || '';
        lbDesc.textContent  = trigger.getAttribute('data-desc')  || '';

        var meta = '';
        var t = trigger.getAttribute('data-technique');
        var d = trigger.getAttribute('data-dimensions');
        var y = trigger.getAttribute('data-year');
        if (t) meta += '<dt>Technique</dt><dd>' + t + '</dd>';
        if (d) meta += '<dt>Dimensions</dt><dd>' + d + '</dd>';
        if (y) meta += '<dt>Year</dt><dd>' + y + '</dd>';
        lbMeta.innerHTML = meta;

        showImage();
        buildThumbs();

        lightbox.classList.add('open');
        document.body.classList.add('lightbox-open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        document.body.classList.remove('lightbox-open');
        document.body.style.overflow = '';
    }

    function showImage() {
        lbImg.src = images[idx] || '';
        lbImg.alt = lbTitle.textContent;
        var total = images.length;
        if (total > 1) {
            lbPrev.classList.add('visible');
            lbNext.classList.add('visible');
            lbCounter.classList.add('visible');
            lbCounter.textContent = (idx + 1) + ' / ' + total;
            lbPrev.style.opacity = idx === 0 ? '0.35' : '1';
            lbNext.style.opacity = idx === total - 1 ? '0.35' : '1';
        } else {
            lbPrev.classList.remove('visible');
            lbNext.classList.remove('visible');
            lbCounter.classList.remove('visible');
        }
        document.querySelectorAll('.lb-thumb').forEach(function(t, i) {
            t.classList.toggle('lb-thumb--active', i === idx);
        });
    }

    function buildThumbs() {
        lbThumbs.innerHTML = '';
        if (images.length <= 1) return;
        images.forEach(function(src, i) {
            var img = document.createElement('img');
            img.src = thumbs[i] || src;
            img.className = 'lb-thumb' + (i === 0 ? ' lb-thumb--active' : '');
            img.addEventListener('click', function() { idx = i; showImage(); });
            lbThumbs.appendChild(img);
        });
    }

    function nav(dir) {
        idx = (idx + dir + images.length) % images.length;
        showImage();
    }

    // Attach to every trigger card
    document.querySelectorAll('.lightbox-trigger').forEach(function(el) {
        // Track touch so we can distinguish a tap from a scroll
        var touchStartX, touchStartY;

        el.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        el.addEventListener('touchend', function(e) {
            var dx = Math.abs(e.changedTouches[0].clientX - touchStartX);
            var dy = Math.abs(e.changedTouches[0].clientY - touchStartY);
            if (dx < 10 && dy < 10) {          // it was a tap, not a scroll
                e.preventDefault();
                openLightbox(el);
            }
        }, { passive: false });

        el.addEventListener('click', function(e) {
            e.preventDefault();
            openLightbox(el);
        });

        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLightbox(el); }
        });
    });

    lbClose.addEventListener('click', closeLightbox);
    lbPrev.addEventListener('click',  function(e) { e.stopPropagation(); nav(-1); });
    lbNext.addEventListener('click',  function(e) { e.stopPropagation(); nav(1); });
    lightbox.addEventListener('click', function(e) { if (e.target === lightbox) closeLightbox(); });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('open')) return;
        if (e.key === 'Escape')    closeLightbox();
        if (e.key === 'ArrowLeft') nav(-1);
        if (e.key === 'ArrowRight') nav(1);
    });
});
