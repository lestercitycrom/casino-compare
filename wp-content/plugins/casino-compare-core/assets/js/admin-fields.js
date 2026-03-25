document.addEventListener('DOMContentLoaded', () => {
    const updateConditionals = () => {
        document.querySelectorAll('[data-ccc-condition-field]').forEach((block) => {
            const field = block.getAttribute('data-ccc-condition-field');
            const expected = block.getAttribute('data-ccc-condition-value');
            const input = document.querySelector(`[name="${field}"]`) || document.querySelector(`[name="${field}[]"]`);

            if (!input) {
                return;
            }

            const value = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
            block.style.display = value === expected ? '' : 'none';
        });
    };

    const buildRepeaterControl = (key, index, subfieldKey, subfield) => {
        const fieldName = `${key}[${index}][${subfieldKey}]`;
        const fieldType = subfield.type || 'text';
        const cell = document.createElement('div');
        cell.className = `ccc-repeater-cell ccc-repeater-cell--${subfield.layout || 'full'} ccc-repeater-cell--${fieldType}`;

        const label = document.createElement('label');
        label.className = 'ccc-repeater-cell__label';
        label.textContent = subfield.label || '';
        cell.appendChild(label);

        if (fieldType === 'textarea') {
            const textarea = document.createElement('textarea');
            textarea.className = 'widefat';
            textarea.name = fieldName;
            textarea.rows = String(subfield.rows || 3);
            textarea.placeholder = subfield.label || '';
            cell.appendChild(textarea);
            return cell;
        }

        if (fieldType === 'relation' && Array.isArray(subfield.options)) {
            const select = document.createElement('select');
            select.className = 'widefat';
            select.name = fieldName;

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Select an item';
            select.appendChild(emptyOption);

            subfield.options.forEach((option) => {
                const element = document.createElement('option');
                element.value = option.value;
                element.textContent = option.label;
                select.appendChild(element);
            });

            cell.appendChild(select);
            return cell;
        }

        const input = document.createElement('input');
        input.className = 'widefat';
        input.type = fieldType === 'number' ? 'number' : 'text';
        input.name = fieldName;
        input.placeholder = subfield.label || '';

        if (fieldType === 'number' && subfield.step) {
            input.step = String(subfield.step);
        }

        cell.appendChild(input);

        return cell;
    };

    document.addEventListener('change', updateConditionals);
    document.addEventListener('change', (event) => {
        const select = event.target;

        if (!(select instanceof HTMLSelectElement) || !select.multiple || !select.dataset.cccMaxItems) {
            return;
        }

        const maxItems = Number(select.dataset.cccMaxItems);
        const selected = Array.from(select.options).filter((option) => option.selected);

        if (!maxItems || selected.length <= maxItems) {
            return;
        }

        selected.slice(maxItems).forEach((option) => {
            option.selected = false;
        });
    });
    updateConditionals();

    document.querySelectorAll('.ccc-media-button').forEach((button) => {
        button.addEventListener('click', () => {
            const targetName = button.getAttribute('data-ccc-media-target');
            const input = document.querySelector(`input[name="${targetName}"]`);
            const preview = button.parentElement?.querySelector('.ccc-image-preview');

            if (!input || typeof wp === 'undefined' || !wp.media) {
                return;
            }

            const frame = wp.media({
                title: 'Select image',
                button: { text: 'Use image' },
                multiple: false,
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                input.value = attachment.id || '';

                if (preview) {
                    preview.innerHTML = attachment.sizes?.thumbnail
                        ? `<img src="${attachment.sizes.thumbnail.url}" alt="">`
                        : '';
                }
            });

            frame.open();
        });
    });

    document.querySelectorAll('.ccc-repeater').forEach((repeater) => {
        const rowsContainer = repeater.querySelector('.ccc-repeater-rows');
        const key = repeater.getAttribute('data-key');
        const subfields = JSON.parse(repeater.getAttribute('data-subfields') || '{}');

        repeater.querySelector('.ccc-add-row')?.addEventListener('click', () => {
            const index = rowsContainer.querySelectorAll('.ccc-repeater-row').length;
            const row = document.createElement('div');
            row.className = 'ccc-repeater-row';

            const fields = document.createElement('div');
            fields.className = 'ccc-repeater-row__fields';

            Object.entries(subfields).forEach(([subfieldKey, subfield]) => {
                fields.appendChild(buildRepeaterControl(key, index, subfieldKey, subfield));
            });

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'button-link-delete ccc-remove-row';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', 'Remove row');

            row.appendChild(fields);
            row.appendChild(remove);
            rowsContainer.appendChild(row);
        });

        repeater.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement) || !target.classList.contains('ccc-remove-row')) {
                return;
            }

            const rows = rowsContainer.querySelectorAll('.ccc-repeater-row');

            if (rows.length <= 1) {
                rows[0].querySelectorAll('input, textarea, select').forEach((input) => {
                    input.value = '';
                });
                return;
            }

            target.closest('.ccc-repeater-row')?.remove();
        });
    });
});
