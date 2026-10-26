document.querySelectorAll('input[data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const file = input.files[0];
        const error = input.parentElement.querySelector('.field-error');
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 3000000) {
            input.value = '';
            if (error) error.textContent = 'Choose a JPG, PNG, or WebP under 3 MB.';
            return;
        }
        if (error) error.textContent = '';
        const avatar = document.getElementById(input.dataset.preview);
        if (avatar) {
            let image = avatar.querySelector('img');
            if (!image) {
                image = document.createElement('img');
                image.alt = '';
                avatar.appendChild(image);
            }
            image.src = URL.createObjectURL(file);
            image.onerror = () => image.remove();
        }
    });
});
document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.submitter?.formNoValidate) return;
        let valid = true;
        form.querySelectorAll('input, textarea, select').forEach((field) => {
            const error = field.parentElement.querySelector('.field-error');
            let message = field.validity.valid ? '' : field.validationMessage;
            if (field.dataset.match && field.value !== form.elements[field.dataset.match]?.value) message = 'Passwords do not match.';
            if (error) error.textContent = message;
            if (message) valid = false;
        });
        if (!valid) event.preventDefault();
    });
});

async function reverseLookupInBrowser(latitude, longitude) {
    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=' + encodeURIComponent(latitude) + '&lon=' + encodeURIComponent(longitude);
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    const result = await response.json();
    if (!response.ok || !result.display_name) throw new Error('We could not convert this location into an address. Enter your address manually.');
    return result.display_name;
}

document.querySelectorAll('[data-use-location]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('form');
        const address = form?.querySelector('[name="address_line"]');
        const status = form?.querySelector('[data-location-status]');
        if (!address || !status) return;
        if (!navigator.geolocation) {
            status.textContent = 'Location is not supported by this browser. Enter your address manually.';
            return;
        }

        button.disabled = true;
        status.textContent = 'Finding your location...';
        navigator.geolocation.getCurrentPosition(async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            try {
                const response = await fetch('location-lookup.php?lat=' + encodeURIComponent(latitude) + '&lon=' + encodeURIComponent(longitude), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                const result = await response.json().catch(() => ({}));
                const resolvedAddress = response.ok && result.address
                    ? result.address
                    : await reverseLookupInBrowser(latitude, longitude);
                address.value = resolvedAddress;
                address.dispatchEvent(new Event('input', { bubbles: true }));
                status.textContent = 'Location added as a draft. Check the address and add house or flat details before saving.';
            } catch (error) {
                status.textContent = error.message || 'Location found, but the address could not be filled. Add your address manually.';
            } finally {
                button.disabled = false;
            }
        }, (error) => {
            const message = error.code === error.PERMISSION_DENIED ? 'Location permission was not granted. Enter your address manually.' : 'Your location could not be determined. Enter your address manually.';
            status.textContent = message;
            button.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });
});
