(function () {
  if (window.top !== window.self) {
    window.top.location = window.self.location.href;
  }
})();

// Submit Form on success.
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("tripzzy-checkout-form");
  if (!form) return;

  const data = TripzzyGetpaySafeStorage.get("tripzzyCheckoutForm");
  if (!data) return;

  const responseElement = document.getElementById("getpay-response");

  Object.entries(data).forEach(([key, value]) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = key;
    input.value = value;
    form.appendChild(input);
  });

  const paymentDetailElement = document.querySelector(
    'input[name="payment_details"]'
  );
  paymentDetailElement.value = responseElement.value;
  TripzzyGetpaySafeStorage.remove("tripzzyCheckoutForm");
  form.submit();
});
