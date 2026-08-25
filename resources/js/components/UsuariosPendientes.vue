<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Cuentas pendientes de aprobación</h1>
      <p class="page-subtitle">Define rol, departamento y salario antes de aprobar.</p>
    </div>

    <div v-if="mensaje" class="alert-success mb-4">{{ mensaje }}</div>

    <div v-if="cargando" class="text-slate-500 text-sm">Cargando…</div>

    <div v-else-if="usuarios.length === 0" class="card card-body text-center text-slate-400">
      No hay solicitudes pendientes.
    </div>

    <div v-for="u in usuarios" :key="u.id" class="card card-body mb-4">
      <div class="mb-3">
        <p class="font-semibold text-slate-900">{{ u.nombre }}</p>
        <p class="text-sm text-slate-500">{{ u.email }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Solicitado: {{ new Date(u.created_at).toLocaleString('es-MX') }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
        <select v-model="formularios[u.id].rol" class="input text-sm">
          <option value="rescatista">Técnico</option>
          <option value="supervisor">Supervisor</option>
          <option value="RH">RH</option>
          <option value="contador">Contador</option>
          <option value="admin">Admin</option>
        </select>
        <input v-model="formularios[u.id].departamento" placeholder="Departamento" class="input text-sm" />
        <input v-model.number="formularios[u.id].salario_diario" type="number" step="0.01" placeholder="Salario diario" class="input text-sm" />
      </div>

      <div class="flex gap-2">
        <button @click="aprobar(u.id)" class="btn-success flex-1">
          Aprobar
        </button>
        <button @click="rechazar(u.id)" class="btn-danger flex-1">
          Rechazar
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'UsuariosPendientes',
  setup() {
    const usuarios = ref([])
    const formularios = ref({})
    const cargando = ref(false)
    const mensaje = ref('')

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/usuarios/pendientes/data')
        usuarios.value = data.usuarios
        data.usuarios.forEach((u) => {
          formularios.value[u.id] = { rol: 'rescatista', departamento: '', salario_diario: 0 }
        })
      } finally {
        cargando.value = false
      }
    }

    const aprobar = async (id) => {
      const { data } = await axios.post(`/usuarios/${id}/aprobar`, formularios.value[id])
      mensaje.value = data.message
      await cargar()
    }

    const rechazar = async (id) => {
      const motivo = prompt('Motivo del rechazo (opcional):') || null
      const { data } = await axios.post(`/usuarios/${id}/rechazar`, { motivo })
      mensaje.value = data.message
      await cargar()
    }

    onMounted(cargar)

    return { usuarios, formularios, cargando, mensaje, aprobar, rechazar }
  },
}
</script>
