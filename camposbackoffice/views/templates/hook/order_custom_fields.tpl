<div id="extraOrderDetails" class="info-block">
  <p><strong>¿Necesita factura CCF?:</strong> {if $need_tax_invoice}Sí{else}No{/if}</p>
  <p><strong>Razón Social:</strong> {$razon_social|escape:'html':'UTF-8'}</p>
  <p><strong>NIT:</strong> {$nit_number|escape:'html':'UTF-8'}</p>
  <p><strong>Latitud:</strong> {$latitude|escape:'html':'UTF-8'}</p>
  <p><strong>Longitud:</strong> {$longitude|escape:'html':'UTF-8'}</p>
  <hr>
  <p><strong>Día de recogida seleccionado:</strong> {$pickup_day|escape:'html':'UTF-8'}</p>
  <p><strong>Hora de recogida seleccionada:</strong> {$pickup_time|escape:'html':'UTF-8'}</p>
  <p><strong>Opción de empaque seleccionada:</strong> {$packaging_option|escape:'html':'UTF-8'}</p>

      <p><strong>Dinero entregado:</strong> $ {$cash_given|escape:'html':'UTF-8'}</p>
    <p><strong>Cambio:</strong> $ {$change|escape:'html':'UTF-8'}</p>
</div>
