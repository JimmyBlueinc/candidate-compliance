import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Small reusable polling helper with pause/resume support.
 * Keeps polling behavior consistent across notifications/activity/presence.
 */
export function usePolling(task, intervalMs = 30000, options = {}) {
    const { immediate = true } = options;
    const running = ref(false);
    let timer = null;

    async function tick() {
        if (typeof task !== 'function') return;
        try {
            await task();
        } catch {
            // Polling should be resilient; callers handle their own error state.
        }
    }

    function start() {
        if (running.value) return;
        running.value = true;
        if (immediate) tick();
        timer = setInterval(tick, Math.max(5000, Number(intervalMs) || 30000));
    }

    function stop() {
        running.value = false;
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    onMounted(start);
    onUnmounted(stop);

    return {
        running,
        start,
        stop,
        tick,
    };
}
