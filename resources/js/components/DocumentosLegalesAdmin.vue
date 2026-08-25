<template>
  <div class="container-page">
    <div class="page-header">
      <h1 class="page-title">Documentos legales</h1>
      <p class="page-subtitle">{{ usuarioNombre }}</p>
    </div>

    <div class="card card-body mb-6">
      <h2 class="font-semibold text-slate-900 mb-3">Subir documento</h2>
      <form @submit.prevent="subir" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <select v-model="form.tipo" required class="input">
          <option value="">Tipo de documento</option>
          <option value="IMSS">IMSS</option>
          <option value="INE">INE</option>
          <option value="RFC">RFC</option>
          <option value="CURP">CURP</option>
          <option value="comprobante_domicilio">Comprobante de domicilio</option>
          <option value="contrato">Contrato</option>
          <option value="acta_nacimiento">Acta de nacimiento</option>
          <option value="otro">Otro</option>
        </select>
        <input v-model="form.numero_documento" placeholder="Número (NSS, folio, RFC…)" class="input" />
        <input v-model="form.fecha_vencimiento" type="date" class="input" />
        <input @change="onArchivo" type="file" required class="input sm:col-span-2" />
        <button type="submit" :disabled="subiendo" class="btn-primary sm:col-span-2">
          {{ subiendo ? 'Subiendo…' : 'Subir documento' }}
        </button>
      </form>
      <p v-if="error" class="field-error">{{ error }}</p>
    </div>

    <div class="table-wrap">
      <table class="table-base">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Número</th>
            <th>Archivo</th>
            <th>Vence</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in documentos" :key="d.id">
            <td>{{ d.tipo }}</td>
            <td>{{ d.numero_documento || '—' }}</td>
            <td>
              <a :href="`/documentos-legales/${d.id}/download`" class="btn-link">
                {{ d.nombre_archivo }}
              </a>
            </td>
            <td>{{ d.fecha_vencimiento || '—' }}</td>
            <td class="text-right">
              <button @click="eliminar(d.id)" class="text-red-600 hover:underline font-medium">Eliminar</button>
            </td>
          </tr>
          <tr v-if="!cargando && documentos.length === 0">
            <td colspan="5" class="text-center text-slate-400 py-6">Sin documentos subidos todavía.</td>
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
  name: 'DocumentosLegalesAdmin',
  props: {
    usuarioId: { type: Number, required: true },
    usuarioNombre: { type: String, default: '' },
  },
  setup(props) {
    const documentos = ref([])
    const cargando = ref(false)
    const subiendo = ref(false)
    const error = ref('')
    const archivo = ref(null)
    const form = ref({ tipo: '', numero_documento: '', fecha_vencimiento: '' })

    const cargar = async () => {
      cargando.value = true
      try {
        const { data } = await axios.get(`/usuarios/${props.usuarioId}/documentos-legales/data`)
        documentos.value = data.documentos
      } finally {
        cargando.value = false
      }
    }

    const onArchivo = (evento) => {
      archivo.value = evento.target.files[0] ?? null
    }

    const subir = async () => {
      error.value = ''
      if (!archivo.value) {
        error.value = 'Selecciona un archivo.'
        return
      }

      const formData = new FormData()
      formData.append('tipo', form.value.tipo)
      formData.append('numero_documento', form.value.numero_documento ?? '')
      formData.append('fecha_vencimiento', form.value.fecha_vencimiento ?? '')
      formData.append('archivo', archivo.value)

      subiendo.value = true
      try {
        await axios.post(`/usuarios/${props.usuarioId}/documentos-legales`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        form.value = { tipo: '', numero_documento: '', fecha_vencimiento: '' }
        archivo.value = null
        await cargar()
      } catch (e) {
        error.value = e.response?.data?.message ?? 'No se pudo subir el documento.'
      } finally {
        subiendo.value = false
      }
    }

    const eliminar = async (id) => {
      if (!confirm('¿Eliminar este documento?')) return
      await axios.delete(`/documentos-legales/${id}`)
      await cargar()
    }

    onMounted(cargar)

    return { documentos, cargando, subiendo, error, form, onArchivo, subir, eliminar }
  },
}
</script>
