# plateforme-safebase

## Base de données MySQL avec Docker

Le service Docker démarre MySQL 8.0 sur `127.0.0.1:3308`. Le port est limité à
la machine locale pour éviter d'exposer cette base sur le réseau. Les données
Docker sont conservées dans le volume `safebase_mysql_data`.

### Importer la base WAMP existante

Depuis PowerShell, exportez la base actuellement utilisée par WAMP vers le
fichier d'initialisation local (adaptez le chemin si votre version MySQL diffère) :

```powershell
& 'C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqldump.exe' -h 127.0.0.1 -P 3306 -u root --single-transaction --routines --events --triggers --databases safebase '--result-file=docker\mysql\initdb\safebase.sql'
```

Le dump WAMP MySQL 9.1 est compatible avec l'image MySQL 8.0 utilisée ici. Le
fichier SQL peut contenir des données privées ; il est donc ignoré par Git.
Démarrez ensuite le conteneur :

```powershell
docker compose up -d
docker compose ps
```

L'import est exécuté automatiquement uniquement lors de la première création
du volume de données. Les démarrages suivants réutilisent les données du volume
et n'écrasent pas la base. Si le volume existe déjà, le dump ne sera pas
réimporté.

### Connexion de l'application

`Core/Database.php` lit les variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`
et `DB_PASSWORD`. Par défaut, l'application se connecte à la base Docker sur
`127.0.0.1:3308` avec l'utilisateur `root` sans mot de passe, pour un usage
local uniquement. Si vous gardez MySQL WAMP sur le port 3306, configurez
`DB_PORT=3306` dans l'environnement du processus Apache et redémarrez WAMP.

Pour arrêter MySQL sans supprimer les données :

```powershell
docker compose stop
```
