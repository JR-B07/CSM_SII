<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Dashboard Ejecutivo</h1>
      <p class="page-subtitle">Panorama general de la operación en tiempo real.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
      <div class="card card-body">
        <div class="text-xs sm:text-sm text-slate-500">Asistencias hoy</div>
        <div class="text-2xl sm:text-3xl font-bold text-green-600">{{ kpis.asistencias_hoy ?? '—' }}</div>
      </div>
      <div class="card card-body">
        <div class="text-xs sm:text-sm text-slate-500">Ausencias hoy</div>
        <div class="text-2xl sm:text-3xl font-bold text-red-600">{{ kpis.ausencias_hoy ?? '—' }}</div>
      </div>
      <div class="card card-body">
        <div class="text-xs sm:text-sm text-slate-500">Documentos</div>
        <div class="text-2xl sm:text-3xl font-bold text-blue-600">{{ kpis.documentos_totales ?? '—' }}</div>
      </div>
      <div class="card card-body">
        <div class="text-xs sm:text-sm text-slate-500">Proyectos activos</div>
        <div class="text-2xl sm:text-3xl font-bold text-indigo-600">{{ kpis.proyectos_activos ?? '—' }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <div class="card card-body">
        <h2 class="font-semibold text-slate-900 mb-4">Asistencia por día (últimos 7 días)</h2>
        <Line v-if="lineData" :data="lineData" :options="chartOptions" />
      </div>
      <div class="card card-body">
        <h2 class="font-semibold text-slate-900 mb-4">Asistencia por departamento (hoy)</h2>
        <Bar v-if="barData" :data="barData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, BarElement, CategoryScale, LinearScale, PointElement)

export default {
  name: 'Dashboard',
  components: { Line, Bar },
  setup() {
    const kpis = ref({})
    const lineData = ref(null)
    const barData = ref(null)
    const chartOptions = { responsive: true, plugins: { legend: { display: false } } }

    const cargarKpis = async () => {
      const { data } = await axios.get('/dashboard/kpis')
      kpis.value = data
    }

    const cargarGraficos = async () => {
      const { data } = await axios.get('/dashboard/graficos')

      lineData.value = {
        labels: data.asistencia_por_dia.map((d) => d.fecha),
        datasets: [
          {
            label: 'Asistencias',
            data: data.asistencia_por_dia.map((d) => d.total),
            borderColor: '#2563eb',
            backgroundColor: '#2563eb33',
            tension: 0.3,
          },
        ],
      }

      barData.value = {
        labels: data.asistencia_por_departamento.map((d) => d.departamento ?? 'Sin departamento'),
        datasets: [
          {
            label: 'Asistencias',
            data: data.asistencia_por_departamento.map((d) => d.total),
            backgroundColor: '#16a34a',
          },
        ],
      }
    }

    onMounted(() => {
      cargarKpis()
      cargarGraficos()
    })

    return { kpis, lineData, barData, chartOptions }
  },
}
</script>
