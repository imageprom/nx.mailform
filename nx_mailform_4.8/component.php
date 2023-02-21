<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!class_exists("nxInput"))   require_once("mInput.3.19.php");
if (!class_exists("PHPMailer")) require_once("class.phpmailer.php");
if(!CModule::IncludeModule("iblock")) return;
if(!CModule::IncludeModule("highloadblock")) return;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

$arParams["FORM_ID"] = strval($arParams["FORM_ID"]);
for ($i=0; $i<= $arParams["COUNT"]; $i++) {
	
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
			if($arParams["LOG_FORMAT"]!= 'hib') {
				$rs=CIBlockElement::GetList(array("PROPERTY_ID"=>"desc"), array("IBLOCK_ID"=>$arParams["LOG_ID"]), false, array("nTopCount"=>1), array("ID", "IBLOCK_ID", "PROPERTY_ID"));
				if ($ar = $rs->GetNext()) {$XML_ID = $ar["PROPERTY_ID_VALUE"]+1;} 	
			}
			else {
				$rsHIBlock =  $hlblock = HL\HighloadBlockTable::getById($arParams["LOG_HIB_ID"]);
				$entity = HL\HighloadBlockTable::compileEntity($rsHIBlock->Fetch());
				$main_query = new Entity\Query($entity);
				
				$main_query->registerRuntimeField("max", array("data_type" => "integer", "expression" => array("max(%s)", "UF_ORDERS_ID")));
				$main_query->setSelect(array('max'));
				$main_query->setLimit(1);
				$result = $main_query->exec();
				$result = new CDBResult($result);		
				if ($ar = $result->GetNext()) {$XML_ID = $ar["max"] + 1;} 	
			}
		return $XML_ID;
	}
}

$this->IncludeComponentTemplate();
?>
