# Installation
## Environnement nécessaire
Symfony 6.4.*

PHP 8.2.*

MySql 8

## Suivre les étapes suivantes :

**Etape 1.1 :** Cloner le repository suivant depuis votre terminal :
```
  git clone https://github.com/KarolZ13/P7-OCR
```
**Etape 1.2 :** Executer la commande suivante :
  composer install

**Etape 2 :** Editer le fichier .env
- pour renseigner vos paramètres de connexion à votre base de donnée dans la variable DATABASE_URL

**Etape 3 :** Démarrer votre environnement local (Par exemple : Xampp)

**Etape 4 :** Exécuter les commandes symfony suivantes depuis votre terminal
```
    symfony console doctrine:database:create (ou php bin/console d:d:c si vous n'avez pas installé le client symfony)
    symfony console doctrine:schema:update
    symfony console doctrine:fictures:load  
```
**Etape 5.1 :** Générer vos clés pour l'utilisation de JWT Token
```
    $ mkdir -p config/jwt
    $ openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
    $ openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```
**Etape 5.2 :** Renseigner vos paramètres de configuration dans votre ficher .env
```
    ###> lexik/jwt-authentication-bundle ###
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=VotrePassePhrase
    ###< lexik/jwt-authentication-bundle ###
```
**Etape 6:** Lancer le projet symfony
```
symfony server:start -d
```

**Etape 6.1:** Tester l'API avec la documentation comme support
```
http://127.0.0.1:8000/api/doc
```
Selon votre environnement local
```
http://localhost/api/doc
```
