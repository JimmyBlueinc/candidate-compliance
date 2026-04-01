<template>
  <div class="space-y-6">
    <UiPageHeader
      title="HR Tasks"
      subtitle="Assign follow-ups, set reminders, and keep hiring operations moving."
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <Button label="Refresh" icon="pi pi-refresh" size="small" :loading="loading" @click="refresh" />
          <Button label="New Task" icon="pi pi-plus" size="small" @click="openCreate" />
        </div>
      </template>
    </UiPageHeader>

    <UiCard>
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Status</div>
          <select v-model="filters.status" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white">
            <option value="">All</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">View</div>
          <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-200">
            <input v-model="filters.mine" type="checkbox" />
            Assigned to me only
          </label>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Open</div>
          <div class="mt-2 text-2xl font-semibold text-white">{{ stats.open }}</div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Overdue</div>
          <div class="mt-2 text-2xl font-semibold text-rose-300">{{ stats.overdue }}</div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Due Today</div>
          <div class="mt-2 text-2xl font-semibold text-amber-300">{{ stats.due_today }}</div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-3">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Completed (7d)</div>
          <div class="mt-2 text-2xl font-semibold text-emerald-300">{{ stats.completed_last_7d }}</div>
        </div>
      </div>
    </UiCard>

    <UiCard>
      <div v-if="loading && tasks.length === 0" class="py-12 text-center text-sm text-[color:var(--p-text-muted-color)]">Loading tasks...</div>
      <div v-else-if="tasks.length === 0" class="py-12 text-center text-sm text-[color:var(--p-text-muted-color)]">No tasks found.</div>
      <div v-else class="space-y-3">
        <div v-for="task in tasks" :key="task.id" class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-white break-words">{{ task.title }}</div>
              <div class="mt-1 text-xs text-slate-300">
                Assignee: {{ task.assignee?.name || 'Unknown' }}
                <span class="opacity-40">•</span>
                Priority: <span class="uppercase">{{ task.priority || 'medium' }}</span>
                <template v-if="task.recurrence && task.recurrence !== 'none'">
                  <span class="opacity-40">•</span>
                  Recurs: {{ task.recurrence }} ({{ task.recurrence_interval || 1 }})
                </template>
              </div>
              <div v-if="task.description" class="mt-2 text-xs text-[color:var(--p-text-muted-color)] break-words">
                {{ task.description }}
              </div>
              <div class="mt-2 text-[11px] text-[color:var(--p-text-muted-color)]">
                Due: {{ formatDateTime(task.due_at) }}
                <span v-if="task.candidate">
                  <span class="opacity-40">•</span>
                  Candidate: {{ task.candidate?.name || `${task.candidate?.first_name || ''} ${task.candidate?.last_name || ''}`.trim() }}
                </span>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <select
                class="rounded-lg bg-white/5 border border-white/10 px-2 py-1 text-xs text-white"
                :value="task.status"
                :disabled="actingId === task.id"
                @change="updateStatus(task, $event)"
              >
                <option value="open">open</option>
                <option value="in_progress">in_progress</option>
                <option value="completed">completed</option>
                <option value="cancelled">cancelled</option>
              </select>
              <Button
                label="Delete"
                icon="pi pi-trash"
                size="small"
                severity="danger"
                text
                :disabled="actingId === task.id"
                @click="removeTask(task)"
              />
            </div>
          </div>
        </div>
      </div>
    </UiCard>

    <Dialog v-model:visible="createOpen" modal header="Create HR Task" :style="{ width: 'min(720px, 96vw)' }">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Title</div>
            <input v-model="form.title" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white" />
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Assign To</div>
            <select v-model="form.assigned_to_user_id" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white">
              <option value="">Select assignee</option>
              <option v-for="r in recruiters" :key="r.id" :value="String(r.id)">{{ r.name }} ({{ r.role }})</option>
            </select>
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Candidate ID (optional)</div>
            <input v-model="form.candidate_id" inputmode="numeric" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white" />
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Priority</div>
            <select v-model="form.priority" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white">
              <option value="low">low</option>
              <option value="medium">medium</option>
              <option value="high">high</option>
              <option value="urgent">urgent</option>
            </select>
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Due At</div>
            <input v-model="form.due_at" type="datetime-local" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white" />
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Recurrence</div>
            <select v-model="form.recurrence" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white">
              <option value="none">none</option>
              <option value="daily">daily</option>
              <option value="weekly">weekly</option>
              <option value="monthly">monthly</option>
            </select>
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Recurrence Interval</div>
            <input v-model.number="form.recurrence_interval" type="number" min="1" max="52" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white" />
          </div>
          <div>
            <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Remind At</div>
            <input v-model="form.remind_at" type="datetime-local" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white" />
          </div>
        </div>
        <div>
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Description</div>
          <textarea v-model="form.description" rows="4" class="mt-2 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white"></textarea>
        </div>
        <div v-if="createError" class="text-sm text-rose-400">{{ createError }}</div>
        <div class="flex items-center justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined size="small" @click="createOpen = false" />
          <Button label="Create Task" icon="pi pi-check" size="small" :loading="creating" @click="createTask" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { apiDelete, apiGet, apiPost, apiPut, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const loading = ref(false);
const tasks = ref([]);
const actingId = ref(null);
const stats = ref({
  open: 0,
  overdue: 0,
  due_today: 0,
  completed_last_7d: 0,
});

const filters = reactive({
  status: '',
  mine: true,
});

const recruiters = ref([]);
const createOpen = ref(false);
const creating = ref(false);
const createError = ref('');
const form = reactive({
  title: '',
  description: '',
  assigned_to_user_id: '',
  candidate_id: '',
  priority: 'medium',
  recurrence: 'none',
  recurrence_interval: 1,
  due_at: '',
  remind_at: '',
});

const openCount = computed(() => tasks.value.filter((t) => ['open', 'in_progress'].includes(String(t.status))).length);
const overdueCount = computed(() => tasks.value.filter((t) => {
  if (!['open', 'in_progress'].includes(String(t.status))) return false;
  if (!t.due_at) return false;
  const d = new Date(t.due_at);
  return !Number.isNaN(d.getTime()) && d.getTime() < Date.now();
}).length);

async function refresh() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.status) params.set('status', filters.status);
    if (filters.mine) params.set('mine', '1');
    const res = await apiGet(`/v1/recruiter-tasks?${params.toString()}`);
    tasks.value = normalizeApiList(res);
  } catch {
    tasks.value = [];
  } finally {
    loading.value = false;
  }
  await loadStats();
}

async function loadStats() {
  try {
    const params = new URLSearchParams();
    if (filters.mine) params.set('mine', '1');
    const res = await apiGet(`/v1/recruiter-tasks/stats?${params.toString()}`);
    const payload = res?.data || res || {};
    stats.value = {
      open: Number(payload.open || 0),
      overdue: Number(payload.overdue || 0),
      due_today: Number(payload.due_today || 0),
      completed_last_7d: Number(payload.completed_last_7d || 0),
    };
  } catch {
    stats.value = {
      open: openCount.value,
      overdue: overdueCount.value,
      due_today: 0,
      completed_last_7d: 0,
    };
  }
}

async function loadRecruiters() {
  try {
    const res = await apiGet('/v1/org/recruiters');
    recruiters.value = normalizeApiList(res);
  } catch {
    recruiters.value = [];
  }
}

function openCreate() {
  createError.value = '';
  createOpen.value = true;
  if (recruiters.value.length === 0) {
    loadRecruiters();
  }
}

async function createTask() {
  if (!form.title.trim() || !form.assigned_to_user_id) return;
  creating.value = true;
  createError.value = '';
  try {
    await apiPost('/v1/recruiter-tasks', {
      title: form.title.trim(),
      description: form.description.trim() || null,
      assigned_to_user_id: Number(form.assigned_to_user_id),
      candidate_id: form.candidate_id ? Number(form.candidate_id) : null,
      priority: form.priority || 'medium',
      recurrence: form.recurrence || 'none',
      recurrence_interval: Number(form.recurrence_interval || 1),
      due_at: form.due_at ? new Date(form.due_at).toISOString() : null,
      remind_at: form.remind_at ? new Date(form.remind_at).toISOString() : null,
    });
    createOpen.value = false;
    form.title = '';
    form.description = '';
    form.assigned_to_user_id = '';
    form.candidate_id = '';
    form.priority = 'medium';
    form.recurrence = 'none';
    form.recurrence_interval = 1;
    form.due_at = '';
    form.remind_at = '';
    await refresh();
  } catch (e) {
    createError.value = e?.response?.data?.message || e?.message || 'Failed to create task.';
  } finally {
    creating.value = false;
  }
}

async function updateStatus(task, event) {
  const next = String(event?.target?.value || '');
  if (!task?.id || !next) return;
  actingId.value = task.id;
  try {
    await apiPut(`/v1/recruiter-tasks/${task.id}`, { status: next });
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function removeTask(task) {
  if (!task?.id) return;
  if (!window.confirm('Delete this task?')) return;
  actingId.value = task.id;
  try {
    await apiDelete(`/v1/recruiter-tasks/${task.id}`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

function formatDateTime(value) {
  if (!value) return 'No due date';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleString();
}

watch(() => filters.status, refresh);
watch(() => filters.mine, async () => {
  await Promise.all([refresh(), loadStats()]);
});

onMounted(async () => {
  await Promise.all([refresh(), loadRecruiters(), loadStats()]);
});
</script>

