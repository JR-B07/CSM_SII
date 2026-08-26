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

    <input
      v-model="busqueda"
      type="text"
      placeholder="Buscar por nombre, correo, rol o departamento…"
      class="input mb-4"
    />

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
          <tr v-for="u in usuariosFiltrados" :key="u.id">
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
          <tr v-if="!cargando && usuariosFiltrados.length === 0">
            <td colspan="6" class="text-center text-slate-400 py-6">
              Ningún usuario coincide con tu búsqueda.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="text-sm text-slate-400 mt-2">
      Mostrando {{ usuariosFiltrados.length }} de {{ usuarios.length }} usuarios.
    </p>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'UsuariosAdmin',
  props: {
    status: { type: String, default: null },
  },
  setup(props) {
    const usuarios = ref([])
    const mensaje = ref('')
    const cargando = ref(false)
    const busqueda = ref('')

    const usuariosFiltrados = computed(() => {
      const texto = busqueda.value.trim().toLowerCase()
      if (!texto) return usuarios.value

      return usuarios.value.filter((u) =>
        [u.nombre, u.email, u.rol, u.departamento]
          .filter(Boolean)
          .some((campo) => campo.toLowerCase().includes(texto))
      )
    })

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/usuarios/data')
        usuarios.value = data.data ?? data
      } finally {
        cargando.value = false
      }
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

    return { usuarios, usuariosFiltrados, busqueda, cargando, mensaje, darDeBaja, reactivar }
  },
}
</script>
