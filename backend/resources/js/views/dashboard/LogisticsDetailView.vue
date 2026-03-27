<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Placement Logistics</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Housing and travel itinerary.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-xs font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10"
            @click="refresh"
          >
            Refresh
          </button>
          <RouterLink
            class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            :to="{ name: 'dashboard.logistics' }"
          >
            Back
          </RouterLink>
        </div>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>

      <div v-else class="mt-6 space-y-6">
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Placement</div>
          <div class="mt-2 font-semibold text-white">
            {{ data?.candidate?.name || 'Candidate' }}
            <span class="opacity-40">•</span>
            {{ data?.job_order?.title || 'Job Order' }}
          </div>
          <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
            Stage: {{ data?.placement?.stage || '—' }}
            <span class="opacity-40">•</span>
            Start: {{ data?.placement?.start_date || '—' }}
            <span class="opacity-40">•</span>
            Arrival: {{ data?.placement?.arrival_confirmed_at ? 'Confirmed' : 'Not confirmed' }}
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Housing</div>
                <div class="mt-1 text-sm text-slate-300">Upsert housing record for this placement.</div>
              </div>
              <button
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                :disabled="savingHousing"
                @click="saveHousing"
              >
                {{ savingHousing ? 'Saving…' : 'Save' }}
              </button>
            </div>

            <div class="mt-4 space-y-3">
              <input v-model="housing.address" type="text" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" placeholder="Address" />
              <input v-model="housing.landlord_contact" type="text" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" placeholder="Landlord contact" />
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input v-model="housing.lease_start" type="date" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" />
                <input v-model="housing.lease_end" type="date" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" />
              </div>
            </div>
          </div>

          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Travel</div>
                <div class="mt-1 text-sm text-slate-300">Add itinerary items (flight/drive/hotel).</div>
              </div>
            </div>

            <div class="mt-4 p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <select v-model="newTravel.type" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white">
                  <option value="flight">Flight</option>
                  <option value="drive">Drive</option>
                  <option value="hotel">Hotel</option>
                </select>
                <input v-model="newTravel.confirmation_number" type="text" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" placeholder="Confirmation #" />
              </div>
              <textarea v-model="newTravel.details" class="mt-3 w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" placeholder="Details (flight #, hotel name, etc)"></textarea>
              <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input v-model="newTravel.start_date" type="date" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" />
                <input v-model="newTravel.end_date" type="date" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" />
              </div>
              <button
                type="button"
                class="mt-3 w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                :disabled="addingTravel"
                @click="addTravel"
              >
                {{ addingTravel ? 'Adding…' : 'Add Travel Item' }}
              </button>
            </div>

            <div class="mt-4 space-y-3">
              <div
                v-for="t in travel"
                :key="t.id"
                class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="font-semibold text-white capitalize">{{ t.type }}</div>
                    <div v-if="t.details" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">{{ t.details }}</div>
                    <div v-if="t.confirmation_number" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Confirmation: {{ t.confirmation_number }}</div>
                    <div class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">{{ toYmd(t.start_date) }} → {{ toYmd(t.end_date) }}</div>
                  </div>
                  <button
                    type="button"
                    class="px-3 py-1.5 rounded-full text-xs font-bold border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/15"
                    :disabled="deletingId === t.id"
                    @click="delTravel(t)"
                  >
                    {{ deletingId === t.id ? 'Deleting…' : 'Delete' }}
                  </button>
                </div>
              </div>

              <div v-if="travel.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No travel items yet.</div>
            </div>
          </div>
        </div>

        <div v-if="message" class="text-sm text-[color:var(--p-text-muted-color)]">{{ message }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const route = useRoute();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const message = ref('');

const data = ref(null);
const travel = ref([]);

const savingHousing = ref(false);
const addingTravel = ref(false);
const deletingId = ref(null);

const housing = reactive({
    address: '',
    landlord_contact: '',
    lease_start: '',
    lease_end: '',
});

const newTravel = reactive({
    type: 'flight',
    details: '',
    confirmation_number: '',
    start_date: '',
    end_date: '',
});

function toYmd(v) {
    if (!v) return '—';
    return String(v).slice(0, 10);
}

function setHousingFromApi(h) {
    housing.address = h?.address || '';
    housing.landlord_contact = h?.landlord_contact || '';
    housing.lease_start = toYmd(h?.lease_start);
    housing.lease_end = toYmd(h?.lease_end);
}

async function refresh() {
    const id = route.params.id;
    if (!id) return;

    loading.value = true;
    message.value = '';
    try {
        const res = await apiGet(`/v1/logistics/placements/${id}`);
        data.value = res?.data || null;
        travel.value = Array.isArray(res?.data?.travel) ? res.data.travel : [];
        setHousingFromApi(res?.data?.housing);
    } finally {
        loading.value = false;
    }
}

async function saveHousing() {
    const id = route.params.id;
    if (!id) return;

    savingHousing.value = true;
    message.value = '';
    try {
        await apiPut(`/v1/logistics/placements/${id}/housing`, {
            address: housing.address || null,
            landlord_contact: housing.landlord_contact || null,
            lease_start: housing.lease_start || null,
            lease_end: housing.lease_end || null,
        });
        message.value = 'Housing saved.';
        await refresh();
    } finally {
        savingHousing.value = false;
    }
}

async function addTravel() {
    const id = route.params.id;
    if (!id) return;

    addingTravel.value = true;
    message.value = '';
    try {
        await apiPost(`/v1/logistics/placements/${id}/travel`, {
            type: newTravel.type,
            details: newTravel.details || null,
            confirmation_number: newTravel.confirmation_number || null,
            start_date: newTravel.start_date || null,
            end_date: newTravel.end_date || null,
        });

        newTravel.type = 'flight';
        newTravel.details = '';
        newTravel.confirmation_number = '';
        newTravel.start_date = '';
        newTravel.end_date = '';

        message.value = 'Travel item added.';
        await refresh();
    } finally {
        addingTravel.value = false;
    }
}

async function delTravel(t) {
    deletingId.value = t.id;
    message.value = '';
    try {
        await apiDelete(`/v1/logistics/travel/${t.id}`);
        message.value = 'Travel item deleted.';
        await refresh();
    } finally {
        deletingId.value = null;
    }
}

refresh();
</script>
