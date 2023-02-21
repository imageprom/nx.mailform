<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($isAuth = $USER->IsAuthorized()) {
	$userId = $USER->GetID();
	$arResult['isPerson'] = true; $arResult['isCorporate'] = true;
	
	if ($userId)  {
		$UserGroup = CUser::GetUserGroup($userId);
		if(in_array(7, $UserGroup)) $arResult['isPerson'] = true; else $arResult['isPerson'] = false;
		if(in_array(8, $UserGroup)) $arResult['isCorporate'] = true; else $arResult['isCorporate'] = false;
	}

	$rsUser = CUser::GetList(($by='ID'), ($order = 'desc'), array('ID' => $userId), array('SELECT'=>array('UF_*')));
	if($User = $rsUser->GetNext()) {

		if($arParams['USER_CONNECTION']) {
			$user_template = json_decode(htmlspecialchars_decode($arParams['USER_CONNECTION']), true);

			$currentUser = array();
			foreach ($user_template as $stringKey => $userFields) {
				$userFields = explode(',', $userFields);
				foreach ($userFields as $key => $value) {
					$currentUser[$stringKey][] = $User[trim($value)];
				}
				$currentUser[$stringKey] = implode(' ', $currentUser[$stringKey]);
			}

		}
	}
}
print_r($currentUser);
$count = 0;
for ($i = 0; $i <= $arParams['COUNT']; $i++) {
	if ($arParams["F{$i}_NAME"] != '' ) {  
		$arItem = array(); $count++;
		$typeConf = NxSimpleIm::GetBaseConf($arParams["F{$i}_TYPE"]);
		
		$arItem['type'] = $typeConf['TYPE'];
		$arItem['name'] = $arParams['FORM_ID'].'_'.$count;
		$arItem['description'] = $arParams["F{$i}_NAME"];
		$arItem['eText'] = '';
		$arItem['method'] = 'post';
		$arItem['rDetail'] = true;
		$arItem['regular'] = $typeConf['REG'];
		
		if($arParams['PLACEHOLDERS'] == 'Y' && $arParams["F{$i}_PLACEHOLDER"])
			$arItem['placeholder'] = $arParams["F{$i}_PLACEHOLDER"];
		
		if ($typeConf['TYPE'] == 'checkbox' || $typeConf['TYPE'] == 'checkboxgroup')
			$arItem['intext'] = 'class="cb"';
		elseif ($typeConf['TYPE'] == 'radio')
			$arItem['intext'] = 'class="rb"';
		elseif($typeConf['TYPE'] == 'data') 
			 $arItem['intext'] = 'class="inpt data"';	
		else $arItem['intext'] = 'class="inpt"';
		
		if ($typeConf['REG'] == 'mail')
			$arItem['rRight'] = true;

		if ($arParams["F{$i}_OBLIG"] == 'Y') {$arItem['oblig'] = '1';}
		else $arItem['oblig'] = '0';
		
		if ($typeConf['TYPE'] == 'hidden') {
			$arItem['default'] = $arParams["F{$i}_VALS"];		
		}
		else {
			
			if($isAuth) { $arItem['default'] = $currentUser[$count];}
			else $$arItem['default'] = "";
		}
		
		if ($typeConf['TYPE'] == 'select' || $typeConf['TYPE'] == 'mselect' || $typeConf['TYPE'] == 'radio' || $typeConf['TYPE'] == 'checkboxgroup') {
				$arItem['values'] = $arParams["F{$i}_VALS"];
		}
		
		elseif ($typeConf['TYPE'] == 'file') {
			$arItem['config']['UploadMaxSize'] = intval($arParams["F{$i}_SIZE"]) * 1024;
			$arItem['config']['FileType'] = NxSimpleIm::GetFileFormat($arParams["F{$i}_VALS"]);
			$arItem['config']['Host'] = 'http://'.$_SERVER['HTTP_HOST'];
			$arItem['config']['UploadUrl'] = $arParams["F{$i}_URL"];	
		}
		else {  
			if ($typeConf['TYPE'] == 'checkbox')
				$arItem['values'] = 'Да';

			else $arItem['values'] = '';
		}

		$arResult['ITEMS'][$count] = $arItem;			
	}       
}
$arResult['COUNT'] = $count;
?>