document.addEventListener('DOMContentLoaded', () => {
    const updateStatusButton = document.getElementById('update_status_btn');

    if (!updateStatusButton) {
        console.error('Botón de actualización de estado no encontrado.');
        return;
    }

    updateStatusButton.addEventListener('click', () => {
        const statusId = document.getElementById('order_status').value;
        const orderIdElement = document.querySelector('input[name="order_id"]');
        
        if (!orderIdElement) {
            console.error('Elemento con name="order_id" no encontrado.');
            alert('Error: No se pudo obtener el ID del pedido.');
            return;
        }

        const orderId = orderIdElement.value.trim();
        const token = window.adminTokenStatus || ''; // Usar el token correcto

        if (!token) {
            alert('Token de administrador no encontrado.');
            return;
        }

        fetch(`/xd/admin123/index.php?controller=AdminUpdateOrderStatus&fc=module&module=liveorderstatus&ajax=1&action=updateOrderStatus&token=${token}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_order=${orderId}&new_status=${statusId}`
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Actualizar el texto del estado actual sin recargar la página
                    const currentStatusElement = document.querySelector('#order_status option:checked').textContent;
                    const statusLabel = document.querySelector('.current-order-status');
                    if (statusLabel) {
                        statusLabel.textContent = `Estado actual: ${currentStatusElement}`;
                    }

                } else {
                    alert('Error al actualizar el estado: ' + (data.message || 'Desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar la solicitud. Por favor, inténtelo de nuevo.');
            });
        
    });
});
