<template>
  <v-app>
    <v-app-bar color="primary" density="comfortable">
      <v-app-bar-title>
        <v-icon class="mr-2">mdi-chart-timeline-variant-shimmer</v-icon>
        График баланса
      </v-app-bar-title>
    </v-app-bar>

    <v-main>
      <v-container class="py-6" fluid>
        <v-row justify="center">
          <v-col cols="12" lg="10" xl="8">
            <FileUpload
              :loading="loading"
              :error="error"
              @file-selected="onFileSelected"
              @clear-error="clearError"
            />

            <template v-if="data">
              <StatementInfo
                :meta="data.meta"
                :operations="data.operations"
              />

              <BalanceChart :operations="data.operations" />
            </template>

            <v-card v-else-if="!loading && !error" class="text-center pa-8" variant="outlined">
              <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-chart-line</v-icon>
              <div class="text-h6 text-grey-darken-1 mb-2">Данные не загружены</div>
              <div class="text-body-2 text-grey">
                Загрузите HTML-отчёт для отображения графика баланса
              </div>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { useStatementApi } from './composables/useStatementApi'
import FileUpload from './components/FileUpload.vue'
import StatementInfo from './components/StatementInfo.vue'
import BalanceChart from './components/BalanceChart.vue'

const { data, loading, error, parseStatement, reset } = useStatementApi()

function onFileSelected(file) {
  parseStatement(file)
}

function clearError() {
  error.value = null
}
</script>
