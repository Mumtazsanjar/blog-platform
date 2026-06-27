/**
 * ModernBlog – Admin JavaScript
 */

'use strict';

/* ── Sidebar Toggle ────────────────────────────────────────── */
const Sidebar = (() => {
  function init() {
    const toggle  = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('admin-sidebar') || document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
      document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    });
    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
      });
    }

    // Close on ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
      }
    });
  }
  return { init };
})();

/* ── Slug Auto-generation ──────────────────────────────────── */
const SlugGenerator = (() => {
  function init() {
    const titleInput = document.getElementById('post-title');
    const slugInput  = document.getElementById('post-slug');
    const slugPreview= document.getElementById('slug-preview-value');
    if (!titleInput || !slugInput) return;

    let manualSlug = slugInput.value.length > 0;

    slugInput.addEventListener('input', () => { manualSlug = slugInput.value.length > 0; });

    titleInput.addEventListener('input', () => {
      if (manualSlug) return;
      const slug = toSlug(titleInput.value);
      slugInput.value = slug;
      if (slugPreview) slugPreview.textContent = slug || 'your-post-slug';
    });

    slugInput.addEventListener('input', () => {
      const slug = toSlug(slugInput.value);
      slugInput.value = slug;
      if (slugPreview) slugPreview.textContent = slug || 'your-post-slug';
    });
  }

  function toSlug(s) {
    return s.toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s_]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  return { init };
})();

/* ── Tag Input ─────────────────────────────────────────────── */
const TagInput = (() => {
  function init() {
    document.querySelectorAll('.tags-input-wrapper').forEach(wrapper => {
      const input   = wrapper.querySelector('input[type="text"]');
      const hidden  = document.getElementById(wrapper.dataset.target);
      if (!input || !hidden) return;

      let tags = hidden.value ? hidden.value.split(',').filter(Boolean) : [];
      renderTags();

      input.addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) {
          e.preventDefault();
          addTag(input.value.trim().replace(/,/g,''));
          input.value = '';
        } else if (e.key === 'Backspace' && !input.value && tags.length) {
          tags.pop();
          renderTags();
        }
      });

      wrapper.addEventListener('click', () => input.focus());

      function addTag(tag) {
        tag = tag.trim();
        if (!tag || tags.includes(tag)) return;
        tags.push(tag);
        renderTags();
      }

      function removeTag(tag) {
        tags = tags.filter(t => t !== tag);
        renderTags();
      }

      function renderTags() {
        wrapper.querySelectorAll('.tag-pill').forEach(p => p.remove());
        tags.forEach(tag => {
          const pill = document.createElement('span');
          pill.className = 'tag-pill';
          pill.innerHTML = `${escHtml(tag)}<button type="button" aria-label="Remove ${escHtml(tag)}">×</button>`;
          pill.querySelector('button').addEventListener('click', () => removeTag(tag));
          wrapper.insertBefore(pill, input);
        });
        hidden.value = tags.join(',');
      }
    });
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  return { init };
})();

/* ── Delete Confirmation ───────────────────────────────────── */
const DeleteConfirm = (() => {
  function init() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click', e => {
        const msg = el.dataset.confirm || 'Are you sure you want to delete this item?';
        if (!confirm(msg)) e.preventDefault();
      });
    });
  }
  return { init };
})();

/* ── Flash / Alert Auto-dismiss ────────────────────────────── */
const FlashMessages = (() => {
  function init() {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
      setTimeout(() => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
      }, parseInt(el.dataset.autoDismiss, 10) || 4000);
    });
  }
  return { init };
})();

/* ── Image Preview ─────────────────────────────────────────── */
const ImagePreview = (() => {
  function init() {
    const input   = document.getElementById('featured-image-url');
    const preview = document.getElementById('featured-image-preview');
    if (!input || !preview) return;

    function update() {
      const src = input.value.trim();
      preview.style.display = src ? 'block' : 'none';
      if (src) { preview.src = src; }
    }

    input.addEventListener('input', debounce(update, 400));
    update();
  }

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

  return { init };
})();

/* ── Bulk Actions ──────────────────────────────────────────── */
const BulkActions = (() => {
  function init() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = () => document.querySelectorAll('input[name="selected[]"]');
    const bulkForm   = document.getElementById('bulk-action-form');
    const bulkBtn    = document.getElementById('bulk-apply');

    if (!selectAll) return;

    selectAll.addEventListener('change', () => {
      checkboxes().forEach(cb => { cb.checked = selectAll.checked; });
      updateBulkBtn();
    });

    document.addEventListener('change', e => {
      if (e.target.matches('input[name="selected[]"]')) {
        const all = checkboxes();
        const checked = [...all].filter(cb => cb.checked);
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        selectAll.checked = checked.length === all.length;
        updateBulkBtn();
      }
    });

    function updateBulkBtn() {
      if (!bulkBtn) return;
      const checked = [...checkboxes()].filter(cb => cb.checked).length;
      bulkBtn.textContent = checked > 0 ? `Apply to ${checked} item${checked > 1 ? 's' : ''}` : 'Apply';
      bulkBtn.disabled = checked === 0;
    }

    if (bulkForm) {
      bulkForm.addEventListener('submit', e => {
        const action = bulkForm.querySelector('[name="bulk_action"]')?.value;
        const count  = [...checkboxes()].filter(cb => cb.checked).length;
        if (count === 0) { e.preventDefault(); alert('Please select at least one item.'); return; }
        if (!action)     { e.preventDefault(); alert('Please select an action.'); return; }
        if (action === 'delete' && !confirm(`Delete ${count} item${count > 1 ? 's' : ''}? This cannot be undone.`)) {
          e.preventDefault();
        }
      });
    }
  }
  return { init };
})();

/* ── Stats Chart (canvas bar chart) ────────────────────────── */
const StatsChart = (() => {
  function drawBarChart(canvasId, labels, values, color = '#6366f1') {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width  = canvas.offsetWidth  * (window.devicePixelRatio || 1);
    const h = canvas.height = canvas.offsetHeight * (window.devicePixelRatio || 1);
    ctx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
    const W = canvas.offsetWidth;
    const H = canvas.offsetHeight;

    const maxVal = Math.max(...values, 1);
    const padding = { top: 20, right: 10, bottom: 30, left: 40 };
    const chartW = W - padding.left - padding.right;
    const chartH = H - padding.top  - padding.bottom;
    const barW   = (chartW / labels.length) * 0.6;
    const gapW   = chartW / labels.length;

    ctx.clearRect(0, 0, W, H);

    // Grid lines
    ctx.strokeStyle = 'rgba(148,163,184,.15)';
    ctx.lineWidth   = 1;
    [0, 0.25, 0.5, 0.75, 1].forEach(frac => {
      const y = padding.top + chartH * (1 - frac);
      ctx.beginPath(); ctx.moveTo(padding.left, y); ctx.lineTo(padding.left + chartW, y); ctx.stroke();
    });

    // Bars
    values.forEach((val, i) => {
      const barH = (val / maxVal) * chartH;
      const x    = padding.left + i * gapW + (gapW - barW) / 2;
      const y    = padding.top  + chartH - barH;

      const grad = ctx.createLinearGradient(0, y, 0, y + barH);
      grad.addColorStop(0, color);
      grad.addColorStop(1, color + '66');
      ctx.fillStyle = grad;
      ctx.beginPath();
      ctx.roundRect(x, y, barW, barH, 4);
      ctx.fill();

      // Label
      ctx.fillStyle = 'rgba(148,163,184,.8)';
      ctx.font = '11px Inter, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(labels[i], x + barW / 2, H - padding.bottom + 14);
    });
  }

  function init() {
    const el = document.getElementById('views-chart');
    if (!el) return;
    const labels = JSON.parse(el.dataset.labels || '[]');
    const values = JSON.parse(el.dataset.values || '[]');
    drawBarChart('views-chart', labels, values, '#6366f1');

    window.addEventListener('resize', debounce(() => {
      drawBarChart('views-chart', labels, values, '#6366f1');
    }, 200));
  }

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

  return { init, drawBarChart };
})();

/* ── Init ──────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  Sidebar.init();
  SlugGenerator.init();
  TagInput.init();
  DeleteConfirm.init();
  FlashMessages.init();
  ImagePreview.init();
  BulkActions.init();
  StatsChart.init();
});
