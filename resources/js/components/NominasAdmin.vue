<template>
  <div class="container-page">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="page-title">Nóminas</h1>
        <p class="page-subtitle">Calcula y consulta la nómina de todos los empleados.</p>
      </div>

      <div class="flex items-center gap-2">
        <select v-model.number="mes" class="input w-auto">
          <option v-for="m in 12" :key="m" :value="m">{{ nombreMes(m) }}</option>
        </select>
        <select v-model.number="anio" class="input w-auto">
          <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
        </select>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 mb-6">
      <button
        @click="calcular"
        :disabled="calculando"
        class="btn-primary"
      >
        {{ calculando ? 'Calculando…' : 'Calcular nómina del periodo' }}
      </button>
      <a
        :href="`/nominas/reporte-contador/csv?mes=${mes}&anio=${anio}`"
        class="btn-secondary"
      >
        Exportar a contador (CSV)
      </a>
    </div>

    <div v-if="mensaje" class="alert-success mb-4">{{ mensaje }}</div>

    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Empleado</th>
            <th>Departamento</th>
            <th class="text-right">Días asistidos</th>
            <th class="text-right">Descuentos</th>
            <th class="text-right">Total</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in nominas" :key="n.id">
            <td class="font-medium text-slate-900">{{ n.usuario?.nombre }}</td>
            <td>{{ n.usuario?.departamento }}</td>
            <td class="text-right">{{ n.dias_asistidos }}</td>
            <td class="text-right">${{ formatoMoneda(Number(n.descuentos_faltas) + Number(n.descuentos_retardos)) }}</td>
            <td class="text-right font-bold">${{ formatoMoneda(n.total_pago) }}</td>
            <td><span class="badge-gray">{{ n.estado }}</span></td>
          </tr>
          <tr v-if="!cargando && nominas.length === 0">
            <td colspan="6" class="text-center text-slate-400 py-6">
              Sin nóminas calculadas para este periodo todavía.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'NominasAdmin',
  setup() {
    const hoy = new Date()
    const mes = ref(hoy.getMonth() + 1)
    const anio = ref(hoy.getFullYear())
    const anios = [anio.value, anio.value - 1, anio.value - 2]
    const nominas = ref([])
    const cargando = ref(false)
    const calculando = ref(false)
    const mensaje = ref('')

    const nombreMes = (m) =>
      ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][m - 1]

    const formatoMoneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })

    const cargarNominas = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/nominas/data', { params: { mes: mes.value, anio: anio.value } })
        nominas.value = data.nominas
      } finally {
        cargando.value = false
      }
    }

    const calcular = async () => {
      calculando.value = true
      mensaje.value = ''
      try {
        const { data } = await axios.post('/nominas/calcular', { mes: mes.value, anio: anio.value })
        mensaje.value = data.message
        await cargarNominas()
      } finally {
        calculando.value = false
      }
    }

    watch([mes, anio], cargarNominas)
    onMounted(cargarNominas)

    return { mes, anio, anios, nominas, cargando, calculando, mensaje, nombreMes, formatoMoneda, calcular }
  },
}
</script>
