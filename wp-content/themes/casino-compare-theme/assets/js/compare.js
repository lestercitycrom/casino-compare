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

    const renderEmptyState = (app) => {
        app.replaceChildren();
        const message = document.createElement('p');
        message.textContent = 'No casinos selected.';
        app.appendChild(message);
    };

    const renderCompareTable = (app, payload) => {
        const items = payload.items || [];
        const fields = payload.fields || {};

        if (!items.length) {
            renderEmptyState(app);
            return;
        }

        const table = document.createElement('table');
        const thead = document.createElement('thead');
        const headRow = document.createElement('tr');
        const fieldHeader = document.createElement('th');
        fieldHeader.textContent = 'Field';
        headRow.appendChild(fieldHeader);

        items.forEach((item) => {
            const itemHeader = document.createElement('th');
            const title = document.createElement('div');
            title.textContent = item.title || '';
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.setAttribute('data-ccc-remove-compare-id', String(item.id));
            removeButton.textContent = 'Remove';
            itemHeader.append(title, removeButton);
            headRow.appendChild(itemHeader);
        });

        thead.appendChild(headRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');

        Object.entries(fields).forEach(([fieldKey, fieldLabel]) => {
            const row = document.createElement('tr');
            const labelCell = document.createElement('th');
            labelCell.textContent = String(fieldLabel);
            row.appendChild(labelCell);

            items.forEach((item) => {
                const valueCell = document.createElement('td');
                valueCell.textContent = item[fieldKey] || '';
                row.appendChild(valueCell);
            });

            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        app.replaceChildren(table);
    };

    const renderCompareApp = (app) => {
        const ids = getIds();

        if (!app || !window.cccTheme) {
            return;
        }

        if (!ids.length) {
            renderEmptyState(app);
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
            compareButton.textContent = added ? 'Added' : 'Comparer';
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