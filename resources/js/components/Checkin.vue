<template>
  <div class="container-page-narrow">
    <div class="page-header">
      <h1 class="page-title">Registrar Asistencia</h1>
      <p class="page-subtitle">Completa tus datos y confirma tu entrada o salida.</p>
    </div>

    <div class="card card-body space-y-4">
      <div>
        <label class="label">Proyecto</label>
        <input
          v-model="proyecto"
          type="text"
          list="lista-proyectos"
          class="input"
          placeholder="Nombre del proyecto"
        />
        <datalist id="lista-proyectos">
          <option v-for="p in proyectos" :key="p" :value="p"></option>
        </datalist>
        <p v-if="errores.proyecto" class="field-error">{{ errores.proyecto }}</p>
      </div>

      <div>
        <label class="label">Compañía</label>
        <input
          v-model="compania"
          type="text"
          class="input"
          placeholder="Compañía con la que asistes"
        />
        <p v-if="errores.compania" class="field-error">{{ errores.compania }}</p>
      </div>

      <div>
        <label class="label">Puesto</label>
        <input
          v-model="puesto"
          type="text"
          class="input"
          placeholder="Tu puesto"
        />
        <p v-if="errores.puesto" class="field-error">{{ errores.puesto }}</p>
      </div>

      <div class="text-center pt-1">
        <button
          type="button"
          @click="camaraActiva ? detenerCamara() : abrirCamara()"
          class="btn-outline w-full sm:w-auto"
        >
          {{ camaraActiva ? '✕ Cerrar cámara' : '📷 Escanear QR' }}
        </button>

        <div v-show="camaraActiva" class="mt-3">
          <video ref="qrVideo" class="mx-auto rounded-lg border border-slate-200" style="width: 280px; height: 210px; object-fit: cover;" muted playsinline></video>
          <canvas ref="qrCanvas" class="hidden"></canvas>
          <p class="text-xs text-slate-400 mt-2">Apunta la cámara al código QR de tu gafete.</p>
        </div>
      </div>

      <div>
        <label class="label">O código manual</label>
        <input
          v-model="codigoManual"
          type="text"
          class="input"
          placeholder="Ingresar código"
        />
      </div>

      <p v-if="ubicacion" class="text-sm text-slate-500">
        📍 Ubicación capturada: {{ ubicacion.lat.toFixed(5) }}, {{ ubicacion.lng.toFixed(5) }}
      </p>
      <p v-else class="text-sm text-slate-400">Obteniendo ubicación…</p>

      <div class="flex flex-col sm:flex-row gap-3 pt-1">
        <button
          @click="confirmarEntrada"
          :disabled="cargando"
          class="btn-success flex-1 py-3.5 text-base"
        >
          {{ cargando ? 'Enviando…' : 'CONFIRMAR ENTRADA' }}
        </button>
        <button
          @click="confirmarSalida"
          :disabled="cargando"
          class="btn-secondary flex-1 py-3.5 text-base"
        >
          {{ cargando ? 'Enviando…' : 'CONFIRMAR SALIDA' }}
        </button>
      </div>

      <div v-if="mensaje" class="alert-info">
        {{ mensaje }}
      </div>
    </div>

    <div class="card card-body mt-4 text-sm text-slate-600">
      <p class="font-semibold text-slate-900 mb-2">Última asistencia</p>
      <template v-if="ultimaAsistencia">
        <div class="space-y-1">
          <p>✓ Fecha: {{ ultimaAsistencia.fecha }}</p>
          <p>✓ Entrada: {{ ultimaAsistencia.hora_entrada ?? '—' }}</p>
          <p>✓ Salida: {{ ultimaAsistencia.hora_salida ?? '—' }}</p>
        </div>
      </template>
      <p v-else class="text-slate-400">Aún no tienes registros de asistencia.</p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import jsQR from 'jsqr'

export default {
  name: 'Checkin',
  setup() {
    const proyectos = ref([])
    const proyecto = ref('')
    const compania = ref('')
    const puesto = ref('')
    const codigoManual = ref('')
    const ubicacion = ref(null)
    const mensaje = ref('')
    const cargando = ref(false)
    const ultimaAsistencia = ref(null)
    const errores = ref({})

    const camaraActiva = ref(false)
    const qrVideo = ref(null)
    const qrCanvas = ref(null)
    let stream = null
    let animacionId = null

    // Hora local del dispositivo del empleado (no la del servidor), en
    // formato HH:mm:ss, tal como se guarda en la BD.
    const horaDispositivo = () => {
      const ahora = new Date()
      const dosDigitos = (n) => String(n).padStart(2, '0')
      return `${dosDigitos(ahora.getHours())}:${dosDigitos(ahora.getMinutes())}:${dosDigitos(ahora.getSeconds())}`
    }

    const cargarProyectos = async () => {
      const { data } = await axios.get('/asistencias/proyectos')
      proyectos.value = data.proyectos
    }

    const cargarUltimaAsistencia = async () => {
      const { data } = await axios.get('/asistencias/ultima')
      ultimaAsistencia.value = data.asistencia
    }

    const capturarUbicacion = () => {
      if (!navigator.geolocation) return
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          ubicacion.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
        },
        () => {
          mensaje.value = 'No se pudo obtener tu ubicación. Puedes continuar sin ella.'
        }
      )
    }

    const escanearFrame = () => {
      if (!camaraActiva.value || !qrVideo.value || qrVideo.value.readyState !== qrVideo.value.HAVE_ENOUGH_DATA) {
        animacionId = requestAnimationFrame(escanearFrame)
        return
      }

      const canvas = qrCanvas.value
      canvas.width = qrVideo.value.videoWidth
      canvas.height = qrVideo.value.videoHeight
      const contexto = canvas.getContext('2d')
      contexto.drawImage(qrVideo.value, 0, 0, canvas.width, canvas.height)

      const imagen = contexto.getImageData(0, 0, canvas.width, canvas.height)
      const codigo = jsQR(imagen.data, imagen.width, imagen.height)

      if (codigo) {
        codigoManual.value = codigo.data
        mensaje.value = 'Código QR detectado.'
        detenerCamara()
        return
      }

      animacionId = requestAnimationFrame(escanearFrame)
    }

    const abrirCamara = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        camaraActiva.value = true
        await new Promise((resolve) => setTimeout(resolve, 0))
        qrVideo.value.srcObject = stream
        await qrVideo.value.play()
        animacionId = requestAnimationFrame(escanearFrame)
      } catch (error) {
        mensaje.value = 'No se pudo acceder a la cámara. Usa el código manual.'
      }
    }

    const detenerCamara = () => {
      camaraActiva.value = false
      if (animacionId) cancelAnimationFrame(animacionId)
      if (stream) {
        stream.getTracks().forEach((track) => track.stop())
        stream = null
      }
    }

    const validar = () => {
      errores.value = {}
      if (!proyecto.value) errores.value.proyecto = 'Escribe el proyecto antes de continuar.'
      if (!compania.value) errores.value.compania = 'Escribe la compañía antes de continuar.'
      if (!puesto.value) errores.value.puesto = 'Escribe tu puesto antes de continuar.'
      return Object.keys(errores.value).length === 0
    }

    const confirmarEntrada = async () => {
      if (!validar()) return

      cargando.value = true
      try {
        const { data } = await axios.post('/asistencias/checkin', {
          proyecto: proyecto.value,
          compania: compania.value,
          puesto: puesto.value,
          codigo: codigoManual.value,
          hora_dispositivo: horaDispositivo(),
          ubicacion_lat: ubicacion.value?.lat ?? null,
          ubicacion_lng: ubicacion.value?.lng ?? null,
        })
        mensaje.value = `✓ Entrada registrada: ${data.horaEntrada} (${data.estado})`
        await cargarUltimaAsistencia()
      } catch (error) {
        mensaje.value = 'Error: ' + (error.response?.data?.message ?? 'No se pudo registrar la entrada.')
      } finally {
        cargando.value = false
      }
    }

    const confirmarSalida = async () => {
      cargando.value = true
      try {
        const { data } = await axios.post('/asistencias/checkout', {
          hora_dispositivo: horaDispositivo(),
        })
        mensaje.value = `✓ Salida registrada: ${data.horaSalida}`
        await cargarUltimaAsistencia()
      } catch (error) {
        mensaje.value = 'Error: ' + (error.response?.data?.message ?? 'No se pudo registrar la salida.')
      } finally {
        cargando.value = false
      }
    }

    onMounted(() => {
      capturarUbicacion()
      cargarProyectos()
      cargarUltimaAsistencia()
    })

    onBeforeUnmount(() => {
      detenerCamara()
    })

    return {
      proyectos,
      proyecto,
      compania,
      puesto,
      codigoManual,
      ubicacion,
      mensaje,
      cargando,
      ultimaAsistencia,
      errores,
      camaraActiva,
      qrVideo,
      qrCanvas,
      abrirCamara,
      detenerCamara,
      confirmarEntrada,
      confirmarSalida,
    }
  },
}
</script>
