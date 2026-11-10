import './bootstrap';
import { createApp } from 'vue';
import Checkin from './components/Checkin.vue';
import MiNomina from './components/MiNomina.vue';
import Dashboard from './components/Dashboard.vue';
import NominasAdmin from './components/NominasAdmin.vue';
import UsuariosPendientes from './components/UsuariosPendientes.vue';
import UsuariosAdmin from './components/UsuariosAdmin.vue';
import DocumentosLegalesAdmin from './components/DocumentosLegalesAdmin.vue';
import AsistenciasAdmin from './components/AsistenciasAdmin.vue';
import DocumentosProyecto from './components/DocumentosProyecto.vue';
import Formatos from './components/Formatos.vue';
import DocumentosBosOfs from './components/DocumentosBosOfs.vue';

const componentes = {
    '#app-checkin': Checkin,
    '#app-mi-nomina': MiNomina,
    '#app-dashboard': Dashboard,
    '#app-nominas-admin': NominasAdmin,
    '#app-usuarios-pendientes': UsuariosPendientes,
    '#app-asistencias-admin': AsistenciasAdmin,
    '#app-documentos-proyecto': DocumentosProyecto,
    '#app-formatos': Formatos,
    '#app-documentos-bos-ofs': DocumentosBosOfs,
};

Object.entries(componentes).forEach(([selector, componente]) => {
    const el = document.querySelector(selector);

    if (el) {
        createApp(componente).mount(el);
    }
});

// Componentes que necesitan props leídos desde data-* attributes del Blade.
const elDocumentosLegales = document.querySelector('#app-documentos-legales-admin');
if (elDocumentosLegales) {
    createApp(DocumentosLegalesAdmin, {
        usuarioId: Number(elDocumentosLegales.dataset.usuarioId),
        usuarioNombre: elDocumentosLegales.dataset.usuarioNombre,
    }).mount(elDocumentosLegales);
}

const elUsuariosAdmin = document.querySelector('#app-usuarios-admin');
if (elUsuariosAdmin) {
    createApp(UsuariosAdmin, {
        status: elUsuariosAdmin.dataset.status || null,
    }).mount(elUsuariosAdmin);
}
