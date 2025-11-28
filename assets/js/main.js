document.addEventListener('DOMContentLoaded', () => {
    // Loading Screen - Show for 2 seconds
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.classList.add('fade-out');
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500); // Wait for fade animation
        }, 2000); // 2 seconds
    }

    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
            // toggle overlay
            const overlay = document.getElementById('nav-overlay');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    // Close button inside nav panel
    const navOverlay = document.getElementById('nav-overlay');
    function closeNavPanel() {
        if (navLinks) navLinks.classList.remove('active');
        if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
        if (navOverlay) navOverlay.classList.remove('active');
    }

    if (navOverlay) {
        navOverlay.addEventListener('click', closeNavPanel);
    }

    // Modal Handling
    const loginBtn = document.getElementById('login-btn');
    const signupBtn = document.getElementById('signup-btn');
    const panelLoginBtn = document.getElementById('panel-login');
    const loginModal = document.getElementById('login-modal');
    const signupModal = document.getElementById('signup-modal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const switchToSignup = document.getElementById('switch-to-signup');
    const switchToLogin = document.getElementById('switch-to-login');

    function openModal(modal) {
        if (modal) {
            // Close all modals first
            closeAllModals();
            // Close nav panel if open
            closeNavPanel();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
    }

    function closeAllModals() {
        const modals = document.querySelectorAll('.modal-overlay');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = ''; // Restore scrolling
    }

    // Open Login Modal
    if (loginBtn) {
        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(loginModal);
        });
    }

    // Panel Login Button
    if (panelLoginBtn) {
        panelLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(loginModal);
        });
    }

    // Open Signup Modal
    if (signupBtn) {
        signupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(signupModal);
        });
    }

    // Switch to Signup
    if (switchToSignup) {
        switchToSignup.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(signupModal);
        });
    }

    // Switch to Login
    if (switchToLogin) {
        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(loginModal);
        });
    }

    // Close Modals
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            closeAllModals();
        }
    });

    // Check for errors to auto-open modal (handled by PHP rendering 'active' class, but good to have backup)
    const hasLoginErrors = document.querySelector('#login-modal .alert-error');
    const hasSignupErrors = document.querySelector('#signup-modal .alert-error');
    const hasSuccess = document.querySelector('.alert-success');

    if (hasLoginErrors || hasSuccess) {
        openModal(loginModal);
    } else if (hasSignupErrors) {
        openModal(signupModal);
    }
});
