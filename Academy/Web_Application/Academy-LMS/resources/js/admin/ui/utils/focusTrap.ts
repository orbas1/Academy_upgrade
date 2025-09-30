export function trapFocus(container: HTMLElement): () => void {
    const focusableSelectors = [
        'a[href]',
        'button:not([disabled])',
        'textarea:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ];

    const focusable = Array.from(
        container.querySelectorAll<HTMLElement>(focusableSelectors.join(',')),
    ).filter((element) => !element.hasAttribute('aria-hidden'));

    if (focusable.length === 0) {
        container.tabIndex = -1;
        container.focus();
        return () => {
            container.removeAttribute('tabindex');
        };
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    function onKeyDown(event: KeyboardEvent) {
        if (event.key !== 'Tab') {
            return;
        }

        if (event.shiftKey) {
            if (document.activeElement === first) {
                event.preventDefault();
                last.focus();
            }
        } else if (document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    container.addEventListener('keydown', onKeyDown);
    first.focus();

    return () => {
        container.removeEventListener('keydown', onKeyDown);
    };
}
