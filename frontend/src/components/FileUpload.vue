<template>
  <v-card class="mb-6" variant="outlined">
    <v-card-text>
      <div
        class="drop-zone"
        :class="{ 'drop-zone--active': isDragging }"
        @dragenter.prevent="onDragEnter"
        @dragover.prevent
        @dragleave="onDragLeave"
        @drop.prevent="onDrop"
      >
        <div v-if="isDragging" class="drop-zone__overlay">
          <v-icon size="48" color="primary">mdi-file-upload-outline</v-icon>
          <div class="text-body-1 text-primary mt-2">Отпустите файл для загрузки</div>
        </div>

        <v-file-input
          v-model="files"
          label="Загрузить HTML-отчёт"
          accept=".html,.htm"
          prepend-icon="mdi-file-document-outline"
          show-size
          :loading="loading"
          :disabled="loading"
          variant="outlined"
          density="comfortable"
          hint="Поддерживаемый формат: HTML-отчёт (.html, .htm), максимум 20 МБ"
          persistent-hint
          @update:model-value="onFileSelected"
        />
      </div>

      <v-alert
        v-if="error"
        type="error"
        variant="tonal"
        closable
        class="mt-4"
        @click:close="$emit('clear-error')"
      >
        {{ error }}
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  loading: Boolean,
  error: String,
})

const emit = defineEmits(['file-selected', 'clear-error'])

const files = ref(null)
const isDragging = ref(false)
let dragEnterCount = 0

function onFileSelected(newFiles) {
  const file = Array.isArray(newFiles) ? newFiles[0] : newFiles
  if (file) {
    emit('file-selected', file)
  }
}

function onDragEnter() {
  dragEnterCount++
  isDragging.value = true
}

function onDragLeave() {
  dragEnterCount--
  if (dragEnterCount <= 0) {
    dragEnterCount = 0
    isDragging.value = false
  }
}

function onDrop(event) {
  dragEnterCount = 0
  isDragging.value = false

  const file = event.dataTransfer?.files?.[0]
  if (file) {
    files.value = file
    emit('file-selected', file)
  }
}

watch(() => props.error, (val) => {
  if (val) {
    files.value = null
  }
})
</script>

<style scoped>
.drop-zone {
  position: relative;
  transition: border-color 0.2s, background-color 0.2s;
  border: 2px dashed transparent;
  border-radius: 8px;
  padding: 4px;
}

.drop-zone--active {
  border-color: #1976D2;
  background-color: rgba(25, 118, 210, 0.05);
}

.drop-zone__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  z-index: 1;
}
</style>
