/**
 * Guided tour overlay.
 *
 * A lightweight, dependency-free spotlight tour: each step highlights a
 * `[data-tour="<id>"]` element already present in the page and shows an
 * instruction card. Step content (title/text) is supplied by the Blade
 * partial, keeping translations server-side:
 *   - `init(steps)` registers the full manual tour, played by `start()`.
 *   - `startWith(steps)` plays a one-off sequence (the contextual tips
 *     fired after creating a first file / adding a first row) without
 *     touching the main tour's step list.
 *
 * When a step's target exists in both a mobile and a desktop variant
 * (e.g. the header search button), both share the same `data-tour` id;
 * `resolveTarget()` picks whichever one is actually visible.
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('tour', {
        active: false,
        stepIndex: 0,
        steps: [],
        mainSteps: [],
        rect: null,

        init(steps) {
            this.mainSteps = steps;
        },

        start() {
            this.play(this.mainSteps);
        },

        startWith(steps) {
            this.play(steps);
        },

        play(steps) {
            if (!steps.length) {
                return;
            }

            this.steps = steps;
            this.stepIndex = 0;
            this.active = true;
            requestAnimationFrame(() => this.locate());
        },

        stop() {
            this.active = false;
            this.rect = null;
        },

        next() {
            if (this.stepIndex >= this.steps.length - 1) {
                this.stop();
                return;
            }

            this.stepIndex += 1;
            this.locate();
        },

        prev() {
            if (this.stepIndex <= 0) {
                return;
            }

            this.stepIndex -= 1;
            this.locate();
        },

        get current() {
            return this.steps[this.stepIndex] ?? null;
        },

        get isLastStep() {
            return this.stepIndex === this.steps.length - 1;
        },

        resolveTarget() {
            const step = this.current;
            if (!step) {
                return null;
            }

            const candidates = document.querySelectorAll(`[data-tour="${step.target}"]`);
            for (const el of candidates) {
                if (el.offsetParent !== null) {
                    return el;
                }
            }

            return candidates[0] ?? null;
        },

        locate() {
            const el = this.resolveTarget();
            if (!el) {
                this.rect = null;
                return;
            }

            el.scrollIntoView({ block: 'center', behavior: 'smooth' });

            // Let the smooth scroll settle before measuring the final position.
            setTimeout(() => this.track(), 300);
        },

        /** Re-reads the current target's position without scrolling (used on resize/scroll). */
        track() {
            if (!this.active) {
                return;
            }

            const el = this.resolveTarget();
            if (!el) {
                this.rect = null;
                return;
            }

            const r = el.getBoundingClientRect();
            this.rect = {
                top: r.top - 6,
                left: r.left - 6,
                width: r.width + 12,
                height: r.height + 12,
            };
        },

        get spotlightStyle() {
            if (!this.rect) {
                return 'display: none;';
            }

            // Note: Alpine's x-bind:style fully replaces the element's style
            // attribute (it doesn't merge with Tailwind's ring box-shadow), so
            // the dimming + ring box-shadow must be generated here as a single
            // value rather than relying on `ring-*` utility classes.
            return `top: ${this.rect.top}px; left: ${this.rect.left}px; width: ${this.rect.width}px; height: ${this.rect.height}px; box-shadow: 0 0 0 9999px rgba(9, 9, 11, 0.75), 0 0 0 2px rgb(251 191 36);`;
        },
    });

    let trackScheduled = false;
    const scheduleTrack = () => {
        if (trackScheduled) {
            return;
        }

        trackScheduled = true;
        requestAnimationFrame(() => {
            trackScheduled = false;
            Alpine.store('tour').track();
        });
    };

    window.addEventListener('resize', scheduleTrack);
    window.addEventListener('scroll', scheduleTrack, true);
});
