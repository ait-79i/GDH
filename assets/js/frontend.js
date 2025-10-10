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

  function validateCurrentStep() {
    $currentStepContent = $(`.gdh-rdv-step-content[data-step="${currentStep}"`);
    let isValid = true;
    let errorMessage = "";

    $("gdh-rdv-eror").remove();

    switch (currentStep) {
      case 1:
        const slot1 = $currentStepContent.find('input[name="slot_1"]').val();
        if (!slot1) {
          isValid = false;
          errorMessage = "Veuillez sélectionner au moins le premier créneau.";
        }
        break;
      case 2:
        const requiredFields = ["address", "postal_code", "city"];
        requiredFields.forEach((field) => {
          const value = $currentStepContent
            .find(`input[name="${field}"]`)
            .val()
            .trim();
          if (!value) {
            isValid = false;
            errorMessage = "Veuillez remplir tous les champs obligatoires.";
          }
        });

        const postalCode = $currentStepContent
          .find('input[name="postal_code"]')
          .val();
        if (postalCode && !/^[0-9]{5}$/.test(postalCode)) {
          isValid = false;
          errorMessage = "Le code postal doit contenir 5 chiffres.";
        }
        break;
      case 3:
        const personalFeilds = ["first_name", "last_name", "email", "phone"];
        personalFeilds.forEach((field) => {
          const value = $currentStepContent
            .find(`input[name="${field}"]`)
            .val()
            .trim();
          if (!value) {
            isValid = false;
            errorMessage = "Veuillez remplir tous les champs obligatoires.";
          }
        });

        const email = $currentStepContent.find('input[name="email"]').val();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          isValid = false;
          errorMessage = "Veuillez saisir une adresse email valide.";
        }

        const termsAccepted = $currentStepContent
          .find('input[name="accept_terms"]')
          .is(":checked");
        if (!termsAccepted) {
          isValid = false;
          errorMessage = "Vous devez accepter les conditions générales.";
        }
        break;
    }

    // Display error message if validation failed
    if (!isValid) {
      showError(errorMessage);
    }

    return isValid;
  }

  function updateStep() {
    $(".gdh-rdv-step-content").removeClass("active");

    $(`.gdh-rdv-step-content[data-step="${currentStep}"`).addClass("active");

    $(".gdh-rdv-step").removeClass("active completed");
    $(".gdh-rdv-step").each(function (index) {
      if (index + 1 < currentStep) {
        $(this).addClass("completed");
      } else {
        $(this).addClass("active");
      }
    });

    const progressPercentage = (currentStep / totalSteps) * 100;
    $(".gdh-rdv-progress-fill").css("width", progressPercentage + "%");

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
    updateStep();
  }

  updateStep();
});
