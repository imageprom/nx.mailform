<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("MAIN_MENU_ITEMS_NAME"),
	"DESCRIPTION" => GetMessage("MAIN_MENU_ITEMS_DESC"),
	"ICON" => "/images/mailform.gif",
	"PATH" => array(
		"ID" => "my_components",
		"NAME" => GetMessage("IP_COMPONENTS_TITLE"),
		"CHILD" => array(
			"ID" => "my_forms",
			"NAME" => GetMessage("MAIN_NAVIGATION_SERVICE")
		)
	),
);

?>