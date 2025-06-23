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
});
