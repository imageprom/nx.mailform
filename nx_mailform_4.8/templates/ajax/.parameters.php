<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arTemplateParameters["BUTTON"] = Array(
		"NAME"=>"Надпись на кнопке",
		"TYPE" => "TEXT",
		"DEFAULT" => "Отправить",
		"PARENT" => "BASE",
		);

$arTemplateParameters["TEXT"] = Array(
		"NAME"=>"Сопровождающий текст",
		"TYPE" => "TEXT",
		"DEFAULT" => "Отправить",
		"PARENT" => "BASE",
		);

$arTemplateParameters["FORM_TITLE"] = Array(
		"NAME"=>"Заголовок формы",
		"TYPE" => "TEXT",
		"DEFAULT" => "Заказать звонок",
		"PARENT" => "BASE",
		);
		
$arTemplateParameters["MANAGER_BACK"] = Array(
		"NAME"=>"Сообщение от менеджеров",
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"PARENT" => "BASE",
		);

$arTemplateParameters["USER_CONNECTION"] = Array(
		"NAME"=>"Связь с данными зарегестрированного пользователя",
		"TYPE" => "TEXT",
		"DEFAULT" => "",
		"PARENT" => "BASE",
		);



?>
