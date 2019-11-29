# Typo3 Edusharing Plugin

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



## Troubleshooting

### Invalid Host: `::1`

- Go the Edu-Sharing / *Admin-Tools* / *Applications* and edit your Typo3-plugin entry.
- Add a property:

    Key | Value
    --- | -----
    host_aliases | `::1`
