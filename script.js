document.addEventListener("DOMContentLoaded", () => {
    const loginBtn = document.getElementById("loginBtn");
    const signupBtn = document.getElementById("signupBtn");
    const heroSignupBtn = document.getElementById("heroSignupBtn");

    const authModal = document.getElementById("authModal");
    const loginWrapper = document.getElementById("loginFormWrapper");
    const signupWrapper = document.getElementById("signupFormWrapper");

    const closeButtons = document.querySelectorAll(".modal-close");

    function openModal() {
        authModal.classList.remove("hidden");
    }

    function closeModal() {
        authModal.classList.add("hidden");
    }

   
    if (loginBtn) loginBtn.addEventListener("click", () => {
        loginWrapper.style.display = 'block';
        signupWrapper.style.display = 'none';
        openModal();
    });

    
    if (signupBtn) signupBtn.addEventListener("click", () => {
        loginWrapper.style.display = 'none';
        signupWrapper.style.display = 'block';
        openModal();
    });

    if (heroSignupBtn) heroSignupBtn.addEventListener("click", () => {
        loginWrapper.style.display = 'none';
        signupWrapper.style.display = 'block';
        openModal();
    });
    closeButtons.forEach(btn => btn.addEventListener("click", closeModal));
    document.addEventListener("keydown", e => {
        if (e.key === "Escape") closeModal();
    });
    document.querySelectorAll("#swapToLogin").forEach(btn => {
        btn.addEventListener("click", () => {
            loginWrapper.style.display = 'block';
            signupWrapper.style.display = 'none';
        });
    });

    document.querySelectorAll("#swapToSignup").forEach(btn => {
        btn.addEventListener("click", () => {
            loginWrapper.style.display = 'none';
            signupWrapper.style.display = 'block';
        });
    });
});
