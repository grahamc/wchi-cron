<?php
/*
 * Plugin Name: WChi Cron
 * Plugin URI: https://github.com/grahamc/wchi-cron
 * Version: 0.1.0
 * Author: Graham Christensen
 * Description: Make cron more efficient.
 */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
register_activation_hook( __FILE__, 'wchi_install');
add_filter('pre_update_option_cron', 'wchi_cron_write', 99, 2);
add_filter('pre_option_cron', 'wchi_cron_read', 99, 0);

function wchi_table_name()
{
  global $wpdb;
  return $wpdb->prefix . 'wchi_cron';
}

function wchi_install() {
  global $wpdb;

  $table_name = wchi_table_name();
  $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  time int NOT NULL,
  name varchar(255) NOT NULL,
  job text NOT NULL,
  UNIQUE KEY id (id),
  UNIQUE KEY cron_time_name(time, name)
  );";

  dbDelta( $sql );

  add_option( "wchi_table_version", 1 );
}


function wchi_cron_write($value, $old_value)
{

  wchi_transaction_begin();

  if (isset($value['version'])) {
    update_option('wchi-cron-version', $value['version']);
    unset($value['version']);
  }

  if (get_option('wchi-cron-overthrow') == false) {
    # For the first import, set the old value to an empty array
    # so all entries are imported
    $old_value = array();
  }

  $to_remove = calculate_diff($old_value, $value);
  $to_add = calculate_diff($value, $old_value);

  foreach($to_remove as $job) {
    wchi_remove_job($job);
  }

  foreach($to_add as $job) {
    wchi_insert_job($job);
  }


  update_option('wchi-cron-overthrow', true);
  wchi_transaction_end();

}

function wchi_cron_read($null)
{
  global $wpdb;
  if (get_option('wchi-cron-overthrow') != '1') {
    return false;
  }

  $return = array();
  if (get_option('wchi-cron-version', false) !== false) {
    $return['version'] = get_option('wchi-cron-version');
  }

  $jobs = $wpdb->get_results('SELECT time, name, job FROM ' . wchi_table_name());

  foreach ($jobs as $job) {
    $time = $job->time;
    $name = $job->name;
    $job_content = maybe_unserialize($job->job);

    if (!isset($return[$time])) {
      $return[$time] = array();
    }
    $return[$time][$name] = $job_content;
  }

  var_dump($return);

  die('wat');
}

function wchi_remove_job($job)
{
  global $wpdb;

  $wpdb->delete(
    wchi_table_name(),
    array(
      'time' => $job['timestamp'],
      'name' => $job['name'],
    )
  );
}

function wchi_insert_job($job)
{
  global $wpdb;

  $wpdb->insert(
    wchi_table_name(),
    array(
      'time' => $job['timestamp'],
      'name' => $job['name'],
      'job' => $job['job'],
    ),
    array('%s', '%s', '%s')
  );
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
  $not_in_right  = array();
  foreach ($left_value as $timestamp => $contents) {
    foreach ($contents as $name => $job) {
      if (!isset($right_value[$timestamp][$name])) {
        $not_in_right[] = create_row_for_time_name($timestamp, $name, $job);
      } else if ($right_value[$timestamp][$name] !== $job) {
        $not_in_right[] = create_row_for_time_name($timestamp, $name, $job);
      }
    }
  }

  return $not_in_right;
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

