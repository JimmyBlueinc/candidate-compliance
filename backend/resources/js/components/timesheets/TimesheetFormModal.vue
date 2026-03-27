<template>
  <Dialog 
    v-model:visible="visible" 
    modal 
    header="Log Timesheet" 
    :style="{ width: 'min(600px, 96vw)' }"
    @hide="$emit('close')"
  >
    <div class="space-y-6 pt-2">
      <div class="grid grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Week Starting</label>
          <DatePicker v-model="form.week_start" class="w-full" dateFormat="yy-mm-dd" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Facility</label>
          <InputText v-model="form.facility" placeholder="Facility name" class="w-full bg-white/5 border-white/10" />
        </div>
      </div>

      <div class="border border-white/5 rounded-2xl overflow-hidden bg-white/[0.01]">
        <table class="w-full text-xs">
          <thead>
            <tr class="bg-white/5 border-b border-white/5">
              <th v-for="day in days" :key="day" class="p-2 text-center uppercase font-black tracking-widest text-[color:var(--p-text-muted-color)]">
                {{ day }}
              </th>
              <th class="p-2 text-center uppercase font-black tracking-widest text-white">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td v-for="(val, idx) in form.hours" :key="idx" class="p-1">
                <InputText 
                  v-model="form.hours[idx]" 
                  class="w-full text-center bg-transparent border-transparent focus:bg-white/5" 
                  placeholder="0"
                />
              </td>
              <td class="p-2 text-center font-bold text-white text-sm">
                {{ totalHours }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Notes</label>
        <Textarea v-model="form.notes" rows="2" class="w-full bg-white/5 border-white/10" placeholder="Optional notes..." />
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
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
          {{ loading ? 'Saving...' : 'Save Timesheet' }}
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';

const props = defineProps({
  show: Boolean,
  loading: Boolean,
  primaryColor: String
});

const emit = defineEmits(['close', 'submit']);

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const visible = ref(props.show);
const form = ref({
  week_start: null,
  facility: '',
  hours: [0, 0, 0, 0, 0, 0, 0],
  notes: ''
});

const totalHours = computed(() => {
  return form.value.hours.reduce((acc, curr) => acc + (parseFloat(curr) || 0), 0);
});

watch(() => props.show, (val) => {
  visible.value = val;
});

function handleSubmit() {
  emit('submit', { ...form.value, total_hours: totalHours.value });
}
</script>
