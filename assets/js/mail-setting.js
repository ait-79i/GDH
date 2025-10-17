(function ($) {
  'use strict';

  // Copy placeholder chips to clipboard
  $(document).on('click', '[data-var]', function (e) {
    e.preventDefault();
    const $el = $(this);
    const varText = $el.attr('data-var');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(varText);
      const orig = $el.text();
      $el.text('Copié!');
      setTimeout(function () { $el.text(orig); }, 1000);
    }
  });

  // Delegated handler: close our validation notice on dismiss icon click
  $(document).on('click', '#gdh-confirm-validate-error .notice-dismiss', function () {
    const $notice = $('#gdh-confirm-validate-error');
    if ($notice.length) { $notice.remove(); }
  });

  // Live toggle for confirmation panel
  $(document).on('change', '#gdh_confirm_enabled', function(){
    const on = this.checked;
    $('#gdh_confirm_wrap').toggleClass('gdh-muted', !on);
    $('#gdh_confirm_block').toggleClass('gdh-pe-none', !on);
    $('#gdh_confirm_subject').prop('disabled', !on);
  });

  // Toggle: static receiver fields
  $(document).on('change', '#gdh_recv_static_enabled', function(){
    const on = this.checked;
    $('#gdh_recv_static_wrap').toggleClass('gdh-muted', !on);
    $('#gdh_recv_static_block').toggleClass('gdh-pe-none', !on);
    $('#receiver_static_email, #receiver_static_name').prop('disabled', !on);
    // If static is enabled, disable dynamic
    if (on) {
      const $dyn = $('#gdh_recv_dyn_enabled');
      if ($dyn.prop('checked')) {
        $dyn.prop('checked', false).trigger('change');
      }
    }
  });

  // Toggle: dynamic receiver fields
  $(document).on('change', '#gdh_recv_dyn_enabled', function(){
    const on = this.checked;
    $('#gdh_recv_dyn_wrap').toggleClass('gdh-muted', !on);
    $('#gdh_recv_dyn_block').toggleClass('gdh-pe-none', !on);
    $('#receiver_dynamic_post_type, #receiver_dynamic_email, #receiver_dynamic_name').prop('disabled', !on);
    // If dynamic is enabled, disable static
    if (on) {
      const $stat = $('#gdh_recv_static_enabled');
      if ($stat.prop('checked')) {
        $stat.prop('checked', false).trigger('change');
      }
    }
  });

  // Prevent submit if confirmation is enabled but subject/body is empty
  $(document).on('submit', 'form', function (e) {
    const $form = $(this);
    // Ensure this is our email settings form
    const hasAction = $form.find('input[name="action"][value="gdh_save_email_settings"]').length > 0;
    if (!hasAction) return;

    const enabled = document.getElementById('gdh_confirm_enabled');
    if (!enabled || !enabled.checked) return;

    const subjEl = document.getElementById('gdh_confirm_subject');
    const subject = (subjEl && !subjEl.disabled ? (subjEl.value || '').trim() : '');

    // Try TinyMCE first, fallback to textarea
    let bodyText = '';
    try {
      if (typeof tinymce !== 'undefined') {
        const ed = tinymce.get('gdh_confirm_body');
        if (ed && !ed.isHidden()) {
          const html = ed.getContent({ format: 'raw' }) || '';
          bodyText = html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').trim();
        }
      }
    } catch (err) { }

    if (!bodyText) {
      const ta = document.getElementById('gdh_confirm_body');
      if (ta) bodyText = (ta.value || '').trim();
    }

    if (!subject || !bodyText) {
      e.preventDefault();
      e.stopPropagation();
      // Show an error notice
      const wrap = document.querySelector('.wrap');
      let notice = document.getElementById('gdh-confirm-validate-error');
      if (!notice) {
        notice = document.createElement('div');
        notice.id = 'gdh-confirm-validate-error';
        notice.className = 'notice notice-error is-dismissible';
        notice.setAttribute('role', 'alert');
        notice.setAttribute('aria-live', 'assertive');
        notice.tabIndex = -1;
        notice.innerHTML = '<p></p><button type="button" class="notice-dismiss" aria-label="Fermer cette notification"><span class="screen-reader-text">Fermer cette notification.</span></button>';
        if (wrap) {
          wrap.insertBefore(notice, wrap.firstChild.nextSibling);
        } else {
          $(notice).insertBefore($form);
        }
      } else {
        // Ensure dismiss button exists for already-rendered notice
        if (!notice.querySelector('.notice-dismiss')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'notice-dismiss';
          btn.setAttribute('aria-label', 'Fermer cette notification');
          btn.innerHTML = '<span class="screen-reader-text">Fermer cette notification.</span>';
          notice.appendChild(btn);
        }
      }

      const p = notice.querySelector('p');
      if (p) {
        if (!subject && !bodyText) {
          p.textContent = "Le modèle de confirmation est activé : veuillez renseigner le sujet et le corps avant d'enregistrer.";
        } else if (!subject) {
          p.textContent = "Le modèle de confirmation est activé : veuillez renseigner le sujet avant d'enregistrer.";
        } else {
          p.textContent = "Le modèle de confirmation est activé : veuillez renseigner le corps du message avant d'enregistrer.";
        }
      }

      // Enable manual dismiss and auto-dismiss of the notice
      const dismissBtn = notice.querySelector('.notice-dismiss');
      if (dismissBtn && !dismissBtn._gdhBound) {
        dismissBtn.addEventListener('click', function () {
          if (notice && notice.parentNode) {
            notice.parentNode.removeChild(notice);
          }
        });
        dismissBtn._gdhBound = true;
      }
      if (notice._gdhTimer) { clearTimeout(notice._gdhTimer); }
      notice._gdhTimer = setTimeout(function () {
        if (notice && notice.parentNode) {
          notice.parentNode.removeChild(notice);
        }
      }, 7000);

      // Bring notice into view and focus for clarity
      try { notice.scrollIntoView({ behavior: 'smooth', block: 'start' }); notice.focus(); } catch (_) { }

      // Focus the first missing field
      if (!subject && subjEl) {
        subjEl.focus();
      } else {
        try {
          if (typeof tinymce !== 'undefined') {
            const ed = tinymce.get('gdh_confirm_body');
            if (ed && !ed.isHidden()) { ed.focus(); }
            else {
              const ta = document.getElementById('gdh_confirm_body');
              if (ta) ta.focus();
            }
          }
        } catch (err) {
          const ta = document.getElementById('gdh_confirm_body');
          if (ta) ta.focus();
        }
      }
    }
  });
})(jQuery);
