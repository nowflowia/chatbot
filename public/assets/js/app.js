/* ================================================================
   ChatBot NowFlow — Global JS
================================================================ */

// ----------------------------------------------------------------
// Toast system
// ----------------------------------------------------------------
window.Toast = (function () {
  function getContainer() {
    let c = document.getElementById('toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  function show(message, type, duration) {
    type     = type     || 'success';
    duration = duration || 4000;

    const icons = {
      success: 'bi-check-circle-fill',
      error:   'bi-exclamation-triangle-fill',
      info:    'bi-info-circle-fill',
      warning: 'bi-exclamation-circle-fill',
    };

    const toast = document.createElement('div');
    toast.className = 'app-toast toast-' + type;
    toast.innerHTML =
      '<i class="bi ' + (icons[type] || icons.info) + ' flex-shrink-0 mt-1"></i>' +
      '<span style="flex:1">' + message + '</span>' +
      '<button class="toast-close" onclick="this.closest(\'.app-toast\').remove()">&times;</button>';

    getContainer().appendChild(toast);

    if (duration > 0) {
      setTimeout(function () {
        toast.style.transition = 'opacity .3s, transform .3s';
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(24px)';
        setTimeout(function () { toast.remove(); }, 320);
      }, duration);
    }
  }

  return { show: show };
})();

// ----------------------------------------------------------------
// API Helper
// ----------------------------------------------------------------
window.Api = (function () {
  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function request(url, method, body) {
    var headers = { 'X-Requested-With': 'XMLHttpRequest' };
    var payload = null;

    if (body instanceof FormData) {
      payload = body;
    } else if (body && typeof body === 'object') {
      headers['Content-Type'] = 'application/json';
      headers['X-CSRF-Token'] = getCsrf();
      payload = JSON.stringify(Object.assign({ _csrf_token: getCsrf() }, body));
    }

    return fetch(url, { method: method, headers: headers, body: payload })
      .then(function (r) {
        if (!r.ok && r.status !== 422 && r.status !== 400 && r.status !== 401 && r.status !== 403 && r.status !== 404 && r.status !== 419) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .catch(function (err) {
        console.error('Api error:', err);
        Toast.show('Erro de conexão. Tente novamente.', 'error');
        return { success: false, message: 'Erro de conexão.', data: {}, errors: {} };
      });
  }

  // Promise-based (used with .then())
  function get(url) {
    return request(url, 'GET', null);
  }

  function post(url, data) {
    var fd = new FormData();
    fd.append('_csrf_token', getCsrf());
    if (data && typeof data === 'object') {
      Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    }
    return request(url, 'POST', fd);
  }

  function formPost(url, formEl, callback) {
    var fd = new FormData(formEl);
    request(url, 'POST', fd).then(function (res) {
      if (callback) callback(res, null);
    });
  }

  return { get: get, post: post, formPost: formPost };
})();

// ----------------------------------------------------------------
// Sidebar collapse toggle
// ----------------------------------------------------------------
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var sidebar  = document.getElementById('sidebar');
    var topbar   = document.getElementById('topbar');
    var content  = document.getElementById('main-content');
    var toggle   = document.getElementById('sidebar-toggle');
    var overlay  = document.getElementById('sidebar-overlay');

    var isMobile = function () { return window.innerWidth < 992; };

    // Restore state
    var collapsed = localStorage.getItem('sidebar_collapsed') === '1';
    if (!isMobile() && collapsed) {
      sidebar.classList.add('collapsed');
      topbar.classList.add('shifted');
      content.classList.add('shifted');
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        if (isMobile()) {
          sidebar.classList.toggle('mobile-open');
          if (overlay) overlay.classList.toggle('show');
        } else {
          var isCollapsed = sidebar.classList.toggle('collapsed');
          topbar.classList.toggle('shifted', isCollapsed);
          content.classList.toggle('shifted', isCollapsed);
          localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
        }
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('show');
      });
    }
  });
})();

// ----------------------------------------------------------------
// Form field error helper
// ----------------------------------------------------------------
window.FormHelper = {
  clearErrors: function (formEl) {
    formEl.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    formEl.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; el.style.display = 'none'; });
  },
  showErrors: function (formEl, errors) {
    if (!errors) return;
    Object.keys(errors).forEach(function (field) {
      var msgs = errors[field];
      var el   = formEl.querySelector('[data-error="' + field + '"]') || document.getElementById('err-' + field);
      var inp  = formEl.querySelector('[name="' + field + '"]') || formEl.getElementById('f-' + field);
      if (el && msgs.length) { el.textContent = msgs[0]; el.style.display = 'block'; }
      if (inp) inp.classList.add('is-invalid');
    });
  },
  setLoading: function (btn, loading, text) {
    var spin = btn.querySelector('.spinner-border');
    btn.disabled = loading;
    if (spin) { spin.classList.toggle('d-none', !loading); }
    if (text) { var t = btn.querySelector('[data-btn-text]'); if (t) t.textContent = text; }
  },
};

// ----------------------------------------------------------------
// Confirm helper
// ----------------------------------------------------------------
window.confirm2 = function (message) {
  return new Promise(function (resolve) {
    resolve(window.confirm(message));
  });
};

// ----------------------------------------------------------------
// Auto-dismiss alerts after 5s
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.auto-dismiss').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .5s';
      el.style.opacity    = '0';
      setTimeout(function () { el.remove(); }, 520);
    }, 5000);
  });
});
