#Plugin CRM Bundle : integration INES CRM


##Configuration de l'intégration INES CRM

1. Dans le menu settings / custom fields, ajouter deux champs supplémentaires, de type Text ou Number, qui serviront à mémoriser pour chaque lead les références des contacts et des clients dans INES CRM.
Ils peuvent être nommés librement (par exemple "ID contact INES" et "ID client INES") et appartenir à n'importe quel groupe de champs.

2. Dans le menu settings / plugins, si l'icone "INES" n'est pas présent, cliquer sur "Install / Upgrade Plugins".
Cliquer sur l'icone INES et configurer l'intégration à l'aide de vos codes d'accès au CRM : Compte, Utilisateur, Mot de passe.
Puis testez la connexion.

3. Lorsque la connexion est OK, enregistrer la configuration, la fermer puis l'ouvrir à nouveau.
L'onglet "Contact Field Mapping" doit être présent.
Dans cet onglet, affecter aux champs "Référence contact chez INES" et "Référence société chez INES" les champs Automation créés à l'étape 1.
Puis affecter parmis les autres champs proposés ceux qui doivent être synchronisés avec INES CRM.
A noter que certains champs sont présents en doubles (par exemple l'adresse) car ils peuvent être synchronisés soit avec un contact INES, soit avec un client INES.

4. Dans l'onglet "Features" :
- L'option "Push contacts to this integration" active la fonction du même nom présente dans les actions de formulaire ou les campagnes.
- L'option "Synchronisation de tous les leads" permet d'injecter dans INES CRM tous les contacts ayant au minimum un email et une société. Pour fonctionner, elle nécessite la mise en place d'une tâche planifiée (CRON) sur le serveur : EN COURS DE DEVELOPPEMENT.
- Les champs cochés en dernière partie de l'onglet "features" deviennent non écrasables : la valeur de ces champs dans Automation n'affecte le champ correspondant dans INES CRM que s'il n'est pas encore renseigné.

5. Dans l'onglet "Enable/Auth", mettre le commutateur "Published" sur "ON".


##Test minimaliste

1. Dans Automation, créer un formulaire de type "Standalone".
Lui ajouter au minimum les champs Email et Société (liés aux champs Core correspondants).
Lui ajouter l'action "push contacts to integration", vers l'intégration INES.

2. Afficher un aperçu de ce formulaire, le remplir avec des données fictives et valider.
Dans INES CRM, un nouveau client contenant un contact a du être créé, avec les renseignements saisis dans le formulaire, concernant les champs mappés.
Dans Automation, le lead doit avoir reçu un ID de contact et un ID de société suite à l'opération.

3. Depuis le même formulaire, envoyer un deuxième test avec le même email et la même société, mais en modifiant l'un des autres champs.
Dans INES CRM, le client /contact a du être mis à jour, en respectant les éventuels champs non-écrasables.


##Lecture des champs mappés via l'API Automation (réservé aux développeurs)

EndPoint : GET /api/ines/getMapping

Paramètres : aucun

En sortie :

array(

	'mapping' => array(
	
		0 => _config_champ_1_,
		
		1 => _config_champ_2_,
		
		1 => _config_champ_3_,
		
		...
		
	)
	
)


Où _config_champ_x_ a la structure suivante :

array(

	'concept' => 'contact' | 'client',
	
	'inesFieldKey' => 'PrimaryMailAddress',
	
	'isCustomField' => 0 | 1,
	
	'atmtFieldKey' => 'email',
	
	'isEcrasable' => 0 | 1
	
)


L'attribut 'concept' indique si le champ en question est lié à un contact ou à une société.
L'attribut 'inesFieldKey' correspond au nom interne du champ côté INES, tel qu'il est nommé dans les balises XML des WS.
L'attribut 'isCustomField' indique s'il s'agit d'un champ personnalisé (au sens INES du terme) ou non.
L'attribut 'atmtFieldKey' correspond au nom interne dans Automation du champ mappé.
L'attribut 'isEcrasable' indique si les valeurs présentes dans INES peuvent être écrasées par une valeur Automation différente ou non. Il est donné à titre indicatif car c'est Automation qui s'occupe du filtrage lors des mises à jour des contacts et clients chez INES.


Notes :

- Les champs non mappés se sont pas retournés. L'attribut 'atmtFieldKey' est donc toujours renseigné.

- Deux champs sont toujours présents dans la réponse et ont pour attribut 'inesFieldKey' les valeurs 'InternalContactRef' et 'InternalCompanyRef'. Ils indiquent les champs Automation utilisés pour stocker les InternalRef des contacts et des clients.
