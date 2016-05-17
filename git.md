# Manipulations entre les repos

Pour pouvoir au mieux utiliser cette doc, veuillez d'abord ajouter les deux remotes suivantes :

1. `mautic_mautic` : correspondant au mautic officiel dont l'adresse est `https://github.com/mautic/mautic.git`
2. `mautic_wmk_public` : correspondant au mautic public de webmecanik et dont l'adresse est `https://github.com/webmecanik/mautic.git`

Pour ajouter une remote : aller dans le menu contextuel de git : clic droit sur remote puis **new remote**, sélectionner **Configure Fetch** en option.

Toutes les commandes sont valables dans l'interface eclipse avec le projet [egit](www.eclipse.org/egit/documentation/).

---

### Développement d'une nouvelle fonctionnalité.

Pour développer une nouvelle fonctionnalité, il faut [suivre le process](lien_vers_le_process...) décrit par Lauren.

Succintement le plus important pour nous :

* Créer une branche tirée depuis `staging`. Penser à vous mettre à jour avant : **Team -> pull** puis **Team -> branch -> new branch -> select source** et sélectionner **origin/staging**
* Une fois vos développements faits : tester votre branche avec la dernière version de staging :  **Team -> merge** puis sélectionner **origin/staging**
* Une fois les tests bons, changer de branche : **Team -> switch to** et sélectionner **staging** puis **Team -> merge** puis sélectionner **votre_branche**.

On évite tant que possible de merger la branche `staging` de `mautic_mautic` dans la branche staging de `automation_dev` pour éviter d'avoir de l'instabilité à la fois de la part de mautic et de notre part.

La mise à jour de la branche `master` de `automation_dev` se fera à partir de la branche `staging` de `automation_dev`.

##### David s'occupe de mettre à jour la branche `staging` de `automation_dev` d'après la branche `master` de `mautic_mautic`.

---

### Partage de commits vers la communauté.

Process de partage de commits de `automation_dev` vers `mautic_mautic`.

1. Se mettre à jour : **Team -> pull**
2. Créer une branche depuis la staging de `mautic_wmk_public` : **Team -> branch -> new branch -> select source** et sélectionner **mautic_wmk_public/staging**
3. Si le branche contient des commits "privés", pour chaque commit faites un cherry pick ( respecter l'ordre historique autant que possible même si pas obligatoire ). Pour ce faire rendez vous dans la vue historique de Eclipse, sélectionner votre commit avec un clic droit puis cherry-pick ( note : il peut y avoir un conflit ).

Enfin, il faut se rendre sur l'interface de github du projet mautic public sélectionner sa branche et faire un new pull request.

---

### Tester une version de mautic officiel dans la branche staging.
	
Comment tester une version de staging de mautic sans impacter le reste ?

* On se met à jour : **Team -> pull**
* On crée une nouvelle branche à partir de la branche staging du repository `mautic_mautic` : **Team -> branch -> new branch -> select source**
* La nouvelle branche est notre branche de test.

Si vous souhaitez ajouter des fonctionnalités privées à votre test, il faut merger la branche content ces fonctionnalités dans la branche créée à l'étape précédente : **Team -> Merge** et sélectionner la branche à merger, par exemple master pour tester l'actuelle version de automation en prod avec les nouvelles fonctionnalités de mautic officiel.

Une fois les tests faits, il faut supprimer la branche : **Team > Advanced > Delete Branch**
