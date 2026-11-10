<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Mi Nómina</h1>
      <p class="page-subtitle">Consulta tu pago del periodo y tu histórico.</p>
    </div>

    <div v-if="cargando" class="text-slate-500 text-sm">Cargando…</div>

    <template v-else>
      <div v-if="periodoInicio && periodoFin" class="text-sm text-slate-500 mb-3">
        Periodo actual: {{ formatoFecha(periodoInicio) }} al {{ formatoFecha(periodoFin) }}
      </div>

      <div v-if="!nomina" class="alert-info">
        Aún no hay nómina calculada para este periodo. Se genera automáticamente al registrar tu primera asistencia.
      </div>

      <div v-else class="card card-body space-y-3">
        <div class="flex justify-between">
          <span class="text-slate-500">Salario diario</span>
          <span class="font-medium">${{ formatoMoneda(nomina.salario_diario) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">Días laborales</span>
          <span class="font-medium">{{ nomina.dias_laborales }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">Días asistidos</span>
          <span class="font-medium">{{ nomina.dias_asistidos }}</span>
        </div>
        <div class="flex justify-between text-red-600">
          <span>Descuentos por faltas</span>
          <span>- ${{ formatoMoneda(nomina.descuentos_faltas) }}</span>
        </div>
        <hr class="border-slate-200">
        <div class="flex justify-between text-xl font-bold text-slate-900">
          <span>Total a pagar</span>
          <span>${{ formatoMoneda(nomina.total_pago) }}</span>
        </div>
        <div>
          <span :class="nomina.estado === 'pagada' ? 'badge-green' : 'badge-blue'">{{ nomina.estado }}</span>
        </div>

        <a
          :href="`/nominas/mi-nomina/pdf?fecha=${periodoInicio}`"
          class="btn-secondary w-full mt-2"
        >
          Descargar comprobante (PDF)
        </a>
      </div>
    </template>

    <h2 class="text-lg font-bold mt-8 mb-3 text-slate-900">Histórico (últimos periodos)</h2>
    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Periodo</th>
            <th class="text-right">Días asistidos</th>
            <th class="text-right">Total</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in historico" :key="n.id">
            <td>{{ formatoFecha(n.periodo_inicio) }} - {{ formatoFecha(n.periodo_fin) }}</td>
            <td class="text-right">{{ n.dias_asistidos }}</td>
            <td class="text-right">${{ formatoMoneda(n.total_pago) }}</td>
            <td><span :class="n.estado === 'pagada' ? 'badge-green' : 'badge-gray'">{{ n.estado }}</span></td>
          </tr>
          <tr v-if="!cargando && historico.length === 0">
            <td colspan="4" class="text-center text-slate-400 py-6">Sin historial todavía.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'MiNomina',
  setup() {
    const nomina = ref(null)
    const periodoInicio = ref(null)
    const periodoFin = ref(null)
    const historico = ref([])
    const cargando = ref(false)

    const formatoFecha = (valor) =>
      new Date(`${valor}T00:00:00`).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' })

    const formatoMoneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })

    const cargarNomina = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/nominas/mi-nomina/data')
        nomina.value = data.nomina
        periodoInicio.value = data.periodo_inicio
        periodoFin.value = data.periodo_fin
      } finally {
        cargando.value = false
      }
    }

    const cargarHistorico = async () => {
      const { data } = await axios.get('/nominas/historico')
      historico.value = data.nominas
    }

    onMounted(() => {
      cargarNomina()
      cargarHistorico()
    })

    return { nomina, periodoInicio, periodoFin, historico, cargando, formatoFecha, formatoMoneda }
  },
}
</script>
