import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Filter and search forms: submit only the fields that hold a value, so the
// address reads ?risk=High rather than ?repository=&risk=High&status=...
Alpine.store('filters', {
    go(form) {
        const params = new URLSearchParams();
        for (const [key, value] of new FormData(form)) {
            if (String(value).trim() !== '') params.append(key, value);
        }
        const query = params.toString();
        window.location.assign(form.action + (query ? '?' + query : ''));
    },
});

Alpine.start();
