# Sentry Loader für den JTL Shop

> **Hinweis:** Dieses Plugin ist nur für Entwickler gedacht und sollte nur in einer Testumgebung verwendet werden. Im Produktivbetrieb sollte das Plugin nicht verwendet sein, da das Plugin nicht vollständig getestet wurde.

Dieses Plugin lädt den Sentry SDK in den JTL Shop. Der Sentry SDK ist ein Open Source Projekt, welches es ermöglicht, Fehler und Ausnahmen in der Anwendung zu erfassen und an Sentry zu übermitteln. Als Alternative zu diesem Plugin kann auch das Open-Source Projekt GlitchTip verwendet werden.

## Voraussetzungen

Folgende Voraussetzungen müssen erfüllt sein, um das Plugin zu verwenden:

- [JTL Shop](https://jtl-software.de) ab Version 5.0.0
- [Composer](https://getcomposer.org/)
- [PHP](https://php.net/) ab Version 8.1
- PHP-Funktionen `exec` und `chdir` müssen aktiviert sein
- Es muss eine [Sentry](https://sentry.io/)- oder [GlitchTip](https://glitchtip.com/)-Instanz mit DSN vorhanden sein

## Arbeitsweise

Das Plugin führt bei der Installation des Plugins ein `composer install` aus, um die Abhängigkeiten des Sentry SDK zu installieren. Anschließend wird das Sentry SDK in den Shop geladen und konfiguriert, indem es die `Sentry\init`-Funktion in die Datei `includes/globalinclude.php` einfügt. Wird die DSN in den Plugin-Einstellungen geändert, so
wird die Konfiguration des Sentry SDKs entsprechend in der Datei `includes/globalinclude.php` neu geschrieben. Bei der Deinstallation des Plugins wird das Sentry SDK mit der Implementation der Funktion `Sentry\init` wieder aus dem Shop entfernt.