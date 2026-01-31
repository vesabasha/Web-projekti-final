<div id="authModal" class="modal hidden">

    <div class="modal-content">
        <button class="modal-close">&times;</button>

        <!-- Login Form -->
        <div id="loginFormWrapper" style="display: block;">
            <h2>Log in</h2>
            <button class="google-btn">
                <img src="images/googleicon.png" alt="Google Icon"> Continue with Google
            </button>
            <p class="divider-text">or</p>
            <form id="loginForm" class="modal-form">
                <label>Email<input type="email" name="email" placeholder="Enter your email or username" required></label>
                <label>Password<input type="password" name="password" placeholder="Enter your password" required></label>
                <button type="submit">Log in</button>
            </form>
            <p id="loginError" style="color:red;text-align:center;display:none;"></p>
            <p style="text-align:center;">
                No account? <button id="swapToSignup" style="background:none;border:none;color:#44A1A0;text-decoration:underline;cursor:pointer;">Create one</button>
            </p>
        </div>

        <!-- Signup Form -->
        <div id="signupFormWrapper" style="display: none;">
            <h2>Sign Up</h2>
            <button class="google-btn">
                <img src="images/googleicon.png" alt="Google Icon"> Continue with Google
            </button>
            <p class="divider-text">or</p>
            <form id="signupForm" class="modal-form">
                <label>Username<input type="text" name="username" placeholder="Choose a username" required></label>
                <label>Email<input type="email" name="email" placeholder="Enter your email" required></label>
                <label>Password<input type="password" name="password" placeholder="Create a password" required></label>
                <label>Confirm Password<input type="password" name="confirm_password" placeholder="Confirm your password" required></label>
                <button type="submit">Create Account</button>
            </form>
            <p id="signupError" style="color:red;text-align:center;display:none;"></p>
            <p style="text-align:center;">
                Already a member? <button id="swapToLogin" style="background:none;border:none;color:#44A1A0;text-decoration:underline;cursor:pointer;">Log in!</button>
            </p>
        </div>
    </div>
</div>

<script>
const authModal = document.getElementById('authModal');
const loginWrapper = document.getElementById('loginFormWrapper');
const signupWrapper = document.getElementById('signupFormWrapper');
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const loginError = document.getElementById('loginError');
const signupError = document.getElementById('signupError');

document.querySelectorAll('#swapToSignup').forEach(btn => btn.onclick = () => {
    loginWrapper.style.display = 'none';
    signupWrapper.style.display = 'block';
    loginError.style.display = 'none';
});

document.querySelectorAll('#swapToLogin').forEach(btn => btn.onclick = () => {
    signupWrapper.style.display = 'none';
    loginWrapper.style.display = 'block';
    signupError.style.display = 'none';
});

document.querySelector('.modal-close').onclick = () => authModal.classList.add('hidden');

loginForm.onsubmit = async (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    formData.append('action', 'login');

    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            loginError.textContent = data.message;
            loginError.style.display = 'block';
        }
    } catch (error) {
        loginError.textContent = 'An error occurred. Please try again.';
        loginError.style.display = 'block';
    }
};

signupForm.onsubmit = async (e) => {
    e.preventDefault();
    const formData = new FormData(signupForm);
    formData.append('action', 'signup');

    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            signupError.textContent = data.message;
            signupError.style.display = 'block';
        }
    } catch (error) {
        signupError.textContent = 'An error occurred. Please try again.';
        signupError.style.display = 'block';
    }
};
</script>
