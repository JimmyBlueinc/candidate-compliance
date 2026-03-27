<template>
  <div class="space-y-8">
    <UiPageHeader 
      title="Compliance Hub" 
      subtitle="Global health and status monitoring for all facilities and personnel"
    />

    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-8">
        <UiCard class="h-full min-h-[400px] flex flex-col justify-center relative overflow-hidden group">
          <div class="absolute top-0 left-0 z-20 m-6">
            <UiBadge variant="outline" class="bg-red-500/10 text-red-400 border-red-500/20">
              SYSTEM STATUS: OPTIMAL
            </UiBadge>
          </div>

          <div class="flex flex-col md:flex-row h-full items-center gap-10">
            <div class="flex-1 text-center md:text-left">
              <h2 class="text-4xl font-display mb-3 text-white tracking-tight leading-tight">Global Compliance Health</h2>
              <p class="text-slate-400 text-base max-w-sm mb-10 leading-relaxed mx-auto md:mx-0">
                Real-time monitoring across all active healthcare facilities and personnel.
              </p>

              <div class="grid grid-cols-2 gap-6 mb-10">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 justify-center md:justify-start">
                    <div
                      class="w-2 h-2 rounded-full"
                      :style="{ backgroundColor: primaryColor, boxShadow: primaryDotShadow }"
                    ></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Credentialed</span>
                  </div>
                  <p class="text-2xl font-bold text-white">8,432</p>
                </div>
                <div class="space-y-1">
                  <div class="flex items-center gap-2 justify-center md:justify-start">
                    <div class="w-2 h-2 rounded-full bg-slate-700"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending</span>
                  </div>
                  <p class="text-2xl font-bold text-white">412</p>
                </div>
              </div>

              <div class="flex items-end gap-4 justify-center md:justify-start">
                <span class="text-6xl font-bold text-white tracking-tighter">95.3%</span>
                <div class="flex items-center gap-1 text-green-500 text-sm font-bold mb-2">
                  <TrendingUp class="w-4 h-4" />
                  <span>+0.4%</span>
                </div>
              </div>
            </div>

            <div class="relative w-72 h-72 flex items-center justify-center shrink-0">
              <svg class="w-full h-full transform -rotate-90">
                <circle
                  class="text-white/[0.03]"
                  cx="50%" cy="50%" fill="transparent" r="110" stroke="currentColor" stroke-width="20"
                ></circle>
                <circle
                  :style="{ color: primaryColor, filter: primaryRingShadow }"
                  cx="50%" cy="50%" fill="transparent" r="110"
                  stroke="currentColor" stroke-dasharray="691" stroke-dashoffset="173"
                  stroke-width="20" stroke-linecap="round"
                ></circle>
              </svg>
              <div class="absolute flex flex-col items-center">
                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 mb-3 shadow-2xl">
                  <ShieldCheck 
                    class="w-10 h-10"
                    :style="{ color: primaryColor, filter: primaryIconShadow }"
                  />
                </div>
                <span class="text-[10px] uppercase tracking-[0.2em] text-slate-500 font-black">Score</span>
              </div>
            </div>
          </div>
        </UiCard>
      </div>

      <div class="col-span-12 lg:col-span-4 space-y-8">
        <UiCard title="Critical Tasks" subtitle="Items requiring immediate action">
          <template #header-right>
            <UiBadge variant="outline" class="bg-red-500/10 text-red-500 border-red-500/20">12 ALERTING</UiBadge>
          </template>

          <div class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar max-h-[300px]">
            <div
              v-for="item in criticalActions"
              :key="item.id"
              class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all cursor-pointer group relative overflow-hidden"
            >
              <div v-if="item.urgent" class="absolute left-0 top-0 bottom-0 w-1 bg-red-500/50"></div>

              <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-bold text-slate-200 group-hover:text-white transition-colors">{{ item.title }}</span>
                <UiBadge variant="outline" :class="item.urgent ? 'text-red-400 border-red-500/20' : 'text-slate-500 border-white/10'">
                  {{ item.deadline }}
                </UiBadge>
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-500">
                <User class="w-3 h-3 opacity-50" />
                <span class="truncate">{{ item.assignee }}</span>
                <span class="opacity-30">•</span>
                <span class="truncate">{{ item.facility }}</span>
              </div>
            </div>
          </div>

          <template #footer>
            <Button label="View All Alerts" icon="pi pi-bell" class="w-full" text size="small" />
          </template>
        </UiCard>

        <RecentIntakes />
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="flex flex-col items-center gap-4">
        <RefreshCw class="w-8 h-8 text-slate-500 animate-spin" />
        <span class="text-sm text-slate-400">Loading compliance data...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, normalizeApiList } from '../../lib/api';
import RecentIntakes from '../../components/dashboard/RecentIntakes.vue';
import { useBrandStore } from '../../stores/brand';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import Button from 'primevue/button';
import { ShieldCheck, TrendingUp, User, RefreshCw } from 'lucide-vue-next';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);
const primaryDotShadow = computed(() => `0 0 8px color-mix(in srgb, ${primaryColor.value} 55%, transparent)`);
const primaryRingShadow = computed(() => `drop-shadow(0 0 20px color-mix(in srgb, ${primaryColor.value} 40%, transparent))`);
const primaryIconShadow = computed(() => `drop-shadow(0 0 8px color-mix(in srgb, ${primaryColor.value} 55%, transparent))`);

const loading = ref(true);
const credentials = ref([]);
const backgroundChecks = ref([]);
const healthRecords = ref([]);
const workAuths = ref([]);

onMounted(async () => {
    loading.value = true;
    try {
        const results = await Promise.allSettled([
            apiGet('/credentials'),
            apiGet('/background-checks'),
            apiGet('/health-records'),
            apiGet('/work-authorizations'),
        ]);

        const getVal = (i) => (results[i].status === 'fulfilled' ? normalizeApiList(results[i].value) : []);

        credentials.value = getVal(0);
        backgroundChecks.value = getVal(1);
        healthRecords.value = getVal(2);
        workAuths.value = getVal(3);
    } finally {
        loading.value = false;
    }
});

const criticalActions = computed(() => {
    const items = [];

    const now = new Date();
    const in30Days = new Date(now);
    in30Days.setDate(in30Days.getDate() + 30);

    const expiringCreds = credentials.value
        .filter((c) => c?.expiry_date)
        .map((c) => {
            const expiry = new Date(String(c.expiry_date));
            return { c, expiry };
        })
        .filter(({ expiry }) => !Number.isNaN(expiry.getTime()) && expiry >= now && expiry <= in30Days)
        .sort((a, b) => a.expiry.getTime() - b.expiry.getTime())
        .slice(0, 6);

    for (const { c, expiry } of expiringCreds) {
        const diffDays = Math.max(0, Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)));
        items.push({
            id: `cred-${c.id}`,
            title: c.credential_type ? `${c.credential_type} Expiry` : 'Credential Expiry',
            deadline: `${diffDays} days left`,
            assignee: c.candidate_name || 'Unknown',
            facility: c.facility || '—',
            urgent: diffDays <= 7,
        });
    }

    const addGeneric = (arr, prefix, title) => {
        for (const row of arr.slice(0, Math.max(0, 6 - items.length))) {
            const id = row?.id ?? Math.random().toString(36).slice(2);
            items.push({
                id: `${prefix}-${id}`,
                title,
                deadline: 'Pending',
                assignee: row?.candidate_name || row?.user?.name || 'Admin',
                facility: row?.facility || '—',
            });
        }
    };

    if (items.length < 6) addGeneric(backgroundChecks.value, 'bg', 'Background Check');
    if (items.length < 6) addGeneric(healthRecords.value, 'health', 'Health Record');
    if (items.length < 6) addGeneric(workAuths.value, 'wa', 'Work Authorization');

    return items.slice(0, 6);
});
</script>
