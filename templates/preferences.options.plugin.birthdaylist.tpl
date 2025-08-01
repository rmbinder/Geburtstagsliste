<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.input.tpl' data=$elements['vorschau_tage_default']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['vorschau_liste']} 
    {include 'sys-template-parts/form.select.tpl' data=$elements['config_default']} 
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['configuration_as_header']}           

    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
