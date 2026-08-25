<template>
  <div class="container-page">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="page-title">Gestión de Asistencias</h1>
        <p class="page-subtitle">Filtra por fecha y justifica faltas.</p>
      </div>
      <input v-model="fecha" type="date" class="input w-auto" />
    </div>

    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Empleado</th>
            <th>Fecha</th>
            <th>Proyecto</th>
            <th>Compañía</th>
            <th>Puesto</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in asistencias" :key="a.id">
            <td class="font-medium text-slate-900">{{ a.usuario?.nombre }}</td>
            <td>{{ a.fecha }}</td>
            <td>{{ a.proyecto || '—' }}</td>
            <td>{{ a.compania || '—' }}</td>
            <td>{{ a.puesto || '—' }}</td>
            <td>{{ a.hora_entrada || '—' }}</td>
            <td>{{ a.hora_salida || '—' }}</td>
            <td>
              <span class="font-medium" :class="colorEstado(a.estado)">{{ a.estado }}</span>
            </td>
            <td class="text-right">
              <button v-if="a.estado === 'ausente'" @click="justificar(a)" class="btn-link">
                Justificar
              </button>
            </td>
          </tr>
          <tr v-if="!cargando && asistencias.length === 0">
            <td colspan="9" class="text-center text-slate-400 py-6">Sin registros para este filtro.</td>
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
  name: 'AsistenciasAdmin',
  setup() {
    const asistencias = ref([])
    const fecha = ref('')
    const cargando = ref(false)

    const colorEstado = (estado) => ({
      presente: 'text-green-600',
      tarde: 'text-amber-600',
      ausente: 'text-red-600',
      justificado: 'text-blue-600',
    }[estado] || 'text-gray-600')

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/asistencias/data', { params: { fecha: fecha.value || undefined } })
        asistencias.value = data.data ?? data
      } finally {
        cargando.value = false
      }
    }

    const justificar = async (a) => {
      const justificante = prompt('Motivo de la justificación:')
      if (!justificante) return

      await axios.post(`/asistencias/${a.id}/justificar`, { justificante })
      await cargar()
    }

    watch(fecha, cargar)
    onMounted(cargar)

    return { asistencias, fecha, cargando, colorEstado, justificar }
  },
}
</script>
