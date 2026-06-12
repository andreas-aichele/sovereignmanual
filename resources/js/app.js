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

const setupBackgroundParallax = () => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let animationFrame = null;
    let motionDisabled = reducedMotion.matches;
    let pointerX = 0;
    let pointerY = 0;

    const resetBackground = () => {
        document.body.style.removeProperty('--parallax-x');
        document.body.style.removeProperty('--parallax-y');
        document.body.style.removeProperty('--parallax-grid-x');
        document.body.style.removeProperty('--parallax-grid-y');
    };

    const updateBackground = () => {
        if (motionDisabled) {
            resetBackground();
            animationFrame = null;

            return;
        }

        const scrollY = window.scrollY || 0;

        document.body.style.setProperty('--parallax-x', `${pointerX * 16}px`);
        document.body.style.setProperty(
            '--parallax-y',
            `${pointerY * 12 - scrollY * 0.018}px`,
        );
        document.body.style.setProperty(
            '--parallax-grid-x',
            `${pointerX * -10}px`,
        );
        document.body.style.setProperty(
            '--parallax-grid-y',
            `${scrollY * -0.08}px`,
        );

        animationFrame = null;
    };

    const scheduleUpdate = () => {
        if (animationFrame !== null) {
            return;
        }

        animationFrame = window.requestAnimationFrame(updateBackground);
    };

    window.addEventListener(
        'pointermove',
        (event) => {
            const viewportWidth = Math.max(window.innerWidth, 1);
            const viewportHeight = Math.max(window.innerHeight, 1);

            pointerX = event.clientX / viewportWidth - 0.5;
            pointerY = event.clientY / viewportHeight - 0.5;
            scheduleUpdate();
        },
        { passive: true },
    );
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    reducedMotion.addEventListener('change', (event) => {
        motionDisabled = event.matches;
        scheduleUpdate();
    });

    scheduleUpdate();
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

document.addEventListener('DOMContentLoaded', () => {
    renderMermaidDiagrams();
    setupBackgroundParallax();
});
