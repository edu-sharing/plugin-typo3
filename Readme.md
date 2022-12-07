# Typo3 Edusharing Plugin

## Features (German)

### CKEditor-Plugin

Durch Klick auf den edu-sharing Toolbar-Button öffnet sich ein Dialog mit der Suchansicht des
enstpr. Repositoriums. Nach Auswahl eines Objektes werden die Details angezeigt und es können
Optionen zur Anzeige des Objekts im CMS gesetzt werden:

- Bild, Video
  - Dimensionen
    - Höhe [px]
    - Breite [px]
  - Ausrichtung (für Bilder und Videos)
    - links umfließend
    - rechts umfließend
    - keine
  - Version
    - genau diese Version
    - immer die aktuellste Version
- Andere Formate
  - Version
    - genau diese Version
    - immer die aktuellste Version
- Gespeicherte Suche
  - Anzahl anzuzeigender Elemente
  - Sortierung
  - Anzeige
    - Liste
    - Kachel

Durch Klick auf Einfügen wird das Objekt in den WYSIWYG-Editor eingefügt. Beim Speichern des Inhalts
wird ein lokales Datenbankobjekt angelegt und ein Usage im Repositorium gesetzt. Beim Entfernen wird
beides gelöscht.

### Frontend-Filter

Wird im Frontend ein edu-sharing-Objekt erkannt, wird dieses per AJAX über das Repositorium bzw. den
Renderingservice geladen und mit Lizenzinformationen und Metadaten angezeigt.

### Usage of Saved Searches

Usage of saved searches has some pre-requirements and restrictions:

1.  There has to be a guest user configured in edu-sharing.  
    This is needed for preview images and the ability to open search results in edu-sharing for page visitors.
2.  Saved searches have to be made public via the "invite" dialog.
3.  Only public elements will be listed in search results.

## Compatibility

Different Typo versions are supported by different branches of this plugin.

SOAP-based versions (compatible with Edu-Sharing up to 6.0):

- `typo3v8-soap`
- `typo3v9-soap`
- `typo3v10-soap`

REST-based versions (compatible with Edu-Sharing 7.0 and higher):

- `typo3v10`

Note that SOAP-based versions rely on third-party cookies and IFrames, which might stop working due
to stricter cookie-handling behavior in recent browsers.

## Preparation

Initialize the submodule containing Edu-Sharing-Library used by this plugin:
```sh
git submodule update --init
```

## Installation

This describes the installation of the plugin in an existing Typo3 instance. For a testing setup
that includes a Typo3 installation, see [Try Out Using Docker](#try-out-using-docker) below.

### Requirements

- A Typo3 instance
- An Edu-Sharing instance

### Installation Steps

1. Move or copy the contents of the `src` folder of this repository into the folder
   `public/typo3conf/ext/edusharing` in your Typo3 installation, e.g.
   ```
   cp -r ./src $TYPO3_ROOT/public/typo3conf/ext/edusharing
   ```

2. In the Typo3 web UI, go to **Extensions** and click **Activate** on the extension **Edusharing**.

3. Register the plugin with Edu-Sharing:

   - Reload the page
   - Go to **Settings** / **Configure Extensions** / **edusharing**
   - On the **Setup** tab, copy the URL of **Application XML** to your clipboard
   - Then, in your Edusharing instance, go to **Admin-Tools** / **Remote-systems**, paste the URL
     and click **Connect**
   - Copy the URL of your Edusharing instance up to and including `/edu-sharing/`
   - Go back to the extension settings of Typo3, paste the URL under **Repository URL**, and click
     **Setup repository**

4. Include the Typoscript page template (required when embedding saved searches):  
   In the Typo3 backend
   - navigate to **Template**
   - select your page in the page tree
   - select "Info / Modify" in the dropdown menu at the page top
   - click **Edit the whole template record**
   - go to the **Includes** tab
   - move the entry "Edusharing CSS (edusharing)" from **Available Items** to **Selected Items**
   - click **Save**

## Try Out Using Docker

We provide a `docker-compose` file for a fresh Typo3 installation containing the plugin for testing
and development purposes.

### Requirements

- Docker
- Docker-Compose

### Installation Steps

1. Create and configure an edu-sharing instance using
   [edu-sharing-dev-tools](https://scm.edu-sharing.com/edu-sharing/edu-sharing-dev-tools).
    - Setup Edu-Sharing's development environment:  
      ```sh
      git clone https://scm.edu-sharing.com/edu-sharing/edu-sharing-dev-tools.git
      cd edu-sharing-dev-tools
      cp .env.example .env
      $EDITOR .env  # Set `EDU_ROOT` to your preferred location
      ./edu.sh ce add maven/release/7.0 -t origin/maven/release/7.0
      ```
    - Set Edu-Sharing to be accessible from other hosts (needed for access from Typo's docker container)
      - Under the installation path (configured as `EDU_ROOT` above), go to `community/link/maven/fixes/7.0/deploy/deploy/docker`
      - Copy `.env.sample` to `.env` and set
        ```
        COMMON_BIND_HOST=0.0.0.0
        ```
    - Start Edu-Sharing
      - In the directory `deploy/deploy/docker` (see above), run
        ```
        ./deploy.sh rstart
        ```

2. In this plugin directory:

   - Run
     ```
     docker-compose up --build
     ```
   - Open http://localhost:8000/ in a browser and complete the Typo3 setup process:
     - (2) Select database:
       - Connection: \[MySQLi\] Manually configured MySQL TCP/IP connection
       - Username: "typo3"
       - Password: "typo3"
       - Host: "db"
       - Port: 3306
     - (3) Select a database:
       - Use an existing empty databse:
         - "typo3"
     - Freely choose admin credentials and starting setup in the next steps

3. Activate the Edusharing extension as described in step 2 in [Installation](#installation) above.

4. Configure the App URL so it is visible from Edu-Sharing's network:

   - Find The gateway of the network of `edu-sharing-community` by inspecting it, e.g.,
     ```
     docker network inspect community-docker-maven-fixes-7-0_default | grep Gateway
     ```
   - In the Typo3 backend, go to **Settings** / **Extension Configuration** / **edusharing** /
     **Extension**.
   - Under **App URL**, change "localhost" to the gateway address obtained above, e.g.,
     "http://172.19.0.1:8000/".
   - Save the changes and close the dialog.

5. Follow the remaining steps in [Installation](#installation).

### Tear Down

```
docker-compose down
```

**State is only persisted as long as the containers live.**

## Develop Setup

While the setup described in [Try Out Using Docker](#try-out-using-docker) above builds a Docker
image with the Edu-Sharing plugin from this repository, an additional dev setup is available, that

- mounts the plugin source code directly into the running Docker container,
- mirrors the container's public `html` directory into the host filesystem, and
- enables the `xdebug` debugger in Typo3's PHP installation

This allows us

- to develop the plugin with code changes directly being picked up by the Typo3 container, and
- to debug the plugin including Typo3 vendor code from the host.

You can switch back and forth between the develop and testing setup with existing containers, but
keep in mind that the non-develop container will not reflect any changes to the plugin source code
until it is destroyed.

### Start In Develop Mode

- Create the directory structure under `mnt` inside this repository:
  ```
  mkdir -p mnt/{html,typo3temp}
  ```
  The directories need to be completely empty when first starting the Docker containers in develop
  mode for the Docker image's files to be mirrored as intended.
- Start the containers with the following command:
  ```
  docker-compose -f docker-compose.yml -f docker-compose.dev.yml up --build
  ```

### Debug

The server will try to connect to `xdebug` on port 9003 on the host system. You can use the
configuration provided for VSCode (`.vscode/launch.json`) using the extension [PHP
Debug](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug) or apply the
mapping to a debugger of your choice.


### Tear Down

```
docker-compose down -v
sudo rm -r mnt
```

## Troubleshooting

### Typo3 Logs

Typo3 creates log files within its own directory structure in `typo3temp/var/log`.

```sh
docker exec -ti plugin-typo3-typo3-1 bash -c 'tail -f typo3temp/var/log/*.log'
```

### PHP Error Logs

The container logs include access logs and error messages of Xdebug if it cannot connect to a debugger. To filter for relevant output use e.g.
```sh
# bash
docker logs -f plugin-typo3-typo3-1 > /dev/null 2> >(grep -v Xdebug >&2)
# fish
docker logs -f plugin-typo3-typo3-1 > /dev/null 2>| grep -v Xdebug
```

### Typo3 Backend Users Are Signed In As "guest" In Edu-Sharing

The host verification of Edu-Sharing might have failed. Check the `catalina.out` logfile.

#### Invalid Host: `::1`

- Go the Edu-Sharing / **Admin-Tools** / **Remote-systems** and edit your Typo3-plugin entry.
- Add a property:

  | Key          | Value |
  | ------------ | ----- |
  | host_aliases | `::1` |

#### Test Installations On Different Hosts

For testing purposes, you allow arbitrary hosts:

- Go the Edu-Sharing / **Admin-Tools** / **Remote-systems** and edit your Typo3-plugin entry.
- Modify the property

  | Key  | Value |
  | ---- | ----- |
  | host | `*`   |
