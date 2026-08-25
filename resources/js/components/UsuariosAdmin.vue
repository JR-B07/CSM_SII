<template>
  <div class="container-page">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="page-title">Usuarios</h1>
        <p class="page-subtitle">Administra el personal con acceso al portal.</p>
      </div>
      <a href="/usuarios/create" class="btn-primary">
        + Nuevo usuario
      </a>
    </div>

    <div v-if="mensaje" class="alert-success mb-4">{{ mensaje }}</div>

    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Departamento</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in usuarios" :key="u.id">
            <td class="font-medium text-slate-900">{{ u.nombre }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.rol }}</td>
            <td>{{ u.departamento || '—' }}</td>
            <td>
              <span :class="u.activo ? 'badge-green' : 'badge-gray'">
                {{ u.activo ? 'Activo' : 'Dado de baja' }}
              </span>
            </td>
            <td class="text-right space-x-3">
              <a :href="`/usuarios/${u.id}/edit`" class="btn-link">Editar</a>
              <a :href="`/usuarios/${u.id}/documentos-legales`" class="btn-link">Documentos</a>
              <button v-if="u.activo" @click="darDeBaja(u)" class="text-red-600 hover:underline font-medium">
                Dar de baja
              </button>
              <button v-else @click="reactivar(u)" class="text-green-600 hover:underline font-medium">
                Reactivar
              </button>
            </td>
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
  name: 'UsuariosAdmin',
  props: {
    status: { type: String, default: null },
  },
  setup(props) {
    const usuarios = ref([])
    const mensaje = ref('')

    const cargar = async () => {
      const { data } = await axios.get('/usuarios/data')
      usuarios.value = data.data ?? data
    }

    if (props.status) {
      alert(props.status)
    }

    const darDeBaja = async (u) => {
      if (!confirm(`¿Dar de baja a ${u.nombre}? Ya no podrá iniciar sesión en el portal.`)) return

      const { data } = await axios.delete(`/usuarios/${u.id}`)
      mensaje.value = data.message
      u.activo = false
    }

    const reactivar = async (u) => {
      if (!confirm(`¿Reactivar a ${u.nombre}?`)) return

      const { data } = await axios.post(`/usuarios/${u.id}/activar`)
      mensaje.value = data.message
      u.activo = true
    }

    onMounted(cargar)

    return { usuarios, mensaje, darDeBaja, reactivar }
  },
}
</script>
