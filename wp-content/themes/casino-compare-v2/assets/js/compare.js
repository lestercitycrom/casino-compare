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
        const count = getIds().length;
        ['ccc-compare-badge', 'ccc-compare-badge-home'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.textContent = `Comparer (${count})`;
        });
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
        app.className = 'compare-app compare-app--empty';
        const wrap = document.createElement('div');
        wrap.className = 'compare-empty';
        const title = document.createElement('h2');
        title.textContent = 'No casinos selected yet';
        const message = document.createElement('p');
        message.textContent = 'Add up to three casinos from review pages, landing cards or guide sidebars to build a side-by-side comparison.';
        wrap.append(title, message);
        app.appendChild(wrap);
    };

    const renderCompareTable = (app, payload) => {
        const items = payload.items || [];
        const fields = payload.fields || {};

        if (!items.length) {
            renderEmptyState(app);
            return;
        }

        app.className = 'compare-app compare-app--loaded';
        const table = document.createElement('table');
        table.className = 'compare-table';
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
            removeButton.className = 'compare-remove-btn';
            removeButton.setAttribute('data-ccc-remove-compare-id', String(item.id));
            removeButton.setAttribute('aria-label', 'Retirer');
            removeButton.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
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
                valueCell.textContent = item[fieldKey] || '—';
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
            .then((payload) => renderCompareTable(app, payload))
            .catch(() => renderEmptyState(app));
    };

    document.addEventListener('click', (event) => {
        const compareButton = event.target.closest('[data-ccc-compare-id]');

        if (compareButton) {
            const added = toggleId(Number(compareButton.getAttribute('data-ccc-compare-id')));
            compareButton.textContent = added ? 'Ajouté ✓' : 'Comparer';
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
