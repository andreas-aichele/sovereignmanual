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

const initializeTableOfContentsScrollSpy = () => {
    const links = Array.from(document.querySelectorAll('[data-toc-link]'));
    const headings = [...new Set(links.map((link) => link.hash.slice(1)))]
        .map((id) => document.getElementById(id))
        .filter(Boolean);

    if (headings.length === 0) {
        return;
    }

    const updateActiveLink = () => {
        const offset = window.innerHeight * 0.25;
        const activeId =
            headings
                .filter(
                    (heading) => heading.getBoundingClientRect().top <= offset,
                )
                .at(-1)?.id ?? headings[0].id;

        links.forEach((link) => {
            const isActive = link.hash === `#${activeId}`;

            link.toggleAttribute('aria-current', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'location');
            }
        });
    };

    updateActiveLink();
    window.addEventListener('scroll', updateActiveLink, { passive: true });
    window.addEventListener('resize', updateActiveLink);
};

document.querySelectorAll('[data-appearance-value]').forEach((button) => {
    button.addEventListener('click', () => {
        const appearance = button.dataset.appearanceValue || 'system';

        setCookie('appearance', appearance);
        applyAppearance(appearance);
        window.location.reload();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    renderMermaidDiagrams();
    initializeTableOfContentsScrollSpy();
});
