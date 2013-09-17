<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formWorkerPeek" name="formWorkerPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="workers">
<input type="hidden" name="action" value="saveWorkerPeek">
<input type="hidden" name="id" value="{$worker->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>Contact Information</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'worker.first_name'|devblocks_translate|capitalize}:</b> </td>
			<td width="100%"><input type="text" name="first_name" value="{$worker->first_name}" class="required" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'worker.last_name'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="last_name" value="{$worker->last_name}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'worker.title'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="title" value="{$worker->title}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'common.email'|devblocks_translate}</b>: </td>
			<td width="100%"><input type="text" name="email" value="{$worker->email}" class="required" style="width:98%;"></td>
		</tr>
	</table>
</fieldset>
	
<fieldset class="peek">
	<legend>Login</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top"><b>Authentication</b>: </td>
			<td width="100%">
				<select name="auth_extension_id">
					{foreach from=$auth_extensions item=auth_ext_mft key=auth_ext_id}
					<option value="{$auth_ext_id}" {if $worker->auth_extension_id==$auth_ext_id}selected="selected"{/if}>{$auth_ext_mft->name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'worker.is_superuser'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if $active_worker->id == $worker->id}
					<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
					{if !$worker->is_superuser}{'common.no'|devblocks_translate|capitalize}{else}{'common.yes'|devblocks_translate|capitalize}{/if}
				{else}
					<select name="is_superuser">
						<option value="0" {if !$worker->is_superuser}selected{/if}>{'common.no'|devblocks_translate|capitalize}</option>
						<option value="1" {if $worker->is_superuser}selected{/if}>{'common.yes'|devblocks_translate|capitalize}</option>
					</select>
				{/if}
			</td>
		</tr>
		{if $active_worker->id == $worker->id}
			<input type="hidden" name="is_disabled" value="{$worker->is_disabled}">
		{else}
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.disabled'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<select name="is_disabled">
					<option value="0" {if !$worker->is_disabled}selected{/if}>{'common.no'|devblocks_translate|capitalize}</option>
					<option value="1" {if $worker->is_disabled}selected{/if}>{'common.yes'|devblocks_translate|capitalize}</option>
				</select>
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.groups'|devblocks_translate|capitalize}</legend>
	
	{if $worker->id}{assign var=workerGroups value=$worker->getMemberships()}{/if}
	{foreach from=$groups item=group key=group_id}
	{assign var=member value=$workerGroups.$group_id}
	<input type="hidden" name="group_ids[]" value="{$group->id}">
	<select name="group_roles[]" {if $disabled} disabled="disabled"{/if}>
		<option value="">&nbsp;</option>
		<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
		<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
	</select>
	{$group->name}<br>
	{/foreach}
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER context_id=$worker->id}

{if $active_worker->is_superuser}
	<button type="button" onclick="if($('#formWorkerPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formWorkerPeek', '{$view_id}', false, 'worker_save'); } "><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !$disabled}
		{if !empty($worker)}{if $active_worker->is_superuser && $active_worker->id != $worker->id}<button type="button" onclick="if(confirm('Are you sure you want to delete this worker and their history?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'formWorkerPeek', '{$view_id}'); } "><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}{/if}
	{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}

<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Worker");
		
		$("#formWorkerPeek").validate( {
			rules: {
				password2: {
					equalTo: "#password"
				}
			},
			messages: {
				password2: {
					equalTo: "The passwords don't match."
				}
			}		
		});
		
		$(this).find('input:text:first').select().focus();
	});
</script>

