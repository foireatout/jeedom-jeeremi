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
