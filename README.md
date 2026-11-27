Tolgee Translation Provider
=============================

Provides [Tolgee](https://tolgee.io/) integration for Symfony Translation.

DSN example
-----------

For https:
```
DSN=tolgees://PROJECT_ID:API_KEY@default/FILTER_STATES
```

For http:
```
DSN=tolgee://PROJECT_ID:API_KEY@default/[FILTER_STATES]
```


where:
 - `PROJECT_ID` is your tolgee Project ID
 - `API_KEY` is your Tolgee API key
 - `[FILTER_STATES]` this argument is optional.<br>Filter translations with state, multiple states separated by comma.<br>Possible values: `UNTRANSLATED`, `TRANSLATED`, `REVIEWED`<br>[tolgee export API](https://tolgee.io/api#tag/Export/operation/export_1)

Empty translations
-----------

Tolgee does not store empty strings, an empty translation is untranslated. Untranslated keys are
read as empty string (`''`), so include `UNTRANSLATED` in the filter states to deliver empty translations:

```
DSN=tolgees://PROJECT_ID:API_KEY@default/TRANSLATED,REVIEWED,UNTRANSLATED
```

Keep in mind that this applies to every untranslated key in every language: they no longer fall back
to another locale but are shown empty.


You get the project ID from the project URL.

[Generate an API key on Tolgee](https://app.tolgee.io/account/apiKeys)

In-context translation
-----------

`Netlogix\SymfonyTolgeeTranslationProvider\InContext` contains the framework independent parts for
[Tolgee in-context translation](https://docs.tolgee.io): rendered translations get their Tolgee key
appended as invisible characters, which the invisible observer of `@tolgee/web` (`fullKeyEncode: true`) reads.

- `InvisibleWrapper` encodes key and namespace in the format of `@tolgee/web` 7.3 and removes such markers again.
- `TranslationMarker` marks a translation while the in-context mode is active. The application decides when it is
  active by implementing `InContextState`.
- `DomainNamespaceKeyMapper` maps translation domains to Tolgee namespaces the way `TolgeeProvider` stores them.
  Implement `KeyMapper` for other key formats.

The package does not register services for these classes. The integration into an application (intercepting
translations, activation, bypassing caches, removing markers from mails, loading the Tolgee SDK in the browser)
is up to the application.

DevSetup
-----------

If you want to develop this package, you can use [gitpod.io](https://gitpod.io/) to start a development environment with all dependencies installed.

It also starts a local Tolgee server with a test project.
http://localhost:8085

You can use the following commands to pull and push translations from/to Tolgee server.

```bash
tests/console translation:pull
tests/console translation:push
```
