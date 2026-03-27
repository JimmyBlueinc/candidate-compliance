<template>
  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="font-semibold text-white truncate">{{ candidateName }}</div>
        <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
          {{ candidate?.specialty || 'No specialty' }}
        </div>
        <div class="mt-1 text-xs text-slate-400">
          Recruiter: {{ recruiterName }}
        </div>
      </div>
      <div class="shrink-0 flex flex-col items-end gap-1">
        <div v-if="notesCount > 0" class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-white/5 text-[10px] text-slate-400">
          <i class="pi pi-comments text-[10px]"></i>
          {{ notesCount }}
        </div>
        <div 
          class="w-2 h-2 rounded-full" 
          :class="complianceColor" 
          v-tooltip.left="'Compliance Status'"
        ></div>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <button
        v-if="hasPrev"
        type="button"
        class="px-2 py-1 rounded-full text-[10px] font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 transition-colors"
        @click="$emit('move', 'prev')"
      >
        Back
      </button>
      <button
        v-if="hasNext"
        type="button"
        class="px-2 py-1 rounded-full text-[10px] font-bold border transition-colors"
        :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
        @click="$emit('move', 'next')"
      >
        Next
      </button>
      <button
        type="button"
        class="ml-auto p-1.5 rounded-full hover:bg-white/5 text-slate-400 transition-colors"
        @click="$emit('action', 'menu')"
      >
        <i class="pi pi-ellipsis-v text-xs"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  candidate: { type: Object, required: true },
  primaryColor: { type: String, default: 'var(--p-primary-color)' },
  primarySoftBg: { type: String, default: '' },
  primarySoftBorder: { type: String, default: '' },
  hasPrev: { type: Boolean, default: false },
  hasNext: { type: Boolean, default: false }
});

defineEmits(['move', 'action']);

const candidateName = computed(() => {
  if (props.candidate.name) return props.candidate.name;
  return `${props.candidate.first_name || ''} ${props.candidate.last_name || ''}`.trim() || 'Candidate';
});

const recruiterName = computed(() => props.candidate.recruiter?.name || 'Unassigned');
const notesCount = computed(() => props.candidate.notes_count || 0);

const complianceColor = computed(() => {
  const status = props.candidate.compliance_status || 'pending';
  if (status === 'complete') return 'bg-green-500';
  if (status === 'expiring') return 'bg-yellow-500';
  if (status === 'expired') return 'bg-red-500';
  return 'bg-slate-500';
});
</script>
