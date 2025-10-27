(function ($) {
  'use strict';

  // Logger conditionnel (affiche uniquement si debug est activé)
  const gdhrdvDebug = (window.gdhrdvMailSettings && !!gdhrdvMailSettings.debug) || false;
  const gdhrdvLog = {
    trace: (...a) => { if (gdhrdvDebug && console && console.trace) try { console.trace(...a); } catch (_) { } },
    info: (...a) => { if (gdhrdvDebug && console && console.info) try { console.info(...a); } catch (_) { } },
    warn: (...a) => { if (gdhrdvDebug && console && console.warn) try { console.warn(...a); } catch (_) { } },
    error: (...a) => { if (gdhrdvDebug && console && console.error) try { console.error(...a); } catch (_) { } },
  };

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
  }); // Added closure here

  // Default templates (recommended)
  const gdhrdvDefaultTemplates = {
    main: {
      subject: 'Nouvelle demande de rendez-vous – {{nom_lead}} – {{date_rdv}}',
      body: (
        '<h2 style="margin:0 0 12px;">Nouvelle demande de rendez-vous</h2>' +
        '<p>Bonjour {{nom_destinataire}},</p>' +
        '<p>Vous avez reçu une nouvelle demande de rendez-vous de la part de <strong>{{nom_lead}}</strong>.</p>' +
        '<h3 style="margin:16px 0 8px;">Détails du contact</h3>' +
        '<ul style="margin:0 0 12px; padding-left:18px;">' +
        '<li>E‑mail&nbsp;: {{email_lead}}</li>' +
        '<li>Téléphone&nbsp;: {{phone}}</li>' +
        '<li>Adresse&nbsp;: {{address}}, {{postal_code}} {{city}}</li>' +
        '<li>Message&nbsp;: {{message}}</li>' +
        '</ul>' +
        '<h3 style="margin:16px 0 8px;">Créneaux proposés</h3>' +
        '{{creneaux_rdv}}' +
        '<p style="margin-top:16px;">Merci de revenir vers le client pour confirmer un créneau.</p>' +
        '<p>Cordialement,</p>'
      )
    },
    confirm: {
      subject: 'Confirmation de votre demande – {{date_rdv}}',
      body: (
        '<h2 style="margin:0 0 12px;">Confirmation de votre demande de rendez‑vous</h2>' +
        '<p>Bonjour {{nom_lead}},</p>' +
        '<p>Nous avons bien reçu votre demande de rendez‑vous. Voici un rappel des informations transmises&nbsp;:</p>' +
        '<h3 style="margin:16px 0 8px;">Créneaux proposés</h3>' +
        '{{creneaux_rdv}}' +
        '<h3 style="margin:16px 0 8px;">Vos coordonnées</h3>' +
        '<ul style="margin:0 0 12px; padding-left:18px;">' +
        '<li>E‑mail&nbsp;: {{email_lead}}</li>' +
        '<li>Téléphone&nbsp;: {{phone}}</li>' +
        '<li>Adresse&nbsp;: {{address}}, {{postal_code}} {{city}}</li>' +
        '<li>Message&nbsp;: {{message}}</li>' +
        '</ul>' +
        '<p>Cordialement</p>'
      )
    }
  };

  function gdhrdvSetEditorContent(editorId, html) {
    try {
      if (typeof tinymce !== 'undefined') {
        const ed = tinymce.get(editorId);
        if (ed && !ed.isHidden()) { ed.setContent(html); return; }
      }
    } catch (_) { }
    const ta = document.getElementById(editorId);
    if (ta) { ta.value = html; }
  }

  function gdhrdvGetEditorContent(editorId) {
    try {
      if (typeof tinymce !== 'undefined') {
        const ed = tinymce.get(editorId);
        if (ed && !ed.isHidden()) { return ed.getContent({ format: 'raw' }) || ''; }
      }
    } catch (_) { }
    const ta = document.getElementById(editorId);
    return ta ? (ta.value || '') : '';
  }

  // Helper: check if HTML content is effectively empty (ignoring tags and NBSP)
  function gdhrdvIsEmptyHtml(html) {
    try {
      const text = (html || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').trim();
      return text.length === 0;
    } catch (_) { return !html; }
  }

  // Auto-apply defaults on initial load when empty
  $(function () {
    // Main (artisan) template
    try {
      const mainTpl = gdhrdvDefaultTemplates.main;
      const subjEl = document.getElementById('gdhrdv_subject');
      const bodyRaw = gdhrdvGetEditorContent('gdhrdv_body');
      const subjEmpty = !subjEl || (subjEl.value || '').trim() === '';
      const bodyEmpty = gdhrdvIsEmptyHtml(bodyRaw);
      if (mainTpl && (subjEmpty || bodyEmpty)) {
        if (subjEl && subjEmpty) { subjEl.value = mainTpl.subject; }
        if (bodyEmpty) { gdhrdvSetEditorContent('gdhrdv_body', mainTpl.body); }
      }
    } catch (e) { gdhrdvLog.warn('Init modèle principal: exception', e); }

    // Confirmation template (only if confirmation is enabled)
    try {
      const confirmOn = $('#gdhrdv_confirm_enabled').prop('checked');
      if (confirmOn) {
        const confTpl = gdhrdvDefaultTemplates.confirm;
        const cSubjEl = document.getElementById('gdhrdv_confirm_subject');
        const cBodyRaw = gdhrdvGetEditorContent('gdhrdv_confirm_body');
        const cSubjEmpty = !cSubjEl || (cSubjEl.value || '').trim() === '';
        const cBodyEmpty = gdhrdvIsEmptyHtml(cBodyRaw);
        if (confTpl && (cSubjEmpty || cBodyEmpty)) {
          if (cSubjEl && cSubjEmpty) { cSubjEl.value = confTpl.subject; }
          if (cBodyEmpty) { gdhrdvSetEditorContent('gdhrdv_confirm_body', confTpl.body); }
        }
      }
    } catch (e) { gdhrdvLog.warn('Init modèle de confirmation: exception', e); }
  });

  // Delegated handler: close our validation notice on dismiss icon click
  $(document).on('click', '#gdhrdv-confirm-validate-error .notice-dismiss', function () {
    const $notice = $('#gdhrdv-confirm-validate-error');
    if ($notice.length) { $notice.remove(); }
  });

  // Live toggle for confirmation panel
  $(document).on('change', '#gdhrdv_confirm_enabled', function () {
    const on = this.checked;
    $('#gdhrdv_confirm_wrap').toggleClass('gdhrdv-muted', !on);
    $('#gdhrdv_confirm_block').toggleClass('gdhrdv-pe-none', !on);
    $('#gdhrdv_confirm_subject').prop('disabled', !on);
    // If enabling and content is empty, auto-fill defaults
    if (on) {
      try {
        const confTpl = gdhrdvDefaultTemplates.confirm;
        const cSubjEl = document.getElementById('gdhrdv_confirm_subject');
        const cBodyRaw = gdhrdvGetEditorContent('gdhrdv_confirm_body');
        const cSubjEmpty = !cSubjEl || (cSubjEl.value || '').trim() === '';
        const cBodyEmpty = gdhrdvIsEmptyHtml(cBodyRaw);
        if (confTpl && (cSubjEmpty || cBodyEmpty)) {
          if (cSubjEl && cSubjEmpty) { cSubjEl.value = confTpl.subject; }
          if (cBodyEmpty) { gdhrdvSetEditorContent('gdhrdv_confirm_body', confTpl.body); }
        }
      } catch (_) { }
    }
  });

  // Toggle: receiver mode via radio buttons
  $(document).on('change', 'input[name="receiver_mode"]', function () {
    const mode = $('input[name="receiver_mode"]:checked').val();
    const staticOn = (mode === 'static');
    const dynOn = (mode === 'dynamic');
    // Static panel
    $('#gdhrdv_recv_static_wrap').toggleClass('gdhrdv-muted', !staticOn);
    $('#gdhrdv_recv_static_block').toggleClass('gdhrdv-pe-none', !staticOn);
    $('#receiver_static_email, #receiver_static_name').prop('disabled', !staticOn);
    // Dynamic panel
    $('#gdhrdv_recv_dyn_wrap').toggleClass('gdhrdv-muted', !dynOn);
    $('#gdhrdv_recv_dyn_block').toggleClass('gdhrdv-pe-none', !dynOn);
    $('#receiver_dynamic_post_type, #receiver_dynamic_email, #receiver_dynamic_name').prop('disabled', !dynOn);
    // If switching to dynamic and PT already chosen, ensure meta are loaded
    if (dynOn) {
      const postType = ($('#receiver_dynamic_post_type').val() || '').trim();
      if (postType) { gdhrdvFetchAndPopulateMeta(postType); }
    }
  });

  // Helpers to populate dynamic meta key selects
  function gdhrdvResetMetaSelect($sel) {
    if (!$sel || !$sel.length) return;
    $sel.empty();
    $sel.append($('<option/>', { value: '', text: '— Sélectionner une meta —' }));
  }

  function gdhrdvPopulateMetaSelect($sel, keys, selected) {
    if (!$sel || !$sel.length) return;
    gdhrdvResetMetaSelect($sel);
    if (Array.isArray(keys)) {
      keys.forEach(function (k) {
        $sel.append($('<option/>', { value: k, text: k }));
      });
    }
    if (selected && keys && keys.indexOf(selected) !== -1) {
      $sel.val(selected);
    } else {
      $sel.val('');
    }
  }

  function gdhrdvFetchAndPopulateMeta(postType) {
    const $emailSel = $('#receiver_dynamic_email');
    const $nameSel = $('#receiver_dynamic_name');
    if (!postType) {
      gdhrdvResetMetaSelect($emailSel);
      gdhrdvResetMetaSelect($nameSel);
      return;
    }
    // Show loading state
    if ($emailSel.length) { $emailSel.prop('disabled', true); }
    if ($nameSel.length) { $nameSel.prop('disabled', true); }

    const selectedEmail = ($emailSel.attr('data-selected') || '').trim();
    const selectedName = ($nameSel.attr('data-selected') || '').trim();

    $.ajax({
      url: (window.gdhrdvMailSettings ? gdhrdvMailSettings.ajax_url : ''),
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'gdhrdv_get_meta_keys',
        nonce: (window.gdhrdvMailSettings ? gdhrdvMailSettings.nonce : ''),
        post_type: postType
      }
    }).done(function (res) {
      const keys = (res && res.success && res.data && Array.isArray(res.data.meta_keys)) ? res.data.meta_keys : [];
      gdhrdvPopulateMetaSelect($emailSel, keys, selectedEmail);
      gdhrdvPopulateMetaSelect($nameSel, keys, selectedName);
    }).fail(function (jqXHR, textStatus, errorThrown) {
      gdhrdvLog.error('AJAX échec (gdhrdv_get_meta_keys):', textStatus || 'unknown', errorThrown || '', jqXHR && jqXHR.responseText);
      gdhrdvResetMetaSelect($emailSel);
      gdhrdvResetMetaSelect($nameSel);
    }).always(function () {
      const dynOn = ($('input[name="receiver_mode"]:checked').val() === 'dynamic');
      if ($emailSel.length) { $emailSel.prop('disabled', !dynOn); }
      if ($nameSel.length) { $nameSel.prop('disabled', !dynOn); }
    });
  }

  // Change handler: fetch meta keys when post type changes
  $(document).on('change', '#receiver_dynamic_post_type', function () {
    if ($('input[name="receiver_mode"]:checked').val() !== 'dynamic') return;
    const postType = this.value;
    if (postType) {
      gdhrdvFetchAndPopulateMeta(postType);
    } else {
      gdhrdvFetchAndPopulateMeta('');
    }
  });

  // Initial population on load if dynamic enabled and post type already chosen
  $(function () {
    try {
      const dynEnabled = ($('input[name="receiver_mode"]:checked').val() === 'dynamic');
      const postType = ($('#receiver_dynamic_post_type').val() || '').trim();
      if (dynEnabled && postType) {
        gdhrdvFetchAndPopulateMeta(postType);
      }
    } catch (_) { }
  });

  // Prevent submit if confirmation is enabled but subject/body is empty
  $(document).on('submit', 'form', function (e) {
    const $form = $(this);
    // Ensure this is our email settings form
    const hasAction = $form.find('input[name="action"][value="gdhrdv_save_email_settings"]').length > 0;
    if (!hasAction) return;

    // Validate receiver selection (static/dynamic)
    (function () {
      const modeEl = document.querySelector('input[name="receiver_mode"]:checked');
      const mode = modeEl ? modeEl.value : '';
      let errors = [];
      let firstField = null;

      // No method selected -> block and notify
      if (!mode) {
        e.preventDefault();
        e.stopPropagation();
        const wrap = document.querySelector('.wrap');
        let notice = document.getElementById('gdhrdv-receiver-validate-error');
        if (!notice) {
          notice = document.createElement('div');
          notice.id = 'gdhrdv-receiver-validate-error';
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
        } else if (!notice.querySelector('.notice-dismiss')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'notice-dismiss';
          btn.setAttribute('aria-label', 'Fermer cette notification');
          btn.innerHTML = '<span class="screen-reader-text">Fermer cette notification.</span>';
          notice.appendChild(btn);
        }
        const p = notice.querySelector('p');
        if (p) {
          p.textContent = 'Veuillez sélectionner une méthode de destinataire (statique ou dynamique) avant d\'enregistrer.';
        }
        const dismissBtn = notice.querySelector('.notice-dismiss');
        if (dismissBtn && !dismissBtn._gdhrdvBound) {
          dismissBtn.addEventListener('click', function () {
            if (notice && notice.parentNode) { notice.parentNode.removeChild(notice); }
          });
          dismissBtn._gdhrdvBound = true;
        }
        if (notice._gdhrdvTimer) { clearTimeout(notice._gdhrdvTimer); }
        notice._gdhrdvTimer = setTimeout(function () {
          if (notice && notice.parentNode) { notice.parentNode.removeChild(notice); }
        }, 7000);
        try { notice.scrollIntoView({ behavior: 'smooth', block: 'start' }); notice.focus(); } catch (_) { }
        const firstRadio = document.querySelector('input[name="receiver_mode"]');
        if (firstRadio && typeof firstRadio.focus === 'function') { firstRadio.focus(); }
        return;
      }
      if (mode === 'static') {
        const emailEl = document.getElementById('receiver_static_email');
        const nameEl = document.getElementById('receiver_static_name');
        const email = emailEl ? (emailEl.value || '').trim() : '';
        const name = nameEl ? (nameEl.value || '').trim() : '';
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        if (!email) { errors.push('adresse e-mail du destinataire'); firstField = firstField || emailEl; }
        else if (!emailOk) { errors.push('adresse e-mail du destinataire (format invalide)'); firstField = firstField || emailEl; }
        if (!name) { errors.push('nom complet du destinataire'); firstField = firstField || nameEl; }
      } else if (mode === 'dynamic') {
        const ptEl = document.getElementById('receiver_dynamic_post_type');
        const emSel = document.getElementById('receiver_dynamic_email');
        const nmSel = document.getElementById('receiver_dynamic_name');
        const pt = ptEl ? (ptEl.value || '').trim() : '';
        const emKey = emSel ? (emSel.value || '').trim() : '';
        const nmKey = nmSel ? (nmSel.value || '').trim() : '';
        if (!pt) { errors.push('type de contenu'); firstField = firstField || ptEl; }
        if (!emKey) { errors.push('meta de l\'e-mail du destinataire'); firstField = firstField || emSel; }
        if (!nmKey) { errors.push('meta du nom complet'); firstField = firstField || nmSel; }
      }
      if (mode && errors.length) {
        e.preventDefault();
        e.stopPropagation();
        const wrap = document.querySelector('.wrap');
        let notice = document.getElementById('gdhrdv-receiver-validate-error');
        if (!notice) {
          notice = document.createElement('div');
          notice.id = 'gdhrdv-receiver-validate-error';
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
        } else if (!notice.querySelector('.notice-dismiss')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'notice-dismiss';
          btn.setAttribute('aria-label', 'Fermer cette notification');
          btn.innerHTML = '<span class="screen-reader-text">Fermer cette notification.</span>';
          notice.appendChild(btn);
        }
        const p = notice.querySelector('p');
        if (p) {
          if (mode === 'static') {
            p.textContent = 'Le destinataire statique est sélectionné : veuillez renseigner ' + errors.join(', ') + ' avant d\'enregistrer.';
          } else {
            p.textContent = 'Le destinataire dynamique est sélectionné : veuillez renseigner ' + errors.join(', ') + ' avant d\'enregistrer.';
          }
        }
        // Dismiss binding and auto-dismiss
        const dismissBtn = notice.querySelector('.notice-dismiss');
        if (dismissBtn && !dismissBtn._gdhrdvBound) {
          dismissBtn.addEventListener('click', function () {
            if (notice && notice.parentNode) { notice.parentNode.removeChild(notice); }
          });
          dismissBtn._gdhrdvBound = true;
        }
        if (notice._gdhrdvTimer) { clearTimeout(notice._gdhrdvTimer); }
        notice._gdhrdvTimer = setTimeout(function () {
          if (notice && notice.parentNode) { notice.parentNode.removeChild(notice); }
        }, 7000);
        try { notice.scrollIntoView({ behavior: 'smooth', block: 'start' }); notice.focus(); } catch (_) { }
        if (firstField && typeof firstField.focus === 'function') { firstField.focus(); }
      }
    })();

    const enabled = document.getElementById('gdhrdv_confirm_enabled');
    if (!enabled || !enabled.checked) return;

    const subjEl = document.getElementById('gdhrdv_confirm_subject');
    const subject = (subjEl && !subjEl.disabled ? (subjEl.value || '').trim() : '');

    // Try TinyMCE first, fallback to textarea
    let bodyText = '';
    try {
      if (typeof tinymce !== 'undefined') {
        const ed = tinymce.get('gdhrdv_confirm_body');
        if (ed && !ed.isHidden()) {
          const html = ed.getContent({ format: 'raw' }) || '';
          bodyText = html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').trim();
        }
      }
    } catch (err) { }

    if (!bodyText) {
      const ta = document.getElementById('gdhrdv_confirm_body');
      if (ta) bodyText = (ta.value || '').trim();
    }

    if (!subject || !bodyText) {
      e.preventDefault();
      e.stopPropagation();
      // Show an error notice
      const wrap = document.querySelector('.wrap');
      let notice = document.getElementById('gdhrdv-confirm-validate-error');
      if (!notice) {
        notice = document.createElement('div');
        notice.id = 'gdhrdv-confirm-validate-error';
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
      if (dismissBtn && !dismissBtn._gdhrdvBound) {
        dismissBtn.addEventListener('click', function () {
          if (notice && notice.parentNode) {
            notice.parentNode.removeChild(notice);
          }
        });
        dismissBtn._gdhrdvBound = true;
      }
      if (notice._gdhrdvTimer) { clearTimeout(notice._gdhrdvTimer); }
      notice._gdhrdvTimer = setTimeout(function () {
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
            const ed = tinymce.get('gdhrdv_confirm_body');
            if (ed && !ed.isHidden()) { ed.focus(); }
            else {
              const ta = document.getElementById('gdhrdv_confirm_body');
              if (ta) ta.focus();
            }
          }
        } catch (err) {
          const ta = document.getElementById('gdhrdv_confirm_body');
          if (ta) ta.focus();
        }
      }
    }
  });

  // Expose a read-only namespace for organization and debugging (no behavior change)
  try {
    window.GDHRDV = window.GDHRDV || {};
    window.GDHRDV.Admin = Object.freeze({
      log: gdhrdvLog,
      gdhrdvDefaultTemplates,
      gdhrdvSetEditorContent,
      gdhrdvGetEditorContent,
      gdhrdvIsEmptyHtml,
      gdhrdvResetMetaSelect,
      gdhrdvPopulateMetaSelect,
      gdhrdvFetchAndPopulateMeta,
    });
  } catch (_) { }
})(jQuery);
