import '../css/app.css';

const setCookie = (name, value) => {
    document.cookie = `${name}=${value}; path=/; max-age=31536000; SameSite=Lax`;
};

const applyAppearance = (appearance) => {
    const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    document.documentElement.classList.toggle(
        'dark',
        appearance === 'dark' || (appearance === 'system' && prefersDark),
    );
};

document.querySelectorAll('[data-appearance-value]').forEach((button) => {
    button.addEventListener('click', () => {
        const appearance = button.dataset.appearanceValue || 'system';

        setCookie('appearance', appearance);
        applyAppearance(appearance);
        window.location.reload();
    });
});
