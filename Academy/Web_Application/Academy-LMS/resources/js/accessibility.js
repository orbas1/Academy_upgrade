const root = document.documentElement;
const body = document.body;

const enableKeyboardFocus = () => {
    body.classList.add('user-is-tabbing');
    window.removeEventListener('keydown', handleFirstTab);
    window.addEventListener('mousedown', handleMouseDownOnce);
};

const handleFirstTab = (event) => {
    if (event.key === 'Tab') {
        enableKeyboardFocus();
    }
};

const handleMouseDownOnce = () => {
    body.classList.remove('user-is-tabbing');
    window.removeEventListener('mousedown', handleMouseDownOnce);
    window.addEventListener('keydown', handleFirstTab, { once: true });
};

window.addEventListener('keydown', handleFirstTab, { once: true });

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const applyReducedMotionPreference = (mediaQuery) => {
    if (mediaQuery.matches) {
        body.classList.add('prefers-reduced-motion');
    } else {
        body.classList.remove('prefers-reduced-motion');
    }
};

prefersReducedMotion.addEventListener('change', applyReducedMotionPreference);
applyReducedMotionPreference(prefersReducedMotion);

const mainContent = document.getElementById('main-content');
if (mainContent && !mainContent.hasAttribute('tabindex')) {
    mainContent.setAttribute('tabindex', '-1');
}

const skipLinks = document.querySelectorAll('a[href^="#main-content"]');
skipLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
        if (mainContent) {
            requestAnimationFrame(() => mainContent.focus());
        }
        event.preventDefault();
    });
});

const localeForm = document.querySelector('.locale-switcher');
if (localeForm) {
    const select = localeForm.querySelector('select[name="locale"]');
    const redirectInput = localeForm.querySelector('input[name="redirect_to"]');
    if (select && redirectInput) {
        select.addEventListener('change', () => {
            redirectInput.value = window.location.pathname + window.location.search;
            localeForm.submit();
        });
    }
}

const localeDirection = body.getAttribute('data-locale-direction');
if (localeDirection) {
    root.setAttribute('data-locale-direction', localeDirection);
}

const flashMessage = document.querySelector('[data-locale-flash]');
if (flashMessage) {
    flashMessage.setAttribute('role', 'status');
    flashMessage.setAttribute('aria-live', 'polite');
}
