<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

T�m� ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

/********************************************************************
Includefile : htmlfuncs.php
    Functions to create various html elements

********************************************************************/    

function htmlPageStart($strTitle, $arrExtraScripts = null) {
/********************************************************************
Function : htmlPageStart
    create Html-pagestart

Args : 
    $strTitle (string): pages title
    
Return : $strHtmlStart (string): page startpart

Todo : This could be more generic...
********************************************************************/

    //These are to prevent browser & proxy caching
    // HTTP/1.1
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    // Date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    // always modified
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

    $charset = (_CHARSET_ == 'UTF-8') ? 'UTF-8' : 'ISO-8859-15';
    $strHtmlStart = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=$charset">
  <title>$strTitle</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" type="text/css" href="jquery/css/smoothness/jquery-ui-1.8.6.custom.css">
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <script type="text/javascript" src="jquery/js/jquery-1.4.4.min.js"></script>
  <script type="text/javascript" src="jquery/js/jquery.json-2.2.min.js"></script>
  <script type="text/javascript" src="jquery/js/jquery-ui-1.8.6.custom.min.js"></script>
  <script type="text/javascript" src="jquery/js/jquery.ui.datepicker-fi.js"></script>
  <script type="text/javascript" src="datatables/media/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="js/functions.js"></script>

EOT;

    if (isset($arrExtraScripts))
    {
      foreach ($arrExtraScripts as $script)
      {
        $strHtmlStart .= "  <script type=\"text/javascript\" src=\"$script\"></script>\n";
      }
    }
    $strHtmlStart .= "</head>\n";

    return $strHtmlStart;
}

function htmlListBox( $strName, $astrValues, $astrOptions, $strSelected, $strStyle = "", $blnOnChange = FALSE, $blnShowEmpty = TRUE, $astrAdditionalAttributes = '') {
/********************************************************************
Function : htmlListBox
    Create Html-listbox

Args : 
    $strName (string): listbox name
    $astrValues (stringarray): listbox values
    $astrOptions (stringarray): listbox options
    $strSelected (string): selected value
    
Return : $strListBox (string) : listbox element

Todo : 
********************************************************************/
    $strOnChange = '';
    if( $blnOnChange ) {
        $strOnChange = "onchange='this.form.submit();'";
    }
    $strListBox = 
        "<select class=\"$strStyle\" id=\"$strName\" name=\"$strName\" $strOnChange $astrAdditionalAttributes>\n";
    if( $blnShowEmpty ) {
        $strListBox .= "<option value=\"\" selected> - </option>\n";
    }
    
    for( $i = 0; $i < count($astrValues); $i++ ) {
        if( $strSelected == $astrValues[$i] ) {
            $strSelect = "selected";
        }
        else {
            $strSelect = "";
        }
        $strListBox .= 
            "<option value=\"" . htmlspecialchars($astrValues[$i]) . "\" $strSelect>" .
            htmlspecialchars($astrOptions[$i]) . "</option>\n";
    }        
    $strListBox .= "</select>\n";

    return $strListBox;
}

function getSQLResult( $strQuery ) {
/********************************************************************
Function : getSQLResult
    Return sql-query results

Args : 
    $strQuery (string): query to execute
        
Return : $strResult (string) : 
            result string on success
            FALSE on error

Todo : style, Sorting? Allow only select query?
********************************************************************/
    $intRes = mysql_query_check( $strQuery );
    return reset(mysql_fetch_row($intRes));
}
function htmlSQLListBox( $strName, $strQuery, $strSelected, $strStyle = "", $intOnChange = 0, $astrAdditionalAttributes ) {
/********************************************************************
Function : htmlSQLListBox
    Create Html-listbox from results of given query

Args : 
    $strName (string): listbox name
    $strQuery (string): query to execute
    $strSelected (string): selected value
    
Return : $strListBox (string) : 
            listbox element on success
            FALSE on error

Todo : style, Sorting? 
********************************************************************/
    $astrValues = array();
    $astrOptions = array();
    $intRes = mysql_query_check( $strQuery );
    while ($row = mysql_fetch_row($intRes)) 
    {
        $astrValues[] = $row[0];
        $astrOptions[] = $row[1];
    }
    $strListBox = htmlListBox($strName, $astrValues, $astrOptions, $strSelected, $strStyle, $intOnChange, TRUE, $astrAdditionalAttributes);

    return $strListBox;
}

// Get the value for the specified option
function getSQLListBoxSelectedValue( $strQuery, $strSelected ) 
{
    $intRes = mysql_query_check( $strQuery );
    while ($row = mysql_fetch_row($intRes)) 
    {
        if ($row[0] == $strSelected)
          return $row[1];
    }
    return '';
}

/********************************************************************
Function : htmlFormElement
    Create html formelements

Args : 
    $strName (string): element name
    $strType (string): element type
    $strValue (string): element value
    
Return : $strFormElement : html formelement

Todo : 
    Check values. Errors. Style?
********************************************************************/
function htmlFormElement( $strName, $strType, $strValue, $strStyle, $strListQuery, $strMode = "MODIFY", $strParentKey = NULL, $strTitle = "", $astrDefaults = array(), $astrAdditionalAttributes = '' ) {
    if ($astrAdditionalAttributes)
      $astrAdditionalAttributes = " $astrAdditionalAttributes";
    $strFormElement = '';
    switch( $strType ) {
        case 'TEXT' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                  "<input type=\"text\" class=\"$strStyle\" " .
                  "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\"$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = $strValue;
            }
            else {
                $strFormElement = htmlspecialchars($strValue) . "\n";
            }
        break;
        case 'TITLEDTEXT' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                  "<input type=\"text\" class=\"$strStyle\" " .
                  "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\"$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = $strValue;
            }
            else {
                $strFormElement = '<span title="' . $strTitle . '">' . 
                  htmlspecialchars($strValue) . "</span>\n";
            }
        break;
        case 'PASSWD' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                  "<input type=\"password\" class=\"$strStyle\" " .
                  "id=\"$strName\" name=\"$strName\" value=\"\"$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = "********\n";
            }
            else {
                $strFormElement = "********\n";
            }
        break;
        case 'CHECK' :
            if( $strMode == "MODIFY" ) {
                $strValue = $strValue ? 'checked' : '';
                $strFormElement = 
                  "<input type=\"checkbox\" id=\"$strName\" name=\"$strName\" value=\"1\" " . htmlspecialchars($strValue) . "$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strValue = $strValue ? "X" : "";
                $strFormElement = $strValue;
            }
            else {
                $strValue = $strValue ? $GLOBALS['locYES'] : $GLOBALS['locNO'];
                $strFormElement = $strValue . "\n";
            }
        break;
        case 'RADIO' :
            if( $strMode == "MODIFY" ) {
                $strChecked = $strValue ? 'checked' : '';
                $strFormElement = 
                  "<input type=\"radio\" id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\"$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strValue = $strValue ? "X" : "";
                $strFormElement = $strValue;
            }
            else {
                $strValue = $strValue ? $GLOBALS['locYES'] : $GLOBALS['locNO'];
                $strFormElement = $strValue . "\n";
            }
        break;
        case 'INT' :
            $strValue = str_replace(".", ",", $strValue); // TODO: make this configurable
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                  "<input type=\"text\" class=\"$strStyle\" " .
                  "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\"$astrAdditionalAttributes>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = $strValue;
            }
            else {
                $strFormElement = htmlspecialchars($strValue) . "\n";
            }
        break;
        case 'INTDATE' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                "<input type=\"text\" class=\"$strStyle hasCalendar\" ".
                "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\"$astrAdditionalAttributes>\n";
                if( $strListQuery == "gif" ) {
                    $strExtension = "gif";
                }
                else {
                    $strExtension = "png";
                }
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = $strValue;
            }
            else {
                $strFormElement = htmlspecialchars($strValue) . "\n";
            }
        break;
        case 'HID_INT' :
            $strFormElement = 
                "<input type=\"hidden\" ".
                "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($strValue) . "\">\n";
        break;
        case 'AREA' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = 
                  "<textarea rows=\"24\" cols=\"80\" class=\"" . $strStyle . "\" ".
                  "id=\"" . $strName . "\" name=\"" . $strName . "\"$astrAdditionalAttributes>" . $strValue . "</textarea>\n";
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = $strValue;
            }
            else {
                $strFormElement = nl2br(htmlspecialchars($strValue)) . "\n";
            }
            break;
        
        case 'RESULT' :
            $strListQuery = str_replace("_ID_", $strValue, $strListQuery);
            if( $strMode != "PDF" ) {
                $strFormElement = htmlspecialchars(getSQLResult( $strListQuery )) . "\n";
            }
            else {
                $strFormElement = getSQLResult( $strListQuery );
            }
            
        break;
        case 'LIST' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = htmlSQLListBox( $strName, $strListQuery, $strValue, $strStyle, 0, $astrAdditionalAttributes );
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = getSQLListBoxSelectedValue( $strListQuery, $strValue );
            }
            else {
                $strFormElement = htmlspecialchars(getSQLListBoxSelectedValue( $strListQuery, $strValue )) . "\n";
            }
        break;
        case 'SUBMITLIST' :
            if( $strMode == "MODIFY" ) {
                $strFormElement = htmlSQLListBox( $strName, $strListQuery, $strValue, $strStyle, 1, $astrAdditionalAttributes );
            }
            elseif( $strMode == "PDF" ) {
                $strFormElement = getSQLListBoxSelectedValue( $strListQuery, $strValue );
            }
            else {
                $strFormElement = htmlspecialchars(getSQLListBoxSelectedValue( $strListQuery, $strValue )) . "\n";                 
            }
        break;
        
        case 'BUTTON' :
            $strListQuery = str_replace("_ID_", $strValue, $strListQuery);
            switch( $strStyle ) {
                case 'tiny' :
                    $strHW = "height=1,width=1,";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'small' :
                    $strHW = "height=200,width=200,";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'medium' :
                    $strHW = "height=400,width=400,";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'large' :
                    $strHW = "height=600,width=600,";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'xlarge' :
                    $strHW = "height=800,width=650,";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'full' :
                    $strHW = "";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
                case 'custom' :
                case 'pdf' :
                    $strListQuery = str_replace("'","",$strListQuery);
                    $strHref = $strListQuery;
                    $strOnClick = "";
                break;
                case 'redirect':
                    $strHref = "#";
                    $strOnClick = "onclick=\"var form = document.getElementById('admin_form'); form.saveact.value=1; form.redirect.value='$strName'; form.submit(); return false;\"";
                break;
                default :
                    $strHW = "";
                    $strHref = "#";
                    $strOnClick = "onclick=\"window.open(".
                    $strListQuery .",'". $strHW. "menubar=no,scrollbars=no,".
                    "status=no,toolbar=no'); return false;\"";
                break;
            }
            $strFormElement = 
                "<a class=\"formbuttonlink\" href=\"$strHref\" $strOnClick$astrAdditionalAttributes>" . htmlspecialchars($strTitle) . "</a>\n";
        break;
        case 'JSBUTTON' :
            if( $strValue ) 
            {
              $strListQuery = str_replace("_ID_", $strValue, $strListQuery);
              $strOnClick = "onClick=\"$strListQuery\"";
              $strFormElement = 
                "<a class=\"formbuttonlink\" href=\"#\" $strOnClick$astrAdditionalAttributes>" . htmlspecialchars($strTitle) . "</a>\n";
            }
            else 
            {
              $strFormElement = $GLOBALS['locSAVEFIRST'];
            }
        break;
        case 'IMAGE' :
            $strListQuery = str_replace("_ID_", $strValue, $strListQuery);
            $strFormElement = "<img class=\"$strStyle\" src=\"$strListQuery\" title=\"" . htmlspecialchars($strTitle) . "\"></div>\n";
        break;
        
    }

    return $strFormElement;
}
?>
