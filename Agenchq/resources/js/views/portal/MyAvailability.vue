<template>
  <div class="space-y-6">
    <div class="aq-card p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">My Availability</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Declare your recurring weekly availability for scheduling.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 transition-colors"
          @click="refresh"
        >
          <i class="pi pi-refresh mr-1"></i>
          Refresh
        </button>
      </div>

      <div class="mt-6 aq-card p-5">
        <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Add window</div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
          <div>
            <label class="block text-xs text-slate-300 mb-1">Day</label>
            <select v-model.number="form.day_of_week" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm">
              <option v-for="d in days" :key="d.value" :value="d.value">{{ d.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs text-slate-300 mb-1">Start</label>
            <input v-model="form.start_time" type="time" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm" />
          </div>

          <div>
            <label class="block text-xs text-slate-300 mb-1">End</label>
            <input v-model="form.end_time" type="time" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm" />
          </div>

          <div>
            <label class="block text-xs text-slate-300 mb-1">Type</label>
            <select v-model="form.is_available" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm">
              <option :value="true">Available</option>
              <option :value="false">Unavailable</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
          <div v-if="error" class="text-xs text-red-400">{{ error }}</div>
          <div class="flex items-center gap-2 ml-auto">
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-slate-200 hover:bg-white/10 transition-colors"
              @click="reset"
            >
              Reset
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-green-500 text-white hover:bg-green-600 transition-colors"
              :disabled="saving"
              @click="createWindow"
            >
              {{ saving ? 'Saving...' : 'Add' }}
            </button>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="i in 4" :key="i" class="h-24 rounded-2xl bg-white/5 animate-pulse"></div>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="w in windows"
            :key="w.id"
            class="aq-card p-5"
          >
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
              <div>
                <label class="block text-xs text-slate-300 mb-1">Day</label>
                <select v-model.number="w.day_of_week" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm">
                  <option v-for="d in days" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs text-slate-300 mb-1">Start</label>
                <input v-model="w.start_time" type="time" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm" />
              </div>

              <div>
                <label class="block text-xs text-slate-300 mb-1">End</label>
                <input v-model="w.end_time" type="time" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm" />
              </div>

              <div>
                <label class="block text-xs text-slate-300 mb-1">Type</label>
                <select v-model="w.is_available" class="w-full px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm">
                  <option :value="true">Available</option>
                  <option :value="false">Unavailable</option>
                </select>
              </div>

              <div class="flex items-center gap-2 justify-end">
                <button
                  type="button"
                  class="px-3 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-slate-200 hover:bg-white/10 transition-colors"
                  :disabled="savingId === w.id"
                  @click="updateWindow(w)"
                >
                  {{ savingId === w.id ? 'Saving...' : 'Save' }}
                </button>
                <button
                  type="button"
                  class="px-3 py-2 rounded-xl text-xs font-bold bg-red-500/15 border border-red-500/30 text-red-300 hover:bg-red-500/25 transition-colors"
                  :disabled="savingId === w.id"
                  @click="deleteWindow(w)"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>

          <div v-if="windows.length === 0" class="py-16 text-center text-slate-400">
            <span class="material-symbols-outlined text-4xl text-white/10 mb-3">calendar_month</span>
            <p class="text-sm">No recurring availability added yet.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';

const days = [
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
  { value: 7, label: 'Sunday' }
];

const windows = ref([]);
const loading = ref(false);
const saving = ref(false);
const savingId = ref(null);
const error = ref('');

const form = ref({
  day_of_week: 1,
  start_time: '07:00',
  end_time: '15:00',
  is_available: true
});

function normalizeWindows(payload) {
  const list = Array.isArray(payload?.data?.windows) ? payload.data.windows : (Array.isArray(payload?.windows) ? payload.windows : []);
  return list.map((w) => ({
    id: w.id,
    day_of_week: Number(w.day_of_week),
    start_time: String(w.start_time || '').slice(0, 5),
    end_time: String(w.end_time || '').slice(0, 5),
    is_available: Boolean(w.is_available)
  }));
}

function reset() {
  form.value = {
    day_of_week: 1,
    start_time: '07:00',
    end_time: '15:00',
    is_available: true
  };
  error.value = '';
}

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/portal/availability');
    windows.value = normalizeWindows(res);
  } finally {
    loading.value = false;
  }
}

async function createWindow() {
  saving.value = true;
  error.value = '';
  try {
    await apiPost('/v1/portal/availability', {
      day_of_week: form.value.day_of_week,
      start_time: form.value.start_time,
      end_time: form.value.end_time,
      is_available: form.value.is_available
    });
    await refresh();
    reset();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to save availability.';
  } finally {
    saving.value = false;
  }
}

async function updateWindow(w) {
  savingId.value = w.id;
  error.value = '';
  try {
    await apiPut(`/v1/portal/availability/${w.id}`, {
      day_of_week: w.day_of_week,
      start_time: w.start_time,
      end_time: w.end_time,
      is_available: w.is_available
    });
    await refresh();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to update availability.';
  } finally {
    savingId.value = null;
  }
}

async function deleteWindow(w) {
  if (!confirm('Delete this availability window?')) return;
  savingId.value = w.id;
  error.value = '';
  try {
    await apiDelete(`/v1/portal/availability/${w.id}`);
    await refresh();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to delete availability.';
  } finally {
    savingId.value = null;
  }
}

onMounted(refresh);
</script>
