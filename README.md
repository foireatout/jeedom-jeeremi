# Plugin JeeRemi pour Jeedom

## Description

Le plugin **JeeRemi** permet d'intégrer et de contrôler les réveils connectés **REMI** de la marque **UrbanHello** directement depuis votre installation Jeedom.
Avec ce plugin, vous pouvez :
- **Contrôler la luminosité** de la veilleuse.
- **Gérer le volume** du réveil.
- **Changer l'expression du visage** affiché (éveillé, endormi, semi-ouvert, blanc).
- **Lancer/arrêter la musique** stockée sur l'appareil.
- **Surveiller la température** de la pièce.
- **Vérifier l'état en ligne** du réveil.
- **Rafraîchir manuellement** les informations.

Le plugin utilise l'API officielle d'UrbanHello pour interagir avec vos appareils REMI.

---

## Installation

Une fois le plugin installé depuis le market Jeedom:
- Installez les dépendances via la configuration du plugin
- Renseignez vos identifiants UrbanHello et de cliquer sur "Synchroniser"
> Le plugin va se connecter à UrbanHello et créer un équipement par Réveil REMI qu'il trouvera sur ce compte.

Vos équipements se trouvent créer dans Plugins > Communication > JeeRemi et portent le nom du Réveil.

---

## Fonctionnalités et commandes disponibles

### Informations/Actions disponibles (remontées automatiquement)
Les informations suivantes sont disponibles pour chaque équipement REMI :
- alive (info / binary) : Le REMI est-il actif sur l'API
- online (info / binary) : Le REMI est-il actif
- last_update (info / other) : Date-Heure de la dernière mise à jour sur Jeedom
- face (info / other) : Visage actif sur le REMI.
- IP (info / other) : Adresse IP de l'équipement.
- RSSI (info / other) : Signal WiFi de l'équipement (en dB).
- Veilleuse (info / numeric) : Niveau de la veilleuse (0-100 %)
- Volume (info / numeric) : Niveau du volume (0-100 %)
- MusicMode (info / numeric) : Type de lecture de la music (0= normal, 1=boucle sur la chanson, 2=boucle la playlist)
- MusicPath (info / other) : Lecture en cours, chanson chargée.
- Nom (info / other) : Nom du REMI
- Remi_ID (info / other) : ID du REMI chez UrbanHello
- temperature (info / numeric) : temperature relevée par le REMI (en °C)
- Visage_num (info / numeric) : Numéro du visage affiché sur REMI (1=Ouverts,2=Fermés,3=SemiOuverts,4=blanc).
- play_music (action) : Attend le nom du fichier mp3 à lire sur le REMI puis lance la lecture.
- stop_music (action) : Arrête la lecture en cours.
- set_veilleuse (action) : Défini le niveau de la veilleuse (0-100).
- set_volume (action) : Défini le niveau de volume (0-100).
- awakeFace (action) : Défini "Visage Réveillé/Ouvert" comme visage affiché.
- sleepyFace (action) : Défini "Visage Endormi/Fermé" comme visage affiché.
- semiAwakeFace (action) : Défini "Visage SemiReveillé/SemiOuver" (cligne d'un oeil) comme visage affiché.
- blankFace (action) : Défini un "Visage vide" comme visage affiché.
