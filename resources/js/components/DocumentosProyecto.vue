<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Documentos de Proyecto</h1>
      <p class="page-subtitle">Sube la documentación del proyecto en el que estás trabajando.</p>
    </div>

    <div class="card card-body mb-6">
      <h2 class="font-semibold text-slate-900 mb-3">Subir documento</h2>
      <form @submit.prevent="subir" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <input v-model="form.cliente" type="text" placeholder="Cliente" class="input" />
        <input v-model="form.proyecto" type="text" placeholder="Proyecto" class="input" />
        <input @change="onArchivo" type="file" required class="input sm:col-span-2" />
        <button type="submit" :disabled="subiendo" class="btn-primary sm:col-span-2">
          {{ subiendo ? 'Subiendo…' : 'Subir documento' }}
        </button>
      </form>
      <p v-if="error" class="field-error">{{ error }}</p>
      <p v-if="mensaje" class="text-green-700 text-sm mt-2">{{ mensaje }}</p>
    </div>

    <input
      v-model="busqueda"
      type="text"
      placeholder="Buscar por nombre, cliente, proyecto o quién lo subió…"
      class="input mb-4"
    />

    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Cliente</th>
            <th>Proyecto</th>
            <th>Tipo</th>
            <th>Tamaño</th>
            <th>Subido por</th>
            <th>Fecha</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in documentosFiltrados" :key="d.id">
            <td class="font-medium text-slate-900">{{ d.nombre }}</td>
            <td>{{ d.cliente || '—' }}</td>
            <td>{{ d.proyecto || '—' }}</td>
            <td>{{ d.tipo }}</td>
            <td>{{ formatoTamano(d.tamano) }}</td>
            <td>{{ d.usuario?.nombre }}</td>
            <td>{{ new Date(d.created_at).toLocaleDateString('es-MX') }}</td>
            <td class="text-right">
              <a :href="`/documentos/${d.id}/download`" class="btn-link">Descargar</a>
            </td>
          </tr>
          <tr v-if="!cargando && documentos.length === 0">
            <td colspan="8" class="text-center text-slate-400 py-6">Aún no hay documentos subidos.</td>
          </tr>
          <tr v-else-if="!cargando && documentosFiltrados.length === 0">
            <td colspan="8" class="text-center text-slate-400 py-6">Ningún documento coincide con tu búsqueda.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'DocumentosProyecto',
  setup() {
    const documentos = ref([])
    const cargando = ref(false)
    const subiendo = ref(false)
    const error = ref('')
    const mensaje = ref('')
    const archivo = ref(null)
    const form = ref({ cliente: '', proyecto: '' })
    const busqueda = ref('')

    const documentosFiltrados = computed(() => {
      const texto = busqueda.value.trim().toLowerCase()
      if (!texto) return documentos.value

      return documentos.value.filter((d) =>
        [d.nombre, d.cliente, d.proyecto, d.tipo, d.usuario?.nombre]
          .filter(Boolean)
          .some((campo) => campo.toLowerCase().includes(texto))
      )
    })

    const formatoTamano = (bytes) => {
      if (!bytes) return '—'
      const kb = bytes / 1024
      if (kb < 1024) return `${kb.toFixed(0)} KB`
      return `${(kb / 1024).toFixed(1)} MB`
    }

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/documentos/data')
        documentos.value = data.data ?? data
      } finally {
        cargando.value = false
      }
    }

    const onArchivo = (evento) => {
      archivo.value = evento.target.files[0] ?? null
    }

    const subir = async () => {
      error.value = ''
      mensaje.value = ''
      if (!archivo.value) {
        error.value = 'Selecciona un archivo.'
        return
      }

      const formData = new FormData()
      formData.append('cliente', form.value.cliente ?? '')
      formData.append('proyecto', form.value.proyecto ?? '')
      formData.append('archivo', archivo.value)

      subiendo.value = true
      try {
        const { data } = await axios.post('/documentos/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        mensaje.value = data.message
        form.value = { cliente: '', proyecto: '' }
        archivo.value = null
        await cargar()
      } catch (e) {
        error.value = e.response?.data?.message ?? 'No se pudo subir el documento.'
      } finally {
        subiendo.value = false
      }
    }

    onMounted(cargar)

    return {
      documentos,
      documentosFiltrados,
      busqueda,
      cargando,
      subiendo,
      error,
      mensaje,
      form,
      onArchivo,
      subir,
      formatoTamano,
    }
  },
}
</script>
