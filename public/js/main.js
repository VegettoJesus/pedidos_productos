const menuItemsDropDown = document.querySelectorAll('.menu-item-dropdown')
const menusItemsStatic = document.querySelectorAll('.menu-item-static')
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menu-btn');
const sidebarBtn = document.getElementById('sidebar-btn')
const darkModeBtn = document.getElementById('dark-mode-btn');
let mensajesGlobalLoader = "";
window.iconosGlobales = [];

darkModeBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');

    fetch('/toggle-dark-mode', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(res => res.json())
    .catch(err => console.error(err));
});

sidebarBtn.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-hidden')
});

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('minimize');
});

menuItemsDropDown.forEach((menuItem)=>{
    menuItem.addEventListener('click',()=>{
        console.log(menuItemsDropDown)
        const subMenu = menuItem.querySelector('.sub-menu');
        const isActive = menuItem.classList.toggle('sub-menu-toggle');
        if(subMenu){
            if(isActive){
                subMenu.style.height = `${subMenu.scrollHeight + 6}px`
                subMenu.style.padding = '0.2rem 0'
                subMenu.style.width = 'max-content'
            }else{
                subMenu.style.height = '0';
                subMenu.style.padding = '0';
                subMenu.style.width = 'max-content'
            }
        }
        menuItemsDropDown.forEach((item)=>{
            if(item !== menuItem){
                const otherSubmenu = item.querySelector('.sub-menu');
                if(otherSubmenu){
                    item.classList.remove('sub-menu-toggle');
                    otherSubmenu.style.height = '0';
                    otherSubmenu.style.padding = '0';
                }
            }
        })
    });
})
menusItemsStatic.forEach((menuItem) =>{
    menuItem.addEventListener('mouseenter', () =>{

        if(!sidebar.classList.contains('minimize')) return;

        menuItemsDropDown.forEach((item)=>{
            const otherSubmenu = item.querySelector('.sub-menu');
            if(otherSubmenu){
                item.classList.remove('sub-menu-toggle');
                otherSubmenu.style.height = '0';
                otherSubmenu.style.padding = '0';
            }
        })
    })
})
function checkWindowsSize(){
    sidebar.classList.remove('minimize')
}
checkWindowsSize();
window.addEventListener('resize',checkWindowsSize);

let statusInterval; 

function showPreloader(title = 'Procesando...', action = 'default') {
    const statusMessagesMap = {
        cargar: [
            "Conectando con el servidor...",
            "Obteniendo registros...",
            "Procesando información...",
            "Generando tabla...",
            "Aplicando filtros...",
            "¡Datos cargados correctamente!"
        ],
        eliminar: [
            "Preparando eliminación...",
            "Validando permisos...",
            "Eliminando registros...",
            "Liberando espacio...",
            "Actualizando vista...",
            "¡Registros eliminados!"
        ],
        editar: [
            "Verificando cambios...",
            "Validando datos...",
            "Guardando información...",
            "Aplicando modificaciones...",
            "Sincronizando...",
            "¡Edición completada!"
        ],
        default: [
            "Iniciando proceso...",
            "Verificando información...",
            "Procesando datos...",
            "Realizando cambios...",
            "Finalizando...",
            "¡Proceso completado!"
        ],
        registrar: [
            "Verificando cambios...",
            "Validando datos...",
            "Guardando información...",
            "Aplicando registro...",
            "Sincronizando...",
            "Registro completado!"
        ],
    };

    const statusMessages = statusMessagesMap[action] || statusMessagesMap.default;

    Swal.fire({
        title: title,
        html: `
            <div class="preloader-container">
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <div class="status-text" id="status-text">${statusMessages[0]}</div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            let currentStatus = 0;
            const statusElement = document.getElementById('status-text');
            const progressBar = document.getElementById('progress-bar');

            const totalSteps = statusMessages.length - 1;
            const intervalTime = 1000;
            const stepPercent = 100 / totalSteps;

            statusInterval = setInterval(() => {
                if (currentStatus < totalSteps) {
                    currentStatus++;
                    statusElement.textContent = statusMessages[currentStatus];
                    progressBar.style.width = (currentStatus * stepPercent) + "%";
                } else {
                    clearInterval(statusInterval);
                }
            }, intervalTime);

            Swal.getPopup().addEventListener('hidden', () => {
                clearInterval(statusInterval);
            });

            // Estilos
            if (!document.querySelector("#preloader-style")) {
                const style = document.createElement("style");
                style.id = "preloader-style";
                style.innerHTML = `
                :root {
                    --primary-color: #f35b08;
                    --secondary-color: #fd7e14;
                }
                .preloader-container { padding: 2rem; text-align: center; }
                .progress-container {
                    height: 14px; background-color: #f0f0f0;
                    border-radius: 10px; overflow: hidden; margin: 1.5rem 0;
                }
                .progress-bar {
                    height: 100%; width: 0%;
                    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
                    border-radius: 10px; transition: width 1s ease;
                }
                .status-text {
                    font-size: 0.95rem; color: #333;
                    margin-top: 1rem; font-weight: 500; min-height: 1.5rem;
                }
                .swal2-popup { border-radius: 15px; padding: 2rem; }
                .swal2-title { margin-bottom: 1rem !important; }
                `;
                document.head.appendChild(style);
            }
        }
    });

    return statusMessages;
}

function hidePreloader(statusMessages) {
    if (Swal.isVisible()) {
        const statusElement = document.getElementById('status-text');
        const progressBar = document.getElementById('progress-bar');

        clearInterval(statusInterval);

        // Forzar último estado
        statusElement.textContent = statusMessages[statusMessages.length - 1];
        progressBar.style.width = "100%";

        // Dar tiempo a que el usuario lo vea
        setTimeout(() => {
            Swal.close();
        }, 600);
    }
}

function cargarIconosGlobales(callback) {
    $.ajax({
        url: "/get-iconos",
        dataType: "text",
        success: function (data) {
            window.iconosGlobales = data
                .split(/[\n,]+/)
                .map(icon => icon.trim())
                .filter(icon => icon !== "");
            if (callback) callback();
        },
        error: function (xhr, status, error) {
            console.error("Error cargando iconos.csv:", error);
        }
    });
}

// Genera grilla de iconos con filtro
function generarGridIconos(seleccionado = "", filtro = "") {
    if (seleccionado.startsWith("bi bi-")) seleccionado = seleccionado.replace("bi bi-", "");

    const filtrados = window.iconosGlobales
        .filter(icon => icon.toLowerCase().includes(filtro.toLowerCase()));

    if (filtrados.length === 0) {
        return `<div style="width:100%; text-align:center; padding:20px; color:#888;">
                    😕 No se encontraron iconos
                </div>`;
    }

    return filtrados
        .map(icon => `
            <button class="icon-btn ${icon === seleccionado ? 'selected' : ''}" data-icon="${icon}">
                <i class="bi bi-${icon}" style="font-size:20px;"></i>
            </button>
        `).join("");
}

// Asigna eventos a botones de iconos dentro de un modal
function bindBotones(contenedor, inputHidden) {
    const buttons = contenedor.querySelectorAll('.icon-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            inputHidden.value = btn.dataset.icon;
        });
    });
}