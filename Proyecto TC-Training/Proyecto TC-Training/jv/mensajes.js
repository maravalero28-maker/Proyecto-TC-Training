(function () {
    'use strict';

    const URL_CONTROLADOR = '../controlador/MensajeController.php';
    const ETIQUETAS_TIPO = { prevencion: 'Prevención', cobranza: 'Cobranza', promocion: 'Promoción', otro: 'Otro' };

    const metaPais = document.querySelector('meta[name="codigo-pais"]');
    const CODIGO_PAIS = metaPais ? metaPais.content : '58';

    const selectPlantilla = document.getElementById('msg-plantilla');
    const textarea = document.getElementById('msg-texto');
    const preview = document.getElementById('msg-preview');
    const estado = document.getElementById('msg-estado');
    const contador = document.getElementById('msg-contador');
    if (!selectPlantilla || !textarea) return;

    let plantillas = [];
    try {
        plantillas = JSON.parse(document.getElementById('plantillas-json').textContent) || [];
    } catch (e) {
        plantillas = [];
    }

    // Cliente de referencia para la vista previa: lo define cada pantalla.
    let obtenerClienteVista = () => null;

    function normalizarTelefono(tel) {
        let d = String(tel || '').replace(/\D/g, '');
        if (!d) return '';
        if (d.startsWith('00')) return d.slice(2);
        if (d.startsWith('0')) return CODIGO_PAIS + d.slice(1);
        if (d.length <= 10) return CODIGO_PAIS + d;
        return d;
    }

    function aplicarVariables(texto, c) {
        const cliente = c || { nombre: '', apellido: '', plan: '', dias: '' };
        return texto
            .replace(/\{nombre\}/gi, cliente.nombre || '')
            .replace(/\{apellido\}/gi, cliente.apellido || '')
            .replace(/\{plan\}/gi, cliente.plan || '')
            .replace(/\{dias\}/gi, cliente.dias !== undefined && cliente.dias !== '' ? cliente.dias : '');
    }

    function canalElegido() {
        const r = document.querySelector('input[name="msg-canal"]:checked');
        return r ? r.value : 'whatsapp';
    }

    function construirEnlace(canal, telefono, mensaje) {
        const num = normalizarTelefono(telefono);
        if (!num) return '';
        const texto = encodeURIComponent(mensaje);
        return canal === 'sms'
            ? 'sms:+' + num + '?body=' + texto
            : 'https://wa.me/' + num + '?text=' + texto;
    }

    function mostrarEstado(texto, esError) {
        if (!estado) return;
        estado.textContent = texto;
        estado.classList.toggle('msg-estado-error', !!esError);
    }

    function plantillaActual() {
        const id = parseInt(selectPlantilla.value, 10);
        return plantillas.find(p => parseInt(p.id_plantilla, 10) === id) || null;
    }

    function actualizarVista() {
        if (contador) contador.textContent = textarea.value.length + '/1000';
        if (preview) {
            const base = aplicarVariables(textarea.value, obtenerClienteVista());
            preview.textContent = base || 'Escribe o elige un mensaje.';
        }
        document.dispatchEvent(new CustomEvent('mensaje:cambio'));
    }

    function actualizarBotonesTipo() {
        const p = plantillaActual();
        const btnPred = document.getElementById('btn-predeterminar');
        const btnDel = document.getElementById('btn-eliminar-tipo');
        const esPred = p && parseInt(p.es_predeterminada, 10) === 1;
        if (btnPred) btnPred.disabled = !p || esPred;
        if (btnDel) btnDel.disabled = !p || esPred;
    }

    function llenarSelect(idSeleccion) {
        selectPlantilla.innerHTML = '';
        plantillas.forEach(p => {
            const op = document.createElement('option');
            op.value = p.id_plantilla;
            const etiqueta = ETIQUETAS_TIPO[p.tipo] || 'Otro';
            const estrella = parseInt(p.es_predeterminada, 10) === 1 ? '★ ' : '';
            op.textContent = estrella + p.titulo + ' (' + etiqueta + ')';
            selectPlantilla.appendChild(op);
        });

        let elegida = plantillas.find(p => parseInt(p.id_plantilla, 10) === parseInt(idSeleccion, 10));
        if (!elegida) elegida = plantillas.find(p => parseInt(p.es_predeterminada, 10) === 1) || plantillas[0];
        if (elegida) {
            selectPlantilla.value = elegida.id_plantilla;
            textarea.value = elegida.contenido;
        } else {
            textarea.value = '';
        }
        actualizarBotonesTipo();
        actualizarVista();
    }

    async function llamar(accion, datos) {
        const fd = new FormData();
        Object.keys(datos).forEach(k => fd.append(k, datos[k]));
        try {
            const resp = await fetch(URL_CONTROLADOR + '?action=' + accion, { method: 'POST', body: fd });
            return await resp.json();
        } catch (e) {
            return { ok: false, mensaje: 'No se pudo conectar con el servidor.' };
        }
    }

    selectPlantilla.addEventListener('change', () => {
        const p = plantillaActual();
        if (p) textarea.value = p.contenido;
        mostrarEstado('');
        actualizarBotonesTipo();
        actualizarVista();
    });

    textarea.addEventListener('input', actualizarVista);

    const btnGuardar = document.getElementById('btn-guardar-tipo');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', async () => {
            const titulo = document.getElementById('msg-titulo');
            const tipo = document.getElementById('msg-tipo');
            if (!titulo.value.trim() || !textarea.value.trim()) {
                mostrarEstado('Escribe un título y el texto del mensaje.', true);
                return;
            }
            const res = await llamar('guardar', { titulo: titulo.value, tipo: tipo.value, contenido: textarea.value });
            if (res.ok && res.plantilla) {
                plantillas.push(res.plantilla);
                titulo.value = '';
                llenarSelect(res.plantilla.id_plantilla);
                mostrarEstado('Tipo de mensaje guardado.');
            } else {
                mostrarEstado(res.mensaje || 'No se pudo guardar.', true);
            }
        });
    }

    const btnPred = document.getElementById('btn-predeterminar');
    if (btnPred) {
        btnPred.addEventListener('click', async () => {
            const p = plantillaActual();
            if (!p) return;
            const res = await llamar('predeterminar', { id: p.id_plantilla });
            if (res.ok) {
                plantillas.forEach(x => { x.es_predeterminada = (x.id_plantilla === p.id_plantilla) ? 1 : 0; });
                plantillas.sort((a, b) => (b.es_predeterminada - a.es_predeterminada) || a.titulo.localeCompare(b.titulo));
                llenarSelect(p.id_plantilla);
                mostrarEstado('Ahora es el mensaje predeterminado.');
            } else {
                mostrarEstado(res.mensaje || 'No se pudo cambiar.', true);
            }
        });
    }

    const btnEliminar = document.getElementById('btn-eliminar-tipo');
    if (btnEliminar) {
        btnEliminar.addEventListener('click', async () => {
            const p = plantillaActual();
            if (!p) return;
            if (!confirm('¿Eliminar el tipo de mensaje "' + p.titulo + '"?')) return;
            const res = await llamar('eliminar', { id: p.id_plantilla });
            if (res.ok) {
                plantillas = plantillas.filter(x => x.id_plantilla !== p.id_plantilla);
                llenarSelect(null);
                mostrarEstado('Tipo de mensaje eliminado.');
            } else {
                mostrarEstado(res.mensaje || 'No se pudo eliminar.', true);
            }
        });
    }

    document.querySelectorAll('input[name="msg-canal"]').forEach(r => r.addEventListener('change', () => {
        document.dispatchEvent(new CustomEvent('mensaje:cambio'));
    }));

    /* ---------- Pantalla de clientes: ventana individual ---------- */
    const dialogo = document.getElementById('dlg-mensaje');
    if (dialogo) {
        let clienteActual = null;
        obtenerClienteVista = () => clienteActual;

        document.addEventListener('click', e => {
            const boton = e.target.closest('.btn-enviar-mensaje');
            if (!boton) return;
            clienteActual = {
                nombre: boton.dataset.nombre,
                apellido: boton.dataset.apellido,
                telefono: boton.dataset.telefono,
                plan: boton.dataset.plan,
                dias: boton.dataset.dias
            };
            document.getElementById('dlg-destinatario').textContent =
                clienteActual.nombre + ' ' + clienteActual.apellido + ' · ' + (clienteActual.telefono || 'sin teléfono');
            mostrarEstado('');
            llenarSelect(null);
            dialogo.showModal();
        });

        const cerrar = () => dialogo.close();
        document.getElementById('dlg-cerrar').addEventListener('click', cerrar);
        document.getElementById('dlg-cancelar').addEventListener('click', cerrar);
        dialogo.addEventListener('click', e => { if (e.target === dialogo) cerrar(); });

        document.getElementById('btn-enviar').addEventListener('click', () => {
            if (!clienteActual) return;
            const mensaje = aplicarVariables(textarea.value, clienteActual).trim();
            if (!mensaje) {
                mostrarEstado('El mensaje está vacío.', true);
                return;
            }
            const enlace = construirEnlace(canalElegido(), clienteActual.telefono, mensaje);
            if (!enlace) {
                mostrarEstado('Este cliente no tiene un teléfono válido.', true);
                return;
            }
            window.open(enlace, '_blank', 'noopener');
            mostrarEstado('Se abrió el chat. Pulsa enviar dentro de la aplicación.');
        });
        return;
    }

    /* ---------- Pantalla de mensaje masivo ---------- */
    const tabla = document.getElementById('tabla-masivo');
    if (tabla) {
        const filas = () => Array.from(tabla.querySelectorAll('tbody tr'));
        const datosFila = tr => ({
            nombre: tr.dataset.nombre,
            apellido: tr.dataset.apellido,
            telefono: tr.dataset.telefono,
            plan: tr.dataset.plan,
            dias: tr.dataset.dias
        });
        const seleccionadas = () => filas().filter(tr => tr.querySelector('.chk-cliente').checked && tr.style.display !== 'none');
        const pendientes = () => seleccionadas().filter(tr => tr.dataset.enviado !== '1');

        obtenerClienteVista = () => {
            const primera = seleccionadas()[0] || filas()[0];
            return primera ? datosFila(primera) : null;
        };

        const infoSel = document.getElementById('masivo-info');
        const btnSiguiente = document.getElementById('btn-siguiente');

        const refrescarResumen = () => {
            const sel = seleccionadas().length;
            const pend = pendientes().length;
            if (infoSel) infoSel.textContent = sel + ' seleccionados · ' + pend + ' pendientes';
            if (btnSiguiente) btnSiguiente.disabled = pend === 0;
        };

        const marcarEnviado = tr => {
            tr.dataset.enviado = '1';
            const celda = tr.querySelector('.celda-estado');
            if (celda) {
                celda.textContent = 'Abierto';
                celda.className = 'celda-estado badge badge-active';
            }
        };

        const abrirChat = tr => {
            const c = datosFila(tr);
            const mensaje = aplicarVariables(textarea.value, c).trim();
            if (!mensaje) {
                mostrarEstado('El mensaje está vacío.', true);
                return false;
            }
            const enlace = construirEnlace(canalElegido(), c.telefono, mensaje);
            if (!enlace) {
                mostrarEstado('Sin teléfono válido: ' + c.nombre + ' ' + c.apellido, true);
                return false;
            }
            window.open(enlace, '_blank', 'noopener');
            marcarEnviado(tr);
            refrescarResumen();
            return true;
        };

        tabla.addEventListener('click', e => {
            const b = e.target.closest('.btn-fila-enviar');
            if (b) abrirChat(b.closest('tr'));
        });

        tabla.addEventListener('change', e => {
            if (e.target.classList.contains('chk-cliente')) {
                actualizarVista();
                refrescarResumen();
            }
        });

        const chkTodos = document.getElementById('chk-todos');
        if (chkTodos) {
            chkTodos.addEventListener('change', () => {
                filas().forEach(tr => {
                    if (tr.style.display !== 'none') tr.querySelector('.chk-cliente').checked = chkTodos.checked;
                });
                actualizarVista();
                refrescarResumen();
            });
        }

        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', () => {
                const siguiente = pendientes()[0];
                if (!siguiente) return;
                abrirChat(siguiente);
                siguiente.scrollIntoView({ block: 'center', behavior: 'smooth' });
            });
        }

        const filtro = document.getElementById('masivo-filtro');
        const buscador = document.getElementById('masivo-buscar');
        const aplicarFiltros = () => {
            const f = filtro ? filtro.value : 'todos';
            const q = buscador ? buscador.value.trim().toLowerCase() : '';
            filas().forEach(tr => {
                const okEstado = f === 'todos' || tr.dataset.estatus === f;
                const okTexto = !q || (tr.dataset.nombre + ' ' + tr.dataset.apellido).toLowerCase().includes(q);
                tr.style.display = (okEstado && okTexto) ? '' : 'none';
            });
            refrescarResumen();
            actualizarVista();
        };
        if (filtro) filtro.addEventListener('change', aplicarFiltros);
        if (buscador) buscador.addEventListener('input', aplicarFiltros);

        document.addEventListener('mensaje:cambio', refrescarResumen);
        llenarSelect(null);
        refrescarResumen();
    }
})();
