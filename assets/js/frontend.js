jQuery(document).ready(function ($) {
  let currentStep = 1;
  const totalSteps = 3;

  // Open modal
  $(document).on("click", "[data-gdh-rdv-open]", function (e) {
    e.preventDefault();
    $("#gdh-rdv-popup").show();
    resetForm();
  });

  // Close modal when clicking the close button
  $(document).on("click", "[data-gdh-rdv-close]", function (e) {
    e.preventDefault();
    $("#gdh-rdv-popup").hide();
  });

  // Close modal when clicking the overlay
  $(document).on("click", ".gdh-rdv-popup-overlay", function (e) {
    $("#gdh-rdv-popup").hide();
  });

  //next button
  $(document).on("click", ".gdh-rdv-next", function (e) {
    e.preventDefault();

    if (validateCurrentStep()) {
      if (currentStep < totalSteps) {
        currentStep++;
        updateStep();
      }
    }
  });

  // previous button
  $(document).on("click", ".gdh-rdv-prev", function (e) {
    e.preventDefault();

    if (currentStep > 1) {
      currentStep--;
      updateStep();
    }
  });

  // submit button - validate and show all errors
  $(document).on("click", ".gdh-rdv-submit", function (e) {
    e.preventDefault();

    // Clear previous errors
    $(".gdh-rdv-error").remove();

    // Validate step 3 and show all field errors
    const isValid = validateAllFieldsInStep(3);

    if (isValid) {
      // If validation passes, submit the form
      submitForm();
    } else {
      // Scroll to first error
      scrollToFirstError();
    }
  });

  function validateCurrentStep() {
    $currentStepContent = $(`.gdh-rdv-step-content[data-step="${currentStep}"`);
    let isValid = true;

    $(".gdh-rdv-error").remove();

    switch (currentStep) {
      case 1:
        isValid = validateStep1Slots();
        break;
      case 2:
      case 3:
        // Use the new field-by-field validation
        isValid = validateAllFieldsInStep(currentStep);
        break;
    }

    return isValid;
  }

  function updateStep() {
    $(".gdh-rdv-step-content").removeClass("active");

    $(`.gdh-rdv-step-content[data-step="${currentStep}"`).addClass("active");

    // Update legacy steps
    $(".gdh-rdv-step").removeClass("active completed");
    $(".gdh-rdv-step").each(function (index) {
      if (index + 1 < currentStep) {
        $(this).addClass("completed");
      } else if (index + 1 === currentStep) {
        $(this).addClass("active");
      }
    });

    // Update modern step items
    $(".gdh-rdv-step-item").removeClass("active completed");
    $(".gdh-rdv-step-item").each(function () {
      const stepNum = parseInt($(this).data("step"));
      if (stepNum < currentStep) {
        $(this).addClass("completed");
      } else if (stepNum === currentStep) {
        $(this).addClass("active");
      }
    });

    // Update connectors
    $(".gdh-rdv-step-connector").removeClass("active");
    $(".gdh-rdv-step-connector").each(function () {
      const connector = $(this).data("connector");
      if (connector) {
        const [from, to] = connector.split("-").map(Number);
        if (currentStep > from) {
          $(this).addClass("active");
        }
      }
    });

    updateButtons();
  }

  function updateButtons() {
    const $prevBtn = $(".gdh-rdv-prev");
    const $nextBtn = $(".gdh-rdv-next");
    const $submitBtn = $(".gdh-rdv-submit");

    // Previous button
    if (currentStep === 1) {
      $prevBtn.hide();
    } else {
      $prevBtn.show();
    }

    // Next/Submit buttons
    if (currentStep === totalSteps) {
      $nextBtn.hide();
      $submitBtn.show();
    } else {
      $nextBtn.show();
      $submitBtn.hide();
    }
  }

  function showError(message) {
    const $currentStepContent = $(
      `.gdh-rdv-step-content[data-step="${currentStep}"]`
    );
    const $errorDiv = $(
      '<div class="gdh-rdv-error" style="color: #e74c3c; margin-top: 10px; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"></div>'
    );
    $errorDiv.text(message);
    $currentStepContent.append($errorDiv);
  }

  // Show success message
  function showSuccess() {
    $("#gdh-rdv-form").hide();
    $("#gdh-rdv-success").show();

    // Auto-close popup after 3 seconds
    setTimeout(() => {
      $("#gdh-rdv-popup").hide();
    }, 3000);
  }

  function resetForm() {
    currentStep = 1;
    $("#gdh-rdv-form")[0].reset();
    $("#gdh-rdv-form").show();
    $("#gdh-rdv-success").hide();
    $(".gdh-rdv-error").remove();
    resetStep1Slots();
    updateStep();
    initStep1Slots();
  }

  updateStep();

  // ===== Real-time Validation for Steps 2 & 3 =====
  function initFieldValidation() {
    // Validate on blur (when user leaves the field)
    $(document).on('blur', '.gdh-rdv-field input', function () {
      validateField($(this));
    });

    // Validate on input (real-time)
    $(document).on('input', '.gdh-rdv-field input', function () {
      const $input = $(this);
      // Only validate if field has been touched (has error or was valid)
      if ($input.hasClass('gdh-rdv-field-error') || $input.closest('.gdh-rdv-field').hasClass('has-error')) {
        validateField($input);
      }
    });

    // Validate checkbox
    $(document).on('change', '.gdh-rdv-checkbox-modern input[type="checkbox"]', function () {
      validateCheckbox($(this));
    });
  }

  function validateField($input) {
    const $field = $input.closest('.gdh-rdv-field');
    const $errorMsg = $field.find('.gdh-rdv-error-message');
    const value = $input.val().trim();
    const errorMessage = $input.data('error-message') || 'Ce champ est requis';

    // Remove previous error state
    $input.removeClass('gdh-rdv-field-error');
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
      $input.addClass('gdh-rdv-field-error');
      $field.addClass('has-error');
      $errorMsg.text(customError).show();
    }

    return isValid;
  }

  function validateCheckbox($checkbox) {
    const $label = $checkbox.closest('.gdh-rdv-checkbox-modern');
    const $field = $checkbox.closest('.gdh-rdv-field-checkbox');
    const $errorMsg = $field.find('.gdh-rdv-error-message');
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

  // Validate all fields in current step
  function validateAllFieldsInStep(stepNumber) {
    const $step = $(`.gdh-rdv-step-content[data-step="${stepNumber}"]`);
    let isValid = true;

    // Validate all inputs
    $step.find('.gdh-rdv-field input').each(function () {
      if (!validateField($(this))) {
        isValid = false;
      }
    });

    // Validate checkboxes
    const $checkboxes = $step.find('.gdh-rdv-checkbox-modern input[type="checkbox"]');
    if ($checkboxes.length) {
      $checkboxes.each(function () {
        if (!validateCheckbox($(this))) {
          isValid = false;
        }
      });
    }

    return isValid;
  }

  // Scroll to first error in current step
  function scrollToFirstError() {
    const $step = $(`.gdh-rdv-step-content[data-step="${currentStep}"]`);
    const $firstError = $step.find('.gdh-rdv-field-error, .gdh-rdv-checkbox-modern.has-error').first();

    if ($firstError.length) {
      // Scroll to the error field
      $firstError[0].scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });

      // Focus on the field after scroll
      setTimeout(() => {
        if ($firstError.is('input')) {
          $firstError.focus();
        } else {
          $firstError.find('input').first().focus();
        }
      }, 500);
    }
  }

  // Submit form function
  function submitForm() {
    const $form = $("#gdh-rdv-form");
    const $popup = $("#gdh-rdv-popup");

    // Get current post type and ID for dynamic recipient
    const currentPostType = $popup.data('post-type') || '';
    const currentPostId = $popup.data('post-id') || 0;


    // Collect form data
    const formData = {
      // Step 1 - Slots
      slots: [],
      // Step 2 - Address
      address: $('input[name="address"]').val(),
      postal_code: $('input[name="postal_code"]').val(),
      city: $('input[name="city"]').val(),
      // Step 3 - Personal info
      first_name: $('input[name="first_name"]').val(),
      last_name: $('input[name="last_name"]').val(),
      email: $('input[name="email"]').val(),
      phone: $('input[name="phone"]').val(),
      accept_terms: $('input[name="accept_terms"]').is(':checked'),
      // Recipient information (from hidden inputs)
      recipient_email: $('input[name="recipient_email"]').val(),
      recipient_name: $('input[name="recipient_name"]').val(),
      // Auto-detected post context for dynamic recipient
      current_post_type: currentPostType,
      current_post_id: currentPostId
    };


    // Collect slots data
    $('.gdh-rdv-slot-card').each(function () {
      const $card = $(this);
      const date = $card.find('.gdh-rdv-date').val();
      const times = [];

      $card.find('.gdh-rdv-time.selected').each(function () {
        times.push($(this).data('value'));
      });

      const allDay = $card.find('.gdh-rdv-all-day').hasClass('active');

      if (date && (times.length > 0 || allDay)) {
        formData.slots.push({
          date: date,
          times: allDay ? ['all-day'] : times
        });
      }
    });

    // Show loading state
    const $submitBtn = $('.gdh-rdv-submit');
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Envoi en cours...');

    // AJAX submission
    $.ajax({
      url: gdhRdvData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'gdh_rdv_submit',
        nonce: gdhRdvData.nonce,
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
        console.error('AJAX Error:', status, error);
        console.error('Response:', xhr.responseText);
        $submitBtn.prop('disabled', false).text(originalText);
        showError('Erreur de connexion. Veuillez réessayer.');
      }
    });
  }

  // Initialize validation
  initFieldValidation();

  function initStep1Slots() {
    const $c = $('.gdh-rdv-step-content[data-step="1"]');
    if (!$c.length) return;

    // Check if already initialized to prevent duplicate event listeners
    if ($c.data('slots-initialized')) return;

    const $slotsWrapper = $c.find('.gdh-rdv-slots');
    const $slotsContainer = $c.find('.gdh-rdv-slots-container');
    const max = parseInt($slotsWrapper.attr('data-max') || '3', 10);
    const min = parseInt($slotsWrapper.attr('data-min') || '1', 10);
    const $addBtn = $c.find('.gdh-rdv-add-slot');
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

      // Professional titles for slots
      const titles = {
        1: 'Disponibilité principale',
        2: 'Disponibilité alternative',
        3: 'Disponibilité de secours'
      };

      const cardTitle = titles[index] || `Disponibilité ${index}`;

      // Try client-side Twig rendering first
      const context = { index, isRequired, showRemove, todayStr, timeSlots, cardTitle };
      let html = '';
      try {
        if (window.Twig && typeof window.Twig.twig === 'function' &&
          window.gdhRdvData && window.gdhRdvData.templates && window.gdhRdvData.templates.slotCard) {
          html = window.Twig.twig({ data: window.gdhRdvData.templates.slotCard }).render(context);
        }
      } catch (e) {
        console.error('Twig render error (slotCard):', e);
      }

      const $card = html && typeof html === 'string' && html.trim().length
        ? $(html)
        : null;

      attachCardEvents($card);
      return $card;
    }

    function attachCardEvents($card) {
      const $date = $card.find('.gdh-rdv-date');
      const $dateIcon = $card.find('.gdh-rdv-date-icon');
      const $allDay = $card.find('[data-all-day]');
      const $timeBtns = $card.find('.gdh-rdv-time');
      const $removeBtn = $card.find('.gdh-rdv-remove-slot');

      // Date icon click event - trigger date input
      $dateIcon.on('click', function () {
        $date.focus();
        if ($date[0] && typeof $date[0].showPicker === 'function') {
          $date[0].showPicker();
        }
      });

      // Date input click/keyboard - open native picker when available
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

      // Date change event
      $date.on('change', function () {
        updateCombinedValue($card);
        // Remove date error when date is selected
        if ($date.val()) {
          $date.removeClass('gdh-rdv-field-error');
          // Check if we should clear the whole card error
          const hasTimeSelected = $card.find('.gdh-rdv-time.selected').length > 0 ||
            $card.find('[data-all-day]').hasClass('active');
          if (hasTimeSelected) {
            clearCardError($card);
          }
        }
      });

      // All day button event
      $allDay.on('click', function () {
        $allDay.toggleClass('active');
        $allDay.attr('aria-pressed', $allDay.hasClass('active') ? 'true' : 'false');

        if ($allDay.hasClass('active')) {
          $timeBtns.removeClass('selected').attr('aria-pressed', 'false').prop('disabled', true);
        } else {
          $timeBtns.prop('disabled', false);
        }
        updateCombinedValue($card);
        // Remove error when time is selected
        clearCardError($card);
      });

      // Time button events
      $timeBtns.on('click', function () {
        const $btn = $(this);
        if ($btn.is(':disabled')) return;

        // If all day is active, deactivate it
        if ($allDay.hasClass('active')) {
          $allDay.removeClass('active').attr('aria-pressed', 'false');
          $timeBtns.prop('disabled', false);
        }

        $btn.toggleClass('selected');
        $btn.attr('aria-pressed', $btn.hasClass('selected') ? 'true' : 'false');

        // Check if all 6 time slots are selected
        const selectedCount = $card.find('.gdh-rdv-time.selected').length;
        const totalSlots = $timeBtns.length;

        if (selectedCount === totalSlots && totalSlots === 6) {
          // Deselect all time slots
          $timeBtns.removeClass('selected').attr('aria-pressed', 'false');
          // Activate "Toute la journée"
          $allDay.addClass('active').attr('aria-pressed', 'true');
          $timeBtns.prop('disabled', true);
        }

        updateCombinedValue($card);
        // Remove error when time is selected
        clearCardError($card);
      });

      // Remove button event
      if ($removeBtn.length) {
        $removeBtn.on('click', function () {
          removeSlotCard($card);
        });
      }

      updateCombinedValue($card);
    }

    function updateCombinedValue($card) {
      const date = $card.find('.gdh-rdv-date').val();
      const $combined = $card.find('.gdh-rdv-combined');
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
        const selectedTimes = $card.find('.gdh-rdv-time.selected').map(function () {
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
      $card.find('.gdh-rdv-date, .gdh-rdv-times-grid').removeClass('gdh-rdv-field-error');

      // Remove error message from the card
      $card.find('.gdh-rdv-slot-error').remove();

      // Check if this was the last error in step 1
      const $step1 = $('.gdh-rdv-step-content[data-step="1"]');
      const remainingErrors = $step1.find('.gdh-rdv-field-error, .gdh-rdv-slot-error').length;

      // If no more errors, remove the general error message
      if (remainingErrors === 0) {
        $step1.find('.gdh-rdv-error').remove();
      }
    }

    function addSlotCard() {
      const currentCount = $slotsWrapper.find('.gdh-rdv-slot-card').length;
      if (currentCount >= max) return;

      const newIndex = currentCount + 1;
      const $newCard = createSlotCard(newIndex);
      $slotsWrapper.append($newCard);
      updateAddButtonState();

      // Scroll to show the newly added card
      setTimeout(() => {
        // Get the actual position of the new card after it's been rendered
        const newCardOffset = $newCard[0].offsetLeft;
        const cardWidth = $newCard.outerWidth(true);
        const containerWidth = $slotsContainer.width();
        const scrollWidth = $slotsContainer[0].scrollWidth;

        let targetScroll;

        // Always scroll to show the new card properly
        if (newIndex === max) {
          // For the last card, scroll all the way to the end
          targetScroll = scrollWidth - containerWidth;
        } else {
          // For other cards, position them in the center-right of the viewport
          targetScroll = newCardOffset - (containerWidth / 2) + (cardWidth / 2);
        }

        // Ensure we don't scroll past boundaries
        targetScroll = Math.max(0, Math.min(targetScroll, scrollWidth - containerWidth));

        $slotsContainer.animate({
          scrollLeft: targetScroll
        }, 600, 'swing');

        // Add a subtle highlight effect for the new card
        $newCard.css('opacity', '0.5').animate({ opacity: '1' }, 500);

      }, 150);
    }

    function removeSlotCard($card) {
      const currentCount = $slotsWrapper.find('.gdh-rdv-slot-card').length;
      if (currentCount <= min) return;

      $card.fadeOut(200, function () {
        $card.remove();
        reindexCards();
        updateAddButtonState();
      });
    }

    function reindexCards() {
      // Professional titles for slots
      const titles = {
        1: 'Disponibilité principale',
        2: 'Disponibilité alternative',
        3: 'Disponibilité de secours'
      };

      $slotsWrapper.find('.gdh-rdv-slot-card').each(function (index) {
        const newIndex = index + 1;
        const $card = $(this);
        const cardTitle = titles[newIndex] || `Disponibilité ${newIndex}`;

        $card.attr('data-index', newIndex);
        $card.find('.gdh-rdv-slot-title').text(cardTitle);
        $card.find('.gdh-rdv-date').attr('name', `slot_${newIndex}_date`);
        $card.find('.gdh-rdv-combined').attr('name', `slot_${newIndex}`);

        // Update required attributes for first card
        if (newIndex === 1) {
          $card.find('.gdh-rdv-date').attr('required', 'required');
          $card.find('.gdh-rdv-combined').attr('required', 'required');
          $card.find('.gdh-rdv-remove-slot').remove();
        } else {
          $card.find('.gdh-rdv-date').removeAttr('required');
          $card.find('.gdh-rdv-combined').removeAttr('required');
        }
      });
    }

    function updateAddButtonState() {
      const currentCount = $slotsWrapper.find('.gdh-rdv-slot-card').length;
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

    // Remove any existing event listeners to prevent duplicates
    $addBtn.off('click.slots');

    // Add button event with namespace
    $addBtn.on('click.slots', addSlotCard);

    // Initialize with minimum number of slots
    initializeSlots();

    // Mark as initialized
    $c.data('slots-initialized', true);
  }

  function resetStep1Slots() {
    const $c = $('.gdh-rdv-step-content[data-step="1"]');
    if (!$c.length) return;

    // Clear initialization flag and re-initialize
    $c.removeData('slots-initialized');
    const $addBtn = $c.find('.gdh-rdv-add-slot');
    $addBtn.off('click.slots');

    initStep1Slots();
  }

  function validateStep1Slots() {
    const $step1 = $('.gdh-rdv-step-content[data-step="1"]');
    const $cards = $step1.find('.gdh-rdv-slot-card');
    const $slotsContainer = $step1.find('.gdh-rdv-slots-container');
    let isValid = true;
    let firstErrorCard = null;

    // Remove previous error highlights
    $cards.find('.gdh-rdv-date, .gdh-rdv-times-grid').removeClass('gdh-rdv-field-error');
    $('.gdh-rdv-slot-error').remove();

    $cards.each(function (index) {
      const $card = $(this);
      const cardIndex = index + 1;
      const isRequired = cardIndex === 1;
      const $date = $card.find('.gdh-rdv-date');
      const dateValue = $date.val();
      const $allDay = $card.find('[data-all-day]');
      const $selectedTimes = $card.find('.gdh-rdv-time.selected');
      const hasAllDay = $allDay.hasClass('active');
      const hasTimeSelected = $selectedTimes.length > 0;

      // Check if card has any data
      const hasData = dateValue || hasAllDay || hasTimeSelected;

      // Validation for required first card
      if (isRequired) {
        if (!dateValue) {
          isValid = false;
          $date.addClass('gdh-rdv-field-error');
          $card.append('<div class="gdh-rdv-slot-error">Veuillez sélectionner une date</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }

        if (dateValue && !hasAllDay && !hasTimeSelected) {
          isValid = false;
          $card.find('.gdh-rdv-times-grid').addClass('gdh-rdv-field-error');
          $card.append('<div class="gdh-rdv-slot-error">Veuillez sélectionner au moins un horaire ou "Toute la journée"</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }
      }

      // Validation for optional cards (if they have partial data)
      if (!isRequired && hasData) {
        if (!dateValue) {
          isValid = false;
          $date.addClass('gdh-rdv-field-error');
          $card.append('<div class="gdh-rdv-slot-error">Veuillez sélectionner une date pour cette disponibilité</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }

        if (dateValue && !hasAllDay && !hasTimeSelected) {
          isValid = false;
          $card.find('.gdh-rdv-times-grid').addClass('gdh-rdv-field-error');
          $card.append('<div class="gdh-rdv-slot-error">Veuillez sélectionner au moins un horaire ou "Toute la journée"</div>');
          if (!firstErrorCard) firstErrorCard = $card;
        }
      }
    });

    // Scroll to first error if validation failed
    if (!isValid && firstErrorCard) {
      const cardPosition = firstErrorCard.position().left;
      const containerWidth = $slotsContainer.width();
      const cardWidth = firstErrorCard.outerWidth(true);
      const scrollPosition = $slotsContainer.scrollLeft() + cardPosition - (containerWidth - cardWidth) / 2;

      $slotsContainer.animate({
        scrollLeft: Math.max(0, scrollPosition)
      }, 400);

      // Focus on the first error field
      setTimeout(() => {
        const $errorField = firstErrorCard.find('.gdh-rdv-field-error').first();
        if ($errorField.length) {
          $errorField.focus();
        }
      }, 450);
    }

    return isValid;
  }

  initStep1Slots();
});
