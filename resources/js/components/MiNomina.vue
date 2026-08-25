<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Mi Nómina</h1>
      <p class="page-subtitle">Consulta tu pago del periodo y tu histórico.</p>
    </div>

    <div class="flex gap-2 mb-6">
      <select v-model.number="mes" @change="cargarNomina" class="input w-auto">
        <option v-for="m in 12" :key="m" :value="m">{{ nombreMes(m) }}</option>
      </select>
      <select v-model.number="anio" @change="cargarNomina" class="input w-auto">
        <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
      </select>
    </div>

    <div v-if="cargando" class="text-slate-500 text-sm">Cargando…</div>

    <div v-else-if="!nomina" class="alert-info">
      Aún no hay nómina calculada para este periodo.
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
      <div class="flex justify-between text-red-600">
        <span>Descuentos por retardos</span>
        <span>- ${{ formatoMoneda(nomina.descuentos_retardos) }}</span>
      </div>
      <hr class="border-slate-200">
      <div class="flex justify-between text-xl font-bold text-slate-900">
        <span>Total a pagar</span>
        <span>${{ formatoMoneda(nomina.total_pago) }}</span>
      </div>
      <div>
        <span class="badge-blue">{{ nomina.estado }}</span>
      </div>

      <a
        :href="`/nominas/mi-nomina/pdf?mes=${mes}&anio=${anio}`"
        class="btn-secondary w-full mt-2"
      >
        Descargar comprobante (PDF)
      </a>
    </div>

    <h2 class="text-lg font-bold mt-8 mb-3 text-slate-900">Histórico (últimos 12 meses)</h2>
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
            <td>{{ nombreMes(n.mes) }} {{ n.anio }}</td>
            <td class="text-right">{{ n.dias_asistidos }}</td>
            <td class="text-right">${{ formatoMoneda(n.total_pago) }}</td>
            <td><span class="badge-gray">{{ n.estado }}</span></td>
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
    const hoy = new Date()
    const mes = ref(hoy.getMonth() + 1)
    const anio = ref(hoy.getFullYear())
    const anios = [anio.value, anio.value - 1, anio.value - 2]
    const nomina = ref(null)
    const historico = ref([])
    const cargando = ref(false)

    const nombreMes = (m) =>
      ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][m - 1]

    const formatoMoneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })

    const cargarNomina = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/nominas/mi-nomina/data', { params: { mes: mes.value, anio: anio.value } })
        nomina.value = data.nomina
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

    return { mes, anio, anios, nomina, historico, cargando, nombreMes, formatoMoneda, cargarNomina }
  },
}
</script>
