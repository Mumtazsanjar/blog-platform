/**
 * ModernBlog – Main JavaScript
 */

'use strict';

/* ── Dark / Light Mode ─────────────────────────────────────── */
const ThemeManager = (() => {
  const STORAGE_KEY = 'mb_theme';
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');

  function setTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
    if (toggle) {
      toggle.innerHTML = theme === 'dark'
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
      toggle.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`);
    }
  }

  function init() {
    const stored = localStorage.getItem(STORAGE_KEY);
    const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    setTheme(stored || preferred);
    if (toggle) {
      toggle.addEventListener('click', () => {
        setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
      });
    }
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      if (!localStorage.getItem(STORAGE_KEY)) setTheme(e.matches ? 'dark' : 'light');
    });
  }
  return { init };
})();

/* ── Navigation ────────────────────────────────────────────── */
const NavManager = (() => {
  function init() {
    const navbar    = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu= document.getElementById('mobile-menu');

    if (navbar) {
      window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 20);
      }, { passive: true });
    }

    if (hamburger && mobileMenu) {
      hamburger.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('open', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });
      // Close on outside click
      document.addEventListener('click', e => {
        if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
          mobileMenu.classList.remove('open');
          hamburger.classList.remove('open');
          hamburger.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        }
      });
    }
  }
  return { init };
})();

/* ── Reading Progress Bar ──────────────────────────────────── */
const ProgressBar = (() => {
  function init() {
    const bar = document.getElementById('progress-bar');
    if (!bar) return;
    const article = document.querySelector('.prose') || document.body;

    function update() {
      const articleTop    = article.offsetTop;
      const articleHeight = article.offsetHeight;
      const scrolled      = window.scrollY - articleTop;
      const pct = Math.min(100, Math.max(0, (scrolled / articleHeight) * 100));
      bar.style.width = pct + '%';
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  }
  return { init };
})();

/* ── Reading Time ──────────────────────────────────────────── */
const ReadingTime = (() => {
  function init() {
    const el = document.getElementById('reading-time');
    if (!el) return;
    const prose = document.querySelector('.prose');
    if (!prose) return;
    const words = prose.innerText.trim().split(/\s+/).length;
    const mins  = Math.max(1, Math.ceil(words / 200));
    el.textContent = `${mins} min read`;
  }
  return { init };
})();

/* ── Back to Top ───────────────────────────────────────────── */
const BackToTop = (() => {
  function init() {
    const btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', () => {
      btn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }
  return { init };
})();

/* ── Debounce util ─────────────────────────────────────────── */
function debounce(fn, ms = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}

/* ── Live Search ───────────────────────────────────────────── */
const LiveSearch = (() => {
  function init() {
    const inputs = document.querySelectorAll('.live-search-input');
    inputs.forEach(input => {
      const dropdown = input.closest('.nav-search')?.querySelector('.search-dropdown')
                    || document.getElementById('search-dropdown');
      if (!dropdown) return;

      const doSearch = debounce(async (q) => {
        if (q.length < 2) { dropdown.classList.remove('show'); return; }
        try {
          // Determine base path for AJAX call
          const basePath = document.querySelector('link[rel="canonical"]')?.href
            || window.location.href.replace(/\/[^\/]*(\?.*)?$/, '/');
          const searchUrl = new URL('search.php', basePath).href;
          const res  = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}&ajax=1`);
          const data = await res.json();
          renderDropdown(dropdown, data, q);
        } catch (e) { /* silent */ }
      }, 280);

      input.addEventListener('input', e => doSearch(e.target.value.trim()));
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          window.location.href = `search.php?q=${encodeURIComponent(input.value.trim())}`;
        }
        if (e.key === 'Escape') dropdown.classList.remove('show');
      });

      document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.remove('show');
        }
      });
    });
  }

  function renderDropdown(dropdown, items, q) {
    if (!items || items.length === 0) {
      dropdown.innerHTML = `<div class="search-no-results">No results for "<strong>${escHtml(q)}</strong>"</div>`;
    } else {
      dropdown.innerHTML = items.map(p => `
        <a href="post.php?slug=${escHtml(p.slug)}">
          ${p.featured_image ? `<img src="${escHtml(p.featured_image)}" alt="" loading="lazy">` : ''}
          <div>
            <div class="search-result-title">${escHtml(p.title)}</div>
            <div class="search-result-cat">${escHtml(p.category_name || 'Uncategorized')}</div>
          </div>
        </a>
      `).join('');
    }
    dropdown.classList.add('show');
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  return { init };
})();

/* ── Like Button ───────────────────────────────────────────── */
const LikeButton = (() => {
  function init() {
    document.querySelectorAll('.btn-like').forEach(btn => {
      btn.addEventListener('click', async () => {
        const postId = btn.dataset.postId;
        if (!postId) return;
        btn.disabled = true;
        try {
          // Determine API path dynamically
          const baseMeta = document.querySelector('meta[name="base-url"]');
          const baseUrl  = baseMeta ? baseMeta.content : '/';
          const res  = await fetch(baseUrl + 'api/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
          });
          const data = await res.json();
          if (data.success) {
            btn.classList.toggle('liked', data.liked);
            const countEl = btn.querySelector('.like-count');
            if (countEl) countEl.textContent = data.likes;
            btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
          }
        } catch(e) { /* silent */ }
        btn.disabled = false;
      });
    });
  }
  return { init };
})();

/* ── Comment Form ──────────────────────────────────────────── */
const CommentForm = (() => {
  function init() {
    const form = document.getElementById('comment-form');
    if (!form) return;

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const btn    = form.querySelector('[type="submit"]');
      const status = document.getElementById('comment-status');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Submitting…';

      const body = new FormData(form);

      try {
        // Determine API path dynamically
        const baseMeta = document.querySelector('meta[name="base-url"]');
        const baseUrl  = baseMeta ? baseMeta.content : '/';
        const res  = await fetch(baseUrl + 'api/comment.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          form.reset();
          if (status) {
            status.className = 'alert alert-success';
            status.textContent = '✅ ' + data.message;
            status.style.display = 'flex';
          }
        } else {
          if (status) {
            status.className = 'alert alert-error';
            status.textContent = '❌ ' + (data.message || 'Failed to submit comment.');
            status.style.display = 'flex';
          }
        }
      } catch(err) {
        if (status) {
          status.className = 'alert alert-error';
          status.textContent = '❌ Network error. Please try again.';
          status.style.display = 'flex';
        }
      }
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Post Comment';
    });
  }
  return { init };
})();

/* ── Share Buttons ─────────────────────────────────────────── */
const ShareButtons = (() => {
  function init() {
    document.querySelectorAll('.share-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const url   = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        const type  = btn.dataset.share;

        if (type === 'twitter') {
          window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'noopener');
        } else if (type === 'facebook') {
          window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'noopener');
        } else if (type === 'copy') {
          navigator.clipboard.writeText(window.location.href).then(() => {
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-link"></i> Copy Link'; }, 2500);
          });
        }
      });
    });
  }
  return { init };
})();

/* ── Table of Contents ─────────────────────────────────────── */
const TableOfContents = (() => {
  function init() {
    const container = document.getElementById('toc-container');
    if (!container) return;
    const headings = document.querySelectorAll('.prose h2, .prose h3');
    if (headings.length < 2) { container.style.display = 'none'; return; }

    let html = '<div class="toc"><p class="toc-title"><i class="fas fa-list-ul"></i> Contents</p><ol>';
    let i = 0;
    headings.forEach(h => {
      if (!h.id) {
        h.id = 'heading-' + (++i);
      }
      const indent = h.tagName === 'H3' ? 'margin-left:1.25rem;' : '';
      html += `<li style="${indent}"><a href="#${h.id}">${escHtml(h.textContent)}</a></li>`;
    });
    html += '</ol></div>';
    container.innerHTML = html;

    // Highlight active heading
    const io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        const id = entry.target.id;
        const link = container.querySelector(`a[href="#${id}"]`);
        if (link) link.style.color = entry.isIntersecting ? 'var(--primary)' : '';
      });
    }, { rootMargin: '-20% 0px -70% 0px' });

    headings.forEach(h => io.observe(h));
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
  return { init };
})();

/* ── Lazy Images ───────────────────────────────────────────── */
const LazyImages = (() => {
  function init() {
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; }
            obs.unobserve(img);
          }
        });
      }, { rootMargin: '200px' });
      document.querySelectorAll('img[data-src]').forEach(img => io.observe(img));
    } else {
      document.querySelectorAll('img[data-src]').forEach(img => { img.src = img.dataset.src; });
    }
  }
  return { init };
})();

/* ── Smooth Scroll for anchor links ────────────────────────── */
document.addEventListener('click', e => {
  const a = e.target.closest('a[href^="#"]');
  if (!a) return;
  const target = document.querySelector(a.getAttribute('href'));
  if (target) {
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});

/* ── Contact Form ──────────────────────────────────────────── */
const ContactForm = (() => {
  function init() {
    const form = document.getElementById('contact-form');
    if (!form) return;
    // Standard submit — PHP handles it, just do client-side validation
    form.addEventListener('submit', e => {
      const name    = form.querySelector('[name="name"]');
      const email   = form.querySelector('[name="email"]');
      const message = form.querySelector('[name="message"]');
      let valid = true;
      [name, email, message].forEach(el => {
        if (!el.value.trim()) { el.style.borderColor = 'var(--danger)'; valid = false; }
        else el.style.borderColor = '';
      });
      if (!valid) { e.preventDefault(); return; }
      const btn = form.querySelector('[type="submit"]');
      if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending…'; }
    });
  }
  return { init };
})();

/* ── Init ──────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  ThemeManager.init();
  NavManager.init();
  ProgressBar.init();
  ReadingTime.init();
  BackToTop.init();
  LiveSearch.init();
  LikeButton.init();
  CommentForm.init();
  ShareButtons.init();
  TableOfContents.init();
  LazyImages.init();
  ContactForm.init();

  // Auto-dismiss alerts
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    const delay = parseInt(el.dataset.autoDismiss, 10) || 5000;
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, delay);
  });
});
