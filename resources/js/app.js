const themeVariable = (name) =>
    window
        .getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

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
                    primaryColor: themeVariable('--editorial-mermaid-primary'),
                    primaryTextColor: themeVariable('--editorial-mermaid-text'),
                    primaryBorderColor: themeVariable(
                        '--editorial-mermaid-border',
                    ),
                    lineColor: themeVariable('--editorial-mermaid-line'),
                    secondaryColor: themeVariable(
                        '--editorial-mermaid-secondary',
                    ),
                    tertiaryColor: themeVariable(
                        '--editorial-mermaid-tertiary',
                    ),
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

const isDarkAppearance = (appearance) =>
    appearance === 'dark' ||
    (appearance === 'system' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches);

const applyAppearance = (appearance) => {
    const isDark = isDarkAppearance(appearance);
    const root = document.documentElement;

    root.classList.toggle('dark', isDark);
    root.dataset.appearance = appearance;
    root.dataset.theme = isDark ? 'editorial-dark' : 'editorial-light';
    root.style.colorScheme = isDark ? 'dark' : 'light';

    document
        .querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', isDark ? '#20231f' : '#f7f3ea');

    document.querySelectorAll('[data-appearance-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isDark));
    });
};

applyAppearance(document.documentElement.dataset.appearance || 'system');

const initializeAppearanceControls = () => {
    document.querySelectorAll('[data-appearance-value]').forEach((button) => {
        button.addEventListener('click', () => {
            const appearance = button.dataset.appearanceValue || 'system';

            setCookie('appearance', appearance);
            applyAppearance(appearance);
            window.location.reload();
        });
    });

    document.querySelectorAll('[data-appearance-toggle]').forEach((button) => {
        button.setAttribute(
            'aria-pressed',
            String(document.documentElement.classList.contains('dark')),
        );

        button.addEventListener('click', () => {
            const appearance = document.documentElement.classList.contains(
                'dark',
            )
                ? 'light'
                : 'dark';

            setCookie('appearance', appearance);
            applyAppearance(appearance);
            window.location.reload();
        });
    });

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (document.documentElement.dataset.appearance === 'system') {
                applyAppearance('system');
            }
        });
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

document.addEventListener('DOMContentLoaded', () => {
    initializeAppearanceControls();
    renderMermaidDiagrams();
    initializeTableOfContentsScrollSpy();
});
