{extends file="page.tpl"}

{block name="page_content"}
 
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}liveorderstatus/views/css/order_states.css">

<script>
    var orderId = '{$order_id|escape:'javascript'}';
</script>
<script 
  src="https://maps.googleapis.com/maps/api/js?key={$googleMapsApiKey}&callback=initMap"
  async
  defer
></script>

<script src="{$smarty.const._MODULE_DIR_}liveorderstatus/views/js/order_status.js"></script>
<script src="{$smarty.const._MODULE_DIR_}liveorderstatus/views/js/live_delivery_tracking.js"></script>
     


       
<div class="order-status">
    <h2 class="order-title">
        {l s='Order Status'} 
        {if isset($order_id) && $order_id > 0}
            #{$order_id|escape:'html'}
        {else}
            ({l s='ID not defined'})
        {/if}
    </h2>
    <label for="order_id" style="font-weight: bold; margin-top: 10px;">Order ID:</label>
<input type="text" id="order_id" name="order_id" value="{$order_id}" readonly style="border: 1px solid #ccc; border-radius: 5px; padding: 5px; margin-left: 10px;">

    <div id="status-update" class="status-text">{l s='Loading order status...'}</div>

    <!-- Contenedor de progreso -->
    <div class="progress-container">
        <div class="progress-step" id="step-awaiting-validation">
            <div class="step-icon-container">
                <div class="step-icon">⏳</div>
            </div>
            <div class="step-label">{l s='Awaiting Validation'}</div>
        </div>
        <div class="progress-bar"></div>
        <div class="progress-step" id="step-preparation">
            <div class="step-icon-container">
                <div class="step-icon">👨‍🍳</div>
            </div>
            <div class="step-label">{l s='Preparation in Progress'}</div>
        </div>
        <div class="progress-bar"></div>
        <div class="progress-step" id="step-shipped">
            <div class="step-icon-container">
                <div class="step-icon">📦</div>
            </div>
            <div class="step-label">{l s='Shipped'}</div>
        </div>
        <div class="progress-bar"></div>
        <div class="progress-step" id="step-delivered">
            <div class="step-icon-container">
                <div class="step-icon">✔️</div>
            </div>
            <div class="step-label">{l s='Delivered'}</div>
        </div>
    </div>

    <div id="map" style="height: 400px; margin-top: 20px;"></div>



</div>

<div id="deliveryModal" class="modal">
    <div class="modal-content">
        <h2>{l s='Tu Pedido ya ha sido entregado!'}</h2>
        <p>{l s='Gracias por preferirnos.'}</p>
        <button id="backToHome" class="btn-primary">{l s='Return to Home'}</button>
    </div>
</div>

{/block}
