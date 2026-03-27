<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[28px] p-6 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-xl text-white">My Profile</h2>
          <p class="text-xs text-[color:var(--p-text-muted-color)] mt-1">Complete onboarding and keep your information up to date.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          :disabled="saving || loading"
          @click="save"
        >
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>
      <div v-else class="mt-6 space-y-6">
        <div v-if="error" class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm font-semibold">
          {{ error }}
        </div>
        <div v-if="success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm font-semibold">
          {{ success }}
        </div>

        <div v-if="onboarding" class="p-4 rounded-2xl border border-white/10 bg-white/5">
          <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Profile Status</div>
          <div class="mt-2 text-sm text-slate-200">
            Status:
            <span class="font-bold" :style="{ color: onboarding.phase1_complete ? 'rgb(52,211,153)' : 'rgb(248,113,113)' }">
              {{ onboarding.phase1_complete ? 'Complete' : 'Incomplete' }}
            </span>
          </div>
          <div v-if="!onboarding.phase1_complete && onboarding.phase1_missing?.length" class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
            Missing fields: <span class="text-slate-200">{{ onboarding.phase1_missing.join(', ') }}</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="p-4 rounded-2xl border border-white/10 bg-white/5">
            <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Personal (required)</div>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">First name</div>
                <input v-model="form.first_name" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Last name</div>
                <input v-model="form.last_name" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div class="sm:col-span-2">
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Phone</div>
                <input v-model="form.phone" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div class="sm:col-span-2">
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Address line 1</div>
                <input v-model="form.address_line1" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div class="sm:col-span-2">
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Address line 2</div>
                <input v-model="form.address_line2" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">City</div>
                <input v-model="form.city" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">State/Province</div>
                <input v-model="form.state" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Postal/Zip</div>
                <input v-model="form.postal_code" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Country</div>
                <input v-model="form.country" type="text" class="mt-2 w-full rounded-2xl px-3 py-2.5 text-[13px] bg-white/5 border border-white/10 text-white" />
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="px-4 py-2.5 rounded-2xl text-[11px] font-black tracking-widest uppercase border transition-colors"
            :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, apiPut } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');

const candidate = ref(null);
const onboarding = ref(null);

const form = ref({
  first_name: '',
  last_name: '',
  phone: '',
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
});

function hydrateFromCandidate(c) {
  form.value.first_name = c?.first_name || '';
  form.value.last_name = c?.last_name || '';
  form.value.phone = c?.phone || '';
  form.value.address_line1 = c?.address_line1 || '';
  form.value.address_line2 = c?.address_line2 || '';
  form.value.city = c?.city || '';
  form.value.state = c?.state || '';
  form.value.postal_code = c?.postal_code || '';
  form.value.country = c?.country || '';
}

async function load() {
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    const res = await apiGet('/v1/portal/profile');
    const payload = res?.data || res;
    candidate.value = payload?.candidate || null;
    onboarding.value = payload?.onboarding || null;
    hydrateFromCandidate(candidate.value);
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load profile.';
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (saving.value) return;
  saving.value = true;
  error.value = '';
  success.value = '';
  try {
    const res = await apiPut('/v1/portal/profile', form.value);
    const payload = res?.data || res;
    candidate.value = payload?.candidate || candidate.value;
    onboarding.value = payload?.onboarding || onboarding.value;
    success.value = 'Saved successfully.';
    setTimeout(() => {
      success.value = '';
    }, 2500);
  } catch (e) {
    error.value = e?.response?.data?.message || 'Save failed.';
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  await brand.load();
  await load();
});
</script>
