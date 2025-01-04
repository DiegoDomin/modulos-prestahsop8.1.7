function initMap() {
    const latField = document.querySelector('[name="latitude"]');
    const lngField = document.querySelector('[name="longitude"]');
    const mapElement = document.getElementById('map');

    if (!latField || !lngField || !mapElement) {
        console.error('Campos faltantes o contenedor de mapa no encontrado.');
        return;
    }

    const lat = parseFloat(latField.value) || 0;
    const lng = parseFloat(lngField.value) || 0;

    if (lat === 0 && lng === 0) {
        console.error('Coordenadas no válidas.');
        return;
    }

    const location = { lat, lng };

    const map = new google.maps.Map(mapElement, {
        zoom: 16,
        center: location,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true,
    });

    const marker = new google.maps.Marker({
        position: location,
        map,
        title: 'Ubicación del Cliente',
        animation: google.maps.Animation.DROP,
    });

    console.log('Mapa inicializado correctamente.');
}
