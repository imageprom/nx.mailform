<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @var string $arSorts */
/** @var array $arCurrentValues */
/** @const string LANG_CHARSET */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

CModule::AddAutoloadClasses(
    '',
    Array(
        'NXMailForm\CNXInput' => $componentPath.'/lib/CNXInput.php',
        'NXMailForm\CNXSimpleIm' => $componentPath.'/lib/CNXSimpleIm.php',
    )
);

if(!\Bitrix\Main\Loader::includeModule('iblock')) return;
if(!\Bitrix\Main\Loader::includeModule('highloadblock')) return;

use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

$arParams['FORM_ID'] = trim(strval($arParams['FORM_ID']));
for ($i=0; $i <= $arParams['COUNT']; $i++) {
	
	if ($arParams['F'.$i.'_NAME'] != '') {
		
		if (is_array($arParams['F'.$i.'_VALS'])) {
			$tmp = array();
			foreach ($arParams['F'.$i.'_VALS'] as $key => $val) {
				if($val) $tmp[] = $val; //unset ($arParams['F'.$i.'_VALS'][$key]);
			}
			$arParams['F'.$i.'_VALS'] = $tmp;
		}
	}
}
if(!function_exists('NXMailGetID')) {
	function NXMailGetID($arParams) {
		$XML_ID = 1;
			if($arParams['LOG_FORMAT']!= 'hib') {
				$rs = CIBlockElement::GetList(
				    array('PROPERTY_ID'=>'desc'),
                    array('IBLOCK_ID' => $arParams['LOG_ID']),
                    false,
                    array('nTopCount'=>1),
                    array('ID', 'IBLOCK_ID', 'PROPERTY_ID')
                );

				if ($ar = $rs->GetNext()) {$XML_ID = $ar['PROPERTY_ID_VALUE']+1;} 	
			}
			else {
				$rsHIBlock = HL\HighloadBlockTable::getById($arParams['LOG_HIB_ID']);
				$entity = HL\HighloadBlockTable::compileEntity($rsHIBlock->Fetch());
				$main_query = new Entity\Query($entity);
				
				$main_query->registerRuntimeField('max', array('data_type' => 'integer', 'expression' => array('max(%s)', 'UF_ORDERS_ID')));
				$main_query->setSelect(array('max'));
				$main_query->setLimit(1);
				$result = $main_query->exec();
				$result = new CDBResult($result);		
				if ($ar = $result->GetNext()) {$XML_ID = $ar['max'] + 1;} 	
			}
		return $XML_ID;
	}
}

$arParams['AJAX'] = isset($_REQUEST['nx_form_ajax']) && $_REQUEST['nx_form_ajax'] == 'Y';

if($arParams['AJAX']) {
	$this->setFrameMode(false);
	define('BX_COMPRESSION_DISABLED', true);

	ob_start();
		$this->IncludeComponentTemplate('ajax');
	$json = ob_get_contents();

	$APPLICATION->RestartBuffer();
	while(ob_end_clean());
	header('Content-Type: application/json; charset='.LANG_CHARSET);
	echo $json;
	CMain::FinalActions();
	die();
}
else {
	$this->IncludeComponentTemplate();
}