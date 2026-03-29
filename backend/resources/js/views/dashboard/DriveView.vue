<template>
  <div class="space-y-6">
    <UiPageHeader
      title="My Drive"
      subtitle="Personal file hub for upload, organization, and sharing to chat."
    >
      <template #actions>
        <Button
          label="Refresh"
          icon="pi pi-refresh"
          size="small"
          severity="secondary"
          outlined
          :loading="loading"
          @click="loadFiles"
        />
      </template>
    </UiPageHeader>

    <UiCard>
      <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Upload file</label>
          <input
            ref="fileInputRef"
            type="file"
            class="block w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
            @change="onFileSelected"
          />
          <p class="text-xs text-[color:var(--aq-muted)]">Up to 100MB per file (documents, images, and more). Share directly into chat threads.</p>
        </div>
        <div class="flex items-center justify-end gap-2">
          <Button
            label="Upload"
            icon="pi pi-upload"
            :disabled="!selectedFile"
            :loading="uploading"
            @click="uploadFile"
          />
        </div>
      </div>
    </UiCard>

    <div class="grid gap-5 lg:grid-cols-2">
      <UiCard title="My Files">
        <div v-if="ownedFiles.length === 0 && !loading" class="py-8 text-sm text-[color:var(--aq-muted)]">
          No files uploaded yet.
        </div>

        <div v-else class="space-y-3">
          <article
            v-for="file in ownedFiles"
            :key="file.id"
            class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[color:var(--aq-fg)]">{{ file.name }}</p>
                <p class="mt-1 text-xs text-[color:var(--aq-muted)]">
                  {{ formatBytes(file.size_bytes) }} · {{ file.extension || file.mime_type || 'file' }}
                </p>
                <p class="mt-1 text-[11px] text-[color:var(--aq-muted)]">Shared {{ file.shares_count || 0 }} time(s)</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <Button icon="pi pi-download" size="small" text rounded @click="downloadFile(file)" />
                <Button icon="pi pi-send" size="small" text rounded @click="openShareDialog(file)" />
                <Button icon="pi pi-trash" size="small" text rounded severity="danger" @click="removeFile(file)" />
              </div>
            </div>
          </article>
        </div>
      </UiCard>

      <UiCard title="Shared With Me">
        <div v-if="sharedFiles.length === 0 && !loading" class="py-8 text-sm text-[color:var(--aq-muted)]">
          No files shared with you yet.
        </div>

        <div v-else class="space-y-3">
          <article
            v-for="file in sharedFiles"
            :key="`shared-${file.id}-${file.shared_at || ''}`"
            class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/70 p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[color:var(--aq-fg)]">{{ file.name }}</p>
                <p class="mt-1 text-xs text-[color:var(--aq-muted)]">
                  {{ formatBytes(file.size_bytes) }} · from {{ file.shared_by?.name || 'Unknown' }}
                </p>
                <p class="mt-1 text-[11px] text-[color:var(--aq-muted)]">{{ formatDate(file.shared_at) }}</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <Button icon="pi pi-download" size="small" text rounded @click="downloadFile(file)" />
              </div>
            </div>
          </article>
        </div>
      </UiCard>
    </div>

    <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

    <Dialog
      v-model:visible="shareDialogOpen"
      modal
      header="Share File to Chat"
      :style="{ width: 'min(560px, 95vw)' }"
    >
      <div class="space-y-4">
        <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">File</div>
          <div class="mt-1 text-sm font-semibold text-[color:var(--aq-fg)]">{{ shareTarget?.name || '—' }}</div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Recipient</label>
          <Dropdown
            v-model="shareRecipientId"
            :options="recipients"
            optionLabel="name"
            optionValue="id"
            class="w-full"
            placeholder="Select recipient"
            filter
            :loading="recipientsLoading"
          >
            <template #option="{ option }">
              <div class="flex items-center justify-between gap-2">
                <span class="font-medium">{{ option.name }}</span>
                <span class="text-xs text-[color:var(--aq-muted)]">{{ option.role }}</span>
              </div>
            </template>
          </Dropdown>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Optional note</label>
          <Textarea
            v-model="shareNote"
            autoResize
            rows="3"
            class="w-full"
            placeholder="Add context for recipient"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="closeShareDialog" />
          <Button
            label="Share to Chat"
            icon="pi pi-send"
            :disabled="!shareRecipientId || !shareTarget"
            :loading="sharing"
            @click="shareFile"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiDelete, apiGet, apiPost } from '../../lib/api';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Message from 'primevue/message';
import Textarea from 'primevue/textarea';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';

const loading = ref(false);
const uploading = ref(false);
const sharing = ref(false);
const errorMessage = ref('');

const fileInputRef = ref(null);
const selectedFile = ref(null);

const ownedFiles = ref([]);
const sharedFiles = ref([]);

const shareDialogOpen = ref(false);
const shareTarget = ref(null);
const shareRecipientId = ref(null);
const shareNote = ref('');
const recipients = ref([]);
const recipientsLoading = ref(false);

async function loadFiles() {
  try {
    loading.value = true;
    errorMessage.value = '';
    const res = await apiGet('/drive/files');
    const payload = res?.data?.owned_files
      ? res.data
      : (res?.data || res || {});
    ownedFiles.value = Array.isArray(payload?.owned_files) ? payload.owned_files : [];
    sharedFiles.value = Array.isArray(payload?.shared_with_me) ? payload.shared_with_me : [];
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Failed to load drive files.';
    ownedFiles.value = [];
    sharedFiles.value = [];
  } finally {
    loading.value = false;
  }
}

function onFileSelected(event) {
  const next = event?.target?.files?.[0] || null;
  selectedFile.value = next;
}

async function uploadFile() {
  if (!selectedFile.value) return;
  try {
    uploading.value = true;
    errorMessage.value = '';
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    await apiPost('/drive/files', formData);
    if (fileInputRef.value) fileInputRef.value.value = '';
    selectedFile.value = null;
    await loadFiles();
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Upload failed. Please try again.';
  } finally {
    uploading.value = false;
  }
}

function downloadFile(file) {
  if (!file?.download_url) return;
  window.open(file.download_url, '_blank');
}

async function removeFile(file) {
  if (!file?.id) return;
  const ok = window.confirm(`Delete ${file.name}?`);
  if (!ok) return;
  try {
    errorMessage.value = '';
    await apiDelete(`/drive/files/${encodeURIComponent(String(file.id))}`);
    await loadFiles();
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Failed to delete file.';
  }
}

async function loadRecipients() {
  try {
    recipientsLoading.value = true;
    errorMessage.value = '';
    const res = await apiGet('/drive/recipients');
    const rows = res?.data?.length ? res.data : (res?.data || res);
    recipients.value = Array.isArray(rows) ? rows : [];
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Failed to load recipients.';
    recipients.value = [];
  } finally {
    recipientsLoading.value = false;
  }
}

async function openShareDialog(file) {
  shareTarget.value = file;
  shareRecipientId.value = null;
  shareNote.value = '';
  shareDialogOpen.value = true;
  await loadRecipients();
}

function closeShareDialog() {
  shareDialogOpen.value = false;
  shareTarget.value = null;
  shareRecipientId.value = null;
  shareNote.value = '';
}

async function shareFile() {
  if (!shareTarget.value?.id || !shareRecipientId.value) return;
  try {
    sharing.value = true;
    errorMessage.value = '';
    await apiPost(`/drive/files/${encodeURIComponent(String(shareTarget.value.id))}/share`, {
      recipient_id: shareRecipientId.value,
      note: shareNote.value || null,
    });
    closeShareDialog();
    await loadFiles();
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Failed to share file.';
  } finally {
    sharing.value = false;
  }
}

function formatBytes(bytes) {
  const n = Number(bytes || 0);
  if (!Number.isFinite(n) || n <= 0) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const idx = Math.min(units.length - 1, Math.floor(Math.log(n) / Math.log(1024)));
  const val = n / Math.pow(1024, idx);
  return `${val.toFixed(val >= 10 || idx === 0 ? 0 : 1)} ${units[idx]}`;
}

function formatDate(value) {
  if (!value) return 'Unknown';
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? 'Unknown' : d.toLocaleString();
}

onMounted(loadFiles);
</script>
