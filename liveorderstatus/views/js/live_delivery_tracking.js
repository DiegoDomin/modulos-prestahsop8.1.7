document.addEventListener('DOMContentLoaded', () => {
    initMap();
});

function initMap() {
    const mapElement = document.getElementById('map');
    const orderIdInput = document.querySelector('[name="order_id"]');
    console.log('mapElement:', mapElement);
    console.log('orderIdInput:', orderIdInput);

    const missingFields = {
        mapElement: !!mapElement,
        orderIdInput: !!orderIdInput,
    };

    if (!mapElement || !orderIdInput) {
        console.error('Faltan campos necesarios para inicializar el mapa:', missingFields);
        return;
    }

    const orderId = orderIdInput.value;
    if (!orderId) {
        console.error('No se pudo obtener el ID del pedido.');
        return;
    }

    const defaultLocation = { lat: 13.669526, lng: -89.228413 }; // Ubicación predeterminada
    let map = new google.maps.Map(mapElement, {
        zoom: 15,
        center: defaultLocation,
    });

    let deliveryMarker = new google.maps.Marker({
        position: defaultLocation,
        map: map,
        title: 'Ubicación del delivery',
        icon: {
            url: '/xd/modules/liveorderstatus/views/img/delivery-icon.png',
            scaledSize: new google.maps.Size(64, 64),
        },
    });

    let customerMarker = new google.maps.Marker({
        position: defaultLocation,
        map: map,
        title: 'Ubicación del cliente',
        icon: {
            url: '/xd/modules/liveorderstatus/views/img/home-icon.png',
            scaledSize: new google.maps.Size(64, 64),
        },
    });

    let routeLine = new google.maps.Polyline({
        path: [deliveryMarker.getPosition(), customerMarker.getPosition()],
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 2,
    });
    routeLine.setMap(map);

    // Local Marker
    const localCoordinates = { lat: 13.669526, lng: -89.228413 };
    let localMarker = new google.maps.Marker({
        position: localCoordinates,
        map: map,
        title: 'Ubicación del Local',
        icon: {
            url: '/xd/modules/liveorderstatus/views/img/local-icon.png',
            scaledSize: new google.maps.Size(64, 64),
        },
    });

    // Consultar coordenadas iniciales del controlador
    fetch(`/xd/index.php?fc=module&module=liveorderstatus&controller=updatedeliverylocation&order_id=${orderId}`)
    .then((response) => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then((data) => {
        if (data.success) {
            const deliveryLocation = {
                lat: parseFloat(data.delivery_latitude),
                lng: parseFloat(data.delivery_longitude),
            };
            const customerLocation = {
                lat: parseFloat(data.customer_latitude),
                lng: parseFloat(data.customer_longitude),
            };

            console.log('Coordenadas iniciales obtenidas del controlador:', {
                deliveryLocation,
                customerLocation,
            });
            if (customerLocation.lat && customerLocation.lng) {
                customerMarker.setPosition(customerLocation);
                console.log('Ubicación del cliente actualizada:', customerLocation);
            } else {
                console.warn('Coordenadas del cliente no válidas.');
            }
            if (deliveryLocation.lat && deliveryLocation.lng) {
                deliveryMarker.setPosition(deliveryLocation);
                console.log('Ubicación del delivery actualizada:', deliveryLocation);
            } else {
                console.warn('Coordenadas del delivery no válidas.');
            }
            routeLine.setPath([deliveryMarker.getPosition(), customerMarker.getPosition()]);
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(deliveryMarker.getPosition());
            bounds.extend(customerMarker.getPosition());
            map.fitBounds(bounds);
        } else {
            console.warn(data.message || 'No se encontraron coordenadas iniciales.');
        }
    })
    .catch((error) => {
        console.error('Error al obtener las coordenadas iniciales:', error);
    });

    // WebSocket para actualizaciones en tiempo real
    const socket = new WebSocket('wss://familytracking.online:8080');

    socket.addEventListener('open', () => {
        console.log('Conectado al servidor WebSocket.');

        const registerPayload = JSON.stringify({
            type: 'register',
            role: 'frontoffice',
            orderId: orderId,
        });
        socket.send(registerPayload);
        console.log('Registrado en WebSocket:', registerPayload);
    });

    socket.addEventListener('message', (event) => {
        try {
            const data = JSON.parse(event.data);

            if (data.type === 'coordinatesUpdate' && data.orderId == orderId) {
                const deliveryLocation = {
                    lat: parseFloat(data.latitude),
                    lng: parseFloat(data.longitude),
                };

                console.log('Ubicación actualizada desde WebSocket:', deliveryLocation);

                // Actualizar marcador del delivery
                deliveryMarker.setPosition(deliveryLocation);
                routeLine.setPath([deliveryMarker.getPosition(), customerMarker.getPosition()]);
            }
        } catch (error) {
            console.error('Error al procesar mensaje del WebSocket:', error);
        }
    });

    socket.addEventListener('close', () => {
        console.warn('WebSocket desconectado. Reintentando en 5 segundos...');
        setTimeout(() => initMap(), 5000);
    });

    socket.addEventListener('error', (error) => {
        console.error('Error en el WebSocket:', error);
    });
}
