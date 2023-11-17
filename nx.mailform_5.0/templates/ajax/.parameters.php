<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arTemplateParameters["BUTTON"] = array(
    "NAME" => "Надпись на кнопке",
    "TYPE" => "TEXT",
    "DEFAULT" => "Отправить",
    "PARENT" => "BASE",
);

$arTemplateParameters["TEXT"] = array(
    "NAME" => "Сопровождающий текст",
    "TYPE" => "TEXT",
    "DEFAULT" => "Отправить",
    "PARENT" => "BASE",
);

$arTemplateParameters["FORM_TITLE"] = array(
    "NAME" => "Заголовок формы",
    "TYPE" => "TEXT",
    "DEFAULT" => "Заказать звонок",
    "PARENT" => "BASE",
);

$arTemplateParameters["FORM_TITLE_2"] = array(
    "NAME" => "Заголовок формы 2",
    "TYPE" => "TEXT",
    "DEFAULT" => "Заказать звонок",
    "PARENT" => "BASE",
);

$arTemplateParameters["MANAGER_BACK"] = array(
    "NAME" => "Сообщение от менеджеров",
    "TYPE" => "CHECKBOX",
    "DEFAULT" => "N",
    "PARENT" => "BASE",
);

$arTemplateParameters["USER_CONNECTION"] = array(
    "NAME" => "Связь с данными зарегистрированного пользователя",
    "TYPE" => "TEXT",
    "DEFAULT" => "",
    "PARENT" => "BASE",
);

$arTemplateParameters["ORDER_ADDITIONAL_INFO"] = array(
    "NAME" => "Дополнительная информация для покупателя",
    "TYPE" => "TEXT",
    "DEFAULT" => "",
    "PARENT" => "BASE",
);
