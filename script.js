document.addEventListener("DOMContentLoaded", () => {

    const loginBtn = document.getElementById("loginBtn");
    const signupBtn = document.getElementById("signupBtn");

    const heroSignupBtn = document.getElementById("heroSignupBtn");

    const loginModal = document.getElementById("loginModal");
    const signupModal = document.getElementById("signupModal");

    const closeButtons = document.querySelectorAll(".modal-close");

    function openModal(modal) {
        modal.classList.remove("hidden");
    }

    function closeModal(modal) {
        modal.classList.add("hidden");
    }

    // Top navbar buttons
    if (loginBtn) loginBtn.addEventListener("click", () => openModal(loginModal));
    if (signupBtn) signupBtn.addEventListener("click", () => openModal(signupModal));

    // HERO "Create an account" button
    if (heroSignupBtn) heroSignupBtn.addEventListener("click", () => openModal(signupModal));

    // Close buttons (X)
    closeButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const modal = btn.closest(".modal");
            closeModal(modal);
        });
    });

    // Escape closes any open modal
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeModal(loginModal);
            closeModal(signupModal);
        }
    });

    // Switch from signup → login
    const swapToLogin = document.getElementById("swapToLogin");
    if (swapToLogin) {
        swapToLogin.addEventListener("click", () => {
            closeModal(signupModal);
            openModal(loginModal);
        });
    }

    // Switch from login → signup
    const swapToSignup = document.getElementById("swapToSignup");
    if (swapToSignup) {
        swapToSignup.addEventListener("click", () => {
            closeModal(loginModal);
            openModal(signupModal);
        });
    }

});