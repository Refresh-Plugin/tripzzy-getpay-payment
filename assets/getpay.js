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
        ?.closest(".tripzzy-form-field")
        ?.getAttribute("title");

      if (inputLabel) {
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
        ?.getAttribute("title");
      if (inputLabel) {
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

function initiateGetPayPayment() {
  const paymentOptionWrapper = document.querySelector(
    ".tripzzy-payment-options-wrapper"
  );
  const paymentButtonWrapper = document.getElementById(
    "tripzzy-payment-button"
  );
  displayErrorMessage();

  try {
    let buttonTemplate = wp.template("tripzzy-pay-now");
    paymentButtonWrapper.innerHTML = buttonTemplate();
    paymentButtonWrapper.classList.remove("tripzzy-is-processing");

    let paymentBtn = paymentButtonWrapper.querySelector(
      "input[name='tripzzy_book_now']"
    );
    // Payment Details
    let paymentDetailElement = document.getElementById(
      "tripzzy-payment-details"
    );

    // Form Element.
    let formElement = document.getElementById("tripzzy-checkout-form");
    let currency = paymentButtonWrapper.getAttribute("data-currency");

    if (currency !== "NPR") {
      setTimeout(function () {
        paymentButtonWrapper.classList.remove("tripzzy-is-processing");
      }, 100);
      displayErrorMessage("GetPay Payment only Supports NPR.");
      paymentBtn.disabled = true;
      return;
    }

    paymentBtn.onclick = function (e) {
      e.preventDefault();

      let response = validateCheckoutForm(true);
      if (response.validated) {
        const formData = new FormData(formElement);
        const formObject = Object.fromEntries(formData.entries());
        TripzzyGetpaySafeStorage.set("tripzzyCheckoutForm", formObject);

        const storedData = TripzzyGetpaySafeStorage.get("tripzzyCheckoutForm");
        const isStored = null !== storedData;

        if (!isStored) {
          // In case of not saved due to configuration error like http over https.
          displayErrorMessage("Checkout Data Not Found!");
          paymentBtn.disabled = true;
        }

        let amount = paymentButtonWrapper.getAttribute("data-total");
        let firstName = document.getElementById("billing-first-name")?.value;
        let lastName = document.getElementById("billing-last-name")?.value;
        let billingEmail = document.getElementById("billing-email")?.value;
        let state = document.getElementById("billing-state")?.value;
        // let zipcode = document.getElementById("billing-postcode")?.value;
        let city = document.getElementById("billing-city")?.value;
        let address = document.getElementById("billing-address-1")?.value;
        let phoneNumber = document.getElementById("billing-phone")?.value;

        let zipcode = 44600;

        const { gateway } = tripzzy || {};
        const { getpay_payment } = gateway || {};
        const {
          pap_info: papInfo,
          opr_key: oprKey,
          ins_key: insKey,
          base_url: baseUrl,
          business_name: businessName,
          website_domain: websiteDomain,
          payment_page_url: paymentPageURL,
          success_page_url: successPageURL,
          failed_page_url: failedPageURL,
          order_information_ui: orderInformationUI,
        } = getpay_payment;

        if (!getpay_payment) {
          displayErrorMessage("Configuration error.");
          paymentBtn.disabled = true;
        }

        if ("undefined" === typeof GetPay) {
          displayErrorMessage("GetPay library not loaded");
          paymentBtn.disabled = true;
          return false;
        }

        paymentButtonWrapper.classList.add("tripzzy-is-processing");
        const options = {
          // user Info is optional. If provided, you can choose to prefill those information in checkout page
          userInfo: {
            name: firstName + " " + lastName,
            email: billingEmail,
            state: state,
            country: "Nepal",
            zipcode: zipcode,
            city: city,
            address: address,
            phoneNumber: phoneNumber,
          },
          papInfo: papInfo,
          oprKey: oprKey,
          insKey: "",
          websiteDomain,
          price: parseFloat(amount),
          businessName,
          imageUrl:
            "https://getpay.wptripzzy.com/wp-content/plugins/tripzzy-getpay-payment/assets/favicon.png", // company logo to display in checkout page
          currency: "NPR",
          // provided attributes with value true will autofill in checkout page
          prefill: {
            name: false,
            email: false,
            state: false,
            city: false,
            address: false,
            zipcode: false,
            country: false,
          },
          // provided attributes with value true will be disabled in checkout page. Note that you must only disable fields which are prefilled
          disableFields: {
            address: false,
            state: false, // address and state fields will be disabled in checkout page
          },
          // redirection callback url when payment is either success or fail
          callbackUrl: {
            successUrl: successPageURL,
            failUrl: failedPageURL, // static for now
          },
          // brand theme color to display in checkout page
          themeColor: "#5662FF",
          // accept html with inline css to display UI in checkout page
          orderInformationUI,
          onSuccess: (options) => {
            paymentButtonWrapper.classList.add("tripzzy-is-processing");
            window.location.replace(paymentPageURL);
          },
          onError: (response) => {
            paymentButtonWrapper.classList.remove("tripzzy-is-processing");
            displayErrorMessage(response.error); // handle to display/hide error message.
          },
        };
        console.log("option before call ", options);
        options.baseUrl = baseUrl;

        const getpay = new GetPay(options);
        getpay.initialize();

        // if validated Proceed your payment.

        // add payment response to paymentDetailElement
        // let response = {};
        // paymentDetailElement.value = JSON.stringify(response);
      } else {
        displayErrorMessage(response.message); // handle to display/hide error message.
      }
    };
  } catch (e) {
    displayErrorMessage(e.message);
    // paymentButtonWrapper.classList.remove("tripzzy-is-processing");
    setTimeout(function () {
      paymentButtonWrapper.classList.remove("tripzzy-is-processing");
    }, 100);
  }
}

initiateGetPayPayment();

// const disableUnloadWarning = () => {
//   window.onbeforeunload = null;

//   jQuery(window).off("beforeunload");
// };

// Payment option Button click event.
document.addEventListener("click", function (event) {
  var target = event.target;
  var paymentOption = document.querySelector(
    "#tripzzy-payment-mode-getpay_payment"
  );

  if (target === paymentOption) {
    setTimeout(function () {
      initiateGetPayPayment(true);
    }, 100);
  }
});
