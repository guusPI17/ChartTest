<template>
  <v-card variant="outlined">
    <v-card-title class="d-flex align-center justify-space-between">
      <div class="d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-chart-areaspline</v-icon>
        График баланса
      </div>
      <div v-if="hoveredPoint" class="text-body-2 d-flex ga-3">
        <span><strong>{{ hoveredPoint.type }}</strong></span>
        <span v-if="hoveredPoint.item">{{ hoveredPoint.item }}</span>
        <span :class="hoveredPoint.profit >= 0 ? 'text-success' : 'text-error'">
          {{ hoveredPoint.profit >= 0 ? '+' : '' }}{{ hoveredPoint.profit.toFixed(2) }}
        </span>
        <span>Баланс: <strong>{{ hoveredPoint.balance.toFixed(2) }}</strong></span>
      </div>
    </v-card-title>
    <v-card-text class="pa-0">
      <div ref="chartContainer" class="chart-container"></div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { createChart, ColorType, CrosshairMode, LineStyle } from 'lightweight-charts'

const props = defineProps({
  operations: {
    type: Array,
    required: true,
  },
})

const chartContainer = ref(null)
const hoveredPoint = ref(null)

let chart = null
let areaSeries = null
let resizeObserver = null
let chartDataMap = new Map()

function parseTimestamp(timeStr) {
  // Ручной парсинг "2015.09.15 23:26:35" — Safari не поддерживает формат с пробелом
  const match = timeStr.match(/^(\d{4})[.\-/](\d{2})[.\-/](\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/)
  if (!match) return null
  const [, y, mo, d, h, mi, s] = match
  return Math.floor(Date.UTC(+y, +mo - 1, +d, +h, +mi, +s) / 1000)
}

function computeChartData(operations) {
  const data = []
  chartDataMap = new Map()
  let balance = 0
  let lastTime = 0

  for (const op of operations) {
    balance = Math.round((balance + op.profit) * 100) / 100

    let time = parseTimestamp(op.time)
    if (time === null) {
      // Запасной вариант: используем инкрементальный индекс
      time = lastTime + 86400
    }
    // Lightweight Charts требует строго возрастающее время
    if (time <= lastTime) {
      time = lastTime + 1
    }
    lastTime = time

    data.push({ time, value: balance })
    chartDataMap.set(time, { ...op, balance })
  }

  return data
}

function initChart() {
  if (!chartContainer.value) return

  chart = createChart(chartContainer.value, {
    height: 420,
    layout: {
      background: { type: ColorType.Solid, color: '#ffffff' },
      textColor: '#333333',
      fontFamily: "'Roboto', sans-serif",
    },
    grid: {
      vertLines: { color: '#f0f0f0' },
      horzLines: { color: '#f0f0f0' },
    },
    crosshair: {
      mode: CrosshairMode.Normal,
      vertLine: {
        labelBackgroundColor: '#1976D2',
      },
      horzLine: {
        labelBackgroundColor: '#1976D2',
      },
    },
    rightPriceScale: {
      borderColor: '#e0e0e0',
    },
    timeScale: {
      borderColor: '#e0e0e0',
      timeVisible: true,
      secondsVisible: false,
    },
    localization: {
      priceFormatter: (price) => price.toFixed(2),
    },
  })

  areaSeries = chart.addAreaSeries({
    topColor: 'rgba(25, 118, 210, 0.35)',
    bottomColor: 'rgba(25, 118, 210, 0.02)',
    lineColor: '#1976D2',
    lineWidth: 2,
    crosshairMarkerRadius: 5,
    crosshairMarkerBorderColor: '#1976D2',
    crosshairMarkerBackgroundColor: '#ffffff',
    crosshairMarkerBorderWidth: 2,
  })

  chart.subscribeCrosshairMove((param) => {
    if (!param.time || !param.seriesData.size) {
      hoveredPoint.value = null
      return
    }

    const info = chartDataMap.get(param.time)
    if (info) {
      hoveredPoint.value = info
    }
  })

  resizeObserver = new ResizeObserver((entries) => {
    for (const entry of entries) {
      const { width } = entry.contentRect
      if (chart && width > 0) {
        chart.applyOptions({ width })
      }
    }
  })
  resizeObserver.observe(chartContainer.value)
}

function updateChart() {
  if (!areaSeries || !props.operations?.length) return

  const data = computeChartData(props.operations)
  areaSeries.setData(data)
  chart.timeScale().fitContent()
}

function destroyChart() {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (chart) {
    chart.remove()
    chart = null
    areaSeries = null
  }
}

onMounted(() => {
  initChart()
  if (props.operations?.length) {
    updateChart()
  }
})

onUnmounted(() => {
  destroyChart()
})

watch(
  () => props.operations,
  async () => {
    await nextTick()
    if (!chart) {
      initChart()
    }
    updateChart()
  },
  { deep: true }
)
</script>

<style scoped>
.chart-container {
  width: 100%;
  min-height: 420px;
}
</style>
