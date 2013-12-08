# wchi-cron
Respect the Ch'i of your database...

## The Problem

Wordpress by default stores `cron` in the `wp_options` table. Here is an
example:

```
a:3:{i:1386469409;a:4:{s:16:"wp_version_check";a:1:{s:32:"40cd750bba9870f18aada2478b24840a";a:3:{s:8:"schedule";s:10:"twicedaily";s:4:"args";a:0:{}s:8:"interval";i:43200;}}s:17:"wp_update_plugins";a:1:{s:32:"40cd750bba9870f18aada2478b24840a";a:3:{s:8:"schedule";s:10:"twicedaily";s:4:"args";a:0:{}s:8:"interval";i:43200;}}s:16:"wp_update_themes";a:1:{s:32:"40cd750bba9870f18aada2478b24840a";a:3:{s:8:"schedule";s:10:"twicedaily";s:4:"args";a:0:{}s:8:"interval";i:43200;}}s:19:"wp_scheduled_delete";a:1:{s:32:"40cd750bba9870f18aada2478b24840a";a:3:{s:8:"schedule";s:5:"daily";s:4:"args";a:0:{}s:8:"interval";i:86400;}}}i:1386507600;a:1:{s:20:"wp_maybe_auto_update";a:1:{s:32:"40cd750bba9870f18aada2478b24840a";a:3:{s:8:"schedule";s:10:"twicedaily";s:4:"args";a:0:{}s:8:"interval";i:43200;}}}s:7:"version";i:2;}
```

That is a serialized version of the following:

```
array (
  1386469409 =>
  array (
    'wp_version_check' =>
    array (
      '40cd750bba9870f18aada2478b24840a' =>
      array (
        'schedule' => 'twicedaily',
        'args' =>
        array (
        ),
        'interval' => 43200,
      ),
    ),
    'wp_update_plugins' =>
    array (
      '40cd750bba9870f18aada2478b24840a' =>
      array (
        'schedule' => 'twicedaily',
        'args' =>
        array (
        ),
        'interval' => 43200,
      ),
    ),
    'wp_update_themes' =>
    array (
      '40cd750bba9870f18aada2478b24840a' =>
      array (
        'schedule' => 'twicedaily',
        'args' =>
        array (
        ),
        'interval' => 43200,
      ),
    ),
    'wp_scheduled_delete' =>
    array (
      '40cd750bba9870f18aada2478b24840a' =>
      array (
        'schedule' => 'daily',
        'args' =>
        array (
        ),
        'interval' => 86400,
      ),
    ),
  ),
  1386507600 =>
  array (
    'wp_maybe_auto_update' =>
    array (
      '40cd750bba9870f18aada2478b24840a' =>
      array (
        'schedule' => 'twicedaily',
        'args' =>
        array (
        ),
        'interval' => 43200,
      ),
    ),
  ),
  'version' => 2,
)
```

Every time any one of the cron jobs gets updated, the entire serialized form
gets written out. If there is a bug which causes one of them to get updated on a
highly regular basis, it can quickly add up and write gigabytes of data to a
mysql binary log which is used for replication. This is especially easy and true
on a high traffic website.

## The Solution

`wchi-cron` breaks out the storage of this data. Each job is stored in
the `PREFIX_wchi_cron` table: (timestamp, name, job-data). Each time a job is
added or removed, the new values are interpreted and compared against the
previous version. The differences are then inserted or deleted from the table as
needed. This can reduce the churn on the table to a fraction of what it would if
it had to write the entire table.

### Details

To prevent from breaking the world... wchi-cron is a drop-in enhancement.

When reading the cron from the cron table, it reconstructs the array exactly as
if it had been serialized.

On storing the cron, it deconstructs it and calculates the changes required to
the table.

To maintain data integrity, the writes are performed in a transaction.

Upon removal of the plugin, it reconstructs the serialized version and stores
it in the wp_options table.


