(() => {
    const form = document.getElementById('ccc-filter-form');
    const results = document.getElementById('ccc-filter-results');

    if (!form || !results || !window.cccTheme) {
        return;
    }

    let timeoutId = null;
    const baselineHtml = results.innerHTML;

    const hasActiveFilters = () => {
        const formData = new FormData(form);

        for (const [, value] of formData.entries()) {
            if (String(value).trim() !== '') {
                return true;
            }
        }

        return false;
    };

    const restoreBaseline = () => {
        results.innerHTML = baselineHtml;
        window.history.replaceState({}, '', window.location.pathname);
    };

    const updateResults = () => {
        if (!hasActiveFilters()) {
            restoreBaseline();
            return;
        }

        const params = new URLSearchParams(new FormData(form));
        const queryString = params.toString();
        const url = `${window.cccTheme.restUrl}ccc/v1/filter?${queryString}`;
        const nextLocation = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;

        window.history.replaceState({}, '', nextLocation);

        fetch(url)
            .then((response) => response.json())
            .then((payload) => {
                results.innerHTML = payload.html || '';
            });
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        updateResults();
    });

    form.addEventListener('change', () => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(updateResults, 300);
    });

    if (hasActiveFilters()) {
        updateResults();
    }
})();
