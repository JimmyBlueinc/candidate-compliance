<template>
  <div class="mt-4 rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)]/90 p-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <div class="rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-cyan-300">
          <Clock3 class="h-3.5 w-3.5" />
          Login Time
        </div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ loginTimeLabel }}</div>
      </div>
      <div class="rounded-xl border border-violet-500/20 bg-violet-500/5 p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-violet-300">
          <MapPin class="h-3.5 w-3.5" />
          Location
        </div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ locationLabel }}</div>
      </div>
      <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-amber-300">
          <CloudSun class="h-3.5 w-3.5" />
          Weather
        </div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ weatherLabel }}</div>
      </div>
      <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-emerald-300">
          <Activity class="h-3.5 w-3.5" />
          Recent Activity
        </div>
        <div class="mt-1 text-xs text-[color:var(--aq-fg)] line-clamp-2">{{ latestActivityLabel }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Activity, Clock3, CloudSun, MapPin } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const city = ref('');
const weather = ref('');
const weatherForecast = ref('');
const loadingWeather = ref(false);
const activities = ref([]);
let activityTimer = null;
let weatherTimer = null;

const loginTimeLabel = computed(() => {
  const raw = localStorage.getItem('auth.login_at');
  if (!raw) return 'Unknown';
  const d = new Date(raw);
  if (Number.isNaN(d.getTime())) return 'Unknown';
  return d.toLocaleString();
});

const locationLabel = computed(() => city.value || 'Resolving...');
const weatherLabel = computed(() => {
  if (loadingWeather.value) return 'Loading...';
  return [weather.value, weatherForecast.value].filter(Boolean).join(' | ') || 'Unavailable';
});
const latestActivityLabel = computed(() => {
  const first = activities.value[0];
  if (!first) return 'No tracked actions yet';
  return `${toReadableActivityLabel(first.label || first.route)} - ${new Date(first.at).toLocaleTimeString()}`;
});

function toReadableActivityLabel(value) {
  const raw = String(value || '').trim();
  if (!raw) return 'Activity';
  return raw
    .replace(/\./g, ' ')
    .replace(/_/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b\w/g, (ch) => ch.toUpperCase());
}

function activityStorageKey() {
  const userId = auth.user?.id ? String(auth.user.id) : 'anon';
  return `aq.dashboard.activity.${userId}`;
}

function loadActivities() {
  try {
    const raw = localStorage.getItem(activityStorageKey());
    const parsed = raw ? JSON.parse(raw) : [];
    activities.value = Array.isArray(parsed) ? parsed.slice(0, 5) : [];
  } catch {
    activities.value = [];
  }
}

async function loadLocationAndWeather() {
  loadingWeather.value = true;
  try {
    const geoRes = await fetch('https://ipapi.co/json/');
    const geo = await geoRes.json();
    city.value = geo?.city ? `${geo.city}${geo?.country_name ? `, ${geo.country_name}` : ''}` : 'Unknown';
    const lat = Number(geo?.latitude);
    const lon = Number(geo?.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      weather.value = 'Unavailable';
      return;
    }
    const weatherRes = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&current=temperature_2m,weather_code&daily=temperature_2m_max,temperature_2m_min&timezone=auto`);
    const w = await weatherRes.json();
    const temp = Number(w?.current?.temperature_2m);
    weather.value = Number.isFinite(temp) ? `${temp.toFixed(0)} C` : 'Unavailable';
    const hi = Number(w?.daily?.temperature_2m_max?.[0]);
    const lo = Number(w?.daily?.temperature_2m_min?.[0]);
    weatherForecast.value = Number.isFinite(hi) && Number.isFinite(lo) ? `H ${hi.toFixed(0)} / L ${lo.toFixed(0)} C` : '';
  } catch {
    city.value = city.value || 'Unknown';
    weather.value = 'Unavailable';
    weatherForecast.value = '';
  } finally {
    loadingWeather.value = false;
  }
}

onMounted(() => {
  loadActivities();
  loadLocationAndWeather();
  activityTimer = window.setInterval(loadActivities, 10000);
  weatherTimer = window.setInterval(loadLocationAndWeather, 15 * 60 * 1000);
});

onBeforeUnmount(() => {
  if (activityTimer) {
    window.clearInterval(activityTimer);
    activityTimer = null;
  }
  if (weatherTimer) {
    window.clearInterval(weatherTimer);
    weatherTimer = null;
  }
});
</script>

