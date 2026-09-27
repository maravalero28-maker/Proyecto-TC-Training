document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn = document.getElementById("hamburger-btn") || document.querySelector("menu-toggle");
    const sidebar = document.getElementById("sidebar");

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });

        document.addEventListener("click", function (e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                    sidebar.classList.remove("active");
                }
            }
        });
    }


    const btnTema = document.getElementById('theme-menu-btn');
    const menuOpciones = document.getElementById('theme-options');

    const temaGuardado = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const paletaGuardada = localStorage.getItem('palette') || 'default';
    const paletaModoGuardado = localStorage.getItem('palette-mode') || temaGuardado;

    document.documentElement.setAttribute('data-palette', paletaGuardada);
    if (paletaModoGuardado === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    else document.documentElement.removeAttribute('data-theme');

    if (btnTema && menuOpciones) {
        btnTema.addEventListener('click', e => {
            e.stopPropagation();
            menuOpciones.classList.toggle('show');
        });

        document.addEventListener('click', e => {
            const clickAjuera = !btnTema.contains(e.target) && !menuOpciones.contains(e.target);
            if (clickAjuera) menuOpciones.classList.remove('show');
        });

        menuOpciones.querySelectorAll('.theme-option-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const seleccion = btn.getAttribute('data-theme-value');

                if (seleccion === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    localStorage.setItem('palette-mode', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    localStorage.setItem('palette-mode', 'light');
                }

                menuOpciones.classList.remove('show');
            });
        });
    }

    const customColorInputs = Array.from(document.querySelectorAll('[data-custom-color]'));
    const customPaletteDefaults = Object.fromEntries(customColorInputs.map(input => [input.dataset.customColor, input.value]));
    let savedCustomPalette = {};
    try {
        savedCustomPalette = JSON.parse(localStorage.getItem('custom-palette') || '{}');
    } catch (error) {
        savedCustomPalette = {};
    }
    customColorInputs.forEach(input => {
        const savedColor = savedCustomPalette[input.dataset.customColor];
        if (typeof savedColor === 'string' && /^#[0-9a-f]{6}$/i.test(savedColor)) {
            input.value = savedColor;
        }
    });

    const getCustomPalette = () => {
        const colors = { ...customPaletteDefaults, ...savedCustomPalette };
        customColorInputs.forEach(input => {
            colors[input.dataset.customColor] = input.value;
        });
        return colors;
    };

    const applyPalette = (palette, mode, customColors = getCustomPalette()) => {
        const root = document.documentElement;
        root.setAttribute('data-palette', palette);
        if (palette === 'custom') {
            Object.entries(customColors).forEach(([name, color]) => {
                if (/^#[0-9a-f]{6}$/i.test(color)) root.style.setProperty(`--${name}`, color);
            });
        } else {
            customColorInputs.forEach(input => root.style.removeProperty(`--${input.dataset.customColor}`));
        }
        if (mode === 'dark') root.setAttribute('data-theme', 'dark');
        else root.removeAttribute('data-theme');
    };

    const paletteRadios = document.querySelectorAll('input[name="palette"]');
    if (paletteRadios) {
        paletteRadios.forEach(r => {
            if (r.value === paletaGuardada) {
                r.checked = true;
            } else {
                r.checked = false;
            }
        });
    }

    const paletteModeSelect = document.getElementById('palette-mode-select');
    if (paletteModeSelect) {
        paletteModeSelect.value = paletaModoGuardado === 'dark' ? 'dark' : 'light';
    }

    applyPalette(paletaGuardada, paletaModoGuardado);

    var getSelectedPalette = function () {
        var selected = document.querySelector('input[name="palette"]:checked');
        return selected ? selected.value : 'default';
    };

    var previewPalette = function () {
        var paletteValue = getSelectedPalette();
        var mode = 'light';
        if (paletteModeSelect) {
            mode = paletteModeSelect.value === 'dark' ? 'dark' : 'light';
        } else {
            mode = localStorage.getItem('palette-mode') === 'dark' ? 'dark' : 'light';
        }

        applyPalette(paletteValue, mode);
    };

    var persistPalette = function () {
        var paletteValue = getSelectedPalette();
        var mode = 'light';
        if (paletteModeSelect) {
            mode = paletteModeSelect.value === 'dark' ? 'dark' : 'light';
        } else {
            mode = localStorage.getItem('palette-mode') === 'dark' ? 'dark' : 'light';
        }
        localStorage.setItem('palette', paletteValue);
        localStorage.setItem('palette-mode', mode);
        localStorage.setItem('custom-palette', JSON.stringify(getCustomPalette()));
        applyPalette(paletteValue, mode);
    };

    if (paletteRadios) {
        paletteRadios.forEach(r => {
            r.addEventListener('change', previewPalette);
        });
    }

    if (paletteModeSelect) {
        paletteModeSelect.addEventListener('change', previewPalette);
    }

    customColorInputs.forEach(input => {
        input.addEventListener('input', () => {
            const customRadio = document.querySelector('input[name="palette"][value="custom"]');
            if (customRadio) customRadio.checked = true;
            previewPalette();
        });
    });

    var settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function () {
            persistPalette();
        });
    }

    window.toggleAdminPanel = function () {
        const adminCardElement = document.getElementById('admin-management-card');
        if (!adminCardElement) return;
        if (adminCardElement.style.display === 'none' || adminCardElement.style.display === '') {
            adminCardElement.style.display = 'block';
        } else {
            adminCardElement.style.display = 'none';
        }
    };


    const cajaBusqueda = document.getElementById('table-search');
    const cuerpoTabla = document.querySelector('.payments-table tbody');
    const btnAtras = document.getElementById('btn-prev-page');
    const btnAdelante = document.getElementById('btn-next-page');
    const txtInfo = document.getElementById('pagination-info');

    if (cuerpoTabla) {
        let filasObj = Array.from(cuerpoTabla.querySelectorAll('tr'));

        filasObj.sort((a, b) => {
            const valA = a.querySelector('td:first-child').textContent.trim().toLowerCase();
            const valB = b.querySelector('td:first-child').textContent.trim().toLowerCase();
            return valA.localeCompare(valB);
        });

        cuerpoTabla.innerHTML = '';
        filasObj.forEach(f => cuerpoTabla.appendChild(f));

        let pagActual = 1;
        const LIMITE = 10;
        let filasActivas = [...filasObj];

        const actualizarVista = () => {
            const total = filasActivas.length;
            const pagMax = Math.ceil(total / LIMITE) || 1;

            if (pagActual > pagMax) pagActual = pagMax;
            if (pagActual < 1) pagActual = 1;

            const inicio = (pagActual - 1) * LIMITE;
            const fin = inicio + LIMITE;

            filasObj.forEach(f => f.style.display = 'none');

            filasActivas.forEach((f, i) => {
                if (i >= inicio && i < fin) f.style.display = '';
            });

            if (txtInfo && btnAtras && btnAdelante) {
                const limiteFinal = Math.min(fin, total);
                txtInfo.textContent = `Mostrando ${total === 0 ? 0 : inicio + 1}-${limiteFinal} de ${total} clientes`;
                btnAtras.disabled = pagActual === 1;
                btnAdelante.disabled = pagActual === pagMax;
            }
        };

        actualizarVista();

        if (btnAtras && btnAdelante) {
            btnAtras.addEventListener('click', () => {
                if (pagActual <= 1) return;
                pagActual--;
                actualizarVista();
            });

            btnAdelante.addEventListener('click', () => {
                const pagMax = Math.ceil(filasActivas.length / LIMITE);
                if (pagActual >= pagMax) return;
                pagActual++;
                actualizarVista();
            });
        }

        if (cajaBusqueda) {
            cajaBusqueda.addEventListener('input', e => {
                const query = e.target.value.toLowerCase();
                filasActivas = filasObj.filter(f => {
                    const nombreStr = f.querySelector('td:first-child').textContent.trim().toLowerCase();
                    return nombreStr.includes(query);
                });
                pagActual = 1;
                actualizarVista();
            });
        }


        const toastContainer = document.getElementById('toast-container');
        const showToast = (message, type = 'info', timeout = 5000) => {
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.dataset.type = type;

            const content = document.createElement('div');
            content.className = 'toast-content';
            content.textContent = message;

            const closeBtn = document.createElement('button');
            closeBtn.className = 'toast-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.setAttribute('aria-label', 'Cerrar');

            toast.appendChild(content);
            toast.appendChild(closeBtn);


            toast.style.transform = 'translateX(120%)';
            toast.style.opacity = '0';
            toastContainer.appendChild(toast);


            requestAnimationFrame(() => {
                toast.style.transition = 'transform 320ms cubic-bezier(.2,.9,.2,1), opacity 240ms ease';
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            });

            let hideTimer = setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => toast.remove(), 350);
            }, timeout);

            closeBtn.addEventListener('click', () => {
                clearTimeout(hideTimer);
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => toast.remove(), 220);
            });
        };


        const serverMsg = document.body.dataset.serverMessage;
        const serverMsgType = document.body.dataset.serverMessageType || 'info';
        if (serverMsg) {
            showToast(serverMsg, serverMsgType === 'success' ? 'success' : (serverMsgType === 'error' ? 'danger' : 'info'), 5000);

            delete document.body.dataset.serverMessage;
            delete document.body.dataset.serverMessageType;
        }

        const eliminarUsuario = async (id, rowElement) => {
            try {
                const resp = await fetch('../controlador/eliminar_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {

                    if (rowElement) rowElement.remove();
                    filasObj = filasObj.filter(f => f !== rowElement);
                    filasActivas = filasActivas.filter(f => f !== rowElement);
                    actualizarVista();
                    showToast(data.message || 'Usuario eliminado', 'success');
                } else {
                    showToast(data.message || 'Error al eliminar', 'danger');
                }
            } catch (err) {
                showToast('Error de red', 'danger');
            }
        };
    }
});