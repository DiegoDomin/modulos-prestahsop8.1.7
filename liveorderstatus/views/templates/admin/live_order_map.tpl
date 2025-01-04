<div class="delivery-panel">
<script>
    const adminTokenDelivery = '{$adminTokenDelivery}';
    const adminTokenStatus = '{$adminTokenStatus}';
    window.adminTokenDelivery = adminTokenDelivery;
    window.adminTokenStatus = adminTokenStatus;

    console.log('adminTokenDelivery:', adminTokenDelivery);
    console.log('adminTokenStatus:', adminTokenStatus);
</script>

    <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}liveorderstatus/views/css/admin_delivery_map.css">
    <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}liveorderstatus/views/css/button_order_state_admin.css">

    <div class="delivery-header">
        <h2>{l s='Ubicación del Cliente'}</h2>
    </div>

    <div class="delivery-info">
   <div class="info-box">
    <span class="info-label">{l s='Order ID'}:</span>
    <span class="info-value">
        <input type="hidden" name="order_id" value="{$order_id}" />
        {$order_id}
    </span>
</div>
<div class="info-box">
    <span class="info-label">ID del empleado:</span>
    <span class="info-value">
        <input type="hidden" name="employee_id" value="{$employee_id}" />
        {$employee_id}
    </span>
</div>
<div class="info-box">
    <span class="info-label">Rol del Empleado:</span>
    <span class="info-value">
        <input type="hidden" name="employee_role" value="delivery" />
        delivery
    </span>
</div>


<div class="info-box">
    <span class="info-label">{l s='Latitude'}:</span>
    <span class="info-value">
        <input type="hidden" name="latitude" value="{$latitude}" />
        {$latitude}
    </span>
</div>

<div class="info-box">
    <span class="info-label">{l s='Longitude'}:</span>
    <span class="info-value">
        <input type="hidden" name="longitude" value="{$longitude|escape:'html':'UTF-8'}" />
        {$longitude|escape:'html':'UTF-8'}
    </span>
</div>


    <!-- Selector de estado del pedido -->
    <div class="order-status-update">
        <label for="order_status">{l s='Actualizar estado del pedido:'}</label>
        <select id="order_status">
            {foreach from=$statuses item=status}
                <option value="{$status.id_order_state}" {if $status.id_order_state == $current_status}selected{/if}>
                    {$status.name}
                </option>
            {/foreach}
        </select>
        <button id="update_status_btn" class="btn">{l s='Actualizar Estado'}</button>
    </div>

    <!-- Contenedor del mapa -->
    <div id="map"></div>

    <!-- Scripts -->
    <script 
        src="https://maps.googleapis.com/maps/api/js?key={$googleMapsApiKey}&callback=initMap" 
        async 
        defer>
    </script>
    <script src="{$smarty.const._MODULE_DIR_}liveorderstatus/views/js/admin_google_maps.js"></script>
    <script src="{$smarty.const._MODULE_DIR_}liveorderstatus/views/js/admin_update_order_status.js"></script>
 <script src="{$smarty.const._MODULE_DIR_}liveorderstatus/views/js/admin_delivery_tracking.js"></script>
</div>
