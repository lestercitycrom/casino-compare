/* casino-compare-v2 — main.js */

document.addEventListener('DOMContentLoaded', () => {

    // =====================================================
    // FAQ ACCORDION
    // =====================================================
    document.querySelectorAll('.faq__question').forEach((q) => {
        q.addEventListener('click', () => {
            const item   = q.closest('.faq__item');
            const isOpen = item.classList.contains('is-open');
            // Close all open items
            document.querySelectorAll('.faq__item.is-open').forEach((i) => i.classList.remove('is-open'));
            // Open clicked item if it was closed
            if (!isOpen) {
                item.classList.add('is-open');
            }
        });
    });

    // =====================================================
    // MOBILE MENU TOGGLE
    // =====================================================
    const toggle = document.getElementById('nav-toggle');
    const nav    = document.getElementById('nav-menu');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // =====================================================
    // COMPARE BUTTONS — restore state from localStorage
    // =====================================================
    try {
        const ids = JSON.parse(localStorage.getItem('ccc_compare_ids') || '[]');
        ids.forEach((id) => {
            document.querySelectorAll(`[data-ccc-compare-id="${id}"]`).forEach((btn) => {
                btn.textContent = 'Ajouté ✓';
            });
        });
    } catch (e) {
        // localStorage not available
    }

});
