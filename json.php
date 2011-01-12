<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'form_funcs.php';
require_once 'localize.php';

sesVerifySession(FALSE);

$strFunc = getRequest('func', '');

switch ($strFunc)
{
case 'get_company':
  printJSONRecord('company');
  break;

case 'get_product':
  printJSONRecord('product');
  break;

case 'get_products':
  printJSONRecords('product', '', 'product_name');
  break;

case 'get_products':
  printJSONRecords('product', '', 'product_name');
  break;

case 'get_row_types':
  printJSONRecords('row_type', '', 'order_no');
  break;

case 'get_invoice':
  printJSONRecord('invoice');
  break;

case 'get_invoice_row':
  printJSONRecord('invoice_row');
  break;


case 'get_invoice_rows':
  printJSONRecords('invoice_row', 'invoice_id', 'order_no');
  break;

case 'put_invoice_row':
  saveJSONRecord('invoice_row', 'invoice_id');
  break;

case 'delete_invoice_row':
  deleteRecord('invoice_row');
  break;

case 'get_company_contacts':
  printJSONRecords('company_contact', 'company_id', 'contact_person');
  break;

case 'get_company_contact':
  printJSONRecord('company_contact');
  break;

case 'delete_company_contact':
  deleteRecord('company_contact');
  break;

case 'put_company_contact':
  saveJSONRecord('company_contact', 'company_id');
  break;

case 'get_invoice_defaults':
  $baseId = getRequest('base_id', 0);
  $invoiceId = getRequest('id', 0);
  if (getSetting('invoice_numbering_per_base') && $baseId)
    $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=? AND base_id=?', array($invoiceId, $baseId));
  else
    $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=?', array($invoiceId));
  $invNo = reset(mysql_fetch_row($res)) + 1;
  $refNo = $invNo . miscCalcCheckNo($invNo);
  $strDate = date("d.m.Y");
  $strDueDate = date("d.m.Y", mktime(0, 0, 0, date("m"), date("d")+getSetting('invoice_payment_days'), date("Y")));
  $arrData = array(
    'invoice_no' => $invNo, 
    'ref_no' => $refNo,
    'date' => $strDate,
    'due_date' => $strDueDate
  );
  header('Content-Type: application/json');
  echo json_encode($arrData);
  break;
  
default:
  header('HTTP/1.1 404 Not Found');
}

function printJSONRecord($table, $id = FALSE)
{
  if ($id === FALSE)
    $id = getRequest('id', '');
  if ($id) 
  {
    $res = mysql_param_query("SELECT * FROM {prefix}$table WHERE id=?", array($id));
    $row = mysql_fetch_assoc($res);
    header('Content-Type: application/json');
    echo json_encode($row);
  }
}

function printJSONRecords($table, $parentIdCol, $sort)
{
  $query = "SELECT * FROM {prefix}$table";
  $where = '';
  $params = array();
  $id = getRequest('parent_id', '');
  if ($id && $parentIdCol)
  {
    $where .= " WHERE $parentIdCol=?";
    $params[] = $id;
  }
  if (!getSetting('show_deleted_records'))
  {
    if ($where)
      $where .= " AND deleted=0";
    else
      $where = " WHERE deleted=0";
  }
  
  $query .= $where;
  if ($sort)
    $query .= " order by $sort";

  $res = mysql_param_query($query, $params);
  header('Content-Type: application/json');
  echo "{\"records\":[";
  $first = true;
  while ($row = mysql_fetch_assoc($res))
  {
    if ($first)
    {
      echo "\n";
      $first = false;
    }
    else
      echo ",\n";
    echo json_encode($row);
  }
  echo "\n]}";
}

function saveJSONRecord($table, $parentKeyName)
{
	$data = json_decode(file_get_contents('php://input'), true);
  if (!$data)
  {
    header('HTTP/1.1 400 Bad Request');
    return;
  }
  $strForm = $table;
  require 'form_switch.php';
  $id = isset($data['id']) ? $data['id'] : false;
  $new = $id ? false : true;
  unset($data['id']);
  $res = saveFormData("{prefix}$table", $id, $astrFormElements, $data, $parentKeyName, $parentKeyName ? $data[$parentKeyName] : FALSE);
  if ($res !== true)
  { 
    header('Content-Type: application/json');
    echo json_encode(array('missing_fields' => $res));
    return;
  }
  if ($new)
    header('HTTP/1.1 201 Created');
  printJSONRecord($table, $id);
}

function deleteRecord($table)
{
  $id = getRequest('id', '');
  if ($id)
  {
    $query = "UPDATE {prefix}$table SET deleted=1 WHERE id=?";
    mysql_param_query($query, array($id));
    header('Content-Type: application/json');
    echo json_encode(array('status' => 'ok'));
  }
}
