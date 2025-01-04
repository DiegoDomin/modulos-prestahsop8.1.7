document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('admin-map-container');
    const coordinatesField = document.getElementById('zone_coordinates');
    const clearButton = document.getElementById('clear-polygon');
    const editButtons = document.querySelectorAll('.btn-edit');
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const zoneNameField = document.querySelector('[name="zone_name"]');
    const colorField = document.querySelector('[name="polygon_color"]'); // Campo de color
    const fillField = document.querySelector('[name="polygon_fill"]');  // Campo de relleno

    let map, drawingManager, polygon;

    // Inicializar mapa
    function initMap() {
        map = new google.maps.Map(mapContainer, {
            center: { lat: 13.6929, lng: -89.2182 }, // Centro predeterminado
            zoom: 12,
        });

        // Configurar herramienta de dibujo
        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON],
            },
            polygonOptions: {
                fillColor: '#FF0000', // Color inicial predeterminado
                fillOpacity: 0.4,     // Opacidad inicial
                strokeColor: '#FF0000',
                strokeOpacity: 1,
                strokeWeight: 2,
                editable: true,
                draggable: false,
            },
        });

        drawingManager.setMap(map);

        // Evento: Crear un nuevo polígono
        google.maps.event.addListener(drawingManager, 'overlaycomplete', function (event) {
            if (polygon) polygon.setMap(null); // Eliminar el polígono existente si hay uno

            polygon = event.overlay;
            drawingManager.setDrawingMode(null); // Desactivar el modo de dibujo

            // Aplicar color y relleno desde los valores del formulario
            const fillColor = colorField.value;
            const fill = fillField.checked;

            polygon.setOptions({
                fillColor: fillColor,
                fillOpacity: fill ? 0.4 : 0, // Opacidad 0 si no está marcado el relleno
                strokeColor: fillColor,
            });

            updatePolygonCoordinates();
        });
    }

    // Actualizar las coordenadas del polígono en el campo oculto
    function updatePolygonCoordinates() {
        if (!polygon) return;

        const path = polygon.getPath();
        const coordinates = [];

        for (let i = 0; i < path.getLength(); i++) {
            const point = path.getAt(i);
            coordinates.push({ lat: point.lat(), lng: point.lng() });
        }

        coordinatesField.value = JSON.stringify(coordinates); // Guardar las coordenadas como JSON
    }

    // Editar un polígono existente
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const coordinates = JSON.parse(this.dataset.coordinates);
            const color = this.dataset.color || '#FF0000'; // Color predeterminado
            const fill = this.dataset.fill === '1'; // Convertir a booleano

            // Cargar valores en los campos del formulario
            document.getElementById('id_zone').value = id;
            zoneNameField.value = name;
            coordinatesField.value = JSON.stringify(coordinates);
            colorField.value = color;
            fillField.checked = fill;

            // Dibujar el polígono en el mapa
            if (polygon) polygon.setMap(null); // Eliminar polígono anterior
            polygon = new google.maps.Polygon({
                paths: coordinates,
                map: map,
                fillColor: color,
                fillOpacity: fill ? 0.4 : 0, // Aplicar relleno según la opción
                strokeColor: color,
                strokeOpacity: 1,
                strokeWeight: 2,
                editable: true,
            });

            map.setCenter(coordinates[0]); // Centrar el mapa en el primer punto del polígono
        });
    });

    // Eliminar el polígono actual del mapa
    clearButton.addEventListener('click', function () {
        if (polygon) {
            polygon.setMap(null);
            polygon = null;
            coordinatesField.value = ''; // Limpiar las coordenadas
        }
    });

    // Eliminar una zona restringida de la base de datos
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            if (!confirm('¿Estás seguro de que deseas eliminar esta zona?')) return;

            fetch(window.location.href + '&ajax=1&action=deleteZone&id_zone=' + id, {
                method: 'POST',
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Zona eliminada correctamente.');
                        location.reload();
                    } else {
                        alert('Error al eliminar la zona: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error al intentar eliminar la zona:', error);
                });
        });
    });

    // Inicializar el mapa al cargar la página
    initMap();
});
