(() => {
    const storageKey = 'ccc_compare_ids';

    const getIds = () => {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '[]');
        } catch (error) {
            return [];
        }
    };

    const saveIds = (ids) => localStorage.setItem(storageKey, JSON.stringify(ids.slice(0, 3)));

    const updateBadge = () => {
        const badge = document.getElementById('ccc-compare-badge');

        if (badge) {
            badge.textContent = `Comparer (${getIds().length})`;
        }
    };

    const removeId = (id) => {
        saveIds(getIds().filter((item) => item !== id));
        updateBadge();
    };

    const toggleId = (id) => {
        const ids = getIds();
        const exists = ids.includes(id);
        const next = exists ? ids.filter((item) => item !== id) : [...ids, id].slice(0, 3);
        saveIds(next);
        updateBadge();
        return !exists;
    };

    const renderCompareTable = (app, payload) => {
        const items = payload.items || [];
        const fields = payload.fields || {};

        if (!items.length) {
            app.innerHTML = '<p>No casinos selected.</p>';
            return;
        }

        const headerCells = items.map((item) => `
            <th>
                <div>${item.title || ''}</div>
                <button type="button" data-ccc-remove-compare-id="${item.id}">Remove</button>
            </th>
        `).join('');

        const bodyRows = Object.entries(fields).map(([fieldKey, fieldLabel]) => {
            const values = items.map((item) => `<td>${item[fieldKey] || ''}</td>`).join('');
            return `<tr><th>${fieldLabel}</th>${values}</tr>`;
        }).join('');

        app.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        ${headerCells}
                    </tr>
                </thead>
                <tbody>${bodyRows}</tbody>
            </table>
        `;
    };

    const renderCompareApp = (app) => {
        const ids = getIds();

        if (!app || !window.cccTheme) {
            return;
        }

        if (!ids.length) {
            app.innerHTML = '<p>No casinos selected.</p>';
            return;
        }

        fetch(`${window.cccTheme.restUrl}ccc/v1/compare?ids=${ids.join(',')}`)
            .then((response) => response.json())
            .then((payload) => renderCompareTable(app, payload));
    };

    document.addEventListener('click', (event) => {
        const compareButton = event.target.closest('[data-ccc-compare-id]');

        if (compareButton) {
            const added = toggleId(Number(compareButton.getAttribute('data-ccc-compare-id')));
            compareButton.textContent = added ? 'Ajoute ✓' : 'Comparer';
            return;
        }

        const removeButton = event.target.closest('[data-ccc-remove-compare-id]');

        if (!removeButton) {
            return;
        }

        removeId(Number(removeButton.getAttribute('data-ccc-remove-compare-id')));
        renderCompareApp(document.getElementById('ccc-compare-app'));
    });

    document.addEventListener('DOMContentLoaded', () => {
        updateBadge();
        renderCompareApp(document.getElementById('ccc-compare-app'));
    });
})();
