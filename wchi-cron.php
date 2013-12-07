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

  wchi_transaction_begin();

  if (get_option('wpchi-cron-overthrow') == false) {
    # For the first import, set the old value to an empty array
    # so all entries are imported
    $old_value = array();
  }

  $to_remove = calculate_diff($old_value, $new_value);
  $to_add = calculate_diff($new_value, $old_value);

  var_dump($to_remove, $to_add);

  wchi_transaction_end();

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

function calculate_rows($value)
{
  $rows = array();
  foreach ($value as $timestamp => $jobs) {
    $rows = array_merge($rows, calculate_rows_for_time($timestamp, $jobs));
  }

  return $rows;
}

function calculate_rows_for_time($timestamp, $jobs)
{
  $rows= array();
  foreach ($jobs as $name => $job) {
    $rows[] = create_row_for_time_name($timestamp, $name, $job);
  }
}

function create_row_for_time_name($timestamp, $name, $job)
{
    return array(
      'timestamp' => $timestamp,
      'name' => $name,
      'job' => maybe_serialize($job)
    );

}

function calculate_diff($left_value, $right_value)
{
  $not_equal = array();
  foreach ($left_value as $timestamp => $contents) {
    foreach ($contents as $name => $job) {
      if (!isset($right_value[$timestamp][$name])) {
        $not_equal[] = create_row_for_time_name($timestamp, $name, $job);
      } else if ($right_value[$timestamp][$name] !== $job) {
        $not_equal[] = create_row_for_time_name($timestamp, $name, $job);
      }
    }
  }

  return $not_equal;
}

function wchi_transaction_begin()
{
  global $wpdb;
  $wpdb->query('START TRANSACTION;');
}

function wchi_transaction_end()
{
  global $wpdb;
  $wpdb->query('COMMIT;');
}

