# Typo3 Edusharing Plugin

## Features (German)

### CKEditor-Plugin
Durch Klick auf den edu-sharing Toolbar-Button öffnet sich ein Dialog mit der Suchansicht des enstpr. Repositoriums.
Nach Auswahl eines Objektes werden die Details angezeigt und es können Optionen zur Anzeige des Objekts im CMS gesetzt werden:

* Bild, Video
    * Dimensionen
        * Höhe [px]
        * Breite [px]
    * Ausrichtung (für Bilder und Videos)
        * links umfließend
        * rechts umfließend
        * keine
    * Version
        * genau diese Version
        * immer die aktuellste Version
        
* Andere Formate
    * Version
        * genau diese Version
        * immer die aktuellste Version
        
* Gespeicherte Suche
    * Anzahl anzuzeigender Elemente
    * Sortierung
    * Anzeige
        * Liste
        * Kachel
    
Durch Klick auf Einfügen wird das Objekt in den WYSIWYG-Editor eingefügt.
Beim Speichern des Inhalts wird ein lokales Datenbankobjekt angelegt und ein Usage im Repositorium gesetzt.
Beim Entfernen wird beides gelöscht.

### Frontend-Filter
Wird im Frontend ein edu-sharing-Objekt erkannt, wird dieses per AJAX über das Repositorium bzw. den Renderingservice geladen und mit Lizenzinformationen und Metadaten angezeigt.


## Installation

1. Clone the repository as `edusharing` into the folder `public/typo3conf/ext` in your Typo3 installation.
    ```
    cd public/typo3conf/ext
    git clone ssh://git@scm.edu-sharing.com:2222/edu-sharing/plugin-typo3.git edusharing
    ```

2. In the root directory of your Typo3 installation:
    - Open the file `composer.json`  and add the following:
        ```
        "autoload": {
            "psr-4": {
                "Metaventis\\Edusharing\\": "public/typo3conf/ext/edusharing/Classes"
            }
        }
        ```
    - Run the following commands to reflect the changes:
        ```
        composer dumpautoload -o
        vendor/bin/typo3cms cache:flush
        ```

3. In the Typo3 web UI, go to *Extensions* and click *Activate* on the extension *Edusharing*.

4. Register the plugin with Edu-Sharing:
    - Got to the extension settings of the *Edusharing* extension:  
      **In Typo3 v8:** On the *Extensions* page, click the settings button on the row of the *Edusharing* extension.  
      **In Typo3 v9:** Got to *Settings* / *Configure Extensions* / *edusharing*
    - On the *Setup* tab, copy the URL of the application XML to your clipboard.
    - Then, in your Edusharing instance, go to *Admin-Tools* / *Applications*, paste the URL and click *Connect*.
    - Copy the URL of your Edusharing instance up to and including `/edu-sharing/`.
    - Go back to the extension settings of Typo3, paste the URL under *Repository URL*, and click *Setup repository*.

5. TODO
    > Das Stylesheet für gespeicherte Suchen muss für das Frontend eingebunden werden. Z. B. @import "[../]*typo3conf/ext/edusharing/Resources/Public/css/savedsearch.css";

## Troubleshooting

### Invalid Host: `::1`

- Go the Edu-Sharing / *Admin-Tools* / *Applications* and edit your Typo3-plugin entry.
- Add a property:

    Key | Value
    --- | -----
    host_aliases | `::1`
