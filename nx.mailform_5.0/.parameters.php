<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @var string $templateFolder */
/** @var string $mTemplate */
/** @var array $arCurrentValues */
/** @var array $arCurrentValues */
/** @const string LANG_CHARSET */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if(!\Bitrix\Main\Loader::includeModule('iblock')) return;
if(!\Bitrix\Main\Loader::includeModule('highloadblock')) return;

CModule::AddAutoloadClasses(
    '',
    Array(
        'NXMailForm\CNXSimpleIm' => $componentPath.'/lib/CNXSimpleIm.php',
    )
);

use Bitrix\Highloadblock as HL;

$form_id = 'f'.md5(uniqid());

$arFormats = NXMailForm\CNXSimpleIm::GetFileFormats();
$arType =    NXMailForm\CNXSimpleIm::GetBaseTypes();

$arHIBlocks = Array();
$arHIBlock = Array();
$arLogIBlock = Array();


if($arCurrentValues['LOG_FORMAT'] != 'hib') {

	$arIBlockType = CIBlockParameters::GetIBlockTypes();

	$rsIBlock = CIBlock::GetList(
		Array('sort' => 'asc'),
		Array('TYPE' => $arCurrentValues['SOURCE_TYPE'], 'ACTIVE' => 'Y')
	);

	while($arr=$rsIBlock->Fetch()) {
		$arIBlock[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
    }

	$rsProp = CIBlockProperty::GetList(
		Array('sort'=>'asc', 'name'=>'asc'),
		Array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$arCurrentValues['IBLOCK_ID'])
	);

	while ($arr=$rsProp->Fetch()) {
		if($arr['PROPERTY_TYPE'] != 'F')
			$arProperty[$arr['CODE']] = '['.$arr['CODE'].'] '.$arr['NAME'];
	}

	$rsLogIBlock = CIBlock::GetList(
		Array('sort' => 'asc'),
		Array('TYPE' => $arCurrentValues['LOG_TYPE'], 'ACTIVE'=>'Y')
	);

	while($arr = $rsLogIBlock->Fetch()) {
		$arLogIBlock[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
    }

	$rsLogProp = CIBlockProperty::GetList(
		Array('sort' => 'asc', 'name'=>'asc'),
		Array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$arCurrentValues['LOG_ID'])
	);

	$arProperty_log['none'] = GetMessage('LOG_NONE');
	$arProperty_log['PREVIEW_TEXT'] = GetMessage('LOG_PREVIEW_TEXT');
	$arProperty_log['DETAIL_TEXT'] = GetMessage('LOG_DETAIL_TEXT');

	while ($arr=$rsLogProp->Fetch()) {
		if($arr['PROPERTY_TYPE'] != 'F')
			$arProperty_log[$arr['CODE']] = '['.$arr['CODE'].'] '.$arr['NAME'];
	}
}

else {

	$rsHIBlock = HL\HighloadBlockTable::getList(
		Array(
			'select' => Array('*'), 
			'filter' => Array('!=TABLE_NAME' => '')
		));

	while($arr = $rsHIBlock->Fetch()) {
		$arHIBlock[$arr['ID']] = $arr['NAME'];
		$arHIBlocks[$arr['ID']] = $arr;
	}

	$arProperty_log['none'] = GetMessage('LOG_NONE');
	if($arCurrentValues['LOG_HIB_ID']) {
	    
		$entity = HL\HighloadBlockTable::compileEntity($arHIBlocks[$arCurrentValues['LOG_HIB_ID']]);
		$entityDataClass = $entity->getDataClass();
	
		$fields = $entity->getFields();
		foreach($fields as $code => $filed) {
			if($code != 'ID') {
				$ar_res = CUserTypeEntity::GetList(
					Array('ID' => 'ASC'),
					Array('FIELD_NAME' => $code)
				);

				if($tmp = $ar_res->GetNext()) {
					$res = CUserTypeEntity::GetByID($tmp['ID']);
					if($res['EDIT_FORM_LABEL'][LANGUAGE_ID])
						$arProperty_log[$res['FIELD_NAME']] = $res['EDIT_FORM_LABEL']['ru'];
					else $arProperty_log[$res['FIELD_NAME']] = $res['FIELD_NAME'];
				}       
			}
		}
	}
}

$arComponentParameters = Array(
	
	'GROUPS' => Array(
			
		'FORM_SETTINGS' => Array(
			'SORT' => 120,
			'NAME' => GetMessage('SETTINGS_FORM'),
		),
		
		'SOURCE_SETTINGS' => Array(
			'SORT' => 130,
			'NAME' => GetMessage('SETTINGS_SOURCE'),
		),
		
		'LOG_SETTINGS' => Array(
			'SORT' => 130,
			'NAME' => GetMessage('SETTINGS_LOG'),
		),
	),
			
	'PARAMETERS' => Array(

		'MAIL_RECIPIENT' => Array(
			'NAME' => GetMessage('USE_MAIL_RECIPIENT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'mail@site.ru',
			'PARENT' => 'BASE',
		),
                      
		'SUBJECT' => Array(
			'NAME'=>GetMessage('SUBJ'),
			'TYPE' => 'STRING',
			'DEFAULT' => GetMessage('SUBJ_DEF'),
			'PARENT' => 'BASE',
		),

		'BCC' => Array(
			'NAME'=>GetMessage('USE_BCC'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
			'PARENT' => 'BASE',
		),

        'NAME_MAIL_RECIPIENT' => Array(
			'NAME'=>GetMessage('NAME_RECIPIENT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'site.ru',
			'PARENT' => 'BASE',
		),
           
	    'FROM' => Array(
			'NAME'=>GetMessage('USE_FROM'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'info@site.ru',
			'PARENT' => 'BASE',
		),
		
		'TYPE' => Array(
			'NAME'=>GetMessage('MESSAGE_TYPE'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'Заявка',
			'PARENT' => 'BASE',
		),
		
		'FORM_ID' => Array(
			'NAME'=>GetMessage('FORM_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => $form_id,
			'PARENT' => 'BASE',	
		),

		'COUNT' => Array(
			'NAME' => GetMessage('FIELD_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => '4',
			'REFRESH' => 'Y',
			'PARENT' => 'BASE',
		),

		'PLACEHOLDERS' => Array(
			'NAME' => GetMessage('USE_PLACEHOLDERS'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'REFRESH' => 'Y',
			'PARENT' => 'BASE',
		),
		
		'SHOW_LOG' => Array(
			'NAME' => GetMessage('SAVE_LOG'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'LOG_SETTINGS',
			'REFRESH' => 'Y'
		), 
		
		'MAGAZINE_CONNECT' => Array(
			'NAME' => GetMessage('MESSAGE_MAGAZINE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'LOG_SETTINGS',
			'REFRESH' => 'Y'
		),
    )
);

if($arCurrentValues['SHOW_LOG'] == 'Y') {

	$arComponentParameters['PARAMETERS']['LOG_FORMAT'] = Array(
		'PARENT' => 'LOG_SETTINGS',
		'NAME' => GetMessage('LOG_FORMAT'),
		'TYPE' => 'LIST',
		'VALUES' => Array('ib' => GetMessage('LOG_FORMAT_IB'), 'hib' => GetMessage('LOG_FORMAT_HIB')),
		'REFRESH' => 'Y',
	);
		
	if($arCurrentValues['LOG_FORMAT'] != 'hib') {	

		$arComponentParameters['PARAMETERS']['LOG_TYPE'] = Array(
			'PARENT' => 'LOG_SETTINGS',
			'NAME' => GetMessage('LOG_TYPE_IB'),
			'TYPE' => 'LIST',
			'VALUES' => $arIBlockType,
			'REFRESH' => 'Y',
		);
			
		$arComponentParameters['PARAMETERS']['LOG_ID'] = Array(
			'PARENT' => 'LOG_SETTINGS',
			'NAME' => GetMessage('LOG_IB'),
			'TYPE' => 'LIST',
			'VALUES' => $arLogIBlock,
			'REFRESH' => 'Y',
		);
			
		$arComponentParameters['PARAMETERS']['MOD_LOG'] = Array( 
		   'NAME' => GetMessage('LOG_MODERATION'),
		   'TYPE' => 'CHECKBOX',
		   'DEFAULT' => 'Y',
		   'PARENT' => 'LOG_SETTINGS',
		);
	}
	
	else {
	
		$arComponentParameters['PARAMETERS']['LOG_HIB_ID'] = Array(
			'PARENT' => 'LOG_SETTINGS',
			'NAME' => GetMessage('LOG_HIB'),
			'TYPE' => 'LIST',
			'VALUES' => $arHIBlock,
			'REFRESH' => 'Y',
		);
	}
	
	if(!($def_count = intval($arCurrentValues['COUNT']))){
		$def_count = $arComponentParameters['PARAMETERS']['COUNT']['DEFAULT'];
    }
	   
	for ($i=1; $i <= $def_count; $i++) {
		if($arCurrentValues['F'.$i.'_NAME']){
			$arComponentParameters['PARAMETERS']['F'.$i.'_CONNECT'] = Array(
				'NAME' => GetMessage('LOG_SAVE').' «'.$arCurrentValues['F'.$i.'_NAME'].'» '.GetMessage('LOG_SAVE_IN'),
				'TYPE' => 'LIST',
				'VALUES' => $arProperty_log,
				'PARENT' => 'LOG_SETTINGS',
				'COLS' => 45,
			);
		}
	}

	unset($arProperty_log['PREVIEW_TEXT']);
	unset($arProperty_log['DETAIL_TEXT']);

	$arComponentParameters['PARAMETERS']['USER_CONNECT'] = Array(
		'NAME' => GetMessage('LOG_SAVE').' «'.getMessage('LOG_USER').'» '.GetMessage('LOG_SAVE_IN'),
		'TYPE' => 'LIST',
		'VALUES' => $arProperty_log,
		'PARENT' => 'LOG_SETTINGS',
		'COLS' => 45,
	);

	if($arCurrentValues['LOG_FORMAT'] == 'hib'){
			$arComponentParameters['PARAMETERS']['DATA_CONNECT'] = Array(
				'NAME' => GetMessage('LOG_SAVE').' «'.getMessage('LOG_DATA').'» '.GetMessage('LOG_SAVE_IN'),
				'TYPE' => 'LIST',
				'VALUES' => $arProperty_log,
				'PARENT' => 'LOG_SETTINGS',
				'COLS' => 45,
			);
			
			$arComponentParameters['PARAMETERS']['TITLE_CONNECT'] = Array(
				'NAME' => GetMessage('LOG_SAVE').' «'.getMessage('LOG_TITLE').'» '.GetMessage('LOG_SAVE_IN'),
				'TYPE' => 'LIST',
				'VALUES' => $arProperty_log,
				'PARENT' => 'LOG_SETTINGS',
				'COLS' => 45,
			);
	}


	if($arCurrentValues['MAGAZINE_CONNECT'] == 'Y') {

		$arComponentParameters['PARAMETERS']['SUM_CONNECT'] = Array(
			'NAME' => GetMessage('LOG_SAVE').' «'.getMessage('LOG_SUM').'» '.GetMessage('LOG_SAVE_IN'),
			'TYPE' => 'LIST',
			'VALUES' => $arProperty_log,
			'PARENT' => 'LOG_SETTINGS',
			'COLS' => 45,
		);

		$arComponentParameters['PARAMETERS']['JSON_CONNECT'] = Array(
			'NAME' => GetMessage('LOG_SAVE').' «'.getMessage('LOG_JSON').'» '.GetMessage('LOG_SAVE_IN'),
			'TYPE' => 'LIST',
			'VALUES' => $arProperty_log,
			'PARENT' => 'LOG_SETTINGS',
			'COLS' => 45,
		);

		if($arCurrentValues['LOG_FORMAT'] == 'hib'){
			$arComponentParameters['PARAMETERS']['ARCHIVE_CONNECT'] = Array(
				'NAME'=>GetMessage('LOG_SAVE').' «'.getMessage('LOG_ARCHIVE').'» '.GetMessage('LOG_SAVE_IN'),
				'TYPE' => 'LIST',
				'VALUES' => $arProperty_log,
				'PARENT' => 'LOG_SETTINGS',
				'COLS' => 45,
			);
		}
	}
}

if(!($def_count = intval($arCurrentValues['COUNT'])))
	$def_count = $arComponentParameters['PARAMETERS']['COUNT']['DEFAULT'];

for ($i = 1; $i <= $def_count; $i++)  {

	$arComponentParameters['PARAMETERS']['F'.$i.'_NAME'] = Array(
		'NAME' => GetMessage('F_NAME').' '.$i,
		'TYPE' => 'STRING',
		'DEFAULT' => '',
		'PARENT' => 'FORM_SETTINGS',
	);
	
	if($arCurrentValues['PLACEHOLDERS'] == 'Y' && NXMailForm\CNXSimpleIm::TypeHasPlaceholder($arCurrentValues['F'.$i.'_TYPE'])) {
		$arComponentParameters['PARAMETERS']['F'.$i.'_PLACEHOLDER'] = Array(
			'NAME' => GetMessage('F_PLACEHOLDER').' '.$i,
			'TYPE' => 'STRING',
			'DEFAULT' => '',
			'PARENT' => 'FORM_SETTINGS',
		);
	}

	$arComponentParameters['PARAMETERS']['F'.$i.'_TYPE'] = Array(
		'NAME'=>GetMessage('F_TYPE'),
		'TYPE' => 'LIST',
		'DEFAULT'=>0,
		'VALUES' => $arType,
		'PARENT' => 'FORM_SETTINGS',
		'COLS' => 45,
		'REFRESH' => 'Y',
	);

	$arComponentParameters['PARAMETERS']['F'.$i.'_OBLIG'] = Array(
				'NAME'=>GetMessage('F_OBLIG'),
				'TYPE' => 'CHECKBOX',
				'DEFAULT'=>'N',
				'PARENT' => 'FORM_SETTINGS',
				);
		
	if(NXMailForm\CNXSimpleIm::TypeHasArrValues($arCurrentValues['F'.$i.'_TYPE'])) {
		$arComponentParameters['PARAMETERS']['F'.$i.'_VALS'] = Array(
			'PARENT' => 'FORM_SETTINGS',
			'NAME' => GetMessage('F_VAL').' '.$i,
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => false,
			'DEFAULT_SETTINGS' => 'Y',
			'ADDITIONAL_VALUES' => 'Y',
		);	
	}

	elseif($arCurrentValues['F'.$i.'_TYPE'] == 'hidden') {
		$arComponentParameters['PARAMETERS']['F'.$i.'_VALS'] = Array(
			'PARENT' => 'FORM_SETTINGS',
			'NAME' => GetMessage('F_VAL_SINGLE').' '.$i,
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		);
	}

	elseif ($arCurrentValues['F'.$i.'_TYPE'] == 'file')
	{
		$arComponentParameters['PARAMETERS']['F'.$i.'_SIZE'] = Array(
			'PARENT' => 'FORM_SETTINGS',
			'NAME' => GetMessage('F_MAX_SIZE'),
			'TYPE' => 'STRING',
			'DEFAULT' => 1*1024,
		);
		
		$arComponentParameters['PARAMETERS']['F'.$i.'_URL'] = Array(
			'PARENT' => 'FORM_SETTINGS',
			'NAME' => GetMessage('F_UPLOAD'),
			'TYPE' => 'STRING',
			'DEFAULT' => '/upload/',
		);
		
		$arComponentParameters['PARAMETERS']['F'.$i.'_VALS'] = Array(
			'PARENT' => 'FORM_SETTINGS',
			'NAME' => GetMessage('F_FORMATS'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $arFormats,
			'DEFAULT_SETTINGS' => 'Y',
			'ADDITIONAL_VALUES' => 'Y',
		);
	}
}

$arComponentParameters['PARAMETERS']['F1_NAME']['DEFAULT']  = GetMessage('USE_SENDER_NAME');
$arComponentParameters['PARAMETERS']['F1_TYPE']['DEFAULT']  = 'text';
$arComponentParameters['PARAMETERS']['F1_OBLIG']['DEFAULT'] = 'Y';

if($def_count > 1) {
	$arComponentParameters['PARAMETERS']['F2_NAME']['DEFAULT']  = GetMessage('USE_SENDER_PHONE');
	$arComponentParameters['PARAMETERS']['F2_TYPE']['DEFAULT']  = 'phone';
	$arComponentParameters['PARAMETERS']['F2_OBLIG']['DEFAULT'] = 'N';
}

if($def_count > 2) {
	$arComponentParameters['PARAMETERS']['F3_NAME']['DEFAULT']  = GetMessage('USE_SENDER_MAIL');
	$arComponentParameters['PARAMETERS']['F3_TYPE']['DEFAULT']  = 'mail';
	$arComponentParameters['PARAMETERS']['F3_OBLIG']['DEFAULT'] = 'Y';
}

if($def_count > 3) {
	$arComponentParameters['PARAMETERS']['F4_NAME']['DEFAULT']  = GetMessage('USE_SENDER_MESSAGE');
	$arComponentParameters['PARAMETERS']['F4_TYPE']['DEFAULT']  = 'textarea';
	$arComponentParameters['PARAMETERS']['F4_OBLIG']['DEFAULT'] = 'N';
}