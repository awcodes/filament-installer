# Filament Installer

Install globally with composer.

```bash
composer global require awcodes/filament-installer
```

Now you can run the `new` command to quickly set up a new Filament Project.

```bash
filament new `directory name`
```

## Options / Flags

* --dark (Default Filament to be dark mode enabled)
* --themed (Install custom theme scaffolding)
* --force (Forces install even if the directory already exists)
* --breezy (Installs Filament Breezy Plugin, for Authentication)
* --shield (Installs Filament Shield Plugin, for Authorization)
* --sentry (Installs Filament Sentry Plugin (combines Breezy, Shield and User Management Resources)

## License

Filament Installer is open-sourced software licensed under the [MIT license](LICENSE.md).
