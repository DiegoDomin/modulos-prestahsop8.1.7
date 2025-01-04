<h3>Zonas Restringidas</h3>

<form method="post">
    <input type="hidden" name="id_zone" id="id_zone" value="{$zone.id_zone|default:''}">

    <label>Nombre de la Zona:</label>
    <input type="text" name="zone_name" value="{$zone.name|default:''}" required>

    <label>Coordenadas del Polígono (Generadas automáticamente):</label>
    <textarea name="zone_coordinates" id="zone_coordinates" readonly>{$zone.polygon_coordinates|default:''}</textarea>

    <label>Color del Polígono:</label>
    <input type="color" name="polygon_color" value="{$zone.polygon_color|default:'#FF0000'}">

    <label>Rellenar Polígono:</label>
    <input type="checkbox" name="polygon_fill" value="1" {if isset($zone.polygon_fill) && $zone.polygon_fill == 1}checked{/if}>

    <div id="admin-map-container" style="width: 100%; height: 400px; margin-top: 20px;"></div>

    <button type="button" id="clear-polygon" class="btn btn-warning" style="margin-top: 10px;">Borrar Polígono</button>
    <button type="submit" name="submitRestrictedZones" class="btn btn-primary" style="margin-top: 10px;">Guardar Zona</button>
</form>

<h4>Zonas Configuradas:</h4>
<ul>
    {foreach from=$zones item=zone}
        <li>{$zone.name}: {$zone.polygon_coordinates}
            <button type="button" class="btn btn-primary btn-edit" 
                data-id="{$zone.id_zone}" 
                data-name="{$zone.name}" 
                data-coordinates="{$zone.polygon_coordinates}" 
                data-color="{$zone.polygon_color}" 
                data-fill="{$zone.polygon_fill}">Editar</button>
            <button type="button" class="btn btn-danger btn-delete" data-id="{$zone.id_zone}">Eliminar</button>
        </li>
    {/foreach}
</ul>

<script src="https://maps.googleapis.com/maps/api/js?key={$google_maps_api_key}&libraries=drawing"></script>
<script src="/xd/modules/restrictedzones/views/js/admin_map.js"></script>
