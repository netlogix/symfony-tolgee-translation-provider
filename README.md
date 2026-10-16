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
 - `[FILTER_STATES]` this argument is optional.<br>Filter translations with state.<br>Possible values: `UNTRANSLATED`, `TRANSLATED`, `REVIEWED`<br>[tolgee export API](https://tolgee.io/api#tag/Export/operation/export_1)

Empty translations
-----------

Tolgee treats an empty translation as untranslated and does not export it, so an empty string
can not be delivered by default. Keys tagged with the tag given in the optional `emptyTag` DSN option
are read as empty string (`''`) in every language, regardless of their text in Tolgee:

```
DSN=tolgees://PROJECT_ID:API_KEY@default?emptyTag=empty
```

The tag name has to be URL encoded, e.g. `?emptyTag=hide%20in%20shop`, and must not contain a comma,
because Tolgee splits list filters at commas.

Tagged keys are read as empty string regardless of their state, so `[FILTER_STATES]` does not apply to them.
The tagged keys are fetched with a second export request, so every pull with `emptyTag` makes two requests.

**Caution with `translation:push --force`:** after a pull, the local files contain `''` for tagged keys.
A forced push sends these empty values back to Tolgee and can overwrite the text the keys have there.
Push without `--force` or remove the tagged keys from the local files before pushing.


You get the project ID from the project URL.

[Generate an API key on Tolgee](https://app.tolgee.io/account/apiKeys)

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
