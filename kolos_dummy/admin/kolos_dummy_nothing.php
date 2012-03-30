<?
// подключим все необходимые файлы:
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/kolos_dummy/include.php"); // инициализация модуля
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/kolos_dummy/prolog.php"); // пролог модуля

// подключим языковой файл
IncludeModuleLangFile(__FILE__);

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("kolos_dummy");
// если нет прав - отправим к форме авторизации с сообщением об ошибке
if ($POST_RIGHT == "D")
$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
?>
<?
// здесь будет вся серверная обработка и подготовка данных
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог
?>
Hello World!!!

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>