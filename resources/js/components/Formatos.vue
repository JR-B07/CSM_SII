<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Formatos</h1>
      <p class="page-subtitle">Checklists, formatos de trabajo y triggers listos para consultar e imprimir.</p>
    </div>

    <div class="card card-body mb-6">
      <h2 class="font-semibold text-slate-900 mb-3">Subir formato</h2>
      <form @submit.prevent="subir" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <input v-model="form.nombre" type="text" placeholder="Nombre del formato" required class="input sm:col-span-2" />
        <select v-model="form.tipo" required class="input sm:col-span-2">
          <option value="">Tipo de formato</option>
          <option value="checklist">Checklist</option>
          <option value="formato_trabajo">Formato de trabajo</option>
          <option value="trigger">Trigger</option>
        </select>
        <input @change="onArchivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required class="input sm:col-span-2" />
        <button type="submit" :disabled="subiendo" class="btn-primary sm:col-span-2">
          {{ subiendo ? 'Subiendo…' : 'Subir formato' }}
        </button>
      </form>
      <p class="text-xs text-slate-400 mt-2">PDF, imagen (JPG/PNG), Word o Excel. Máximo 10 MB. PDF e imagen se pueden ver e imprimir directo en el navegador; Word/Excel se descargan.</p>
      <p v-if="error" class="field-error">{{ error }}</p>
      <p v-if="mensaje" class="text-green-700 text-sm mt-2">{{ mensaje }}</p>
    </div>

    <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
      <button
        v-for="opcion in filtros"
        :key="opcion.valor"
        @click="filtroTipo = opcion.valor"
        :class="filtroTipo === opcion.valor ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
        class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap shrink-0"
      >
        {{ opcion.etiqueta }}
      </button>
    </div>

    <div v-for="f in formatosFiltrados" :key="f.id" class="card card-body mb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <p class="font-semibold text-slate-900">{{ f.nombre }}</p>
        <p class="text-sm text-slate-500">{{ etiquetaTipo(f.tipo) }} · Subido por {{ f.subido_por?.nombre }}</p>
      </div>
      <div class="flex gap-3 items-center">
        <a :href="`/formatos/${f.id}/ver`" target="_blank" class="btn-secondary flex-1 sm:flex-none">
          Ver / Imprimir
        </a>
        <button @click="eliminar(f)" class="text-red-600 hover:underline text-sm font-medium">Eliminar</button>
      </div>
    </div>

    <p v-if="!cargando && formatosFiltrados.length === 0" class="text-center text-slate-400 py-8">
      No hay formatos en esta categoría todavía.
    </p>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Formatos',
  setup() {
    const formatos = ref([])
    const cargando = ref(false)
    const subiendo = ref(false)
    const error = ref('')
    const mensaje = ref('')
    const archivo = ref(null)
    const form = ref({ nombre: '', tipo: '' })
    const filtroTipo = ref('')

    const filtros = [
      { valor: '', etiqueta: 'Todos' },
      { valor: 'checklist', etiqueta: 'Checklists' },
      { valor: 'formato_trabajo', etiqueta: 'Formatos de trabajo' },
      { valor: 'trigger', etiqueta: 'Triggers' },
    ]

    const etiquetaTipo = (tipo) => filtros.find((f) => f.valor === tipo)?.etiqueta ?? tipo

    const formatosFiltrados = computed(() =>
      filtroTipo.value ? formatos.value.filter((f) => f.tipo === filtroTipo.value) : formatos.value
    )

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get('/formatos/data')
        formatos.value = data.formatos
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
      formData.append('nombre', form.value.nombre)
      formData.append('tipo', form.value.tipo)
      formData.append('archivo', archivo.value)

      subiendo.value = true
      try {
        const { data } = await axios.post('/formatos/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        mensaje.value = data.message
        form.value = { nombre: '', tipo: '' }
        archivo.value = null
        await cargar()
      } catch (e) {
        error.value = e.response?.data?.message ?? 'No se pudo subir el formato.'
      } finally {
        subiendo.value = false
      }
    }

    const eliminar = async (f) => {
      if (!confirm(`¿Eliminar "${f.nombre}"?`)) return
      try {
        await axios.delete(`/formatos/${f.id}`)
        await cargar()
      } catch (e) {
        alert(e.response?.data?.message ?? 'No se pudo eliminar el formato.')
      }
    }

    onMounted(cargar)

    return {
      formatos,
      formatosFiltrados,
      cargando,
      subiendo,
      error,
      mensaje,
      form,
      filtros,
      filtroTipo,
      etiquetaTipo,
      onArchivo,
      subir,
      eliminar,
    }
  },
}
</script>
