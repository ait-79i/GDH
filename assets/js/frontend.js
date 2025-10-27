jQuery(document).ready(function ($) {
  const gdhrdvDebug = (window.gdhrdvData && !!gdhrdvData.debug) || false;
  const gdhrdvLog = {
    trace: (...a) => { if (gdhrdvDebug && console && console.trace) try { console.trace(...a); } catch (_) {} },
    info:  (...a) => { if (gdhrdvDebug && console && console.info)  try { console.info(...a); }  catch (_) {} },
    warn:  (...a) => { if (gdhrdvDebug && console && console.warn)  try { console.warn(...a); }  catch (_) {} },
    error: (...a) => { if (gdhrdvDebug && console && console.error) try { console.error(...a); } catch (_) {} },
  };
  try { window.GDHRDV = window.GDHRDV || {}; window.GDHRDV.log = gdhrdvLog; } catch (_) {}
  let currentStep = 1;
  const totalSteps = 3;
  let $activePopup = null;
  let activePopupId = null;

  // Ouvrir la popup
  $(document).on("click", "[data-gdhrdv-rdv-open]", function (e) {
    e.preventDefault();
    try {
      const $trigger = $(this);
      const targetSel = $trigger.attr('data-gdhrdv-target') || '';
      let $popup = targetSel ? $(targetSel) : $trigger.siblings('.gdhrdv-rdv-popup').first();
      if (!$popup.length) { $popup = $(".gdhrdv-rdv-popup").first(); }
      if ($popup.length && $popup.parent()[0] !== document.body) {
        $popup.appendTo(document.body);
      }
      if ($popup.length) {
        $activePopup = $popup;
        activePopupId = $popup.attr('id') || null;
        $popup.attr('aria-hidden', 'false');
        try { $popup.css('display', 'flex'); } catch (_) {}
        resetForm();
      } else {
        gdhrdvLog.warn && gdhrdvLog.warn('GDHRDV: aucun élément .gdhrdv-rdv-popup trouvé pour le déclencheur.');
      }
    } catch (_) {}
  });

  // Fermer la popup via le bouton de fermeture
  $(document).on("click", "[data-gdhrdv-rdv-close]", function (e) {
    e.preventDefault();
    const $p = $(this).closest('.gdhrdv-rdv-popup');
    $p.attr('aria-hidden', 'true');
    try { $p.css('display', 'none'); } catch (_) {}
    if ($activePopup && $activePopup[0] === $p[0]) { 
      $activePopup = null;
      activePopupId = null;
    }
  });

  // Fermer la popup en cliquant sur l'overlay
  $(document).on("click", ".gdhrdv-rdv-popup-overlay", function (e) {
    const $p = $(this).closest('.gdhrdv-rdv-popup');
    $p.attr('aria-hidden', 'true');
    try { $p.css('display', 'none'); } catch (_) {}
    if ($activePopup && $activePopup[0] === $p[0]) { 
      $activePopup = null;
      activePopupId = null;
    }
  });

  // Fermer en cliquant en dehors du contenu (zone grise si overlay non cliquée)
  $(document).on('mousedown', '.gdhrdv-rdv-popup', function (e) {
    const $content = $(this).find('.gdhrdv-rdv-popup-content');
    if (!$(e.target).closest('.gdhrdv-rdv-popup-content').length) {
      const $p = $(this);
      $p.attr('aria-hidden', 'true');
      try { $p.css('display', 'none'); } catch (_) {}
      if ($activePopup && $activePopup[0] === $p[0]) { 
        $activePopup = null;
        activePopupId = null;
      }
    }
  });

  // Empêche la propagation des clics à l'intérieur du contenu
  $(document).on('mousedown', '.gdhrdv-rdv-popup-content', function (e) {
    e.stopPropagation();
  });

  // Fermer avec la touche Echap
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      const $open = $('.gdhrdv-rdv-popup[aria-hidden="false"]').last();
      if ($open.length) {
        $open.attr('aria-hidden', 'true');
        try { $open.css('display', 'none'); } catch (_) {}
        if ($activePopup && $activePopup[0] === $open[0]) { 
          $activePopup = null;
          activePopupId = null;
        }
      }
    }
  });

  // Bouton suivant
  $(document).on("click", ".gdhrdv-rdv-next", function (e) {
    e.preventDefault();
    
    const $popup = $(this).closest('.gdhrdv-rdv-popup');
    if (validateCurrentStep($popup)) {
      if (currentStep < totalSteps) {
        currentStep++;
        updateStep($popup);
      }
    }
  });

  // Bouton précédent
  $(document).on("click", ".gdhrdv-rdv-prev", function (e) {
    e.preventDefault();

    const $popup = $(this).closest('.gdhrdv-rdv-popup');
    if (currentStep > 1) {
      currentStep--;
      updateStep($popup);
    }
  });

  // Bouton envoyer - valide et affiche toutes les erreurs
  $(document).on("click", ".gdhrdv-rdv-submit", function (e) {
    e.preventDefault();

    const $popup = $(this).closest('.gdhrdv-rdv-popup');
    // Nettoyer les erreurs précédentes
    $popup.find(".gdhrdv-rdv-error").remove();

    // Valider l'étape 3 et afficher toutes les erreurs de champs
    const isValid = validateAllFieldsInStep(3, $popup);

    if (isValid) {
      // Si la validation passe, soumettre le formulaire
      submitForm($popup);
    } else {
      // Faire défiler jusqu'à la première erreur
      scrollToFirstError();
    }
  });

  function validateCurrentStep($popup) {
    let isValid = true;

    $popup.find(".gdhrdv-rdv-error").remove();

    switch (currentStep) {
      case 1:
        isValid = validateStep1Slots($popup);
        break;
      case 2:
      case 3:
        isValid = validateAllFieldsInStep(currentStep, $popup);
        break;
    }

    return isValid;
  }

  function updateStep($popup) {
    $popup.find(".gdhrdv-rdv-step-content").removeClass("active");
    $popup.find(`.gdhrdv-rdv-step-content[data-step="${currentStep}"]`).addClass("active");

    // Met à jour les anciens indicateurs d'étapes
    $popup.find(".gdhrdv-rdv-step").removeClass("active completed");
    $popup.find(".gdhrdv-rdv-step").each(function (index) {
      if (index + 1 < currentStep) {
        $(this).addClass("completed");
      } else if (index + 1 === currentStep) {
        $(this).addClass("active");
      }
    });

    // Met à jour les éléments d'étapes modernes
    $popup.find(".gdhrdv-rdv-step-item").removeClass("active completed");
    $popup.find(".gdhrdv-rdv-step-item").each(function () {
      const stepNum = parseInt($(this).data("step"));
      if (stepNum < currentStep) {
        $(this).addClass("completed");
      } else if (stepNum === currentStep) {
        $(this).addClass("active");
      }
    });

    // Met à jour les connecteurs
    $popup.find(".gdhrdv-rdv-step-connector").removeClass("active");
    $popup.find(".gdhrdv-rdv-step-connector").each(function () {
      const connector = $(this).data("connector");
      if (connector) {
        const [from, to] = connector.split("-").map(Number);
        if (currentStep > from) {
          $(this).addClass("active");
        }
      }
    });

    updateButtons($popup);
  }

  function updateButtons($popup) {
    const $prevBtn = $popup.find(".gdhrdv-rdv-prev");
    const $nextBtn = $popup.find(".gdhrdv-rdv-next");
    const $submitBtn = $popup.find(".gdhrdv-rdv-submit");

    // Bouton précédent
    if (currentStep === 1) {
      $prevBtn.hide();
    } else {
      $prevBtn.show();
    }

    // Boutons Suivant/Envoyer
    if (currentStep === totalSteps) {
      $nextBtn.hide();
      $submitBtn.show();
    } else {
      $nextBtn.show();
      $submitBtn.hide();
    }
  }

  function showError(message, $context) {
    const $scope = $context && $context.length ? $context : (activePopupId ? $('#' + activePopupId) : ($activePopup || $(document)));
    const $currentStepContent = $scope.find(
      `.gdhrdv-rdv-step-content[data-step="${currentStep}"]`
    );
    const $errorDiv = $(
      '<div class="gdhrdv-rdv-error" style="color: #e74c3c; margin-top: 10px; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"></div>'
    );
    $errorDiv.text(message);
    $currentStepContent.append($errorDiv);
  }

  // Afficher le message de succès
  function showSuccess() {
    const $root = activePopupId ? $('#' + activePopupId) : ($activePopup || $(document));
    $root.find('.gdhrdv-rdv-form').hide();
    $root.find('.gdhrdv-rdv-success').show();

    // Auto-close popup après 3 secondes
    setTimeout(() => {
      const $popup = activePopupId ? $('#' + activePopupId) : ($activePopup || $('.gdhrdv-rdv-popup[aria-hidden="false"]').last());
      if ($popup && $popup.length) {
        $popup.attr('aria-hidden', 'true');
        try { $popup.css('display', 'none'); } catch (_) {}
        if ($activePopup && $activePopup[0] === $popup[0]) { 
          $activePopup = null;
          activePopupId = null;
        }
      }
    }, 3000);
  }

  function resetForm() {
    currentStep = 1;
    const $root = activePopupId ? $('#' + activePopupId) : ($activePopup || $(document));
    const $form = $root.find('.gdhrdv-rdv-form').first();
    if ($form.length && $form[0] && typeof $form[0].reset === 'function') {
      $form[0].reset();
      $form.show();
    }
    $root.find('.gdhrdv-rdv-success').hide();
    $root.find('.gdhrdv-rdv-error').remove();
    resetStep1Slots();
    const $popup = activePopupId ? $('#' + activePopupId) : ($activePopup || $('.gdhrdv-rdv-popup[aria-hidden="false"]').last());
    if ($popup && $popup.length) {
      updateStep($popup);
    }
    initStep1Slots();
  }

  // Initial update only if a popup is already open (rare); otherwise deferred until open
  const $_initialOpen = $('.gdhrdv-rdv-popup[aria-hidden="false"]').last();
  if ($_initialOpen.length) updateStep($_initialOpen);

  // ===== Validation en temps réel pour les étapes 2 & 3 =====
  function initFieldValidation() {
    // Valider au blur (quand l'utilisateur quitte le champ)
    $(document).on('blur', '.gdhrdv-rdv-field input', function () {
      validateField($(this));
    });

    // Valider au blur pour le textarea
    $(document).on('blur', '.gdhrdv-rdv-field textarea', function () {
      validateTextarea($(this));
    });

    // Valider à la saisie (temps réel)
    $(document).on('input', '.gdhrdv-rdv-field input', function () {
      const $input = $(this);
      // Valider uniquement si le champ a déjà été touché (erreur ou valide)
      if ($input.hasClass('gdhrdv-rdv-field-error') || $input.closest('.gdhrdv-rdv-field').hasClass('has-error')) {
        validateField($input);
      }
    });

    // Gérer le compteur de caractères pour le textarea message
    $(document).on('input', 'textarea[name="message"]', function () {
      const $textarea = $(this);
      const $counter = $textarea.siblings('.gdhrdv-rdv-field-counter').find('.gdhrdv-rdv-counter-text');
      const maxLength = parseInt($textarea.attr('maxlength') || '500', 10);
      const currentLength = $textarea.val().length;
      
      $counter.text(`${currentLength}/${maxLength} caractères`);
      
      // Validation optionnelle si le champ a déjà été touché
      if ($textarea.hasClass('gdhrdv-rdv-field-error') || $textarea.closest('.gdhrdv-rdv-field').hasClass('has-error')) {
        validateTextarea($textarea);
      }
    });

    // Valider la case à cocher
    $(document).on('change', '.gdhrdv-rdv-checkbox-modern input[type="checkbox"]', function () {
      validateCheckbox($(this));
    });
  }

  function validateField($input) {
    const $field = $input.closest('.gdhrdv-rdv-field');
    const $errorMsg = $field.find('.gdhrdv-rdv-error-message');
    const value = $input.val().trim();
    const errorMessage = $input.data('error-message') || 'Ce champ est requis';

    // Remove previous error state
    $input.removeClass('gdhrdv-rdv-field-error');
    $field.removeClass('has-error');
    $errorMsg.hide().text('');

    let isValid = true;
    let customError = '';

    // Check if field is required and empty
    if ($input.prop('required') && !value) {
      isValid = false;
      customError = errorMessage;
    }
    // Check pattern validation
    else if (value && $input.attr('pattern')) {
      const pattern = new RegExp($input.attr('pattern'));
      if (!pattern.test(value)) {
        isValid = false;
        customError = errorMessage;
      }
    }
    // Check minlength
    else if (value && $input.attr('minlength')) {
      const minLength = parseInt($input.attr('minlength'));
      if (value.length < minLength) {
        isValid = false;
        customError = `Ce champ doit contenir au moins ${minLength} caractères`;
      }
    }
    // Check email format
    else if ($input.attr('type') === 'email' && value) {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(value)) {
        isValid = false;
        customError = errorMessage;
      }
    }
    // Check tel format
    else if ($input.attr('type') === 'tel' && value) {
      const telPattern = /^(?:(?:\+|00)33[\s.-]{0,3}(?:\(0\)[\s.-]{0,3})?|0)[1-9](?:(?:[\s.-]?\d{2}){4}|\d{2}(?:[\s.-]?\d{3}){2})$/;
      if (!telPattern.test(value)) {
        isValid = false;
        customError = errorMessage;
      }
    }

    if (!isValid) {
      $input.addClass('gdhrdv-rdv-field-error');
      $field.addClass('has-error');
      $errorMsg.text(customError).show();
    }

    return isValid;
  }

  function validateCheckbox($checkbox) {
    const $label = $checkbox.closest('.gdhrdv-rdv-checkbox-modern');
    const $field = $checkbox.closest('.gdhrdv-rdv-field-checkbox');
    const $errorMsg = $field.find('.gdhrdv-rdv-error-message');
    const errorMessage = $checkbox.data('error-message') || 'Vous devez accepter les conditions';

    // Remove previous error state
    $label.removeClass('has-error');
    $errorMsg.hide().text('');

    if ($checkbox.prop('required') && !$checkbox.is(':checked')) {
      $label.addClass('has-error');
      $errorMsg.text(errorMessage).show();
      return false;
    }

    return true;
  }

  function validateTextarea($textarea) {
    const $field = $textarea.closest('.gdhrdv-rdv-field');
    const $errorMsg = $field.find('.gdhrdv-rdv-error-message');
    const value = $textarea.val().trim();
    const maxLength = parseInt($textarea.attr('maxlength') || '500', 10);
    const errorMessage = $textarea.data('error-message') || 'Ce champ contient trop de caractères';

    // Remove previous error state
    $textarea.removeClass('gdhrdv-rdv-field-error');
    $field.removeClass('has-error');
    $errorMsg.hide().text('');

    let isValid = true;
    let customError = '';

    // Check if field is required and empty
    if ($textarea.prop('required') && !value) {
      isValid = false;
      customError = $textarea.data('error-message') || 'Ce champ est requis';
    }
    // Check maxlength
    else if (value && value.length > maxLength) {
      isValid = false;
      customError = `Le message ne peut pas dépasser ${maxLength} caractères`;
    }

    if (!isValid) {
      $textarea.addClass('gdhrdv-rdv-field-error');
      $field.addClass('has-error');
      $errorMsg.text(customError).show();
    }

    return isValid;
  }

  // Valider tous les champs de l'étape courante
  function validateAllFieldsInStep(stepNumber, $popup) {
    const $step = $popup.find(`.gdhrdv-rdv-step-content[data-step="${stepNumber}"]`);
    let isValid = true;

    // Valider tous les inputs
    $step.find('.gdhrdv-rdv-field input').each(function () {
      if (!validateField($(this))) {
        isValid = false;
      }
    });

    // Valider les textareas
    $step.find('.gdhrdv-rdv-field textarea').each(function () {
      if (!validateTextarea($(this))) {
        isValid = false;
      }
    });

    // Valider les cases à cocher
    const $checkboxes = $step.find('.gdhrdv-rdv-checkbox-modern input[type="checkbox"]');
    if ($checkboxes.length) {
      $checkboxes.each(function () {
        if (!validateCheckbox($(this))) {
          isValid = false;
        }
      });
    }

    return isValid;
  }

  // Faire défiler jusqu'à la première erreur de l'étape courante
  function scrollToFirstError($context) {
    const $scope = $context && $context.length ? $context : (activePopupId ? $('#' + activePopupId) : ($activePopup || $(document)));
    const $step = $scope.find(`.gdhrdv-rdv-step-content[data-step="${currentStep}"]`);
    const $firstError = $step.find('.gdhrdv-rdv-field-error, .gdhrdv-rdv-checkbox-modern.has-error').first();

    if ($firstError.length) {
      // Faire défiler jusqu'au champ en erreur
      $firstError[0].scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });

      // Donner le focus au champ après le défilement
      setTimeout(() => {
        if ($firstError.is('input')) {
          $firstError.focus();
        } else {
          $firstError.find('input').first().focus();
        }
      }, 500);
    }
  }

  // Soumission du formulaire
  function submitForm($popup) {
    // Use the active popup or fallback to activePopupId
    if (!$popup || !$popup.length) {
      $popup = activePopupId ? $('#' + activePopupId) : ($activePopup || $('.gdhrdv-rdv-popup[aria-hidden="false"]').last());
    }
    const $form = $popup.find('.gdhrdv-rdv-form').first();

    // Récupère le type de contenu et l'ID courants pour le destinataire dynamique
    const currentPostType = $popup.data('post-type') || '';
    const currentPostId = $popup.data('post-id') || 0;


    // Debug: Log popup info
    gdhrdvLog.info && gdhrdvLog.info('GDHRDV Submit: Popup ID =', $popup.attr('id'), 'Post Type =', currentPostType, 'Post ID =', currentPostId);
    gdhrdvLog.info && gdhrdvLog.info('GDHRDV Submit: Form fields found =', $popup.find('input').length);
    
    // Collecte des données du formulaire
    const formData = {
      // Étape 1 - Créneaux
      slots: [],
      // Étape 2 - Adresse
      address: $popup.find('input[name="address"]').val(),
      postal_code: $popup.find('input[name="postal_code"]').val(),
      city: $popup.find('input[name="city"]').val(),
      // Étape 3 - Informations personnelles
      first_name: $popup.find('input[name="first_name"]').val(),
      last_name: $popup.find('input[name="last_name"]').val(),
      email: $popup.find('input[name="email"]').val(),
      phone: $popup.find('input[name="phone"]').val(),
      message: $popup.find('textarea[name="message"]').val(),
      accept_terms: $popup.find('input[name="accept_terms"]').is(':checked'),
      // Informations du destinataire (à partir des champs cachés)
      recipient_email: $popup.find('input[name="recipient_email"]').val(),
      recipient_name: $popup.find('input[name="recipient_name"]').val(),
      // Contexte de publication auto-détecté pour le destinataire dynamique
      current_post_type: currentPostType,
      current_post_id: currentPostId
    };
    
    // Debug: Log collected data
    gdhrdvLog.info && gdhrdvLog.info('GDHRDV Submit: Collected form data =', formData);


    // Collecte des créneaux
    $popup.find('.gdhrdv-rdv-slot-card').each(function () {
      const $card = $(this);
      const date = $card.find('.gdhrdv-rdv-date').val();
      const times = [];

      $card.find('.gdhrdv-rdv-time.selected').each(function () {
        times.push($(this).data('value'));
      });

      const allDay = $card.find('.gdhrdv-rdv-all-day').hasClass('active');

      if (date && (times.length > 0 || allDay)) {
        formData.slots.push({
          date: date,
          times: allDay ? ['all-day'] : times
        });
      }
    });

    // Affiche l'état de chargement
    const $submitBtn = $popup.find('.gdhrdv-rdv-submit');
    const originalText = $submitBtn.data('orig-text') || ($submitBtn.first().text() || '').trim();
    $submitBtn.data('orig-text', originalText);
    $submitBtn.prop('disabled', true).text('Envoi en cours...');

    // Soumission AJAX
    $.ajax({
      url: gdhrdvData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'gdhrdv_submit_appointment',
        nonce: gdhrdvData.nonce,
        formData: JSON.stringify(formData)
      },
      success: function (response) {
        $submitBtn.prop('disabled', false).text(originalText);
        if (response.success) {
          showSuccess();
        } else {
          showError(response.data.message || 'Une erreur est survenue');
        }
      },
      error: function (xhr, status, error) {
        gdhrdvLog.error('Erreur AJAX :', status, error);
        gdhrdvLog.error('Réponse :', xhr.responseText);
        $submitBtn.prop('disabled', false).text(originalText);
        showError('Erreur de connexion. Veuillez réessayer.');
      }
    });
  }

  // Initialiser la validation
  initFieldValidation();

  function initStep1Slots() {
    const $root = activePopupId ? $('#' + activePopupId) : ($activePopup || $(document));
    const $c = $root.find('.gdhrdv-rdv-step-content[data-step="1"]');
    if (!$c.length) return;

    // Évite une double initialisation (listeners dupliqués)
    if ($c.data('slots-initialized')) return;

    const $slotsWrapper = $c.find('.gdhrdv-rdv-slots');
    const $slotsContainer = $c.find('.gdhrdv-rdv-slots-container');
    const max = parseInt($slotsWrapper.attr('data-max') || '3', 10);
    const min = parseInt($slotsWrapper.attr('data-min') || '1', 10);
    const $addBtn = $c.find('.gdhrdv-rdv-add-slot');
    const todayStr = new Date().toISOString().slice(0, 10);

    const timeSlots = [
      { value: '08:00-10:00', label: '8h - 10h' },
      { value: '10:00-12:00', label: '10h - 12h' },
      { value: '12:00-14:00', label: '12h - 14h' },
      { value: '14:00-16:00', label: '14h - 16h' },
      { value: '16:00-18:00', label: '16h - 18h' },
      { value: '18:00-20:00', label: '18h - 20h' }
    ];

    function createSlotCard(index) {
      const isRequired = index === 1;
      const showRemove = index > min;

      // Titres professionnels pour les créneaux
      const titles = {
        1: 'Disponibilité principale',
        2: 'Disponibilité alternative',
        3: 'Disponibilité de secours'
      };

      const cardTitle = titles[index] || `Disponibilité ${index}`;

      // Essayer de rendre le modèle Twig côté client en premier
      const context = { index, isRequired, showRemove, todayStr, timeSlots, cardTitle };
      let html = '';
      try {
        if (window.Twig && typeof window.Twig.twig === 'function' &&
          window.gdhrdvData && window.gdhrdvData.templates && window.gdhrdvData.templates.slotCard) {
          html = window.Twig.twig({ data: window.gdhrdvData.templates.slotCard }).render(context);
        }
      } catch (e) {
        gdhrdvLog.error('Erreur de rendu Twig (slotCard) :', e);
      }

      const $card = html && typeof html === 'string' && html.trim().length
        ? $(html)
        : null;

      attachCardEvents($card);
      return $card;
    }

    function attachCardEvents($card) {
      const $date = $card.find('.gdhrdv-rdv-date');
      const $dateIcon = $card.find('.gdhrdv-rdv-date-icon');
      const $allDay = $card.find('[data-all-day]');
      const $timeBtns = $card.find('.gdhrdv-rdv-time');
      const $removeBtn = $card.find('.gdhrdv-rdv-remove-slot');

      // Icône du calendrier — déclenche l'ouverture du sélecteur de date
      $dateIcon.on('click', function () {
        $date.focus();
        if ($date[0] && typeof $date[0].showPicker === 'function') {
          $date[0].showPicker();
        }
      });

      // Clic/clavier sur l'input date — ouvre le sélecteur natif si disponible
      $date.on('click', function () {
        if (this && typeof this.showPicker === 'function') {
          this.showPicker();
        }
      });
      $date.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (this && typeof this.showPicker === 'function') {
            this.showPicker();
          }
        }
      });

      // Changement de date
      $date.on('change', function () {
        updateCombinedValue($card);
        // Retire l'erreur de date lorsqu'une date est sélectionnée
        if ($date.val()) {
          $date.removeClass('gdhrdv-rdv-field-error');
          // Check if we should clear the whole card error
          const hasTimeSelected = $card.find('.gdhrdv-rdv-time.selected').length > 0 ||
            $card.find('[data-all-day]').hasClass('active');
          if (hasTimeSelected) {
            clearCardError($card);
          }
        }
      });

      // Bouton "Toute la journée"
      $allDay.on('click', function () {
        $allDay.toggleClass('active');
        $allDay.attr('aria-pressed', $allDay.hasClass('active') ? 'true' : 'false');

        if ($allDay.hasClass('active')) {
          $timeBtns.removeClass('selected').attr('aria-pressed', 'false').prop('disabled', true);
        } else {
          $timeBtns.prop('disabled', false);
        }
        updateCombinedValue($card);
        // Retire l'erreur lorsqu'un horaire est sélectionné
        clearCardError($card);
      });

      // Boutons d'horaires
      $timeBtns.on('click', function () {
        const $btn = $(this);
        if ($btn.is(':disabled')) return;

        // Si "Toute la journée" est actif, le désactiver
        if ($allDay.hasClass('active')) {
          $allDay.removeClass('active').attr('aria-pressed', 'false');
          $timeBtns.prop('disabled', false);
        }

        $btn.toggleClass('selected');
        $btn.attr('aria-pressed', $btn.hasClass('selected') ? 'true' : 'false');

        // Vérifier si les 6 créneaux sont sélectionnés
        const selectedCount = $card.find('.gdhrdv-rdv-time.selected').length;
        const totalSlots = $timeBtns.length;

        if (selectedCount === totalSlots && totalSlots === 6) {
          // Désélectionner tous les créneaux
          $timeBtns.removeClass('selected').attr('aria-pressed', 'false');
          // Activer "Toute la journée"
          $allDay.addClass('active').attr('aria-pressed', 'true');
          $timeBtns.prop('disabled', true);
        }

        updateCombinedValue($card);
        // Remove error when time is selected
        clearCardError($card);
      });

      // Bouton de suppression de créneau
      if ($removeBtn.length) {
        $removeBtn.on('click', function () {
          removeSlotCard($card);
        });
      }

      updateCombinedValue($card);
    }

    function updateCombinedValue($card) {
      const date = $card.find('.gdhrdv-rdv-date').val();
      const $combined = $card.find('.gdhrdv-rdv-combined');
      const $allDay = $card.find('[data-all-day]');

      if (!date) {
        $combined.val('');
        $card.removeClass('has-selection');
        return;
      }

      let value = date;

      if ($allDay.hasClass('active')) {
        value += ' | Toute la journée';
        $card.addClass('has-selection has-all-day');
      } else {
        const selectedTimes = $card.find('.gdhrdv-rdv-time.selected').map(function () {
          return $(this).data('value');
        }).get();

        if (selectedTimes.length > 0) {
          value += ' | ' + selectedTimes.join(',');
          $card.addClass('has-selection');
          $card.removeClass('has-all-day');
        } else {
          $card.removeClass('has-selection has-all-day');
        }
      }

      $combined.val(value);
    }

    function clearCardError($card) {
      // Remove error classes from the card elements
      $card.find('.gdhrdv-rdv-date, .gdhrdv-rdv-times-grid').removeClass('gdhrdv-rdv-field-error');

      // Remove error message from the card
      $card.find('.gdhrdv-rdv-slot-error').remove();

      // Check if this was the last error in step 1
      const $step1 = $root.find('.gdhrdv-rdv-step-content[data-step="1"]');
      const remainingErrors = $step1.find('.gdhrdv-rdv-field-error, .gdhrdv-rdv-slot-error').length;

      // If no more errors, remove the general error message
      if (remainingErrors === 0) {
        $step1.find('.gdhrdv-rdv-error').remove();
      }
    }

    function addSlotCard() {
      const currentCount = $slotsWrapper.find('.gdhrdv-rdv-slot-card').length;
      if (currentCount >= max) return;

      const newIndex = currentCount + 1;
      const $newCard = createSlotCard(newIndex);
      $slotsWrapper.append($newCard);
      updateAddButtonState();

      // Faire défiler pour afficher la carte ajoutée
      setTimeout(() => {
        // Récupère la position réelle de la carte après rendu
        const newCardOffset = $newCard[0].offsetLeft;
        const cardWidth = $newCard.outerWidth(true);
        const containerWidth = $slotsContainer.width();
        const scrollWidth = $slotsContainer[0].scrollWidth;

        let targetScroll;

        // Toujours faire défiler pour bien afficher la nouvelle carte
        if (newIndex === max) {
          // Pour la dernière carte, défiler jusqu'à la fin
          targetScroll = scrollWidth - containerWidth;
        } else {
          // Pour les autres, les centrer dans la zone visible
          targetScroll = newCardOffset - (containerWidth / 2) + (cardWidth / 2);
        }

        // Empêche de défiler au-delà des limites
        targetScroll = Math.max(0, Math.min(targetScroll, scrollWidth - containerWidth));

        $slotsContainer.animate({
          scrollLeft: targetScroll
        }, 600, 'swing');

        // Ajoute un léger effet de surbrillance sur la nouvelle carte
        $newCard.css('opacity', '0.5').animate({ opacity: '1' }, 500);

      }, 150);
    }

    function removeSlotCard($card) {
      const currentCount = $slotsWrapper.find('.gdhrdv-rdv-slot-card').length;
      if (currentCount <= min) return;

      $card.fadeOut(200, function () {
        $card.remove();
        reindexCards();
        updateAddButtonState();
      });
    }

    function reindexCards() {
      // Titres professionnels pour les créneaux
      const titles = {
        1: 'Disponibilité principale',
        2: 'Disponibilité alternative',
        3: 'Disponibilité de secours'
      };

      $slotsWrapper.find('.gdhrdv-rdv-slot-card').each(function (index) {
        const newIndex = index + 1;
        const $card = $(this);
        const cardTitle = titles[newIndex] || `Disponibilité ${newIndex}`;

        $card.attr('data-index', newIndex);
        $card.find('.gdhrdv-rdv-slot-title').text(cardTitle);
        $card.find('.gdhrdv-rdv-date').attr('name', `slot_${newIndex}_date`);
        $card.find('.gdhrdv-rdv-combined').attr('name', `slot_${newIndex}`);

        // Update required attributes for first card
        if (newIndex === 1) {
          $card.find('.gdhrdv-rdv-date').attr('required', 'required');
          $card.find('.gdhrdv-rdv-combined').attr('required', 'required');
          $card.find('.gdhrdv-rdv-remove-slot').remove();
        } else {
          $card.find('.gdhrdv-rdv-date').removeAttr('required');
          $card.find('.gdhrdv-rdv-combined').removeAttr('required');
        }
      });
    }

    function updateAddButtonState() {
      const currentCount = $slotsWrapper.find('.gdhrdv-rdv-slot-card').length;
      $addBtn.prop('disabled', currentCount >= max);
    }

    function initializeSlots() {
      $slotsWrapper.empty();
      for (let i = 1; i <= min; i++) {
        const $card = createSlotCard(i);
        $slotsWrapper.append($card);
      }
      updateAddButtonState();
    }

    // Retire les écouteurs existants pour éviter les doublons
    $addBtn.off('click.slots');

    // Ajoute l'événement du bouton avec un namespace
    $addBtn.on('click.slots', addSlotCard);

    // Initialise avec le nombre minimum de créneaux
    initializeSlots();

    // Marque comme initialisé
    $c.data('slots-initialized', true);
  }

  function resetStep1Slots() {
    const $root = activePopupId ? $('#' + activePopupId) : ($activePopup || $(document));
    const $c = $root.find('.gdhrdv-rdv-step-content[data-step="1"]');
    if (!$c.length) return;

    // Réinitialise le flag d'initialisation et relance l'init
    $c.removeData('slots-initialized');
    const $addBtn = $c.find('.gdhrdv-rdv-add-slot');
    $addBtn.off('click.slots');

    initStep1Slots();
  }

  function validateStep1Slots($context) {
    const $scope = $context && $context.length ? $context : (activePopupId ? $('#' + activePopupId) : ($activePopup || $(document)));
    const $step1 = $scope.find('.gdhrdv-rdv-step-content[data-step="1"]');
    const $cards = $step1.find('.gdhrdv-rdv-slot-card');
    const $slotsContainer = $step1.find('.gdhrdv-rdv-slots-container');
    let isValid = true;
    let firstErrorCard = null;

    // Retirer les surbrillances d'erreur précédentes
    $cards.find('.gdhrdv-rdv-date, .gdhrdv-rdv-times-grid').removeClass('gdhrdv-rdv-field-error');
    $('.gdhrdv-rdv-slot-error').remove();

    $cards.each(function (index) {
      const $card = $(this);
      const cardIndex = index + 1;
      const isRequired = cardIndex === 1;
      const $date = $card.find('.gdhrdv-rdv-date');
      const dateValue = $date.val();
      const $allDay = $card.find('[data-all-day]');
      const $selectedTimes = $card.find('.gdhrdv-rdv-time.selected');
      const hasAllDay = $allDay.hasClass('active');
      const hasTimeSelected = $selectedTimes.length > 0;

      // Vérifie si la carte a des données
      const hasData = dateValue || hasAllDay || hasTimeSelected;

      // Validation pour la première carte (obligatoire)
      if (isRequired) {
        if (!dateValue) {
          isValid = false;
          $date.addClass('gdhrdv-rdv-field-error');
          $card.append('<div class="gdhrdv-rdv-slot-error">Veuillez sélectionner une date</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }

        if (dateValue && !hasAllDay && !hasTimeSelected) {
          isValid = false;
          $card.find('.gdhrdv-rdv-times-grid').addClass('gdhrdv-rdv-field-error');
          $card.append('<div class="gdhrdv-rdv-slot-error">Veuillez sélectionner au moins un horaire ou "Toute la journée"</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }
      }

      // Validation pour les cartes facultatives (si données partielles)
      if (!isRequired && hasData) {
        if (!dateValue) {
          isValid = false;
          $date.addClass('gdhrdv-rdv-field-error');
          $card.append('<div class="gdhrdv-rdv-slot-error">Veuillez sélectionner une date pour cette disponibilité</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }

        if (dateValue && !hasAllDay && !hasTimeSelected) {
          isValid = false;
          $card.find('.gdhrdv-rdv-times-grid').addClass('gdhrdv-rdv-field-error');
          $card.append('<div class="gdhrdv-rdv-slot-error">Veuillez sélectionner au moins un horaire ou "Toute la journée"</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }
      }
    });

    // Défile jusqu'à la première erreur si la validation échoue
    if (!isValid && firstErrorCard) {
      const cardPosition = firstErrorCard.position().left;
      const containerWidth = $slotsContainer.width();
      const cardWidth = firstErrorCard.outerWidth(true);
      const scrollPosition = $slotsContainer.scrollLeft() + cardPosition - (containerWidth - cardWidth) / 2;

      $slotsContainer.animate({
        scrollLeft: Math.max(0, scrollPosition)
      }, 400);

      // Donne le focus au premier champ en erreur
      setTimeout(() => {
        const $errorField = firstErrorCard.find('.gdhrdv-rdv-field-error').first();
        if ($errorField.length) {
          $errorField.focus();
        }
      }, 450);
    }

    return isValid;
  }

  initStep1Slots();

  // Expose a read-only namespace for organization and debugging (no behavior change)
  try {
    window.GDHRDV = window.GDHRDV || {};
    window.GDHRDV.Frontend = Object.freeze({
      get state() { return { currentStep, totalSteps }; },
      validateCurrentStep,
      updateStep,
      showError,
      showSuccess,
      resetForm,
      initFieldValidation,
      validateField,
      validateCheckbox,
      validateTextarea,
      validateAllFieldsInStep,
      scrollToFirstError,
      submitForm,
      initStep1Slots,
      resetStep1Slots,
      validateStep1Slots,
      log: gdhrdvLog,
    });
  } catch (_) {}
});
