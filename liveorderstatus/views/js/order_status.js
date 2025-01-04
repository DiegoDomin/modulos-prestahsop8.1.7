document.addEventListener('DOMContentLoaded', () => {

    const progressSteps = [
        "step-awaiting-validation",
        "step-preparation",
        "step-shipped",
        "step-delivered",
    ];

    const progressStatusMap = {
        "En espera de validación por contra reembolso.": 0,
        "Preparación en curso": 1,
        "Enviado": 2,
        "Entregado": 3,
    };

    const validStates = {
        "En espera de validación por contra reembolso.": "En espera de validación",
        "En espera de pago por cheque": "Pago pendiente (Cheque)",
        "Pago aceptado": "Pago aceptado",
        "Preparación en curso": "Preparación en curso",
        "Enviado": "Enviado",
        "Entregado": "Entregado",
        "Cancelado": "Cancelado",
        "Reembolsado": "Reembolsado",
    };

    const statusElement = document.querySelector('#status-update'); // Elemento del estado textual
    const deliveryModal = document.getElementById('deliveryModal'); // Modal para el estado entregado
    const backToHomeButton = document.getElementById('backToHome'); // Botón para volver al inicio

let isDelivered = false;

    // Actualizar la barra de progreso
const updateProgress = (status) => {
const currentStepIndex = progressStatusMap[status];

progressSteps.forEach((stepId, index) => {
    const stepElement = document.getElementById(stepId);
    const progressBar = stepElement.nextElementSibling;
    const stepIconContainer = stepElement.querySelector('.step-icon-container');

    // Marcar pasos completados
    if (index < currentStepIndex) {
        stepElement.classList.add("completed");
        stepElement.classList.remove("active");
        stepIconContainer.classList.add("completed"); // Añadir clase a icono
        if (progressBar && progressBar.classList.contains("progress-bar")) {
            progressBar.classList.add("completed");
        }
    } 
    // Resaltar el paso actual
else if (index === currentStepIndex) {
stepElement.classList.add("active");
stepIconContainer.classList.add("active");

// Asegúrate de que "Entregado" siempre sea verde
if (stepId === "step-delivered") {
    stepElement.classList.add("completed");
    stepIconContainer.classList.add("completed");
}
}
    // Dejar pasos futuros como inactivos
    else {
        stepElement.classList.remove("completed", "active");
        stepIconContainer.classList.remove("completed", "active");
        if (progressBar && progressBar.classList.contains("progress-bar")) {
            progressBar.classList.remove("completed");
        }
    }
});

// Mostrar el modal si el estado es "Entregado"
if (status === "Entregado" && !isDelivered) {
    deliveryModal.style.display = "block";
    document.getElementById("step-delivered").classList.add("completed");
    isDelivered = true;
}


};


    // Actualizar el texto informativo
    const updateStatusText = (status) => {
        const stateText = validStates[status] || "Desconocido";
        statusElement.textContent = "Estado actual: " + stateText;
    };

// Listener para cerrar el modal y redirigir al inicio
    backToHomeButton.addEventListener('click', () => {
        window.location.href = '/'; // Redirige a la página de inicio
    });

// Hacer la petición inicial al cargar la página
 const fetchOrderStatus = () => {
        fetch('/xd/index.php?fc=module&module=liveorderstatus&controller=checkstatus&order_id=' + orderId)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.order_state) {
                    updateProgress(data.order_state);
                    updateStatusText(data.order_state);
                }
            })
            .catch(error => console.error('Error al obtener el estado del pedido:', error));
    };

    // Realizar la solicitud inicial
    fetchOrderStatus();

    // Actualizar automáticamente cada 5 segundos
    setInterval(fetchOrderStatus, 1000); // 5000 ms = 5 segundos
});

