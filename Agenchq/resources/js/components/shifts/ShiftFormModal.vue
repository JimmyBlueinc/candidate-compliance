<template>
  <Dialog 
    v-model:visible="visible" 
    modal 
    :header="isEdit ? 'Edit Shift' : 'Create Shift'" 
    :style="{ width: 'min(500px, 96vw)' }"
    @hide="$emit('close')"
  >
    <div class="space-y-4 pt-2">
      <div class="flex flex-col gap-1.5">
        <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Operational Placement (Assignment)</label>
        <Dropdown
          v-model="form.assignment_id"
          :options="assignments"
          optionLabel="label"
          optionValue="id"
          placeholder="Select an active assignment"
          class="w-full"
        />
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Shift Template</label>
        <Dropdown
          v-model="form.shift_template_id"
          :options="templates"
          optionLabel="name"
          optionValue="id"
          placeholder="Select a template"
          class="w-full"
        />
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Date</label>
        <DatePicker v-model="form.date" class="w-full" dateFormat="yy-mm-dd" />
      </div>

      <div class="flex items-center justify-end gap-2 pt-4">
        <button
          type="button"
          class="px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 transition-colors"
          @click="$emit('close')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
          :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
          :disabled="loading"
          @click="handleSubmit"
        >
          {{ loading ? 'Saving...' : (isEdit ? 'Update Shift' : 'Create Shift') }}
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
  show: Boolean,
  loading: Boolean,
  isEdit: Boolean,
  initialData: Object,
  assignments: { type: Array, default: () => [] },
  templates: { type: Array, default: () => [] },
  primaryColor: String
});

const emit = defineEmits(['close', 'submit']);

const visible = ref(props.show);
const form = ref({
  assignment_id: null,
  shift_template_id: null,
  date: null
});

const assignments = ref([]);
const templates = ref([]);

watch(() => props.show, (val) => {
  visible.value = val;
});

watch(() => props.initialData, (val) => {
  if (val) {
    form.value = { ...val };
  } else {
    form.value = {
      assignment_id: null,
      shift_template_id: null,
      date: null
    };
  }
}, { immediate: true });

watch(() => props.assignments, (val) => {
  assignments.value = Array.isArray(val) ? val : [];
}, { immediate: true });

watch(() => props.templates, (val) => {
  templates.value = Array.isArray(val) ? val : [];
}, { immediate: true });

function handleSubmit() {
  emit('submit', { ...form.value });
}
</script>
