<div
    x-data="interactiveTour({
        tutorialKey: @js($tutorialKey),
        steps: @js($steps),
        canStart: @js($canStart),
    })"
    x-init="init()"
>
    <template x-teleport="body">
        <div x-show="isOpen" x-cloak style="display: none;">
            <div class="pointer-events-none fixed inset-0 z-[190] bg-slate-950/55" aria-hidden="true"></div>

            <div
                class="pointer-events-none fixed z-[191] rounded-2xl ring-2 ring-indigo-400 transition-[top,left,width,height] duration-150"
                :style="spotlightStyle"
                aria-hidden="true"
            ></div>

            <section
                class="fixed z-[192] w-[calc(100vw-1.5rem)] max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                :class="{ 'bottom-3 left-3': useMobileLayout }"
                :style="tooltipStyle"
                role="dialog"
                aria-modal="true"
                aria-labelledby="interactive-tour-title"
                aria-describedby="interactive-tour-description"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300" x-text="progressLabel"></p>
                        <h2 id="interactive-tour-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-white" x-text="currentStep?.title"></h2>
                    </div>
                    <button type="button" x-on:click="dismiss()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:hover:bg-slate-700 dark:hover:text-white" aria-label="Lewati tutorial" title="Lewati tutorial">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <p id="interactive-tour-description" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="currentStep?.description"></p>

                <div class="mt-5 flex items-center justify-between gap-3">
                    <button type="button" x-on:click="dismiss()" class="text-sm font-semibold text-slate-500 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-slate-400 dark:hover:text-white">
                        Lewati
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" x-on:click="back()" x-bind:disabled="!canGoBack" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                            Kembali
                        </button>
                        <button type="button" x-on:click="next()" class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800" x-text="isLastStep ? 'Selesai' : 'Lanjut'"></button>
                    </div>
                </div>
            </section>
        </div>
    </template>
</div>
