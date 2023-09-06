document.addEventListener("DOMContentLoaded", () => {
  // variables to store buttons
  const vendorLoginBtn = document.getElementById("vendor-login-btn");
  const vendorSignupBtn = document.getElementById("vendor-signup-btn");
  const buyerLoginBtn = document.getElementById("customer-login-btn");
  const buyerSignupBtn = document.getElementById("customer-signup-btn");

  // variables to store forms
  const vendorLoginForm = document.getElementById("vendor-login-form");
  const vendorSignupForm = document.getElementById("vendor-signup-form");
  const buyerLoginForm = document.getElementById("customer-login-form");
  const buyerSignupForm = document.getElementById("customer-signup-form");

  // event listeners for vendor login and signup buttons
  vendorLoginBtn.addEventListener("click", () => {
    toggleFormVisibility(vendorLoginForm, vendorSignupForm);
  });

  vendorSignupBtn.addEventListener("click", () => {
    toggleFormVisibility(vendorSignupForm, vendorLoginForm);
  });

  // event listeners for buyer login and signup buttons
  buyerLoginBtn.addEventListener("click", () => {
    toggleFormVisibility(buyerLoginForm, buyerSignupForm);
  });

  buyerSignupBtn.addEventListener("click", () => {
    toggleFormVisibility(buyerSignupForm, buyerLoginForm);
  });

  // function to toggle form visibility
  function toggleFormVisibility(showForm, hideForm) {
    showForm.style.display = "block";
    hideForm.style.display = "none";
  }
});
