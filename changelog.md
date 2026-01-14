# 1.2.3 (14/01/2026)
Correction d'un bug mineur sur remi_unique_id
Nettoyage de code
Création de la commande "event_set_param" permettant de modifier un event (reveil/alarm): voir README

# 1.2.0 (13/01/2026)
Création des commandes:
- music_list (info / other) : Liste des musiques sur le stockage du REMI.
- play_selectmusic (action / liste) : Joue une musique listée 
- event_list (info / other) : Liste des reveils programmés sur REMI (HH:MM - Nom: 0=disable,1=enable)
- noise_notification_subscribers (info / numeric) : Nombre d'appareils abonné aux notifications en cas de bruit détecté.
- event_enable (action / list) : Active un reveil sélectionné dans la liste
- event_disable (action / list) : Désactive un reveil sélectionné dans la liste
- set_face (action / list) : Appliquer un visage sélectionné dans la liste
- gettime_wayback_max_s (info / numeric) : Si remonté par l'API, délai max avant récupération de l'heure
- call_wayback_max_s (info / numeric) : Si remonté par l'API, délai max avant reconnexion
- gettime_shift_s (info / numeric) : Si remonté par l'API, décalage de temps
- day_reconnection_count (info / numeric) : Si remonté par l'API, nombre de reconnexions du jour
- day_disconnection_time (info / numeric) : Si remonté par l'API, nombre de déconnexions du jour
- alarm_XXXXX (info / binary) : Il s'agit des events/reveils paramétrés. 0=disable, 1=enable

# 1.1.8 (12/01/2026)
Correction d'un bug impectant la lecture et arret des musiques.

# 1.1.7 (17/12/2025)
light_min et set_light_min sont désormais en % (0-100) avec un pas de 10 (0,10,20,30,...).
La valeure saisie est arrondie pour être transmis à l'api.

# 1.1.6 (17/12/2025)
Ajout de la récupération du paramètre light_min: Luminosité de l'écran de REMI dans le noir (0-10)
Ajout du réglage du paramètre light_min: Régler la luminosité de l'écran de REMI dans le noir (0-10)

# 1.1.5 (17/12/2025)
Exclusion de la sauvegarde du dossier python_venv

# 1.1.4 (15/12/2025)
Amélioration: Gestion des erreurs de l'API UrbanHello
En cas de difficulté de communication avec l'API d'UrbanHello, 
l'erreur est capturée et mise en log debug.X

# 1.1.3 (11/12/2025)
Correction: L'équipement était renommé à chaque synchro.
Il peut désormais être renommé sans que ce soit écrasé.

# 1.1.2 (11/12/2025)
Correction: mauvais format de données sur certains nouveaux champs.
Modification de l'affichage des équipements sur la page du plugin.
Commandes crées dans un ordre précis.
Affichage des commandes/infos dans l'ordre.

# 1.1 (11/12/2025)
Ajout des commandes:
- background_color
- Remi_unique_ID
- firmware_need_update
- firmware_version

Modification du comportement à la création
- REMI est crée avec sa couleur paramétrée

# 1.0 (05/12/2025)
Création du plugin et des commandes