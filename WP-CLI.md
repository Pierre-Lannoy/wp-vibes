Vibes is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set Vibes options, view past or current performance signals and much more, without using a web browser.

1. [Viewing perfomance signals](#viewing-performance-signals) - `wp vibes tail`
2. [Getting Vibes status](#getting-vibes-status) - `wp vibes status`
3. [Managing main settings](#managing-main-settings) - `wp vibes settings`
5. [Misc flags](#misc-flags)

## Viewing performance signals

Vibes lets you use command-line to view past and currents API calls. All is done via the `wp vibes tail [<count>] [--signal=<signal_type>] [--filter=<filter>] [--format=<format>] [--col=<columns>] [--theme=<theme>] [--yes]` command.

If you don't specify `<count>`, Vibes will launch an interactive monitoring session: it will display signals as soon as they occur on your site. To quit this session, hit `CTRL+C`.

If you specifiy a value for `<count>` between 1 to 60, Vibes will show you the *count* last signals catched on your site.

> Note the `tail` command needs shared memory support on your server, both for web server and command-line configuration. If it's not already the case, you must activate the ***shmop*** PHP module.

Whether it's an interactive session or viewing past signals, you can filter what is displayed as follows:

### Signal type

To display only signals having a specific type, use `--signal=<signal_type>` parameter. `<signal_type>` can be `all`, `navigation`, `webvital` or `resource`.

### Field filters

You can filter displayed events on fields too. To do it, use the `--filter=<filter>` parameter. `<filter>` is a json string containing "field":"regexp" pairs. The available fields are: 'site' and 'endpoint'.

Each regular expression must be surrounded by `/` like that: `"endpoint":"/\/blog\/(.*)/"` and the whole filter must start with `'{` and end with `}'` (see examples).

### Columns count

By default, Vibes will output each signal string on a 160 character basis. If you want to change it, use `--col=<columns>` where `<columns>` is an integer between 80 and 400.

### Colors scheme

To change the default color scheme to something more *eyes-saving*, use `--theme`.

If you prefer, you can even suppress all colorization with the standard `--no-color` flag.

### Examples

To see all "live" signals, type the following command:
```console
pierre@dev:~$ wp vibes tail
...
```

To see only past signals about blog posts, type the following command:
```console
pierre@dev:~$ wp vibes tail 20 --filter='{"endpoint":"/\/blog\/(.*)/"}'
...
```

## Getting Vibes status

To get detailed status and operation mode, use the `wp vibes status` command.

## Managing main settings

To toggle on/off main settings, use `wp vibes settings <enable|disable> <navigation-analytics|resource-analytics|auto-monitoring|smart-filter|metrics>`.

### Available settings

- `navigation-analytics`: if activated, Vibes will analyze navigation signals and Web Vitals.
- `resource-analytics`: if activated, Vibes will analyze resources signals.
- `auto-monitoring`: if activated, Vibes will silently start the features needed by live console.
- `smart-filter`: if activated, Vibes will not take into account the beacon that generate "noise" in signals.
- `metrics`: if activated, Vibes will collate metrics.

### Example

To disable smart filtering without confirmation prompt, type the following command:
```console
wp vibes settings disable smart-filter --yes
```

## Misc flags

For most commands, Vibes lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note Vibes sets exit code so you can use `$?` to write scripts.
> To know the meaning of Vibes exit codes, just use the command `wp vibes exitcode list`.