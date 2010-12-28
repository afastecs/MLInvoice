<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

T�m� ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";
require "miscfuncs.php";
require "datefuncs.php";

sesVerifySession();

require_once "localize.php";

$strForm = getPost('selectform', getRequest('selectform', ''));
$strMode = getPost('mode', getGet('mode', 'MODIFY'));
$strDefaults = getPost('defaults', getRequest('defaults', '')); 

if( $strDefaults ) {
    $tmpDefaults = explode(" ", urldecode($strDefaults));
    //print_r($tmpDefaults);
    $x = 0;
    while (list($key, $val) = each($tmpDefaults)) {
        if( $val ) {
            $tmpValues = explode(">", $val);
            $astrDefaults[$tmpValues[0]] = $tmpValues[1];
            $x++;
        }
    }
}
else {
    $astrDefaults = array();
}
require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnCopy = getPost('copy_x', FALSE) ? TRUE : FALSE;
$blnAdd = getPost('add_x', FALSE) ? TRUE : FALSE;
$blnDelete = getPost('del_x', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$blnInsertDone = FALSE;
$strOnLoad = '';
for( $i = 0; $i < count($astrFormElements); $i++ ) {
    if($astrFormElements[$i]['name'] != '' && array_key_exists($astrFormElements[$i]['name'],$astrDefaults)) {
        $astrValues[$astrFormElements[$i]['name']] = $astrDefaults[$astrFormElements[$i]['name']];
    }
    elseif( !$astrFormElements[$i]['default'] ) {
        if( $astrFormElements[$i]['type'] == "INT" ) {
            $tmpValue = str_replace(",", ".", getPost($astrFormElements[$i]['name'], ''));
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue ? (float)$tmpValue : FALSE;
        }
        elseif( $astrFormElements[$i]['type'] == "LIST" ) {
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], '');
        }
        else {
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], FALSE);
        }
    }
    else {
        if( $astrFormElements[$i]['default'] == "DATE_NOW" ) {
           $strDefaultValue = date("d.m.Y");
        }
        elseif( $astrFormElements[$i]['default'] == "DATE_NOW+14" ) {
           $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
        }
        elseif( $astrFormElements[$i]['default'] == "DATE_NOW+31" ) {
           $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+31, date("Y")));
        }
        elseif( $astrFormElements[$i]['default'] == "TIME_NOW" ) {
           $strDefaultValue = date("H:i");
        }
        elseif( $astrFormElements[$i]['default'] == "POST" ) {
           $strDefaultValue = getPost($astrFormElements[$i]['name'], '');
        }
        elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
           $strQuery = str_replace("_PARENTID_", $intParentKey, $astrFormElements[$i]['listquery']);
           $intRes = mysql_query_check($strQuery);
           $intAdd = reset(mysql_fetch_row($intRes));
           $strDefaultValue = isset($intAdd) ? $intAdd : 0;
        }
        else {
            $strDefaultValue = $astrFormElements[$i]['default'];
        }
        
        if( $astrFormElements[$i]['type'] == "INT" ) {
            $tmpValue = str_replace(",", ".", getPost($astrFormElements[$i]['name'], ''));
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue !== '' ? (float)$tmpValue : $strDefaultValue;
        }
        else {
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], $strDefaultValue);
        }
    }
}
if( $blnAdd ) {
    $strFields = '';
    $strInsert = '';
    $arrValues = array();
    $blnMissingValues = FALSE;
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        $strControlType = $astrFormElements[$i]['type'];
        $strControlName = $astrFormElements[$i]['name'];
        $mixControlValue = isset($astrValues[$strControlName]) ? $astrValues[$strControlName] : NULL;
        if(isset($mixControlValue)) {
            if( $strControlType != 'NEWLINE' ) {
                if( $strControlType == 'TEXT' || $strControlType == 'AREA' ) {
                  $strFields .= "$strControlName, ";
                  $strInsert .= '?, ';
                  $arrValues[] = $mixControlValue;
                }
                elseif( $strControlType == 'INT' || $strControlType == 'HID_INT' || $strControlType == 'SECHID_INT' ) {
                  //build the insert into fields
                  $strFields .= "$strControlName, ";
                  //format the numbers to right format - finnish use ,-separator
                  $flttmpValue = 
                      $mixControlValue ? str_replace(",", ".", $mixControlValue) : 0;
                  $strInsert .= '?, ';
                  $arrValues[] = $flttmpValue;
                }
                elseif( $strControlType == 'LIST' || $strControlType == 'CHECK' ) {
                    $strFields .= "$strControlName, ";
                    $tmpValue = (isset($mixControlValue) && $mixControlValue !== '') ? str_replace(",", ".", $mixControlValue) : NULL;
                    $strInsert .= '?, ';
                    $arrValues[] = $tmpValue;
                }
                elseif( $strControlType == 'INTDATE' ) {
                    if( !$mixControlValue ) {
                        $mixControlValue = 'NULL';
                    }
                    $strFields .= "$strControlName, ";
                    $strInsert .= '?, ';
                    //convert user input to right format
                    $arrValues[] = dateConvDate2IntDate($mixControlValue);
                }
                elseif( $strControlType == 'TIME' ) {
                    $strFields .= $strControlName.", ";
                    //convert user input to right format
                    $strInsert .= '?, ';
                    //convert user input to right format
                    $astrSearch = array('.', ',', ' ');
                    $arrValues[] = str_replace($astrSearch, ":", $mixControlValue);
                }
            }
        }
        elseif( $strControlType != 'IFORM' && $strControlType != 'HID_INT' && $strControlType != 'NEWLINE' && $strControlType != 'BUTTON' && $strControlType != 'ROWSUM' ) {
            if ( !$astrFormElements[$i]['allow_null'] ) {
                $blnMissingValues = TRUE;
                $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
            }
        }
    }
    if( !$blnMissingValues ) {
        $strInsert = substr($strInsert, 0, -2);
        $strFields = substr($strFields, 0, -2);

        $strQuery = "INSERT INTO $strTable ($strFields, $strParentKey) VALUES ($strInsert, ?)";
        $arrValues[] = $intParentKey;

        $intRes = mysql_param_query($strQuery, $arrValues, TRUE);
        if( $intRes ) {
            $intKeyValue = mysql_insert_id();
            $blnInsertDone = TRUE;
        }
        else {
            $strOnLoad = "alert('".$GLOBALS['locDBERROR']."');";
        }
    }
}
if( $blnInsertDone ) {
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        if( !$astrFormElements[$i]['default'] ) {
            unset($astrValues[$astrFormElements[$i]['name']]);
        }
        
        else {
            if( $astrFormElements[$i]['default'] == "DATE_NOW" ) {
               $strDefaultValue = date("Ymd");
            }
            elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
               $intAdd = substr($astrFormElements[$i]['default'], 4);
               $_POST[$astrFormElements[$i]['name']] += $intAdd;
               
            }
            else {
                $strDefaultValue = $astrFormElements[$i]['default'];
            }
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], $strDefaultValue);
        }
    }
}
if( $blnDelete && $intKeyValue ) {
    //create the delete query
    $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
    //send query to database
    mysql_param_query($strQuery, array($intKeyValue));
    //dispose the primarykey value
    unset($intKeyValue);
    //clear form elements
    unset($astrValues);
    $blnNew = TRUE;
}
if ($intParentKey) 
{
    $strQuery =
        "SELECT * FROM $strTable " .
        'WHERE ' . (getSetting('show_deleted_records') ? '' : 'deleted=0 AND ') . "$strParentKey=? $strOrder";

    $intRes = mysql_param_query($strQuery, array($intParentKey));
    $astrOldValues = array();
    for ($i = 0, $row = mysql_fetch_assoc($intRes); $row; $i++, $row = mysql_fetch_assoc($intRes)) 
    {
        $tmpID = $row['id'];
        $astrOldValues[$i]['deleted'] = $row['deleted'];
        foreach ($astrFormElements as $elem)
        {
            if ($elem['type'] != 'NEWLINE' && $elem['type'] != 'ROWSUM' ) 
            {
                if ($elem['type'] == 'INTDATE') 
                {
                    $astrOldValues[$i][$elem['name']] = dateConvIntDate2Date($row[$elem['name']]);
                }
                elseif ($elem['type'] == 'BUTTON') 
                {
                    $astrOldValues[$i][$elem['name']] = $tmpID;
                }
                else 
                {
                    $astrOldValues[$i][$elem['name']] = $row[$elem['name']];
                }
            }
            else 
            {
                $astrOldValues[$i][$elem['name']] = $intKeyValue;
            }
        }
    }
}

?>
<body class="iform" onload="<?php echo $strOnLoad?>">
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});

function OpenPop(strLink, strTitle, event) {
    $("#popup_edit").dialog({ modal: true, width: 810, height: 340, resizable: false, 
      position: [50, event.clientY], 
      buttons: {
          "<?php echo $GLOBALS['locSAVE']?>": function() { var form = $("#popup_edit_iframe").contents().find("#pop_iform").get(0); form.saveact.value=1; form.submit(); return false; },
          "<?php echo $GLOBALS['locDELETE']?>": function() { if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) { var form = $("#popup_edit_iframe").contents().find("#pop_iform").get(0); form.deleteact.value=1; form.submit(); } return false; },
          "<?php echo $GLOBALS['locCLOSE']?>": function() { $("#popup_edit").dialog('close'); }
      },
      title: strTitle,
    }).find("#popup_edit_iframe").attr("src", strLink);
    
    return true;
}
-->
</script>

<div id="popup_edit" style="display: none; width: 900px; overflow: hidden">
<iframe marginheight="0" marginwidth="0" frameborder="0" id="popup_edit_iframe" src="about:blank" style="width: 800px; height: 290px; overflow: hidden; border: 0"></iframe>
</div>

<form method="post" action="<?php echo $strMainForm?>" target="_self" name="iform">
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<input type="hidden" name="defaults" value="<?php echo $strDefaults?>">
<table class="iform">
  <tr>
<?php
if ($strMode == 'MODIFY') 
{
  $strRowSpan = '';
  foreach ($astrFormElements as $elem)
  {
    if ($elem['type'] == 'ROWSUM') 
    {
?>
    <td class="label <?php echo strtolower($elem['style'])?>_label">
        <?php echo $elem['label']?><br>
    </td>
<?php
    }
    elseif( $elem['type'] != 'HID_INT' && $elem['type'] != 'SECHID_INT' && $elem['type'] != 'BUTTON' && $elem['type'] != 'NEWLINE' && $elem['type'] != 'ROWSUM' ) 
    {
?>
    <td class="label <?php echo strtolower($elem['style'])?>_label">
        <?php echo $elem['label']?><br>
        <?php echo htmlFormElement( $elem['name'],$elem['type'], gpcStripSlashes(isset($astrValues[$elem['name']]) ? $astrValues[$elem['name']] : ''), $elem['style'],$elem['listquery'], "MODIFY", 0, '', array(), $elem['elem_attributes'])?>
    </td>
<?php
    }
    elseif( $elem['type'] == 'SECHID_INT' ) 
    {
?>
    <input type="hidden" name="<?php echo $elem['name']?>" value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
<?php
    }
    elseif( $elem['type'] == 'BUTTON' ) {
?>
    <td class="label">
        &nbsp;
    </td>
<?php
    }
    elseif( $elem['type'] == 'NEWLINE' ) {
        $strRowSpan = 'rowspan="2"';
?>
</tr>
<tr>
<?php
    }
}

?>
    <td class="button" <?php echo $strRowSpan?>>
        <br/>
        <input type="hidden" name="add_x" value="0">
        <a class="tinyactionlink" href="#" onclick="self.document.forms[0].add_x.value=1; self.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locADDROW']?></a>
    </td>
  </tr>

<?php
}
?>

<?php
foreach ($astrOldValues as $row) 
{
?>
  <tr>
<?php
 foreach ($astrFormElements as $elem) 
  {
    $elemStyle = $elem['style'];
    if ($row['deleted'])
      $elemStyle .= ' deleted';
      
    if ($elem['type'] == 'ROWSUM') 
    {
      $items = $row[$multiplierColumn];
      $price = $row[$priceColumn];
      $VATPercent = $row[$VATColumn];
      $VATIncluded = $row[$VATIncludedColumn];
      
      if ($VATIncluded)
      {
        $sumVAT = $items * $price;
        $sum = $sumVAT / (1 + $VATPercent / 100);
        $VAT = $sumVAT - $sum;
      }
      else
      {
        $sum = $items * $price;
        $VAT = $sum * ($VATPercent / 100);
        $sumVAT = $sum + $VAT;
      }
      $title = $GLOBALS['locVATLESS'] . ': ' . miscRound2Decim($sum) . ' &ndash; ' . $GLOBALS['locVATPART'] . ': ' . miscRound2Decim($VAT);         
?>
    <td class="<?php echo $elemStyle?>" >
        <?php echo htmlFormElement($elem['name'], 'TITLEDTEXT', miscRound2Decim($sumVAT), $elem['style'], '', 'NO_MOD', 0, $title, array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
    </td>
<?php
    }
    elseif ($elem['type'] != 'HID_INT' && $elem['type'] != 'SECHID_INT' && $elem['type'] != 'NEWLINE') 
    {
      $value = gpcStripSlashes($row[$elem['name']]);
      if ($elem['style'] == 'percent')
        $value = miscRound2Decim($value, 1);
      elseif ($elem['style'] == 'count' || $elem['style'] == 'currency')
        $value = miscRound2Decim($value, 2);
?>
    <td class="<?php echo $elemStyle?>" >
        <?php echo htmlFormElement($elem['name'], $elem['type'], $value, $elem['style'], $elem['listquery'], 'NO_MOD', 0, $elem['label'], array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
    </td>
<?php
    }
    elseif ($elem['type'] == 'HID_INT' || $elem['type'] == 'SECHID_INT') 
    {
      $strHidField = htmlFormElement( $elem['name'],"HID_INT", gpcStripSlashes($row[$elem['name']]), $elem['style'], $elem['listquery'], 'NO_MOD', 0, $elem['label']);
      if( $elem['type'] == "HID_INT" ) 
      {
        $strPrimaryName = $elem['name'];
        $intPrimaryId = gpcStripSlashes($row[$elem['name']]);
      }
    }
    elseif ($elem['type'] == 'NEWLINE') 
    {
      $strRowSpan = 'rowspan="2"';
?>
  </tr>
  <tr>
<?php
    }
  }
  $strPopLinkEdit = "iform_pop.php?selectform=$strForm&amp;$strParentKey=$intParentKey&amp;$strPrimaryName=$intPrimaryId&amp;defaults=$strDefaults";
  $strPopLinkCopy = "iform_pop.php?selectform=$strForm&amp;$strParentKey=$intParentKey&amp;$strPrimaryName=$intPrimaryId&amp;defaults=$strDefaults&amp;copyact=1";
?>
    
<?php
  if ($strMode == 'MODIFY') 
  {
?>
    <td class="button">
        <a class="tinyactionlink" href="#" onclick="OpenPop('<?php echo $strPopLinkEdit?>', '<?php echo $GLOBALS['locRowModification']?>', event); return false;"><?php echo $GLOBALS['locEDIT']?></a>
    </td>
    <td class="button">
        <a class="tinyactionlink" href="#" onclick="OpenPop('<?php echo $strPopLinkCopy?>', '<?php echo $GLOBALS['locRowCopy']?>', event); return false;"><?php echo $GLOBALS['locCOPY']?></a>
    </td>
<?php
  }
?>
  </tr>
<?php
}

if (isset($showPriceSummary) && $showPriceSummary)
{
  $intTotSum = 0;
  $intTotVAT = 0;
  $intTotSumVAT = 0;
  foreach ($astrOldValues as $row) 
  {
    $intItemPrice = $row[$priceColumn];
    $intItems = $row[$multiplierColumn]; 
    $intVATPercent = $row[$VATColumn];
    $boolVATIncluded = $row[$VATIncludedColumn];
    
    if ($boolVATIncluded)
    {
      $intSumVAT = $intItems * $intItemPrice;
      $intSum = $intSumVAT / (1 + $intVATPercent / 100);
      $intVAT = $intSumVAT - $intSum;
    }
    else
    {
      $intSum = $intItems * $intItemPrice;
      $intVAT = $intSum * ($intVATPercent / 100);
      $intSumVAT = $intSum + $intVAT;
    }

    $intTotSum += $intSum;
    $intTotVAT += $intVAT;
    $intTotSumVAT += $intSumVAT;
  }
?>
  <tr class="summary">
      <td class="input" colspan="9" align="right">
          <b><?php echo $GLOBALS['locTOTALEXCLUDINGVAT']?><br>
          <?php echo $GLOBALS['locTOTALVAT']?><br>
          <?php echo $GLOBALS['locTOTALINCLUDINGVAT']?></b>
      </td>
      <td class="input" colspan="2">
          <b>&nbsp;<?php echo miscRound2Decim($intTotSum)?><br>
          &nbsp;<?php echo miscRound2Decim($intTotVAT)?><br>
          &nbsp;<?php echo miscRound2Decim($intTotSumVAT)?></b>
      </td>
  </tr>
<?php
}

?>
</table>
</form>
</body>

</html>
