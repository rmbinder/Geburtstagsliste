<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['plg_name']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['plg_version']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['plg_date']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['plg_doc']}
            
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>