document.addEventListener('DOMContentLoaded', () => {
    const orderIdElement = document.querySelector('[name="order_id"]');
    const employeeIdElement = document.querySelector('[name="employee_id"]'); // Captura el ID del empleado
    const employeeRoleElement = document.querySelector('[name="employee_role"]'); // Captura el rol

    if (!orderIdElement || !employeeIdElement || !employeeRoleElement) {
        console.error('Faltan elementos necesarios en el DOM (order_id, employee_id, employee_role).');
        return;
    }

    const orderId = orderIdElement.value;
    const employeeId = employeeIdElement.value; // ID del empleado
    const employeeRole = employeeRoleElement.value; // Rol del empleado
    const adminToken = window.adminTokenDelivery || '';
    if (!adminToken) {
        console.error('Admin token no definido.');
        return;
    }

    // Conectar al servidor WebSocket
    const socket = new WebSocket('wss://familytracking.online:8080');

    socket.addEventListener('open', () => {
        console.log('Conectado al WebSocket seguro desde Back Office');

        // Registrar al cliente como "Delivery" con su ID y rol
        const registerPayload = JSON.stringify({
            type: 'register',
            role: employeeRole, // Ahora se envía el rol como "delivery"
            deliveryId: employeeId, // Se envía el ID del delivery
        });
        socket.send(registerPayload);
        console.log('Delivery registrado en el WebSocket:', registerPayload);
    });

    socket.addEventListener('error', (error) => {
        console.error('Error en el WebSocket:', error);
    });

    if (navigator.geolocation) {
        setInterval(() => {
            navigator.geolocation.getCurrentPosition(position => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                // Enviar coordenadas al servidor WebSocket
                const payload = JSON.stringify({
                    type: 'updateCoordinates',
                    deliveryId: employeeId,
                    orderId: orderId,
                    latitude: latitude,
                    longitude: longitude
                });
                socket.send(payload);
                console.log('Ubicación enviada al WebSocket:', payload);

                // (Opcional) Enviar las coordenadas al backend mediante una solicitud HTTP
                fetch(`/xd/admin123/index.php?fc=module&module=liveorderstatus&controller=AdminUpdateDeliveryLocation&token=${adminToken}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&latitude=${latitude}&longitude=${longitude}`
                })
                .then(response => response.json())
                .then(data => console.log('Ubicación también guardada en el backend:', data))
                .catch(err => console.error('Error al guardar las coordenadas en el backend:', err));
            });
        }, 5000); // Actualizar cada 5 segundos
    } else {
        console.error('Geolocation no es soportado por este navegador.');
    }
});
