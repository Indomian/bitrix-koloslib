<?php
IncludeModuleLangFile(__FILE__);//Выполняем подключение файла с языковыми константами
if(class_exists("koloslib")) return;//Защита от повторного подключения

Class koloslib extends CModule {
	var $MODULE_ID;//Обязательная переменная, код модуля
	var $MODULE_VERSION;//Номер версии модуля
    var $MODULE_VERSION_DATE;//Дата создания модуля
    var $MODULE_NAME;//Текстовое имя модуля
    var $MODULE_DESCRIPTION;//Описание модуля
    var $MODULE_GROUP_RIGHTS = "Y";//Флаг указывает есть ли у модуля метод  GetModuleRightList обеспечивающий возврат пользовательских прав доступа к модулю

	//Конструктор класса (!sic)
    function koloslib()
    {
		include(str_replace('index.php','version.php',__FILE__));//Выполняем подключение файла версии модуля
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];//Устанавливаем версию модуля...
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];//...и дату выпуска
        $this->MODULE_NAME = GetMessage("KOLOSLIB_MODULE_NAME");//Название из языкового файла
        $this->MODULE_DESCRIPTION = GetMessage("KOLOSLIB_MODULE_DESCRIPTION");

        $this->MODULE_ID=__CLASS__;
    }

	//Метод вызывается системой 1С-Битрикс, когда надо установить модуль (т. е. нажали на кнопку «Установить модуль» на странице модулей в административном интерфейсе
    function DoInstall()
    {
        global $DB, $APPLICATION;//Глобальные переменные, $DB — база данных (можно нашу $KS_DB, $APPLICATION — глобальный объект, $step — значение переменной step передаваемой из формы (!sic)
        $step=(isset($_REQUEST['step'])) ? (int)$_REQUEST['step'] : 1;
        $RIGHT = $APPLICATION->GetGroupRight($this->MODULE_ID);//Нелепая проверка, есть ли у пользователя доступ к модулю, который мы ещё не установили, помогает узнать админ ли
        if ($RIGHT=="W")
        {
            $step = IntVal($step);//Продолжение бреда, убедимся, что число целое :)
            if($step<2)
            {
                $APPLICATION->IncludeAdminFile(GetMessage("KOLOSLIB_INSTALL_TITLE"),
                $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");//Если шаг не указан, выведем первый с настройками установки модуля
			}
            elseif($step==2)
            {
                $this->InstallDB();
                $this->InstallFiles();
			}
        }
    }

	//Полезный метод не описанный в основном руководстве, выполняет установку базы данных в стиле системы 1С-Битрикс
    function InstallDB()
    {
         global $APPLICATION, $DB, $errors;
         $errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/".strtolower($DB->type)."/install.sql");//Эта строка выполняет SQL запросы из файла SQL запросов, при написании модулей использующих koloslib можно воспользоваться системой контроля структуры БД и не писать запросы
         if (!empty($errors))
         {
              $APPLICATION->ThrowException(implode("", $errors));//Если у нас ошибка — выбрасываем исключение, да именно в таком формате, это особенность Битрикса, такое исключение надо проверять отдельно! http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php
              return false;
         }
         RegisterModule($this->MODULE_ID);//Функция (!sic) выполняет регистрацию модуля в системе, если модуль использует koloslb необходимо также зарегистрировать модуль там
         return true;
    }

	//Метод выполняет установку файлов модуля
    function InstallFiles()
    {
         CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
         CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/kolos_dummy/", true, true);
         CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/", true, true);
         CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
         CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/", true, true);
         return true;
    }
	//Метод выполняет установку событий модуля, см RegisterModuleDependences в руководстве 1С-Битрикс
    function InstallEvents()
    {
         return true;
    }
	//Метод вызывается системой 1С-Битрикс, когда надо удалить модуль (т. е. нажали на кнопку «Удалить модуль» на странице модулей в административном интерфейсе
    function DoUninstall()
    {
        global $DB, $APPLICATION, $step;
        $FORM_RIGHT = $APPLICATION->GetGroupRight($this->MODULE_ID);
        if ($FORM_RIGHT=="W")
        {
            $step = IntVal($step);
            if($step<2){
//                 $APPLICATION->IncludeAdminFile(GetMessage("KOLOSLIB_UNINSTALL_TITLE"),
//                 $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
//             elseif($step==2){
				$this->UnInstallDB(array('savedata'=>'Y'));
				$this->UnInstallFiles();
			}
//             }
        }
    }
	//Метод выполняет удаление таблиц БД, аналогично установке, если используется модуль Koloslib, то удаление можно сделать используя CKSDBController
    function UnInstallDB($arParams = Array())
    {
         global $APPLICATION, $DB, $errors;
         if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
         {
              $errors = false;
              // delete whole base
              $errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/".strtolower($DB->type)."/uninstall.sql");
              if (!empty($errors))
              {
                   $APPLICATION->ThrowException(implode("", $errors));
                   return false;
              }
         }
         COption::RemoveOption($this->MODULE_ID);
         UnRegisterModule($this->MODULE_ID);
         return true;
    }
	//Метод обеспечивает удаление файлов связанных с модулем
    function UnInstallFiles($arParams = array())
    {
         global $DB;
         if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
         {
              // delete all images
              $db_res = $DB->Query("SELECT ID FROM b_file WHERE MODULE_ID = '".$this->MODULE_ID."'");
              while($arRes = $db_res->Fetch()) Cfile::Delete($arRes["ID"]);
              //Этот код проверяет наличие файлов связанных с модулем и удаляет их
         }
         // Delete files
         DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin"); //Функция выполняет удаление файлов во втором каталоге по списку из первого, см документацию http://dev.1c-bitrix.ru/api_help/main/functions/file/deletedirfiles.php
         DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
         DeleteDirFilesEx("/bitrix/themes/.default/icons/".$this->MODULE_ID."/");//icons
         DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID."/");//images
         DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID."/");//Функция удаляет каталог рекурсивно см http://dev.1c-bitrix.ru/api_help/main/functions/file/deletedirfiles.php
         DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/");
         // delete temporary template files
         DeleteDirFilesEx(BX_PERSONAL_ROOT."/tmp/".$this->MODULE_ID."/");
         DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/");
         return true;
     }

	function UnInstallEvents() //Метод выполняет удаление связанных событий
    {
         return true;
    }

    function GetModuleRightList() //Метод возвращает права доступа к модулю, права обозначаются буквами, по приниципу A<Z, Т.е. права уровня A ниже чем права уровня Z, данные права используются при установке прав доступа для групп пользователей.
    {
        $arr = array(
            "reference_id" => array("D","R","W"),
            "reference" => array(
                GetMessage("KOLOSLIB_DENIED"),
                GetMessage("KOLOSLIB_OPENED"),
                GetMessage("KOLOSLIB_FULL"))
            );
        return $arr;
    }
}