<template>
  <div class="mt-4 rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)]/90 p-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-3">
        <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--aq-muted)]">Login Time</div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ loginTimeLabel }}</div>
      </div>
      <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-3">
        <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--aq-muted)]">Location</div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ locationLabel }}</div>
      </div>
      <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-3">
        <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--aq-muted)]">Weather</div>
        <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ weatherLabel }}</div>
      </div>
      <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-3">
        <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--aq-muted)]">Recent Activity</div>
        <div class="mt-1 text-xs text-[color:var(--aq-fg)] line-clamp-2">{{ latestActivityLabel }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const city = ref('');
const weather = ref('');
const loadingWeather = ref(false);
const activities = ref([]);

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
  return weather.value || 'Unavailable';
});
const latestActivityLabel = computed(() => {
  const first = activities.value[0];
  if (!first) return 'No tracked actions yet';
  return `${first.label} - ${new Date(first.at).toLocaleTimeString()}`;
});

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
    const weatherRes = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&current=temperature_2m,weather_code`);
    const w = await weatherRes.json();
    const temp = Number(w?.current?.temperature_2m);
    weather.value = Number.isFinite(temp) ? `${temp.toFixed(0)} C` : 'Unavailable';
  } catch {
    city.value = city.value || 'Unknown';
    weather.value = 'Unavailable';
  } finally {
    loadingWeather.value = false;
  }
}

onMounted(() => {
  loadActivities();
  loadLocationAndWeather();
});
</script>

