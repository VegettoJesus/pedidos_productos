<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ ConfiguracionHelper::getFavicon() }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ ConfiguracionHelper::getFavicon() }}" type="image/x-icon">
    <title>@yield('title', ConfiguracionHelper::getPageTitle())</title>
    <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    @isset($css)
        <link href="{{ asset($css) }}" rel="stylesheet">
    @endisset
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

    <!-- JSZip (para exportar a Excel) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Buttons JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>

    <!-- PDFMake (para exportar a PDF) -->
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>

    <!-- vfs_fonts (para PDF) -->
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        .cart-loader {
            --loader-scale: 1;
            position: relative;
            width: 160px;
            height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            transform: scale(var(--loader-scale));
            transform-origin: center center;
            margin: 0 auto;
        }
        @media (max-width: 768px) { .cart-loader { --loader-scale: 0.85; } }
        @media (max-width: 480px) { .cart-loader { --loader-scale: 0.7; } }

        .items-container {
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            height: 100px;
            z-index: 1;
        }

        .item {
            position: absolute;
            opacity: 0;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            animation: drop-item 4s cubic-bezier(0.3, 0, 0.5, 1) infinite;
        }

        #item-mobile {
            top: -15px;
            left: 58px;
            width: 20px;
            height: 32px;
            --end-rot: -15deg;
            animation-delay: 0.05s;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 36' xmlns='http://www.w3.org/2000/svg'%3E%3Crect x='2' y='2' width='20' height='32' rx='3' fill='%233b82f6'/%3E%3Crect x='4' y='4' width='16' height='25' rx='1' fill='%23eff6ff'/%3E%3Ccircle cx='12' cy='31.5' r='1.5' fill='%23eff6ff'/%3E%3C/svg%3E");
        }

        #item-laptop {
            top: -10px;
            left: 70px;
            width: 35px;
            height: 26px;
            --end-rot: 10deg;
            animation-delay: 0.8s;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 40 30' xmlns='http://www.w3.org/2000/svg'%3E%3Crect x='6' y='4' width='28' height='18' rx='1' fill='%2364748b'/%3E%3Crect x='8' y='6' width='24' height='14' fill='%23cbd5e1'/%3E%3Cpolygon points='2,24 38,24 40,28 0,28' fill='%23334155' stroke-linejoin='round'/%3E%3C/svg%3E");
        }

        #item-tab {
            top: -20px;
            left: 85px;
            width: 24px;
            height: 32px;
            --end-rot: 25deg;
            animation-delay: 1.6s;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 32 40' xmlns='http://www.w3.org/2000/svg'%3E%3Crect x='2' y='2' width='28' height='36' rx='2' fill='%23a855f7'/%3E%3Crect x='4' y='4' width='24' height='32' fill='%23faf5ff'/%3E%3C/svg%3E");
        }

        #item-headphone {
            top: -15px;
            left: 58px;
            width: 28px;
            height: 28px;
            --end-rot: -5deg;
            animation-delay: 2.4s;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M 6 16 C 6 4, 26 4, 26 16' fill='none' stroke='%23ef4444' stroke-width='4'/%3E%3Crect x='2' y='14' width='8' height='14' rx='4' fill='%23ef4444'/%3E%3Crect x='22' y='14' width='8' height='14' rx='4' fill='%23ef4444'/%3E%3C/svg%3E");
        }

        #item-mixer {
            top: -25px;
            left: 75px;
            width: 26px;
            height: 34px;
            --end-rot: 5deg;
            animation-delay: 3.2s;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 32 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M 8 20 L 24 20 L 28 36 L 4 36 Z' fill='%2314b8a6' stroke-linejoin='round'/%3E%3Ccircle cx='16' cy='28' r='4' fill='%23ccfbf1'/%3E%3Cpolygon points='10,20 22,20 24,8 8,8' fill='%23cbd5e1'/%3E%3Crect x='6' y='4' width='20' height='4' rx='2' fill='%230f766e'/%3E%3Cpath d='M 8 10 L 3 10 L 3 18 L 8 18' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linejoin='round'/%3E%3C/svg%3E");
        }

        #cart-icon {
            position: relative;
            z-index: 2;
            width: 140px;
            height: 120px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 140 120' width='140' height='120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23334155' stroke-width='5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='35' y1='90' x2='110' y2='90' /%3E%3Cline x1='40' y1='90' x2='50' y2='70' /%3E%3Cpolyline points='10,15 25,15 40,30' /%3E%3Cline x1='40' y1='30' x2='50' y2='70' /%3E%3Cline x1='68' y1='30' x2='71' y2='70' /%3E%3Cline x1='96' y1='30' x2='93' y2='70' /%3E%3Cline x1='125' y1='30' x2='115' y2='70' /%3E%3Cline x1='40' y1='30' x2='125' y2='30' /%3E%3Cline x1='43' y1='43' x2='122' y2='43' /%3E%3Cline x1='47' y1='57' x2='118' y2='57' /%3E%3Cline x1='50' y1='70' x2='115' y2='70' /%3E%3Ccircle cx='45' cy='105' r='8' /%3E%3Ccircle cx='105' cy='105' r='8' /%3E%3C/g%3E%3C/svg%3E");
            animation: cart-bounce 0.8s ease-in-out infinite;
            animation-delay: 0.2s;
        }

        .loading-text {
            margin-top: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .dot {
            display: inline-block;
            animation: wave 1.5s infinite;
        }
        .dot:nth-child(1) { animation-delay: 0s; }
        .dot:nth-child(2) { animation-delay: 0.1s; }
        .dot:nth-child(3) { animation-delay: 0.2s; }

        @keyframes drop-item {
            0% { transform: translateY(-20px) scale(0.8) rotate(0deg); opacity: 0; }
            10% { opacity: 1; transform: translateY(20px) scale(1) rotate(calc(var(--end-rot) / 2)); }
            25% { transform: translateY(55px) scale(1) rotate(var(--end-rot)); opacity: 1; }
            35%, 100% { transform: translateY(75px) scale(0.9) rotate(var(--end-rot)); opacity: 0; }
        }

        @keyframes cart-bounce {
            0%, 100% { transform: translateY(0); }
            40% { transform: translateY(2.5px); }
            60% { transform: translateY(0); }
        }

        @keyframes wave {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-3px); }
        }

        .cart-loader-popup {
            background: transparent !important;
            box-shadow: none !important;
            border-radius: 20px !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
        }

        .cart-loader-container {
            padding: 0 !important;
        }

        .cart-loader-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            min-width: 250px;
            min-height: 250px;
        }

        @media (max-width: 480px) {
            .cart-loader-popup {
                width: 90vw !important;
                max-width: 320px !important;
            }
            .cart-loader-wrapper {
                padding: 10px;
                min-height: 200px;
            }
        }
    </style>

    <script>
        class CartLoader {
            constructor() {
                this.isLoading = false;
                this.timer = null;
                this.swalInstance = null;
                this.defaultOptions = {
                    title: 'Procesando...',
                    text: 'Estamos añadiendo tu producto al carrito',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: this.injectLoader.bind(this),
                    customClass: {
                        popup: 'cart-loader-popup',
                        container: 'cart-loader-container'
                    }
                };
            }

            injectLoader(popup) {
                if (popup.querySelector('.cart-loader-wrapper')) {
                    return;
                }

                const loaderContainer = document.createElement('div');
                loaderContainer.className = 'cart-loader-wrapper';
                loaderContainer.innerHTML = `
                    <div class="cart-loader">
                        <div class="items-container">
                            <div id="item-mobile" class="item"></div>
                            <div id="item-laptop" class="item"></div>
                            <div id="item-tab" class="item"></div>
                            <div id="item-headphone" class="item"></div>
                            <div id="item-mixer" class="item"></div>
                        </div>
                        <div id="cart-icon"></div>
                        <div class="loading-text">
                            Cargando<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
                        </div>
                    </div>
                `;

                const content = popup.querySelector('.swal2-html-container');
                if (content) {
                    content.innerHTML = '';
                    content.appendChild(loaderContainer);
                } else {
                    popup.appendChild(loaderContainer);
                }

                popup.style.padding = '20px';
                popup.style.borderRadius = '20px';
                popup.style.background = 'rgba(255, 255, 255, 0.95)';
                popup.style.backdropFilter = 'blur(10px)';
            }

            show(options = {}) {
                if (this.isLoading) {
                    return;
                }

                this.isLoading = true;

                const mergedOptions = {
                    ...this.defaultOptions,
                    ...options,
                    didOpen: (popup) => {
                        this.injectLoader(popup);
                        if (options.didOpen) {
                            options.didOpen(popup);
                        }
                    }
                };

                if (options.autoClose) {
                    if (this.timer) {
                        clearTimeout(this.timer);
                    }
                    this.timer = setTimeout(() => {
                        this.close();
                    }, options.autoClose);
                }

                this.swalInstance = Swal.fire(mergedOptions);
                return this.swalInstance;
            }

            close() {
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
                this.isLoading = false;
                if (this.swalInstance) {
                    this.swalInstance.close();
                    this.swalInstance = null;
                } else {
                    Swal.close();
                }
            }

            updateText(text) {
                const popup = Swal.getPopup();
                if (popup) {
                    const textElement = popup.querySelector('.loading-text');
                    if (textElement) {
                        textElement.innerHTML = text + '<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>';
                    }
                }
            }

            async wrap(promise, options = {}) {
                this.show({
                    title: options.title || 'Procesando...',
                    text: options.text || 'Por favor espera',
                    ...options
                });

                try {
                    const result = await promise;
                    this.close();
                    return result;
                } catch (error) {
                    this.close();
                    throw error;
                }
            }

            static getInstance() {
                if (!CartLoader._instance) {
                    CartLoader._instance = new CartLoader();
                }
                return CartLoader._instance;
            }
        }

        window.CartLoader = CartLoader;
        window.cartLoader = CartLoader.getInstance();
    </script>
    <!-- Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</head>