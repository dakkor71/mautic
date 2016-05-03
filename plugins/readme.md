#Plugin GoToWebinar pour Automation

##Pré-requis
- avoir un compte chez GoToWebinar et créer un ou deux webinaires.
- avoir un compte développeur chez GoToWebinar
- avoir patché les deux fichiers de Mautic suivant (cf mon mail du 15.04 à Jérémie) : SubmissionModel.php et mautic-form-src.js.

##Installation
1. Copier le dossier "GoToWebinarBundle" dans /plugins/.
2. Vider le cache de Symfony.
3. Dans le menu settings / plugins, cliquer sur le bouton "Install / Upgrade Plugins". Le module "GoToWebinar" doit apparaître, ainsi qu'un item du même nom dans le menu principal.
4. Ouvrir le menu settings / configuration, section GoToWebinar et copier l'URL de callback pour oAuth.
5. Depuis le compte développeur chez GoToWebinar, créer une application et utiliser l'URL de callback obtenue en 4. Puis copier la clé ConsumerKey.
6. Dans settings / configuration / GoToWebinar, utiliser le ConsumerKey pour obtenir un token. Une fois l'authentification terminée, revenir dans la configuration et vérifier que le token et le OrganiserKey sont bien renseignés. Cliquer sur le bouton "Check API" pour vérifier la connexion.

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
- ajoute ce slug à la liste des tags liés au lead (sera nécessaire pour les filtres)
De son côté, GoToWebinar envoie un email de confirmation d'inscription.

###Smart list et filtres

####Analyse et choix technique
1. Mautic permet d'étendre presque tous ses concepts (campagnes, emails, formulaires, ...), sauf les listes de leads. En effet, le bundle 'lead' ne contient pas d'event listener permettant d'ajouter des critères personnalisés ou des opérateurs personnalisés. Ce qui est confirmé par la doc développeur de Mautic.
2. Les tags attachés aux leads, utilisés d'une certaine façon par le plugin, permettent de contourner cette limitation et obtenir le fonctionnement souhaité.
3. Les opérateurs "a participé" et "s'est inscrit mais n'a pas participé" nécessitent de faire remonter à intervalle régulier les listes de participants de GTW vers Mautic, car le formulaire ne donne que l'information "s'est inscrit". D'où la mise en place d'une synchronisation, qui peut être manuelle ou via un cron.

####En pratique : la synchro
Chaque lead qui s'inscrit via un formulaire reçoit automatiquement un tag de la forme suivante :
`webinar_registered_le-titre-du-webinaire_la-cle-du-webinaire`
Le préfixe `webinar_registered` indique qu'il s'agit d'une inscription.
La partie centrale rappelle le titre donné au webinaire, nettoyé et tronqué si besoin.
La clé du webinaire est un identifiant unique, composé de chiffres et fourni par GTW.
Ces tags peuvent être constatés depuis la page de détail d'un lead.
Pour synchroniser Automation avec GTW, se rendre sur la page GoToWebinar su menu principal. Cette page rappelle tous les webinaires, passés ou à venir, avec pour chacun le nombre de participants et un bouton de synchro.
La tâche principale d'une synchronisation est d'ajouter des tags aux leads qui ont participé à une session. Ces tags ont la même forme que précédemment, mais avec le préfixe `webinar_participated_`.
La synchro vérifie également les tags liés aux inscriptions, en ajoutant ou en supprimant les tags nécessaires. 
Ainsi :
- si une personne annule son inscription, cela se répercute sur le lead. 
- et si une personne s'inscrit par un autre moyen que le formulaire ATMT mais possède un email associé à un lead existant, cela se répercute également.
Astuce pour tester la synchro : ajouter ou supprimer manuellement des tags à certains leads, puis s'assurer que la synchro rétablisse les bons tags.
S'il y a besoin d'automatiser la synchro avec un cron, l'URL /gotowebinar/cron/sync permet de lancer une synchronisation de tous les webinaires. 
 
####En pratique : le filtrage
Dans les smart-list, travailler uniquement avec le critère 'Tags' et les opérateurs 'include' / 'exclude'. Et se servir des préfixes `webinar_registered` ou `webinar_participated` pour construire les requêtes.
Pour obtenir les leads inscrits au webinar1 ou au webinar2 :
Tags  : include  :  `webinar_registered_webinar1_xxx`
OR
Tags  :  include  :  `webinar_registered_webinar2_yyy`
Pour obtenir les leads inscrits mais n'ayant pas participé au webinar3 :
Tags  : include  :  `webinar_registred_webinar3_zzz`
AND
Tags  :  exclude  :  `webinar_participated_webinar3_zzz`
Etc.
Remarque : le filtrage des leads nécessite l'exécution d'une commande depuis la console, via un cron ou manuellement. En environnement de dev ou de test, utiliser la commande suivante pour voir le résultat d'un filtre :
`php app/console mautic:leadlists:update --env=dev`

###Décisions de formulaires

L'analyse est faite, mais cette partie reste à développer.
Contrairement aux filtres, Mautic permet de personnaliser les décisions. A priori il n'y aura donc pas de blocage technique.

###CRUD

La page GoToWebinar est un début : elle affiche le détail des webinaires.
L'api permet de gérer les webinaires, mais aussi les inscriptions, les participations et les sessions. 
Quelles seraient les fonctionnalités utiles en pratiques ?
=> à préciser
