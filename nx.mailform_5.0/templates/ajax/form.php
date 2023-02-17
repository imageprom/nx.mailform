<?
if($arParams['FORM_TITLE'] != ''){
    $mTemplate = '<h3>'.$arParams['FORM_TITLE'].'</h3>';
}

global $APPLICATION;

$mTemplate .='<form action="#smform_'.$arParams['FORM_ID'].
                 '" name="form_'.$arParams['FORM_ID'].
                 '" method="post" class="nx_call_form"'.
                 '  enctype="multipart/form-data">'.
                 '<div class="form-wrap">';

$mTemplate .= '<input type="hidden" name="nx_form_ajax" value="Y" />';
$mTemplate .= '<input type="hidden" name="nx_form_path" value="'.$APPLICATION->GetCurPage().'" />';


for ($i = 1; $i <= $arResult['COUNT']; $i++) {
    if ($arResult['ITEMS'][$i]['type'] == 'hidden')
        $mTemplate .= '{Value['.$i.']}';
}

for ($i = 1; $i <= $arResult['COUNT']; $i++) {

    if ($arResult['ITEMS'][$i]['type'] == 'checkbox') {
        $mTemplateCheckbox .= '<div class="checkbox checkbox-form nx-flex-col-c-c">'.
                                  '{Value['.$i.']}'.
                                  '<label for="'.$arParams['FORM_ID'].'_'.$i.'">'.
                                        '{Desc['.$i.']}'.
                                  '</label>'.
                              '</div>';
    }

    elseif ($arResult['ITEMS'][$i]['type'] == 'checkboxgroup') {
        $mTemplateCheckboxGroup .= '<div class="checkbox checkbox-form checkboxgroup">'.
                                        '{Value['.$i.']}'.
                                   '</div>';
    }

    elseif ($arResult['ITEMS'][$i]['type'] == 'textarea') {
        $mTemplateFields .= '<div class="form-group textarea-form nx-flex-col-btw-st">'.
                                '<label for="'.$arParams['FORM_ID'].'_'.$i.'">'.
                                    '{Desc['.$i.']}'.
                                '</label>'.
                                '{Value['.$i.']}'.
                            '</div>';
    }

    elseif ($arResult['ITEMS'][$i]['type'] != 'hidden' && $arResult['ITEMS'][$i]['type'] != 'checkbox') {
        $mTemplateFields .= '<div class="form-group nx-flex-col-btw-st">'.
                                '<label for="'.$arParams['FORM_ID'].'_'.$i.'" class="nx-flex-row-l-c">'.
                                    '{Desc[' . $i . ']}'.
                                '</label>'.
                                '{Value['.$i.']}'.
		                    '</div>';
    }
}

$mTemplate .= $mTemplateFields;

$mTemplate .= $mTemplateCheckbox;
$mTemplate .=
    '<div class="btn-wrap nx-flex-row-c-c">'.
        '<button type="submit" name="sButton" class="btn btn-small btn-dark">'.
            $arParams['BUTTON'].
        '</button>'.
    '</div>'.
'</div>';
