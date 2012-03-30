<?php
/**
 * В файле размещено управление настройками модуля koloslib
 * Нельзя использовать переменную $arModules, в ней хранится список модулей 1с-битрикс
 */
$module_id = "koloslib";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");
$KOLOSLIB_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($KOLOSLIB_RIGHT>="R")
{
	if($REQUEST_METHOD=="POST" && $KOLOSLIB_RIGHT>='W' && check_bitrix_sessid())
	{
		if(isset($_POST['rechecktable']))
		{
			try
			{
				CKSModules::get_instance()->RecountDBStructure();
				$obDBController=new CKSDBController();
				include KS_MODULES_DIR.'/koloslib/cache/db_structure.php';
				$obDBController->CheckDB($arStructure);
			}
			catch(CKSError $e)
			{
				echo $e;
			}
		}
		if(isset($_POST['Update']))
		{
			if(isset($_POST['db_log']) && $_POST['db_log']=='Y')
				COption::SetOptionString('koloslib','db_log','Y');
			else
				COption::SetOptionString('koloslib','db_log','N');
			if(isset($_POST['db_log_path']))
				COption::SetOptionString('koloslib','db_log_path',$_POST['db_log_path']);
		}
		elseif(isset($_POST['Restore']))
		{
			COption::SetOptionString('koloslib','db_log','Y');
			COption::SetOptionString('koloslib','db_log_path','/mysql.log');
		}
	}
	$arKolosModules=CKSModules::get_instance()->GetList();

	$aTabs = array(
		array("DIV" => "options_tab", "TAB" => "Настройки", "ICON" => "main_settings", "TITLE" => "Настройки"),
		array("DIV" => "modules_tab", "TAB" => "Связанные модули", "ICON" => "main_settings", "TITLE" => "Связанные модули")
	);
	$tabControl = new CAdminTabControl("tabControl2", $aTabs, true, true);
	$tabControl->Begin();
	?>
	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&amp;lang=<?echo LANG?>">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="tabControl2_active_tab" value="options_tab">
		<?$tabControl->BeginNextTab();?>
		<tr>
			<td valign="top" align="right">
				<?=GetMessage('DB_LOG');?>
			</td>
			<td>
				<input type="checkbox" name="db_log" value="Y"<?if(COption::GetOptionString('koloslib','db_log','Y')=='Y'):?> checked="checked"<?endif?>/>
			</td>
		</tr>
		<tr>
			<td valign="top" align="right">
				<?=GetMessage('DB_LOG_PATH');?>
			</td>
			<td>
				<input type="text" name="db_log_path" value="<?=COption::GetOptionString('koloslib','db_log_path','mysql.log')?>"/>
			</td>
		</tr>
		<?$tabControl->BeginNextTab();?>
		<tr>
			<td valign="top" colspan="2" align="left">
				<table border="0" cellspacing="0" cellpadding="2" style="border:1px solid black;">
					<tr>
						<th><?=GetMessage('MT_TITLE');?></th>
						<th><?=GetMessage('MT_CONTROL_DB');?></th>
					</tr>
					<?foreach($arKolosModules as $arItem):?>
					<tr>
						<td><?=$arItem['title']?></td>
						<td><?=GetMessage('MT_CONTROL_DB'.$arItem['check_db'])?>
					</tr>
					<?endforeach;?>
				</table>
			</td>
		</tr>
		<?$tabControl->EndTab();?>
		<?$tabControl->Buttons();?>
			<input <?if ($KOLOSLIB_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("FORM_SAVE")?>">
			<input type="reset" name="reset" value="<?=GetMessage("FORM_RESET")?>">
			<input <?if ($KOLOSLIB_RIGHT<"W") echo "disabled" ?> type="submit" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" name="Restore" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
			<input type="submit" name="rechecktable" value="<?=GetMessage('CHECK_DB');?>">
	</form>
	<?$tabControl->End();
}
