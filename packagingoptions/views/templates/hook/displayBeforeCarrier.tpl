<div class="packaging-options">
    <h4>{l s='Opciones de Empaques'}</h4>
    <form id="packaging-options-form" method="post">
        {foreach from=$packaging_options item=option}
        <label class="packaging-card">
            <input type="radio" name="packaging_option" value="{$option.id_option}" {if $selected_option == $option.id_option}checked{/if}>
            <div class="packaging-content">
                <div class="icon-container">
                    <img src="{$option.image}" alt="{$option.title}">
                </div>
                <div class="text-container">
                    <h5>{$option.title|escape}</h5>
                    <p>{$option.description|escape}</p>
                </div>
            </div>
        </label>
        {/foreach}
    </form>
</div>


<script>
    document.querySelectorAll('input[name="packaging_option"]').forEach(option => {
        option.addEventListener('change', function () {
            const formData = new FormData();
            formData.append('packaging_option', this.value);

            fetch('index.php?fc=module&module=packagingoptions&controller=savepackaging&ajax=1', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Opción de embalaje guardada correctamente.');
                } else {
                    console.error('Error al guardar la opción de embalaje.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
</script>
