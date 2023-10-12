<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Highloadblock as HL; 

if($arParams['LOG_FORMAT'] != 'hib') {

	$el = new CIBlockElement;
	$PREVIEW_TEXT = false;
	$DETAIL_TEXT = false;

	$PROP = array();
	$PROP['ID'] = $XML_ID;
	
	for ($i=1; $i<=$arResult["COUNT"]; $i++) {
		if($arResult['R']->GV($i) && $arParams['F'.$i.'_CONNECT'] && $arParams['F'.$i.'_CONNECT'] != 'none') {
			if($arParams['F'.$i.'_CONNECT'] == 'PREVIEW_TEXT') $PREVIEW_TEXT = $arResult['R']->GV($i);
			elseif($arParams['F'.$i.'_CONNECT'] == 'DETAIL_TEXT') $DETAIL_TEXT = $arResult['R']->GV($i);
			elseif($arResult['ITEMS'][$i]['type'] == 'file') {
				
				$file_upp = CFile::MakeFileArray($arResult['R']->GV($i));
//				$PROP[$arParams['F'.$i.'_CONNECT']] = array('VALUE' => $file_upp);
				$PROP[$arParams['F'.$i.'_CONNECT']] = $file_upp;
			}
			
			else $PROP[$arParams['F'.$i.'_CONNECT']] = $arResult['R']->GV($i);
		}
	}

	if($arParams['USER_CONNECT'] && $arParams['USER_CONNECT'] != 'none')
		 $PROP[$arParams['USER_CONNECT']] = $USER->GetID();


	$arLoadItemArray = Array(
		'MODIFIED_BY'    => $USER->GetID(), 
		'IBLOCK_SECTION_ID' => false,          
		'IBLOCK_ID'      => $arParams['LOG_ID'],
		'PROPERTY_VALUES'=> $PROP,
		'XML_ID' => $XML_ID,
		'ACTIVE' => 'Y',
		'NAME' => $arParams['TYPE'].' №'.$XML_ID,
		'DATE_ACTIVE_FROM'         => date('d.m.Y H:i:s'),       
		'PREVIEW_TEXT'   => $PREVIEW_TEXT,
		'DETAIL_TEXT'   => $DETAIL_TEXT,
	);

	if($arParams['MOD_LOG'] == 'Y') $arLoadItemArray['ACTIVE'] = 'N';

	$el = new CIBlockElement;
	$ITEM_ID = $el->Add($arLoadItemArray);

}
else {  
	$rsHIBlock = HL\HighloadBlockTable::getList(array('select'=>array('*'), 'filter'=>array('ID' => $arParams['LOG_HIB_ID'])));
	$entity = HL\HighloadBlockTable::compileEntity($rsHIBlock->Fetch());
	$entityDataClass = $entity->getDataClass();
	$PROP = array();

	$PROP['UF_ORDERS_ID'] = $XML_ID;

	for ($i = 1; $i <= $arResult['COUNT']; $i++) {
		if($arResult['R']->GV($i) && $arParams['F'.$i.'_CONNECT'] && $arParams['F'.$i.'_CONNECT'] != 'none') {
			if($arResult['ITEMS'][$i]['type'] == 'file') {
				$file_upp = CFile::MakeFileArray($arResult['R']->GV($i));
				$fid = CFile::SaveFile($file_upp, 'hlblock');
				if (intval($fid)>0) $PROP[$arParams['F'.$i.'_CONNECT']] = $file_upp;

//                $file_upp = CFile::MakeFileArray($arResult['R']->GV($i));
//                $PROP[$arParams['F'.$i.'_CONNECT']] = array('VALUE' => $file_upp);
			}
			else $PROP[$arParams['F'.$i.'_CONNECT']] = $arResult['R']->GV($i);
		}
	}

	if($arParams['USER_CONNECT'] && $arParams['USER_CONNECT'] != 'none')
		 $PROP[$arParams['USER_CONNECT']] = $USER->GetID();

	if($arParams['DATA_CONNECT'] && $arParams['DATA_CONNECT'] != 'none')
		 $PROP[$arParams['DATA_CONNECT']] = date("d.m.Y H:i:s");

	if($arParams['TITLE_CONNECT'] && $arParams['TITLE_CONNECT'] != 'none')
		 $PROP[$arParams['TITLE_CONNECT']] = $arParams["TYPE"].' №'.$XML_ID;

	$result = $entityDataClass::add($PROP);			
	if ($result->isSuccess()) $ITEM_ID = $result->getId();   
}

if ($ITEM_ID) {
    if($arParams['MAGAZINE_CONNECT'] == 'Y'){
        if(CModule::IncludeModule('nx_market')) {
            // Google comerce Transaction Data
            $ya_goods =  NXMarket\getYaGoodsDataLayer($NX_BASKET_RESULT_DATA['GOOGLE'][$SHOP_ID]);

            ob_start();
            ?>
            <div id="ecommerce">
                <script type="text/javascript">
                    console.log('e-commerce-send');

                    window.dataLayer = window.dataLayer || [];

                    dataLayer.push({'event': 'event-to-ga', 'eventCategory' : 'order', 'eventAction' : 'order', 'eventLabel' : 'order'});

                    dataLayer.push({
                        'event': 'purchase',
                        'ecommerce': {
                            'purchase': {
                                'actionField': {
                                    'id': '<?=$XML_ID?>',
                                    'affiliation': 'Эван',
                                    'revenue':<?=number_format($NX_BASKET_RESULT_DATA['SUM'][$SHOP_ID], 2, '.', '')?>,
                                    'tax': 0.00,
                                    'shipping': 0.00,
                                },
                                'products': [<?=$ya_goods?>]
                            }
                        }
                    });

                    console.log('----------------------');
                    console.log(window.dataLayer);
                    console.log('----------------------');

                </script>
            </div>
            <?

            $arResult['E_COMMERCE'] .= ob_get_contents();
            ob_end_clean();
        }
    }
}