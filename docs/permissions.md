# Permissions documentation

Permissions are set in config, under "permissions", and then keyed by a number of categories. Permissions rules consist of a key, consisting of one or two strings, which may be wildcards. A key of just "*" will match all requests in that category.

Keys are matched ascending order of specificity, and their rules are applied in order after that. Later keys take precedence.

1. Initially, a permissions request is assumed to be denied
2. `*` rules
3. `*/string` type rules
4. `string/*` type rules

Each key contains an array of rules beginning with either "allow" or "deny" followed by either: "all" to apply to all users, overriding any preceding rules, or by either "user" or "group" followed by a comma-delimited list of the users or groups to be applied to.

For example, if you wanted to make permissions to deny all verbs, except to the group root, but also allow the "edit" verb to the groups "editor" and "webmaster" and allow "page/dump" permissions only to the user "example@system", you would set the following configuration:

```yaml
permissions:
  url:
    '*':
      - deny all
      - allow group root
    '*/edit':
      - allow group editor, webmaster
    'page/dump':
      - allow user example@system
```

## Built-in configuration categories

### url

Controls access permissions by noun/verb. The left side of keys is the type of a requested object, or the noun of the URL if an object isn't used for the requested page. The right side is matched against the verb of the current URL.

### add

Controls what types can be added under what other types. By default all are allowed, but a parent/child pair can be used to provide more specific rules than what's possible in just the url permissions.

For example, adding the following config would prevent adding anything under 'version' type pages:

```yaml
permissions:
  add:
    'version/*':
      - deny all
```

### filters

Controls which output filters users/groups are allowed to use when adding/editing content. The useful patterns are:

* `preset/(name)` allow or deny a specific preset, by default preset/default is set to allow all
* `unsafe` allow or deny all presets with names ending in `-unsafe` -- absolutely no users can use `-unsafe` filters unless this permission is granted to them.
