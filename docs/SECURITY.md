# Notes de sécurité

## Contrôles fournis

- Requêtes PDO préparées, émulation désactivée et identifiants SQL internes en liste blanche.
- DTO explicites, validation Unicode, sélections en liste blanche et échappement de sortie.
- CSRF par action, honeypot, token temporel HMAC de 3 secondes à 2 heures et clé d’idempotence unique.
- Limites persistantes par action et IP HMAC-hachée : 5/15 min et 20/jour par défaut.
- Connexion neutre, hash factice, verrouillage identité/IP, régénération et expiration 30 min/8 h.
- CSP à nonce, refus de framing, nosniff, permissions minimales, politique de référent et HSTS conditionnel.
- Upload par MIME réel, dimensions, taille, nom aléatoire et stockage non exécutable.
- Logs JSON avec identifiant de requête et expurgation des champs sensibles.

## Limites

Ces contrôles ne remplacent pas un pentest, une revue de configuration Apache/PHP/MariaDB, un WAF, une supervision, une politique de correctifs, un coffre de secrets ou une procédure d’incident. Ils ne produisent aucune certification et ne garantissent aucune conformité réglementaire.

## Checklist d’exploitation

- PHP supporté et patché ; `display_errors=Off`, `expose_php=Off`.
- Utilisateur SQL distinct, droits limités, sauvegardes chiffrées et restauration testée.
- TLS moderne, HTTPS vérifié avant HSTS, DNS/SPF/DKIM/DMARC vérifiés.
- `.env`, logs, base, migrations et sources hors document root.
- Alertes sur erreurs 5xx, échecs SMTP, stockage, login bloqué et sauvegarde.
- Revue périodique des administrateurs et rotation des secrets.
- Politique de rétention validée, appliquée et auditée.

## Signalement

Aucune adresse de sécurité publique dédiée n’est inventée. Avant lancement, SCTECH doit choisir et publier son canal de signalement. En attendant, la seule adresse confirmée est `contact@sctech.ma`.

