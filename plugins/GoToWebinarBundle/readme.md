#Plugin GoToWebinar pour Automation

##Pré-requis
- PHP version 5.4 minimum (nécessaire pour utiliser les options de json_decode)
- avoir un compte chez GoToWebinar et créer un ou deux webinaires.
- avoir un compte développeur chez GoToWebinar
- avoir patché les deux fichiers de Mautic suivant (cf mon mail du 15.04 à Jérémie) : SubmissionModel.php et mautic-form-src.js.

##Installation
1. Copier le dossier "GoToWebinarBundle" dans /plugins/.
2. Vider le cache de Symfony
3. Dans le menu settings / plugins, cliquer sur le bouton "Install / Upgrade Plugins". Le module "GoToWebinar" doit apparaître.
4. Ouvrir le menu settings / configuration, section GoToWebinar et copier l'URL de callback pour oAuth.
5. Depuis le compte développeur chez GoToWebinar, créer une application et utiliser l'URL de callback obtenue en 4. Puis copier la clé ConsumerKey.
6. Dans settings / configuration / GoToWebinar, utiliser le ConsumerKey pour obtenir un token. Une fois l'authentification terminée, revenir dans la configuration et vérifier que le token et le OrganiserKey sont bien renseignés. Cliquer sur le bouton "Check API" pour vérifier la connexion.
7. Définir un cronjob (chaque 24h par exemple) de synchro Mautic <=> GTW : php app/console gotowebinar:sync

##Utilisation
###Création d'un formulaire
####Création du formulaire
Créer un formulaire autonome. Dans la liste des champs disponibles, il doit y avoir un nouveau champ : "Webinar list". Il ne nécessite pas de configuration particulière, à part un label.
Lorsqu'il est ajouté dans un formulaire, le premier webinaire à venir doit apparaître dans l'aperçu.

Note importante :
Les formulaires ATMT sont statiques. La liste des webinaires disponibles est donc figée. Si un nouveau webinaire est créé, il n'apparaîtra pas dans les formulaires déjà créés. Pour mettre à jour les options d'un champ webinar-list, retourner dans la configuration du formulaire et l'enregistrer.

####Action de formulaire
Il doit y avoir dans la liste des actions : "subscribe to the selected webinar(s)".
Elle est nécessaire pour que le lead courant soit inscrit au webinaire sélectionné dans le champ webinar-list.
Dès qu'elle est présente, le formulaire doit respecter plusieurs contraintes :
- au moins un champ webinar-list doit être présent, et il doit être obligatoire
- des champs email, prénom et nom doivent être présents et obligatoires
- ces 3 champs doivent être liés aux champs correspondant du lead

A chaque enregistrement d'un formulaire, ces conditions sont vérifiées.
En cas d'erreur, l'explication s'ajoute dans le titre du formulaire.

####Utilisation d'un formulaire

On suppose que le formulaire utilise à la fois le champ "webinar-list" et l'action "subscribe"
Lorsqu'un lead remplit puis valide le formulaire, le plugin :
- vérifie que l'API est joignable
- vérifie que le webinaire sélectionné existe toujours
- affiche une erreur dans le formulaire si nécessaire
- appelle l'API et inscrit l'email / nom / prénom du lead au webinaire choisi
- génère un "slug" qui identifie ce webinaire
- ajoute un événement de type "webinar subscription" dans la timeline du lead, avec le slug dans les détails de l'événement
De son côté, GoToWebinar envoie un email de confirmation d'inscription.

###Synchronisation avec GTW

La tâche principale d'une synchronisation est d'ajouter des écritures dans la timeline des leads qui ont participé à une session.
La synchro vérifie également les écritures liées aux inscriptions, en ajoutant ou en supprimant les écritures nécessaires.
Ainsi :
- si une personne annule son inscription, cela se répercute sur le lead.
- et si une personne s'inscrit par un autre moyen que le formulaire ATMT mais possède un email associé à un lead existant, cela se répercute également.
La synchro s'effectue grâce au cronjob défini ci-dessus (rubrique installation).
Elle peut être exécutée manuellement via : php app/console gotowebinar:sync --webinarKey=xxxxx, en précisant l'identifiant unique du webinar à synchroniser.
Si ce paramètre est absent, tous les webinaires connus sont synchronisés.
Astuce pour tester la synchro : ajouter ou supprimer manuellement des entrées dans la table "plugin_gotowebinar_events", puis s'assurer que la synchro rétablisse les bonnes valeurs.


###Filtres de listes
Dans les smart-list, 3 filtres sont disponibles : "webinaire : est inscrit à...", "webinaire : a participé à...", "webinaire : est inscrit mais n'a pas participé à..."

Exemples :
"est inscrit à [including] webi1, webi2" signifie : "est inscrit à au moins l'un des deux webinaire."

"est inscrit à [excluding] webi1, webi2" signifie : "n'est inscrit à aucun des deux webinaire."

"est inscrit à [including] ANY" signifie : "est inscrit à au moins un webinaire."

"est inscrit à [excluding] ANY" signifie : "n'est inscrit à aucun webinaire."

Remarque :

En environnement de dev, le filtrage des leads nécessite l'exécution d'une commande depuis la console :
`php app/console mautic:leadlists:update --env=dev --force`

###Décisions de formulaires
Le bloc existe mais ne fonctionne pas encore.
