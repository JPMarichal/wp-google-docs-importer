jQuery(document).ready(function($) {
    // Delegación de evento click ANTES de cualquier otra cosa
    $(document).on('click', '#g2wpi-change-folder-btn', function(e) {
        e.preventDefault();
        console.log('Click en Cambiar carpeta');
        abrirGoogleDriveFolderPicker();
    });

    $('#g2wpi-refresh-list-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Actualizando...');
        $.post(ajaxurl, { action: 'g2wpi_refresh_list_ajax' }, function(response) {
            $('#g2wpi-docs-table').html(response);
            $btn.prop('disabled', false).text('Actualizar listado');
        });
    });

    // Búsqueda incremental en la tabla de documentos
    $(document).on('input', '#g2wpi-search-docs', function() {
        var search = $(this).val().toLowerCase();
        // Buscar la tabla de documentos aunque esté fuera del mismo div
        $('table.wp-list-table.widefat.fixed.striped tbody tr').each(function() {
            var nombre = $(this).find('td:first').text().toLowerCase();
            if (nombre.indexOf(search) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Botón para cambiar de carpeta en Google Drive
    var $refreshBtn = $('#g2wpi-refresh-list-btn');
    if ($refreshBtn.length === 0) {
        $refreshBtn = $("button:contains('Actualizar listado'), input[type='button'][value='Actualizar listado']").first();
    }
    if ($('#g2wpi-change-folder-btn').length === 0) {
        var $changeFolderBtn = $('<button id="g2wpi-change-folder-btn" class="button" style="margin-left:8px;"><span class="dashicons dashicons-category"></span> Cambiar carpeta</button>');
        if ($refreshBtn.length > 0) {
            $refreshBtn.after($changeFolderBtn);
        } else {
            // Si no se encuentra el botón, insertarlo al inicio del contenedor principal
            var $mainContainer = $('.wrap:contains("Importador de Google Docs")').first();
            if ($mainContainer.length > 0) {
                $mainContainer.prepend($changeFolderBtn);
            } else {
                // Como último recurso, al body
                $('body').prepend($changeFolderBtn);
            }
        }
        // Log para confirmar que el botón existe
        console.log('Botón Cambiar carpeta insertado:', $('#g2wpi-change-folder-btn').length);
    }

    // Asegurar que los dashicons estén cargados
    if ($('link#dashicons-css').length === 0 && typeof wp !== 'undefined' && wp_enqueue_style) {
        wp_enqueue_style('dashicons');
    }

    // Asegura que el CSS externo se cargue siempre en el admin
    if (typeof wp !== 'undefined' && typeof wp_enqueue_style === 'function') {
        wp_enqueue_style('g2wpi-admin-ui', (window.g2wpi_plugin_url || '') + 'assets/css/g2wpi-admin-ui.css');
    } else if (!document.getElementById('g2wpi-admin-ui-css')) {
        var link = document.createElement('link');
        link.id = 'g2wpi-admin-ui-css';
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = (window.g2wpi_plugin_url ? window.g2wpi_plugin_url : '/wp-content/plugins/google-docs-importer/') + 'assets/css/g2wpi-admin-ui.css';
        document.head.appendChild(link);
    }

    // --- Google Picker API ---
    // Espera que existan las variables globales g2wpi_picker.clientId y g2wpi_picker.apiKey
    var pickerApiLoaded = false;
    var gisLoaded = false;
    var oauthToken;

    // Cargar Google Identity Services
    function loadGISScript(callback) {
        if (window.google && window.google.accounts && window.google.accounts.oauth2) {
            gisLoaded = true;
            console.log('GIS ya cargado');
            if (callback) callback();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.onload = function() {
            gisLoaded = true;
            console.log('GIS cargado');
            if (callback) callback();
        };
        document.body.appendChild(script);
    }

    function loadPickerScript(callback) {
        if (window.google && window.google.picker) {
            pickerApiLoaded = true;
            console.log('Picker ya cargado');
            if (callback) callback();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://apis.google.com/js/api.js';
        script.onload = function() {
            console.log('Script gapi cargado, cargando picker...');
            gapi.load('picker', {'callback': function() {
                pickerApiLoaded = true;
                console.log('Picker cargado');
                if (callback) callback();
            }});
        };
        document.body.appendChild(script);
    }

    function abrirGoogleDriveFolderPicker() {
        console.log('Intentando abrir Google Picker...');
        if (!window.g2wpi_picker || !g2wpi_picker.clientId || !g2wpi_picker.apiKey) {
            alert('Faltan credenciales de Google Picker.');
            return;
        }
        loadGISScript(function() {
            loadPickerScript(function() {
                console.log('Comprobando objetos antes de OAuth...');
                console.log('window.google:', window.google);
                console.log('window.google.accounts:', window.google && window.google.accounts);
                console.log('window.google.accounts.oauth2:', window.google && window.google.accounts && window.google.accounts.oauth2);
                console.log('window.google.picker:', window.google && window.google.picker);
                if (!window.google || !window.google.accounts || !window.google.accounts.oauth2) {
                    alert('No se pudo cargar Google Identity Services.');
                    return;
                }
                if (!window.google.picker) {
                    alert('No se pudo cargar Google Picker.');
                    return;
                }
                if (oauthToken) {
                    console.log('Token OAuth ya disponible, abriendo picker...');
                    showPicker(oauthToken);
                } else {
                    // Lanzar flujo OAuth implícito
                    var clientId = g2wpi_picker.clientId;
                    var scope = ['https://www.googleapis.com/auth/drive.readonly'];
                    console.log('Inicializando tokenClient...');
                    var tokenClient = google.accounts.oauth2.initTokenClient({
                        client_id: clientId,
                        scope: scope.join(' '),
                        callback: function(tokenResponse) {
                            oauthToken = tokenResponse.access_token;
                            console.log('Token OAuth recibido, abriendo picker...');
                            showPicker(oauthToken);
                        }
                    });
                    tokenClient.requestAccessToken();
                }
            });
        });
    }

    function showPicker(token) {
        var view = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
            .setIncludeFolders(true)
            .setSelectFolderEnabled(true);
        var picker = new google.picker.PickerBuilder()
            .addView(view)
            .enableFeature(google.picker.Feature.SUPPORT_DRIVES)
            .setSelectableMimeTypes('application/vnd.google-apps.folder')
            .setOAuthToken(token)
            .setDeveloperKey(g2wpi_picker.apiKey)
            .setCallback(pickerCallback)
            .setTitle('Selecciona una carpeta de Google Drive')
            .build();
        picker.setVisible(true);
    }

    function pickerCallback(data) {
        if (data.action === google.picker.Action.PICKED) {
            var folder = data.docs[0];
            var folderId = folder.id;
            // Guardar el folderId vía AJAX y recargar la página al terminar
            $.post(ajaxurl, {
                action: 'g2wpi_save_folder_id',
                folder_id: folderId,
                _t: Date.now()
            }, function(response) {
                if (response.success) {
                    // Recarga la página para asegurar que todo el estado se actualiza
                    location.reload();
                } else {
                    alert('Error al guardar la carpeta.');
                }
            }).fail(function() {
                alert('Error de red al guardar la carpeta.');
            });
        }
    }

    // Refresca la tabla de documentos
    function refrescarListadoDocs(forceRefresh) {
        var $btn = $('#g2wpi-refresh-list-btn');
        $btn.prop('disabled', true).text('Actualizando...');
        var data = { action: 'g2wpi_refresh_list_ajax', _t: Date.now() };
        if (forceRefresh) data.force_refresh = 1;
        $.post(ajaxurl, data, function(response) {
            $('#g2wpi-docs-table').html(response);
            $btn.prop('disabled', false).text('Actualizar listado');
        });
    }

    // --- UI Mejorada: Reorganización de la cabecera principal ---
    function g2wpiReorganizarCabecera() {
        // Selecciona el contenedor principal de la página de administración
        var $wrap = $('.wrap:contains("Importador de Google Docs")').first();
        if ($wrap.length === 0) return;

        // Elimina elementos previos de la cabecera si existen
        $wrap.find('.g2wpi-main-container').remove();

        // Crea el nuevo contenedor principal
        var $main = $('<div class="g2wpi-main-container"></div>');

        // Título fijo
        var $title = $('<h1 class="g2wpi-title">Importador de Google Docs</h1>');
        $main.append($title);

        // Botonera horizontal
        var $toolbar = $('<nav class="g2wpi-toolbar"></nav>');
        var $changeFolderBtn = $('#g2wpi-change-folder-btn').length ? $('#g2wpi-change-folder-btn') : $('<button id="g2wpi-change-folder-btn" class="button"><span class="dashicons dashicons-category"></span> Cambiar carpeta</button>');
        var $refreshBtn = $('#g2wpi-refresh-list-btn').length ? $('#g2wpi-refresh-list-btn') : $('<button id="g2wpi-refresh-list-btn" class="button"><span class="dashicons dashicons-update"></span> Actualizar listado</button>');
        $toolbar.append($changeFolderBtn, $refreshBtn);
        $main.append($toolbar);

        // Barra de búsqueda
        var $searchBar = $('<div class="g2wpi-searchbar"><input type="text" id="g2wpi-search-docs" placeholder="Buscar por nombre de documento..." /></div>');
        $main.append($searchBar);

        // Inserta el nuevo contenedor antes de la tabla/listado de documentos
        var $docsTable = $('#g2wpi-docs-table').closest('.wrap, .g2wpi-docs-table, table, form').first();
        if ($docsTable.length > 0) {
            $main.insertBefore($docsTable);
        } else {
            $wrap.append($main);
        }
    }

    // Elimina los botones originales para evitar duplicados y asegura que solo la nueva cabecera esté visible
    function g2wpiRemoveOldHeaderButtons() {
        // Elimina los botones sueltos fuera del nuevo contenedor
        $('#g2wpi-change-folder-btn').not('.g2wpi-toolbar .button').remove();
        $('#g2wpi-refresh-list-btn').not('.g2wpi-toolbar .button').remove();
        // Elimina posibles contenedores de búsqueda antiguos
        $('#g2wpi-search-docs').not('.g2wpi-searchbar input').closest('div, p').remove();
    }
    // Llama a la limpieza tras reorganizar
    var oldG2wpiReorganizarCabecera = g2wpiReorganizarCabecera;
    g2wpiReorganizarCabecera = function() {
        oldG2wpiReorganizarCabecera();
        g2wpiRemoveOldHeaderButtons();
    };
    // Ejecuta limpieza inicial
    g2wpiRemoveOldHeaderButtons();

    // Ejecuta la reorganización al cargar el DOM
    g2wpiReorganizarCabecera();

    // --- Forzar recarga visual tras AJAX y DOM ready ---
    function g2wpiForceUIRefresh() {
        setTimeout(function() {
            g2wpiReorganizarCabecera();
        }, 100);
    }
    // Reorganizar cabecera tras cada actualización de la tabla
    $(document).on('g2wpi-docs-table-updated', g2wpiForceUIRefresh);
    // Hook en AJAX de refresco
    var oldRefreshDocs = refrescarListadoDocs;
    refrescarListadoDocs = function(forceRefresh) {
        oldRefreshDocs(forceRefresh);
        g2wpiForceUIRefresh();
    };
    // También tras DOM ready, por si la tabla se renderiza después
    $(window).on('load', g2wpiForceUIRefresh);
    setTimeout(g2wpiForceUIRefresh, 500);

    // --- Inyecta estilos mejorados para la cabecera y controles ---
    function g2wpiInjectStyles() {
        if (document.getElementById('g2wpi-admin-styles')) return;
        var css = `
        .g2wpi-main-container {
            max-width: 900px;
            margin: 0 auto 24px auto;
            padding: 32px 16px 18px 16px;
            background: #f7f7f7;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .g2wpi-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 18px;
            color: #23282d;
            text-align: left;
        }
        .g2wpi-toolbar {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .g2wpi-toolbar .button {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 1rem;
            padding: 8px 18px;
            border-radius: 4px;
            background: #2271b1;
            color: #fff;
            border: none;
            transition: background 0.2s;
            cursor: pointer;
        }
        .g2wpi-toolbar .button:hover {
            background: #135e96;
        }
        .g2wpi-searchbar {
            margin-bottom: 18px;
        }
        .g2wpi-searchbar input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 1rem;
        }
        @media (max-width: 600px) {
            .g2wpi-main-container { padding: 18px 4px; }
            .g2wpi-title { font-size: 1.3rem; }
            .g2wpi-toolbar { gap: 8px; }
            .g2wpi-toolbar .button { font-size: 0.95rem; padding: 7px 10px; }
        }
        `;
        var style = document.createElement('style');
        style.id = 'g2wpi-admin-styles';
        style.innerHTML = css;
        document.head.appendChild(style);
    }
    g2wpiInjectStyles();
});
