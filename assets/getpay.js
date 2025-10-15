// No need to modify this.
function validateCheckoutForm(initialLoad = false) {
  let response = {
    validated: true,
    message: null,
  };

  let focused = false;
  let hasError = false;
  let erorMessage = "";

  // first required validation
  let requiredValidation = document.querySelectorAll(
    "#tripzzy-checkout-form [required]"
  );
  if (requiredValidation.length > 0) {
    requiredValidation.forEach(function (inputElement) {
      let inputVal = inputElement.value;
      let inputLabel = inputElement
        .closest(".tripzzy-form-field")
        .getAttribute("title");

      inputElement.classList.remove("has-error");
      if (!inputVal) {
        hasError = true;
        erorMessage += "* " + inputLabel + " is required field. </br />";
        if (!initialLoad) {
          inputElement.classList.add("has-error"); // not for init.
          if (!focused) {
            focused = true;
            inputElement.focus();
          }
        }
      }
    });
  }
  // email validation.
  let emailValidation = document.querySelectorAll(
    "#tripzzy-checkout-form input[type=email]"
  );
  if (emailValidation.length > 0) {
    emailValidation.forEach(function (inputElement) {
      let inputVal = inputElement.value;
      let inputLabel = inputElement
        .closest(".tripzzy-form-field")
        .getAttribute("title");
      inputElement.classList.remove("has-error");

      // Regx for email id validation.
      let validRegex =
        /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
      if (inputVal && !inputVal.match(validRegex)) {
        hasError = true;
        erorMessage += "* " + inputLabel + " is not valid. </br />";
        if (!initialLoad) {
          inputElement.classList.add("has-error"); // not for init.
          if (!focused) {
            focused = true;
            inputElement.focus();
          }
        }
      }
    });
  }

  // Return response.
  if (hasError) {
    response.validated = false;
    response.message = erorMessage;
  }
  return response;
}

// No need to modify this.
function displayErrorMessage(erorMessage) {
  // ErrorElements
  let errorWrapperElement = document.getElementById(
    "tripzzy-checkout-form-response-msg"
  );
  let errorTitleElement = document.getElementById(
    "tripzzy-checkout-form-response-title"
  );
  let errorMessageElement = document.getElementById(
    "tripzzy-checkout-form-response"
  );

  if (erorMessage) {
    errorWrapperElement.classList.add("tripzzy-response-msg", "tripzzy-error");
    errorTitleElement.innerHTML = "Error";
    errorMessageElement.innerHTML = erorMessage;
  } else {
    errorWrapperElement.classList.remove(
      "tripzzy-response-msg",
      "tripzzy-error"
    );
    errorTitleElement.innerHTML = "";
    errorMessageElement.innerHTML = "";
  }
}

// No need to modify this.
function getPaymentDescription() {
  const { payment_description } = tripzzy;
  return payment_description;
}

function loadGetPayScript(callback) {
  if (window.GetPay) return callback(); // already loaded

  const script = document.createElement("script");
  script.src =
    "https://minio.finpos.global/getpay-cdn/webcheckout/v5/bundle.js";
  script.onload = callback;
  script.onerror = () => {
    console.error("Failed to load GetPay SDK");
  };
  document.head.appendChild(script);
}

function initiateGetPayPayment() {
  let paymentButtonWrapper = document.getElementById("tripzzy-payment-button");
  console.log("init getpay");

  try {
    displayErrorMessage();

    // Remove existing checkout div if it exists (cleanup old instance)
    let existing = document.getElementById("checkout");
    if (existing) existing.remove();

    // Create new #checkout container
    let getpayCardElement = document.createElement("div");
    getpayCardElement.id = "checkout";
    getpayCardElement.classList.add("tripzzy-getpay-payment-element");
    getpayCardElement.hidden = true;
    getpayCardElement.style.padding = "20px";
    getpayCardElement.style.boxShadow = "var(--tripzzy-box-shadow)";
    getpayCardElement.style.background = "#fff";
    getpayCardElement.style.borderRadius = "var(--tripzzy-rounded)";
    getpayCardElement.style.marginBottom = "var(--tripzzy-g)";
    paymentButtonWrapper.prepend(getpayCardElement);

    // Load SDK (only once)
    loadGetPayScript(() => {
      console.log("✅ GetPay SDK loaded and #checkout div ready");

      const { gateway } = tripzzy || {};
      const { getpay_payment } = gateway || {};
      const {
        pap_info: papInfo,
        opr_key: oprKey,
        ins_key: insKey,
      } = getpay_payment;

      const options = {
        papInfo,
        oprKey,
        insKey,
        websiteDomain: window.location.origin,
        price: 10.0,
        businessName: "Book Trips with weflow",
        imageUrl:
          "https://tripzzy-development.local/wp-content/plugins/tripzzy/assets/images/logo.svg",
        currency: "NPR",
        callbackUrl: {
          successUrl: "https://tripzzy-development.local/tz-thankyou",
          failUrl: "https://tripzzy-development.local/tz-thankyou",
        },
        themeColor: "#5662FF",
        onSuccess: (res) => console.log("✅ Payment success", res),
        onError: (err) => console.error("❌ Payment error", err),
      };

      const paymentBtn = document.querySelector(
        "input[name='tripzzy_book_now']"
      );
      if (!paymentBtn) return console.error("Payment button not found");

      // Remove previous click listeners to prevent duplicates
      paymentBtn.replaceWith(paymentBtn.cloneNode(true));
      const newBtn = document.querySelector("input[name='tripzzy_book_now']");

      newBtn.onclick = function (e) {
        e.preventDefault();
        const response = validateCheckoutForm(true);
        if (!response.validated) {
          displayErrorMessage(response.message);
          return;
        }

        console.log("Initializing GetPay...");
        const getpay = new GetPay(options);
        getpay.initialize();
      };
    });
  } catch (e) {
    displayErrorMessage(e.message);
    setTimeout(() => {
      paymentButtonWrapper.classList.remove("tripzzy-is-processing");
    }, 100);
  }
}

initiateGetPayPayment();

// Payment option Button click event.
document.addEventListener("click", function (event) {
  var target = event.target;
  var paymentOption = document.querySelector(
    "#tripzzy-payment-mode-getpay_payment"
  );

  if (target === paymentOption) {
    initiateGetPayPayment();
  }
});
