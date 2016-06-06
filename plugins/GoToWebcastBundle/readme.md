#Plugin GoToWebcast pour Automation

##Pré-requis
- avoir un compte admin chez GoToWebcast et créer un ou deux webcast.
- avoir des codes d'accès à l'API de GoToWebcast (username, password, à demander au support)
- avoir patché les deux fichiers de Mautic suivant (cf mon mail du 15.04 à Jérémie) : SubmissionModel.php et mautic-form-src.js.

## Création d'un webcast depuis l'admin web
Définir un "new event" de type "Live".
Attention, dans les champs requis lors de l'inscription, seuls les champs email, prénom et nom sont gérés par le plugin. Les autres champs ne sont pas remontées de ATMT vers GTW. Ils ne doivent donc pas être cochés comme "requis", sans quoi les inscriptions depuis un formulaire ATMT échoueraient.

##Installation
1. Copier le dossier "GoToWebcastBundle" dans /plugins/.
2. Vider le cache de Symfony
3. Dans le menu settings / plugins, cliquer sur le bouton "Install / Upgrade Plugins". Le module "GoToWebcast" doit apparaître.
4. Ouvrir le menu settings / configuration, section GoToWebcast et renseigner le username et le password de l'API.
5. Définir un cronjob de synchro Mautic <=> GTW : php app/console gotowebcast:sync
(pas plus d'un par heure car risquerait d'atteindre les quotas de l'API)

##Utilisation
###Création d'un formulaire
####Création du formulaire
Créer un formulaire autonome. Dans la liste des champs disponibles, il doit y avoir un nouveau champ : "Webcast-list". Il ne nécessite pas de configuration particulière, à part un label.
Lorsqu'il est ajouté dans un formulaire, le prochain webcast à venir doit apparaître dans l'aperçu.

Note importante :
Les formulaires ATMT sont statiques. La liste des webcasts disponibles est donc figée. Si un nouveau webcast est créé, il n'apparaîtra pas dans les formulaires déjà créés. Pour mettre à jour les options d'un champ webcast-list, retourner dans la configuration du formulaire et l'enregistrer.

####Action de formulaire
Il doit y avoir dans la liste des actions : "subscribe to the selected webcast(s)".
Elle est nécessaire pour que le lead courant soit inscrit au webcast sélectionné dans le champ webcast-list.
Dès qu'elle est présente, le formulaire doit respecter plusieurs contraintes :
- au moins un champ webcast-list doit être présent, et il doit être obligatoire
- le champs email doit être présent, obligatoire, et lié au champ email du lead

A chaque enregistrement d'un formulaire, ces conditions sont vérifiées.
En cas d'erreur, l'explication s'ajoute dans le titre du formulaire.

####Utilisation d'un formulaire

On suppose que le formulaire utilise à la fois le champ "webcast-list" et l'action "subscribe"
Lorsqu'un lead remplit puis valide le formulaire, le plugin :
- vérifie que l'API est joignable
- vérifie que le webcast sélectionné existe toujours
- affiche une erreur dans le formulaire si nécessaire
- appelle l'API et inscrit l'email du lead au webcast choisi (si le nom et le prénom du lead sont connus, ils remontent également)
- génère un "slug" qui identifie ce webcast
- ajoute un événement de type "webcast subscription" dans la timeline du lead, avec le slug dans les détails de l'événement
De son côté, GoToWebcast envoie un email de confirmation d'inscription.

###Synchronisation avec l'API

La tâche principale d'une synchronisation est d'ajouter des écritures dans la timeline des leads qui ont participé à une session.
La synchro vérifie également les écritures liées aux inscriptions, en ajoutant ou en supprimant les écritures nécessaires.
Ainsi, si une personne s'inscrit par un autre moyen que le formulaire ATMT mais possède un email associé à un lead existant, cela se répercute sur la timeline.
La synchro s'effectue grâce au cronjob défini ci-dessus (rubrique installation).
Elle peut être exécutée manuellement via : php app/console gotowebcast:sync --webcastKey=xxxxx, en précisant l'identifiant unique (event ID) du webcast à synchroniser.
Si ce paramètre est absent, tous les webcasts connus sont synchronisés.
Astuce pour tester la synchro : ajouter ou supprimer manuellement des entrées dans la table "plugin_gotowebcast_events", puis s'assurer que la synchro rétablisse les bonnes valeurs.


###Filtres de listes
Dans les smart-list, 3 filtres sont disponibles : "webcast : est inscrit à...", "webcast : a participé à...", "webcast : est inscrit mais n'a pas participé à..."
Exemples :
"est inscrit à [including] webc1, webc2" signifie : "est inscrit à au moins l'un des deux webcast."
"est inscrit à [excluding] webc1, webc2" signifie : "n'est inscrit à aucun des deux webcast."
"est inscrit à [including] ANY" signifie : "est inscrit à au moins un webcast."
"est inscrit à [excluding] ANY" signifie : "n'est inscrit à aucun webcast."
Remarque :
En environnement de dev, le filtrage des leads nécessite l'exécution d'une commande depuis la console :
`php app/console mautic:leadlists:update --env=dev --force`

###Décisions de formulaires
Le bloc existe mais ne fonctionne pas encore.
