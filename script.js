document.addEventListener("DOMContentLoaded", () => {
    const loginBtn = document.getElementById("loginBtn");
    const signupBtn = document.getElementById("signupBtn");

    const loginModal = document.getElementById("loginModal");
    const signupModal = document.getElementById("signupModal");

    const closeButtons = document.querySelectorAll(".modal-close");

    function openModal(modal) {
        if (modal) {
            modal.classList.remove("hidden");
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.add("hidden");
        }
    }

    if (loginBtn) {
        loginBtn.addEventListener("click", () => openModal(loginModal));
    }

    if (signupBtn) {
        signupBtn.addEventListener("click", () => openModal(signupModal));
    }

    closeButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            const modal = e.target.closest(".modal");
            closeModal(modal);
        });
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeModal(loginModal);
            closeModal(signupModal);
        }
    });
});
const swapToLogin = document.getElementById("swapToLogin");
if (swapToLogin) {
    swapToLogin.addEventListener("click", () => {
        signupModal.classList.add("hidden");
        loginModal.classList.remove("hidden");
    });
}
const swapToSignup = document.getElementById("swapToSignup");
if (swapToSignup) {
    swapToSignup.addEventListener("click", () => {
        loginModal.classList.add("hidden");
        signupModal.classList.remove("hidden");
    });
}