# Guide éditorial

## Principes

Écrire pour une personne qui doit prendre une décision : commencer par le problème, préciser l’intervention, nommer les risques et expliquer le prochain pas. Éviter superlatifs, promesses absolues et jargon non expliqué.

## Publication

1. Créer avec une clé stable et un slug français strict.
2. Rédiger résumé, métadonnées et blocs JSON version 1.
3. Enregistrer en brouillon et utiliser la prévisualisation authentifiée.
4. Faire relire exactitude, sécurité, accessibilité et SEO.
5. Pour une réalisation, conserver la preuve écrite du droit de publication et de chaque résultat.
6. Publier seulement après approbation ; vérifier ensuite sitemap et liens internes.

Les cinq offres publiques utilisent uniquement les clés `solution.*` dans `services`. L’écran historique `/admin/expertises` est présenté comme **Solutions** ; ne réactivez pas les anciennes offres généralistes. Les articles financiers utilisent `category_key` et doivent conserver un temps de lecture réaliste.

## Finance islamique

Les critères religieux et de conformité sont définis ou validés par les instances compétentes du client. SCTECH peut expliquer comment leur traduction devient règles, contrôles, validations, traces et rapports logiciels, mais ne revendique jamais d’avis religieux, certification, garantie halal/Charia ou conseil en investissement. Le composant de responsabilité présent sur `/finance-islamique` et les solutions concernées est fixe et ne doit pas être contourné par le contenu CMS.

## Blocs autorisés

```json
{
  "version": 1,
  "blocks": [
    {"type": "heading", "level": 2, "text": "Titre"},
    {"type": "paragraph", "text": "Texte"},
    {"type": "list", "title": "Repères", "items": ["Un", "Deux"]},
    {"type": "quote", "text": "Citation vérifiée", "source": "Source"},
    {"type": "callout", "title": "À retenir", "text": "Message"}
  ]
}
```

Aucun HTML, script, iframe ou code PHP n’est accepté.

## Preuves interdites sans validation

Clients, logos, partenaires, équipes, certifications, années d’expérience, AUM, rendements, volumes, taux, économies, délais, scores de qualité, conformité, garanties halal/Charia et témoignages. Si une donnée n’est pas vérifiable, l’omettre ; ne pas la remplacer par un faux placeholder public.
