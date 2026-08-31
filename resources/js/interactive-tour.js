const MOBILE_BREAKPOINT = 640;

window.interactiveTour = (config) => ({
    tutorialKey: config.tutorialKey,
    configuredSteps: Array.isArray(config.steps) ? config.steps : [],
    canStart: Boolean(config.canStart),
    activeSteps: [],
    stepIndex: 0,
    isOpen: false,
    spotlightStyle: '',
    tooltipStyle: '',
    useMobileLayout: false,

    init() {
        this.startListener = (event) => {
            if (event.detail?.tutorialKey === this.tutorialKey) {
                this.start();
            }
        };
        this.positionListener = () => this.positionCurrentStep();
        this.keyListener = (event) => this.handleKeyboard(event);

        window.addEventListener('start-tour', this.startListener);
    },

    destroy() {
        window.removeEventListener('start-tour', this.startListener);
        this.cleanup();
    },

    get currentStep() {
        return this.activeSteps[this.stepIndex] ?? null;
    },

    get canGoBack() {
        return this.stepIndex > 0;
    },

    get isLastStep() {
        return this.stepIndex === this.activeSteps.length - 1;
    },

    get progressLabel() {
        return `${this.stepIndex + 1} dari ${this.activeSteps.length}`;
    },

    start() {
        if (! this.canStart || (window.__interactiveTourActive && window.__interactiveTourActive !== this)) {
            return;
        }

        this.activeSteps = this.configuredSteps.filter((step) => document.querySelector(step.target));

        if (this.activeSteps.length === 0) {
            this.cleanup();
            return;
        }

        window.__interactiveTourActive = this;
        this.stepIndex = 0;
        this.isOpen = true;
        window.addEventListener('resize', this.positionListener);
        window.addEventListener('scroll', this.positionListener, true);
        window.addEventListener('keydown', this.keyListener);
        this.showCurrentStep();
    },

    next() {
        if (this.isLastStep) {
            this.finish();
            return;
        }

        this.stepIndex += 1;
        this.showCurrentStep();
    },

    back() {
        if (! this.canGoBack) {
            return;
        }

        this.stepIndex -= 1;
        this.showCurrentStep();
    },

    showCurrentStep() {
        const target = document.querySelector(this.currentStep?.target);

        if (! target) {
            this.activeSteps.splice(this.stepIndex, 1);
            if (this.activeSteps.length === 0) {
                this.cleanup();
                return;
            }
            this.stepIndex = Math.min(this.stepIndex, this.activeSteps.length - 1);
            this.showCurrentStep();
            return;
        }

        target.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
        requestAnimationFrame(() => this.positionCurrentStep());
        window.setTimeout(() => this.positionCurrentStep(), 250);
    },

    positionCurrentStep() {
        if (! this.isOpen) {
            return;
        }

        const target = document.querySelector(this.currentStep?.target);
        if (! target) {
            this.showCurrentStep();
            return;
        }

        const rect = target.getBoundingClientRect();
        const padding = 8;
        this.spotlightStyle = `top:${Math.max(padding, rect.top - padding)}px;left:${Math.max(padding, rect.left - padding)}px;width:${Math.max(0, rect.width + (padding * 2))}px;height:${Math.max(0, rect.height + (padding * 2))}px;box-shadow:0 0 0 9999px rgba(15,23,42,.55);`;

        this.useMobileLayout = window.innerWidth < MOBILE_BREAKPOINT || rect.bottom + 280 > window.innerHeight;
        if (this.useMobileLayout) {
            this.tooltipStyle = '';
            return;
        }

        const tooltipWidth = Math.min(384, window.innerWidth - 24);
        const placement = this.currentStep?.placement ?? 'bottom';
        let top = rect.bottom + 16;
        let left = rect.left;

        if (placement === 'top') top = rect.top - 16 - 220;
        if (placement === 'left') {
            top = rect.top;
            left = rect.left - tooltipWidth - 16;
        }
        if (placement === 'right') {
            top = rect.top;
            left = rect.right + 16;
        }

        top = Math.max(12, Math.min(top, window.innerHeight - 220));
        left = Math.max(12, Math.min(left, window.innerWidth - tooltipWidth - 12));
        this.tooltipStyle = `top:${top}px;left:${left}px;`;
    },

    handleKeyboard(event) {
        if (! this.isOpen || event.target.matches('input, textarea, select')) {
            return;
        }

        if (event.key === 'Escape') this.dismiss();
        if (event.key === 'ArrowRight') this.next();
        if (event.key === 'ArrowLeft') this.back();
    },

    async finish() {
        this.cleanup();
        await this.$wire.finish();
        this.canStart = false;
    },

    async dismiss() {
        this.cleanup();
        await this.$wire.dismiss();
        this.canStart = false;
    },

    cleanup() {
        this.isOpen = false;
        this.activeSteps = [];
        this.stepIndex = 0;
        this.spotlightStyle = '';
        this.tooltipStyle = '';
        window.removeEventListener('resize', this.positionListener);
        window.removeEventListener('scroll', this.positionListener, true);
        window.removeEventListener('keydown', this.keyListener);
        if (window.__interactiveTourActive === this) {
            window.__interactiveTourActive = null;
        }
    },
});
