<template>
  <div class="container-page">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="page-title">Nóminas</h1>
        <p class="page-subtitle">Calcula y consulta la nómina de todos los empleados.</p>
      </div>

      <div class="flex items-center gap-2">
        <input v-model="fecha" type="date" class="input w-auto" />
      </div>
    </div>

    <p v-if="periodoInicio && periodoFin" class="text-sm text-slate-500 mb-4">
      Quincena: {{ periodoInicio }} al {{ periodoFin }}
    </p>

    <div class="flex flex-col sm:flex-row gap-2 mb-6">
      <button
        @click="calcular"
        :disabled="calculando"
        class="btn-primary"
      >
        {{ calculando ? 'Calculando…' : 'Calcular nómina del periodo' }}
      </button>
      <a
        :href="`/nominas/reporte-contador/csv?fecha=${fecha}`"
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
            <th class="text-right">Descuento faltas</th>
            <th class="text-right">Total</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in nominas" :key="n.id">
            <td class="font-medium text-slate-900">{{ n.usuario?.nombre }}</td>
            <td>{{ n.usuario?.departamento }}</td>
            <td class="text-right">{{ n.dias_asistidos }}</td>
            <td class="text-right">${{ formatoMoneda(n.descuentos_faltas) }}</td>
            <td class="text-right font-bold">${{ formatoMoneda(n.total_pago) }}</td>
            <td><span :class="n.estado === 'pagada' ? 'badge-green' : 'badge-gray'">{{ n.estado }}</span></td>
            <td class="text-right">
              <button v-if="n.estado !== 'pagada'" @click="marcarPagada(n)" class="btn-link">
                Marcar como pagada
              </button>
            </td>
          </tr>
          <tr v-if="!cargando && nominas.length === 0">
            <td colspan="7" class="text-center text-slate-400 py-6">
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
    const fecha = ref(new Date().toISOString().slice(0, 10))
    const periodoInicio = ref(null)
    const periodoFin = ref(null)
    const nominas = ref([])
    const cargando = ref(false)
    const calculando = ref(false)
    const mensaje = ref('')

    const formatoMoneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })

    const cargarNominas = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/nominas/data', { params: { fecha: fecha.value } })
        nominas.value = data.nominas
        periodoInicio.value = data.periodo_inicio
        periodoFin.value = data.periodo_fin
      } finally {
        cargando.value = false
      }
    }

    const calcular = async () => {
      calculando.value = true
      mensaje.value = ''
      try {
        const { data } = await axios.post('/nominas/calcular', { fecha: fecha.value })
        mensaje.value = data.message
        await cargarNominas()
      } finally {
        calculando.value = false
      }
    }

    const marcarPagada = async (n) => {
      if (!confirm(`¿Marcar la nómina de ${n.usuario?.nombre} como pagada?`)) return

      const { data } = await axios.post(`/nominas/${n.id}/pagar`)
      mensaje.value = data.message
      n.estado = 'pagada'
    }

    watch(fecha, cargarNominas)
    onMounted(cargarNominas)

    return { fecha, periodoInicio, periodoFin, nominas, cargando, calculando, mensaje, formatoMoneda, calcular, marcarPagada }
  },
}
</script>
