<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('FORM_NAME'),
	'DESCRIPTION' => GetMessage('FORM_DESC'),
	'ICON' => '/images/mailform.gif',
	'PATH' => array(
		'ID' => 'my_components',
		'NAME' => GetMessage('IP_COMPONENTS_TITLE'),
		'CHILD' => array(
			'ID' => 'ip_forms',
			'NAME' => GetMessage('IP_FORMS_GROUP')
		)
	),
);