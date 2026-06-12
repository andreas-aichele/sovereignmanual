const renderMermaidDiagrams = () => {
    if (!document.querySelector('.mermaid')) {
        return;
    }

    import('mermaid')
        .then(({ default: mermaid }) => {
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'strict',
                theme: 'base',
                themeVariables: {
                    background: 'transparent',
                    primaryColor: '#1c1630',
                    primaryTextColor: '#f5f1ff',
                    primaryBorderColor: '#26d9ff',
                    lineColor: '#f7931a',
                    secondaryColor: '#241337',
                    tertiaryColor: '#130821',
                },
            });

            return mermaid.run({ querySelector: '.mermaid' });
        })
        .catch((error) => {
            window.console.warn('Unable to render Mermaid diagram.', error);
        });
};

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

document.addEventListener('DOMContentLoaded', renderMermaidDiagrams);
