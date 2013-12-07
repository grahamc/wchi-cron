<?php
/*
 * Plugin Name: WChi Cron
 * Plugin URI: https://github.com/grahamc/wchi-cron
 * Version: 0.1.0
 * Author: Graham Christensen
 * Description: Make cron more efficient.
 */

add_filter('pre_update_option_cron', 'wchi_cron_write', 99, 2);
add_filter('pre_option_cron', 'wchi_cron_read', 99);

function wchi_cron_write($value, $old_value)
{
  if (isset($value['version'])) {
    update_option('wchi-cron-version', $value['version']);
    unset($value['version']);
  }
  var_export($value);
  exit(1);

  update_option('wpchi-cron-overthrow', true);
}

function pre_option_cron()
{
  if (!get_option('wpchi-cron-overthrow')) {
    #return false;
  }
  $return = array();
  if (has_option('wpchi-cron-version')) {
    $return['version'] = get_option('wpchi-cron-version');
  }

  var_dump($return);

  die('wat');
}

