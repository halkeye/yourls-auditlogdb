<?php
/*
Plugin Name: AuditLog DB
Plugin URI: https://github.com/halkeye/yourls-auditlogdb
Description: Logs actions to db
Version: 1.0.0
Author: Gavin "halkeye" Mogan
Author URI: https://www.halkeye.net
*/


// No direct call
if (!defined('YOURLS_ABSPATH')) die();

if (!defined('AUDITLOGDB_DB_TABLE_LOG'))
  define('AUDITLOGDB_DB_TABLE_LOG', YOURLS_DB_PREFIX . 'audit_log');

if (yourls_is_admin()) require_once 'admin_page.php';

// Register Logging Actions.
yourls_add_action('insert_link',   'auditlogdb_insert_link');
yourls_add_action('delete_link',   'auditlogdb_delete_link');
yourls_add_action('pre_edit_link', 'auditlogdb_edit_link');

yourls_add_action('activated_plugin', 'auditlogdb_activated_plugin');
yourls_add_action('deactivated_plugin', 'auditlogdb_deactivated_plugin');

yourls_add_action('add_option', 'auditlogdb_add_option');
yourls_add_action('delete_option', 'auditlogdb_delete_option');

function _auditlogdb_insert_action($action, $old_data = array(), $new_data = array())
{
  $timestamp = date('Y-m-d H:i:s');

  $ydb = yourls_get_db();

  $binds = array(
    'actor' => constant('YOURLS_USER'),
    'timestamp' => $timestamp,
    'action' => $action,
    'old_data' => json_encode($old_data),
    'new_data' => json_encode($new_data),
  );

  $ydb->fetchAffected("INSERT INTO `" . AUDITLOGDB_DB_TABLE_LOG . "` (`timestamp`, `actor`, `action`, `old_data`, `new_data`) VALUES(:timestamp, :actor, :action, :old_data, :new_data);", $binds);
}

function auditlogdb_insert_link()
{

  $args = func_get_args();
  $args = $args[0];

  $insert  = $args[0];
  $url     = $args[1];
  $keyword = $args[2];

  if ($insert) {
    _auditlogdb_insert_action(
      'insert_link',
      array(),
      array('keyword' => $keyword, 'url' => $url)
    );
  }
}


function auditlogdb_delete_link()
{

  $args = func_get_args();
  $args = $args[0];

  $keyword = $args[0];

  _auditlogdb_insert_action(
    'delete_link',
    array('keyword' => $keyword, 'url' => ''),
    array()
  );
}


function auditlogdb_edit_link()
{

  $args = func_get_args();
  $args = $args[0];

  $url                   = $args[0];
  $keyword               = $args[1];
  $newkeyword            = $args[2];
  $new_url_already_there = $args[3];
  $keyword_is_ok         = $args[4];

  _auditlogdb_insert_action(
    'edit_link',
    array('url' => $url, 'keyword' => $keyword),
    array('url' => $url, 'keyword' => $newkeyword, 'new_url_already_there' => $new_url_already_there, 'keyword_is_ok' => $keyword_is_ok)
  );
}


function auditlogdb_activated_plugin($plugin)
{
  // $args = func_get_args();
  // $args = $args[0];
  // 
  // $plugin = $args[0];
  var_dump($plugin);

  $ydb = yourls_get_db();
  $ydb->perform('CREATE TABLE IF NOT EXISTS `' . AUDITLOGDB_DB_TABLE_LOG . '` (' .
    '`timestamp` timestamp NOT NULL DEFAULT current_timestamp(),' .
    '`actor` varchar(100) NOT NULL,' .
    '`action` varchar(100) NOT NULL,' .
    '`old_data` JSON,' .
    '`new_data` JSON,' .
    'KEY `actor` (`actor`),' .
    'KEY `action` (`action`),' .
    'KEY `timestamp` (`timestamp`)' .
    ') DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;');

  _auditlogdb_insert_action(
    'activate_plugin',
    array(),
    array('plugin' => $plugin)
  );
}


function auditlogdb_deactivated_plugin()
{
  $args = func_get_args();
  $args = $args[0];

  $plugin = $args[0];

  _auditlogdb_insert_action(
    'activate_plugin',
    array('plugin' => $plugin),
    array()
  );
}


function auditlogdb_add_option()
{

  $args = func_get_args();
  $args = $args[0];

  $new_data = array();
  $new_data[$args[0]] = $args[1];

  _auditlogdb_insert_action(
    'add_option',
    null,
    $new_data,
  );
}


function auditlogdb_update_option()
{

  $args = func_get_args();
  $args = $args[0];

  $old_data = array();
  $old_data[$args[0]] = $args[1];

  $new_data = array();
  $new_data[$args[0]] = $args[2];

  _auditlogdb_insert_action(
    'update_option',
    null,
    $new_data,
  );
}


function auditlogdb_delete_option()
{

  $args = func_get_args();
  $args = $args[0];

  $new_data = array();
  $new_data[$args[0]] = '';

  _auditlogdb_insert_action(
    'delete_option',
    null,
    $new_data,
  );
}
